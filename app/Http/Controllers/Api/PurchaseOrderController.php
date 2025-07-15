<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderController extends Controller
{
    // Catatan: Asumsikan PurchaseOrderPolicy akan dibuat untuk otorisasi.

    public function index()
    {
        $this->authorize('viewAny', \App\Models\PurchaseOrder::class);
        return PurchaseOrder::with('supplier', 'items.product')->paginate(15);
    }

    public function store(Request $request)
    {
        $this->authorize('create', \App\Models\PurchaseOrder::class);
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'status' => 'required|in:pending,completed,cancelled',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validated = $validator->validated();

        try {
            DB::beginTransaction();

            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $validated['supplier_id'],
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'status' => $validated['status'],
                // Total akan dihitung di observer atau di sini
            ]);

            foreach ($validated['items'] as $item) {
                $purchaseOrder->items()->create($item);
            }

            // Jika status 'completed', idealnya ada logic untuk menambah stok produk.
            // Ini bisa ditangani melalui Observer pada model PurchaseOrder.

            DB::commit();

            return response()->json($purchaseOrder->load('items'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create purchase order', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);
        return $purchaseOrder->load('supplier', 'items.product');
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|in:pending,completed,cancelled',
            'expected_delivery_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $purchaseOrder->update($validator->validated());

        // Seperti store, logic update stok jika status berubah menjadi 'completed'
        // sebaiknya ditangani di Observer.

        return response()->json($purchaseOrder);
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('delete', $purchaseOrder);
        // Best practice: mungkin hanya PO dengan status 'pending' atau 'cancelled'
        // yang boleh dihapus. Logic ini ditempatkan di Policy.
        $purchaseOrder->delete();
        return response()->noContent();
    }
}
