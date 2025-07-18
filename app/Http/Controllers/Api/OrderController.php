<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tax;
use App\Models\ServiceCharge;
use App\Models\Discount;
use App\Services\DiscountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    /**
     * Get all orders with their items
     *
     * @return JsonResponse
     */
    /**
     * Get all orders with their items
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Order::class);
        try {
            $orders = Order::with(['orderItems.product'])
                ->latest('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar pesanan berhasil diambil',
                'data' => OrderResource::collection($orders)
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil daftar pesanan: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil daftar pesanan',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get order details by ID
     *
     * @param int $id
     * @return JsonResponse
     */
    /**
     * Get order details by ID with items
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $this->authorize('view', Order::class);
        try {
            $order = Order::with(['orderItems.product'])->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan tidak ditemukan'
                ], 404);
            }
            $this->authorize('view', $order);

            return response()->json([
                'success' => true,
                'message' => 'Detail pesanan berhasil diambil',
                'data' => new OrderResource($order)
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil detail pesanan: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil detail pesanan',
                'error' => null
            ], 500);
        }
    }

    /**
     * Store a new order with order items
     *
     * @param Request $request
     * @return JsonResponse
     */
    /**
     * Process a simplified order request
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function processSimplifiedOrder(Request $request): JsonResponse
    {
        $this->authorize('create', Order::class);
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'transaction_time' => 'required|date_format:Y-m-d H:i:s',
                'kasir_id' => 'required|exists:users,id',
                'customer_id' => 'nullable|exists:customers,id',
                'sub_total' => 'required|numeric|min:0',
                'tax_id' => 'nullable|exists:taxes,id',
                'tax_rate' => 'nullable|numeric|min:0',
                'service_charge_id' => 'nullable|exists:service_charges,id',
                'service_charge_rate' => 'nullable|numeric|min:0',
                'discount_id' => 'nullable|exists:discounts,id',
                'discount_details' => 'nullable|array',
                'total_price' => 'required|numeric|min:0',
                'total_item' => 'required|integer|min:1',
                'payment_method' => 'required|string|in:cash,card,transfer,qris',
                'payment_amount' => 'required|numeric|min:0',
                'change_amount' => 'required|numeric|min:0',
                'order_type' => 'required|string|in:in-person,phone,mobile_app',
                'customer_order_notes' => 'nullable|string',
                'order_items' => 'required|array|min:1',
                'order_items.*.product_id' => 'required|exists:products,id',
                'order_items.*.quantity' => 'required|integer|min:1',
                'order_items.*.price' => 'required|numeric|min:0',
            ]);

            // Start database transaction
            return DB::transaction(function () use ($validatedData) {
                // Calculate sub_total from order items
                $calculatedSubTotal = collect($validatedData['order_items'])->sum(function ($item) {
                    return $item['quantity'] * $item['price'];
                });

                // Get kasir name from users table
                $kasir = \App\Models\User::find($validatedData['kasir_id']);
                $kasirName = $kasir ? $kasir->name : 'Kasir';

                // Get customer name if customer_id is provided
                $customerName = null;
                if (!empty($validatedData['customer_id'])) {
                    $customer = \App\Models\Customer::find($validatedData['customer_id']);
                    $customerName = $customer ? $customer->name : 'Pelanggan';
                }

                // Create the order with provided data
                $order = new Order([
                    'transaction_time' => $validatedData['transaction_time'],
                    'kasir_id' => $validatedData['kasir_id'],
                    'kasir_name' => $kasirName,
                    'customer_id' => $validatedData['customer_id'] ?? null,
                    'customer_name' => $customerName,
                    'sub_total' => $calculatedSubTotal,
                    'total_price' => $validatedData['total_price'],
                    'total_item' => $validatedData['total_item'],
                    'payment_method' => $validatedData['payment_method'],
                    'payment_amount' => $validatedData['payment_amount'],
                    'change_amount' => $validatedData['change_amount'],
                    'order_type' => $validatedData['order_type'],
                    'customer_order_notes' => $validatedData['customer_order_notes'] ?? null,
                    'status' => Order::STATUS_PAID,
                    'paid_at' => now(),
                    'tax_amount' => 0,
                    'service_charge' => 0,
                    'discount_amount' => 0,
                ]);

                // Set tax information if provided
                if (!empty($validatedData['tax_id'])) {
                    $tax = Tax::find($validatedData['tax_id']);
                    if ($tax) {
                        $taxRate = $validatedData['tax_rate'] ?? $tax->rate;
                        $order->tax_id = $validatedData['tax_id'];
                        $order->tax_rate = $taxRate;
                        $order->tax_amount = ($calculatedSubTotal * $taxRate) / 100;
                    }
                }

                // Set service charge if provided
                if (!empty($validatedData['service_charge_id'])) {
                    $serviceCharge = ServiceCharge::find($validatedData['service_charge_id']);
                    if ($serviceCharge) {
                        $serviceChargeRate = $validatedData['service_charge_rate'] ?? $serviceCharge->rate;
                        $order->service_charge_id = $validatedData['service_charge_id'];
                        $order->service_charge_rate = $serviceChargeRate;
                        $order->service_charge = ($calculatedSubTotal * $serviceChargeRate) / 100;
                    }
                }

                // Set discount information if provided
                if (!empty($validatedData['discount_id'])) {
                    $discount = Discount::find($validatedData['discount_id']);
                    if ($discount) {
                        $order->discount_id = $discount->id;
                        $order->discount_details = [
                            'type' => $discount->type,
                            'value' => $discount->value
                        ];

                        // Calculate discount amount based on type
                        if ($discount->type === 'percentage') {
                            $order->discount_amount = ($calculatedSubTotal * $discount->value) / 100;
                        } else {
                            $order->discount_amount = $discount->value;
                        }
                    }
                }

                // Generate order number for non-cash payments
                if ($validatedData['payment_method'] !== 'cash') {
                    $order->midtrans_order_id = 'ORD-' . date('Ymd') . '-' . str_pad(
                        Order::whereDate('created_at', today())->count() + 1,
                        4,
                        '0',
                        STR_PAD_LEFT
                    ) . '-' . $order->id;
                } else {
                    // Ensure midtrans_order_id is null for cash payments
                    $order->midtrans_order_id = null;
                }

                $order->save();

                // Create order items
                foreach ($validatedData['order_items'] as $item) {
                    // Ambil nama produk dari product_id pada item
                    $productName = null;
                    if (!empty($item['product_id'])) {
                        $product = \App\Models\Product::find($item['product_id']);
                        $productName = $product ? $product->name : 'Nama Product';
                    }

                    $order->orderItems()->create([
                        'product_id' => $item['product_id'],
                        'product_name' => $productName,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'total_price' => $item['quantity'] * $item['price']
                    ]);
                }

                // Calculate and save final totals
                $order->calculateTotals();
                $order->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Order created successfully',
                    'data' => $order->load('orderItems')
                ], 201);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Order::class);
        // Check if this is a simplified order request
        if ($request->has('transaction_time') && $request->has('order_items')) {
            return $this->processSimplifiedOrder($request);
        }

        // Original store logic for backward compatibility
        try {
            // Validate request
            $validatedData = $request->validate([
                'transaction_time' => 'required|date_format:Y-m-d H:i:s',
                'kasir_id' => 'required|exists:users,id',
                'customer_id' => 'nullable|exists:customers,id',
                'customer_name' => 'nullable|string|max:255',
                'sub_total' => 'required|numeric|min:0',
                'tax_id' => 'nullable|exists:taxes,id',
                'tax_rate' => 'nullable|numeric|min:0',
                'service_charge_id' => 'nullable|exists:service_charges,id',
                'service_charge_rate' => 'nullable|numeric|min:0',
                'discount_id' => 'nullable|exists:discounts,id',
                'discount_details' => 'nullable|array',
                'total_price' => 'required|numeric|min:0',
                'total_item' => 'required|numeric|min:1',
                'payment_method' => 'required|string|in:cash,card,transfer,qris',
                'payment_amount' => 'required|numeric|min:0',
                'change_amount' => 'required|numeric|min:0',
                'order_type' => 'nullable|string|in:in-person,phone,mobile_app',
                'order_items' => 'required|array|min:1',
                'order_items.*.product_id' => 'required|exists:products,id',
                'order_items.*.quantity' => 'required|numeric|min:1',
                'order_items.*.price' => 'required|numeric|min:0',
            ]);

            $order = DB::transaction(function () use ($validatedData) {
                // Calculate subtotal from order items first
                $subTotal = collect($validatedData['order_items'])->sum(function ($item) {
                    return $item['quantity'] * $item['price'];
                });

                // Get customer name if customer_id is provided
                $customerName = $validatedData['customer_name'] ?? null;
                if (empty($customerName) && !empty($validatedData['customer_id'])) {
                    $customer = \App\Models\Customer::find($validatedData['customer_id']);
                    if ($customer) {
                        $customerName = $customer->name;
                    }
                }

                // Create order with basic data including required fields
                $order = new Order([
                    'transaction_time' => $validatedData['transaction_time'],
                    'kasir_id' => $validatedData['kasir_id'],
                    'customer_id' => $validatedData['customer_id'] ?? null,
                    'customer_name' => $customerName,
                    'payment_method' => $validatedData['payment_method'],
                    'payment_amount' => $validatedData['payment_amount'],
                    'order_type' => $validatedData['order_type'] ?? 'in-person',
                    'customer_order_notes' => $validatedData['customer_order_notes'] ?? null,
                    'status' => Order::STATUS_PAID,
                    'paid_at' => now(),
                    'sub_total' => $subTotal, // Set initial sub_total
                    'tax_amount' => 0, // Initialize to 0, will be calculated later
                    'service_charge' => 0, // Initialize to 0, will be calculated later
                    'discount_amount' => 0, // Initialize to 0, will be calculated later
                    'total_price' => 0, // Initialize to 0, will be calculated later
                    'total_item' => collect($validatedData['order_items'])->sum('quantity'),
                ]);

                // Set tax and service charge rates if provided
                if (isset($validatedData['tax_id']) && isset($validatedData['tax_rate'])) {
                    $order->tax_id = $validatedData['tax_id'];
                    $order->tax_rate = $validatedData['tax_rate'];
                }

                if (isset($validatedData['service_charge_id']) && isset($validatedData['service_charge_rate'])) {
                    $order->service_charge_id = $validatedData['service_charge_id'];
                    $order->service_charge_rate = $validatedData['service_charge_rate'];
                }

                // Apply discounts if provided
                $appliedDiscounts = [];
                if (isset($validatedData['discounts']) && is_array($validatedData['discounts'])) {
                    $discountService = new DiscountService();
                    $orderItems = collect($validatedData['order_items']);
                    $discountResult = $discountService->applyDiscounts($orderItems, $validatedData['discounts']);

                    $appliedDiscounts = $discountResult['applied_discounts'];
                    $order->discount_amount = $discountResult['total_discount'];
                    $order->discount_details = $appliedDiscounts;

                    // If there's a single discount, set the discount_id
                    if (count($appliedDiscounts) === 1) {
                        $order->discount_id = $appliedDiscounts[0]['id'];
                    }
                }
                $order->discount_amount = $order->discount_amount ?? 0;
                $order->discount_details = $order->discount_details ?? [];

                $order->save();

                // Generate order number for non-cash payments (handled by Midtrans)
                if ($validatedData['payment_method'] !== 'cash') {
                    $order->midtrans_order_id = 'ORD-' . date('Ymd') . '-' . str_pad(Order::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT) . '-' . $order->id;
                } else {
                    // For cash payments, ensure midtrans_order_id is null
                    $order->midtrans_order_id = null;
                }

                $order->save();

                // Create order items and calculate totals
                $totalItems = 0;
                $subTotal = 0;

                foreach ($validatedData['order_items'] as $item) {
                    // Ambil nama produk dari product_id pada item
                    $productName = null;
                    if (!empty($item['product_id'])) {
                        $product = \App\Models\Product::find($item['product_id']);
                        $productName = $product ? $product->name : 'Nama Product';
                    }

                    $orderItem = $order->orderItems()->create([
                        'product_id' => $item['product_id'],
                        'product_name' => $productName,
                        'price' => $item['price'],
                        'total_price' => $item['quantity'] * $item['price']
                    ]);

                    $totalItems += $item['quantity'];
                    $subTotal += $orderItem->total_price;
                }

                // Calculate and save order totals
                $order->total_item = $totalItems;
                $order->sub_total = $subTotal;

                // Calculate final total after discounts
                $order->calculateTotals();

                // Ensure discount is not more than subtotal
                if ($order->discount_amount > $order->sub_total) {
                    $order->discount_amount = $order->sub_total;
                    $order->total_price = 0;
                } else {
                    $order->total_price = max(0, $order->sub_total - $order->discount_amount);
                }

                // Add tax and service charge if applicable
                if ($order->tax_rate > 0) {
                    $order->tax_amount = $order->total_price * ($order->tax_rate / 100);
                    $order->total_price += $order->tax_amount;
                }

                // Apply service charge if provided
                if (isset($validatedData['service_charge_id']) && $validatedData['service_charge_rate'] > 0) {
                    $order->service_charge_id = $validatedData['service_charge_id'];
                    $order->service_charge_rate = $validatedData['service_charge_rate'];
                    $order->service_charge = $subTotal * ($order->service_charge_rate / 100);
                    $order->total_price += $order->service_charge; // Add service charge to total
                }
                $order->service_charge = $order->service_charge ?? 0; // Ensure service charge is set to 0 if null
                $order->save();

                return $order;
            });

            return response()->json([
                'success' => true,
                'message' => 'Order Created Successfully',
                'data' => $order->load('orderItems') // Return the created order with items
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Illuminate\Support\Facades\Log::error('Order creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while creating the order.',
                'error' => $e->getMessage() // Optionally include for dev, remove for prod
            ], 500);
        }
    }

    // /**
    //  * Get order details by invoice number
    //  *
    //  * @param string $invoiceNumber
    //  * @return JsonResponse
    //  */
    // /**
    //  * Get order details by invoice number
    //  *
    //  * @return JsonResponse
    //  */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $this->authorize('update', Order::class);
        $response = [];

        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:' . Order::STATUS_PAID . ',' . Order::STATUS_CANCELLED . ',' . Order::STATUS_FAILED . ',' . Order::STATUS_PENDING . ',' . Order::STATUS_PROCESSING . ',' . Order::STATUS_COMPLETED,
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                $response = [
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ];
                return response()->json($response, 422);
            }

            $order = Order::find($id);

            if (!$order) {
                $response = [
                    'success' => false,
                    'message' => 'Pesanan tidak ditemukan'
                ];
                return response()->json($response, 404);
            }

            $this->authorize('update', $order);

            $order->status = $request->status;
            $order->notes = $request->notes;
            $order->save();

            $response = [
                'success' => true,
                'message' => 'Status pesanan berhasil diperbarui',
                'data' => $order
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui status pesanan: ' . $e->getMessage());

            $response = [
                'success' => false,
                'message' => 'Gagal memperbarui status pesanan',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
            return response()->json($response, 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $this->authorize('delete', Order::class);
        try {
            $order = Order::find($id);
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan tidak ditemukan'
                ], 404);
            }
            $this->authorize('delete', $order);
            $order->delete();
            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Gagal menghapus pesanan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pesanan',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
