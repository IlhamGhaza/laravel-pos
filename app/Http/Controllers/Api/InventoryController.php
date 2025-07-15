<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get current stock status
     */
    public function getStockStatus()
    {
        $this->authorize('viewAny', \App\Models\InventoryLog::class);
        $stockStatus = $this->inventoryService->getStockStatus();
        return response()->json([
            'success' => true,
            'data' => $stockStatus,
        ]);
    }

    /**
     * Get stock history for a product
     */
    public function getStockHistory($productId, Request $request)
    {
        $this->authorize('viewAny', \App\Models\InventoryLog::class);
        $validator = Validator::make([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ], $request->all());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $history = $this->inventoryService->getStockHistory(
            $productId,
            $request->input('start_date'),
            $request->input('end_date')
        );

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Adjust inventory (add/remove stock)
     */
    public function adjustInventory(Request $request)
    {
        $this->authorize('create', \App\Models\InventoryLog::class);
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|not_in:0',
            'type' => 'required|in:adjustment_in,adjustment_out,spoilage,expired,damaged,lost,theft,sample,waste,initial_stock',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::find($request->product_id);

        try {
            $log = $this->inventoryService->updateStock(
                $product,
                $request->quantity,
                $request->type,
                [
                    'reason' => $request->reason,
                    'notes' => $request->notes,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Inventory adjusted successfully',
                'data' => [
                    'log' => $log,
                    'current_stock' => $product->fresh()->stock_quantity,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust inventory',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get low stock alerts
     */
    public function getLowStockAlerts()
    {
        $this->authorize('viewAny', \App\Models\InventoryLog::class);
        $products = Product::whereColumn('stock_quantity', '<=', 'minimum_stock_level')
            ->where('stock_quantity', '>', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Get out of stock items
     */
    public function getOutOfStock()
    {
        $this->authorize('viewAny', \App\Models\InventoryLog::class);
        $products = Product::where('stock_quantity', '<=', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}
