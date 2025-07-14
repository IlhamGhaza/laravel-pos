<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\MobileSyncValidationIssue;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ServiceCharge;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class MobileOrderSyncController extends Controller
{
    public function syncAndStoreOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_time' => 'required|date_format:Y-m-d H:i:s',
            'kasir_id' => 'required|exists:users,id',
            'customer_id' => 'nullable|exists:customers,id',
            'payment_method' => 'required|string|max:50',
            'order_type' => 'required|in:' . implode(',', [Order::ORDER_TYPE_IN_PERSON, Order::ORDER_TYPE_PHONE, Order::ORDER_TYPE_MOBILE_APP]),
            'status' => 'required|in:' . implode(',', [Order::STATUS_PENDING, Order::STATUS_PAID, Order::STATUS_COMPLETED, Order::STATUS_PROCESSING]), // Sesuaikan dengan status yang mungkin dari mobile

            // Nilai dari Mobile untuk divalidasi
            'mobile_sub_total' => 'required|numeric|min:0',
            'mobile_discount_id' => 'nullable|exists:discounts,id',
            'mobile_discount_amount' => 'required|numeric|min:0',
            'mobile_tax_rate' => 'nullable|numeric|min:0', // Opsional, server bisa pakai default
            'mobile_tax_amount' => 'required|numeric|min:0',
            'mobile_service_charge_rate' => 'nullable|numeric|min:0', // Opsional
            'mobile_service_charge_amount' => 'required|numeric|min:0',
            'mobile_total_price' => 'required|numeric|min:0',
            'mobile_total_item' => 'required|integer|min:0',
            'mobile_payment_amount' => 'required|numeric|min:0',
            'mobile_change_amount' => 'required|numeric|min:0',

            'order_items' => 'required|array|min:1',
            'order_items.*.product_id' => 'required|exists:products,id',
            'order_items.*.quantity' => 'required|numeric|min:0.01',
            'order_items.*.mobile_price_per_unit' => 'required|numeric|min:0', // Harga satuan dari mobile
            'order_items.*.mobile_total_price' => 'required|numeric|min:0',   // Total harga item dari mobile
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();
        $issues = [];
        $orderIdForIssues = null; // Akan diisi setelah order dibuat

        DB::beginTransaction();
        try {
            // --- 1. Inisialisasi Order (belum disimpan) ---
            $serverOrder = new Order();
            $serverOrder->transaction_time = Carbon::parse($validatedData['transaction_time']);
            $serverOrder->kasir_id = $validatedData['kasir_id'];
            $serverOrder->customer_id = $validatedData['customer_id'] ?? null;
            $serverOrder->payment_method = $validatedData['payment_method'];
            $serverOrder->order_type = $validatedData['order_type'];
            // Status awal bisa pending, akan diupdate setelah kalkulasi
            $serverOrder->status = $validatedData['status'] ?? Order::STATUS_PENDING;


            // --- 2. Proses Order Items & Hitung SubTotal Server ---
            $serverSubTotal = 0;
            $serverTotalItemsQuantity = 0;
            $serverOrderItemsData = [];

            foreach ($validatedData['order_items'] as $mobileItem) {
                $product = Product::find($mobileItem['product_id']);
                if (!$product) {
                    // Sebenarnya sudah divalidasi 'exists', tapi sebagai jaga-jaga
                    throw ValidationException::withMessages(['order_items' => 'Product with ID ' . $mobileItem['product_id'] . ' not found.']);
                }

                $serverItemUnitPrice = (float) $product->price;
                $itemQuantity = (float) $mobileItem['quantity'];
                $serverItemTotalPrice = $serverItemUnitPrice * $itemQuantity;

                $serverSubTotal += $serverItemTotalPrice;
                $serverTotalItemsQuantity += $itemQuantity;

                $serverOrderItemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $itemQuantity,
                    'price' => $serverItemUnitPrice, // Harga satuan dari server
                    'total_price' => $serverItemTotalPrice, // Total harga item dari server
                ];

                // Validasi harga satuan item
                if (abs((float)$mobileItem['mobile_price_per_unit'] - $serverItemUnitPrice) > 0.01) {
                    $issues[] = $this->logIssueData(
                        $orderIdForIssues,
                        "order_items[{$product->id}].price_per_unit",
                        'Mobile item unit price mismatch with server.',
                        $mobileItem['mobile_price_per_unit'],
                        $serverItemUnitPrice
                    );
                }
                // Validasi total harga item
                if (abs((float)$mobileItem['mobile_total_price'] - $serverItemTotalPrice) > 0.01) {
                    $issues[] = $this->logIssueData(
                        $orderIdForIssues,
                        "order_items[{$product->id}].total_price",
                        'Mobile item total price mismatch with server.',
                        $mobileItem['mobile_total_price'],
                        $serverItemTotalPrice
                    );
                }
            }

            // Validasi SubTotal
            if (abs((float)$validatedData['mobile_sub_total'] - $serverSubTotal) > 0.01) {
                $issues[] = $this->logIssueData($orderIdForIssues, 'sub_total', 'Mobile sub_total mismatch.', $validatedData['mobile_sub_total'], $serverSubTotal);
            }
            $serverOrder->sub_total = $serverSubTotal;
            $serverOrder->total_item = $serverTotalItemsQuantity; // atau count($serverOrderItemsData) jika total_item adalah jumlah jenis produk

            // Validasi Total Item (jumlah kuantitas)
            if ((int)$validatedData['mobile_total_item'] !== (int)$serverTotalItemsQuantity) { // Asumsi mobile_total_item adalah total kuantitas
                $issues[] = $this->logIssueData($orderIdForIssues, 'total_item_quantity', 'Mobile total item quantity mismatch.', $validatedData['mobile_total_item'], $serverTotalItemsQuantity);
            }


            // --- 3. Hitung Diskon Server ---
            $serverDiscountAmount = 0;
            $appliedDiscountId = null;
            if (!empty($validatedData['mobile_discount_id'])) {
                $discount = Discount::find($validatedData['mobile_discount_id']);
                if ($discount && $discount->isValid) { // Anda mungkin perlu scope/logic yang lebih canggih untuk isValid
                    // Logika kalkulasi diskon berdasarkan tipe diskon
                    // Ini adalah contoh sederhana, sesuaikan dengan Discount->calculateDiscount() atau service
                    if ($discount->type == Discount::TYPE_PERCENTAGE) {
                        $serverDiscountAmount = ($serverSubTotal * (float)$discount->value) / 100;
                    } elseif ($discount->type == Discount::TYPE_FIXED) { // Pastikan konstanta TYPE_FIXED sesuai
                        $serverDiscountAmount = (float)$discount->value;
                    }
                    // Tambahkan logika untuk tipe diskon lain (buy_x_get_y, dll.)
                    // ...

                    // Jika ada max_discount pada model Discount (perlu ditambahkan ke tabel & model)
                    // if (isset($discount->max_discount) && $serverDiscountAmount > $discount->max_discount) {
                    //     $serverDiscountAmount = $discount->max_discount;
                    // }
                    $appliedDiscountId = $discount->id;
                } else {
                    $issues[] = $this->logIssueData($orderIdForIssues, 'discount_id', 'Mobile provided discount_id is invalid or not applicable.', $validatedData['mobile_discount_id'], null);
                }
            } elseif ((float)$validatedData['mobile_discount_amount'] > 0) {
                // Manual discount amount from mobile, server accepts it but flags if different from zero if no ID
                $serverDiscountAmount = (float)$validatedData['mobile_discount_amount'];
                if (empty($validatedData['mobile_discount_id'])) {
                    // Ini adalah diskon manual, tidak ada ID untuk divalidasi
                }
            }

            if (abs((float)$validatedData['mobile_discount_amount'] - $serverDiscountAmount) > 0.01) {
                $issues[] = $this->logIssueData($orderIdForIssues, 'discount_amount', 'Mobile discount_amount mismatch.', $validatedData['mobile_discount_amount'], $serverDiscountAmount);
            }
            $serverOrder->discount_id = $appliedDiscountId;
            $serverOrder->discount_amount = $serverDiscountAmount;


            // --- 4. Hitung Pajak Server ---
            // Ambil tarif pajak default dari DB atau config. Contoh:
            $defaultTax = Tax::where('name', 'PPN')->first(); // Atau cara lain mendapatkan default tax
            $serverTaxRate = $defaultTax ? (float)$defaultTax->rate : ((float)($validatedData['mobile_tax_rate'] ?? 11.00)); // Default 11% PPN jika tidak ada

            $taxableAmount = $serverSubTotal - $serverDiscountAmount;
            $serverTaxAmount = ($taxableAmount * $serverTaxRate) / 100;

            if (abs((float)$validatedData['mobile_tax_amount'] - $serverTaxAmount) > 0.01) {
                $issues[] = $this->logIssueData($orderIdForIssues, 'tax_amount', 'Mobile tax_amount mismatch.', $validatedData['mobile_tax_amount'], $serverTaxAmount);
            }
            $serverOrder->tax_rate = $serverTaxRate;
            $serverOrder->tax_amount = $serverTaxAmount;

            // --- 5. Hitung Service Charge Server ---
            // Ambil tarif service charge default. Contoh:
            $defaultServiceCharge = ServiceCharge::first(); // Atau cara lain
            $serverServiceChargeRate = $defaultServiceCharge ? (float)$defaultServiceCharge->rate : ((float)($validatedData['mobile_service_charge_rate'] ?? 0.00));

            // Service charge biasanya dihitung dari subtotal (sebelum atau sesudah diskon, tergantung kebijakan)
            // Asumsi dihitung dari subtotal setelah diskon
            $serviceChargeBaseAmount = $serverSubTotal - $serverDiscountAmount;
            $serverServiceChargeAmount = ($serviceChargeBaseAmount * $serverServiceChargeRate) / 100;

            if (abs((float)$validatedData['mobile_service_charge_amount'] - $serverServiceChargeAmount) > 0.01) {
                $issues[] = $this->logIssueData($orderIdForIssues, 'service_charge_amount', 'Mobile service_charge_amount mismatch.', $validatedData['mobile_service_charge_amount'], $serverServiceChargeAmount);
            }
            $serverOrder->service_charge_rate = $serverServiceChargeRate;
            $serverOrder->service_charge = $serverServiceChargeAmount; // Kolom 'service_charge' di tabel orders menyimpan amount


            // --- 6. Hitung Total Price Server ---
            $serverTotalPrice = $serverSubTotal + $serverTaxAmount + $serverServiceChargeAmount - $serverDiscountAmount;
            if (abs((float)$validatedData['mobile_total_price'] - $serverTotalPrice) > 0.01) {
                $issues[] = $this->logIssueData($orderIdForIssues, 'total_price', 'Mobile total_price mismatch.', $validatedData['mobile_total_price'], $serverTotalPrice);
            }
            $serverOrder->total_price = $serverTotalPrice;

            // --- 7. Pembayaran & Kembalian ---
            $serverOrder->payment_amount = (float)$validatedData['mobile_payment_amount'];
            $serverChangeAmount = $serverOrder->payment_amount - $serverTotalPrice;
            if ($serverChangeAmount < 0) $serverChangeAmount = 0; // Kembalian tidak boleh negatif

            if (abs((float)$validatedData['mobile_change_amount'] - $serverChangeAmount) > 0.01) {
                $issues[] = $this->logIssueData($orderIdForIssues, 'change_amount', 'Mobile change_amount mismatch.', $validatedData['mobile_change_amount'], $serverChangeAmount);
            }
            $serverOrder->change_amount = $serverChangeAmount;


            // --- 8. Set Status Validasi & Info Mobile Sync ---
            $serverOrder->is_synced_from_mobile = true;
            $serverOrder->mobile_synced_at = now();
            if (!empty($issues)) {
                $serverOrder->mobile_sync_validation_status = Order::SYNC_STATUS_FAILED; // atau SYNC_STATUS_REVIEW
            } else {
                $serverOrder->mobile_sync_validation_status = Order::SYNC_STATUS_VALIDATED;
            }

            // Jika status dari mobile adalah 'paid' atau 'completed', set paid_at
            if (in_array($serverOrder->status, [Order::STATUS_PAID, Order::STATUS_COMPLETED])) {
                $serverOrder->paid_at = $serverOrder->paid_at ?? now();
            }


            // --- 9. Simpan Order & Order Items ---
            $serverOrder->save();
            $orderIdForIssues = $serverOrder->id; // Dapatkan ID order setelah disimpan

            // Update order_id untuk issues yang sudah terkumpul
            foreach ($issues as &$issueDataRef) {
                $issueDataRef['order_id'] = $orderIdForIssues;
            }
            unset($issueDataRef); // Hapus referensi

            // Simpan OrderItems
            foreach ($serverOrderItemsData as $itemData) {
                $serverOrder->orderItems()->create($itemData);
            }

            // Simpan semua isu validasi
            if (!empty($issues)) {
                MobileSyncValidationIssue::insert($issues);
            }

            DB::commit();

            // Kurangi stok produk (bisa dipindah ke event listener OrderItem created)
            foreach ($serverOrderItemsData as $itemData) {
                $productToUpdateStock = Product::find($itemData['product_id']);
                if ($productToUpdateStock) {
                    $productToUpdateStock->reduceStock($itemData['quantity']); // Pastikan method reduceStock ada di Product model
                }
            }


            return response()->json([
                'success' => true,
                'message' => 'Order synced and stored successfully.',
                'order_id' => $serverOrder->id,
                'validation_status' => $serverOrder->mobile_sync_validation_status,
                'issues_count' => count($issues),
                'data' => $serverOrder->load('orderItems') // Kirim kembali data order yang sudah diproses server
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Mobile Order Sync Validation Error: ', ['errors' => $e->errors(), 'request' => $request->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error during order sync.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mobile Order Sync Failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString(), 'request' => $request->all()]);
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred during order sync. ' . $e->getMessage()
            ], 500);
        }
    }

    private function logIssueData($orderId, $fieldName, $description, $mobileValue, $serverValue)
    {
        return [
            'order_id' => $orderId, // Bisa null jika order belum dibuat
            'field_name' => $fieldName,
            'issue_description' => $description,
            'mobile_value' => (string)$mobileValue,
            'server_calculated_value' => (string)$serverValue,
            'logged_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
