<?php

namespace App\Services;

use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Update stock based on inventory log
     */
    public function updateStock(Product $product, float $quantity, string $type, array $data = []): InventoryLog
    {
        return DB::transaction(function () use ($product, $quantity, $type, $data) {
            $currentStock = $product->stock_quantity;
            $newStock = $this->calculateNewStock($currentStock, $quantity, $type);

            // Update product stock
            $product->stock_quantity = $newStock;
            $product->save();

            // Create inventory log
            $logData = array_merge([
                'product_id' => $product->id,
                'type' => $type,
                'quantity_change' => $quantity,
                'stock_before_change' => $currentStock,
                'stock_after_change' => $newStock,
                'user_id' => auth()->id(),
            ], $data);

            return InventoryLog::create($logData);
        });
    }

    /**
     * Calculate new stock based on type
     */
    private function calculateNewStock(float $currentStock, float $quantity, string $type): float
    {
        return match($type) {
            InventoryLog::TYPE_RESTOCK,
            InventoryLog::TYPE_ADJUSTMENT_IN,
            InventoryLog::TYPE_TRANSFER_IN => $currentStock + $quantity,

            InventoryLog::TYPE_SALE,
            InventoryLog::TYPE_ADJUSTMENT_OUT,
            InventoryLog::TYPE_SPOILAGE,
            InventoryLog::TYPE_EXPIRED,
            InventoryLog::TYPE_DAMAGED,
            InventoryLog::TYPE_LOST,
            InventoryLog::TYPE_THEFT,
            InventoryLog::TYPE_SAMPLE,
            InventoryLog::TYPE_WASTE,
            InventoryLog::TYPE_TRANSFER_OUT => $currentStock - $quantity,

            default => $currentStock,
        };
    }

    /**
     * Get product stock history
     */
    public function getStockHistory($productId, $startDate = null, $endDate = null)
    {
        $query = InventoryLog::where('product_id', $productId)
            ->with(['user', 'orderItem.order', 'purchaseOrderItem.purchaseOrder'])
            ->orderBy('created_at', 'desc');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->paginate(50);
    }

    /**
     * Get current stock status for all products
     */
    public function getStockStatus()
    {
        return Product::select('id', 'name', 'stock_quantity', 'minimum_stock_level')
            ->with(['category'])
            ->get()
            ->map(function ($product) {
                $product->status = $this->getStockStatusForProduct($product);
                return $product;
            });
    }

    /**
     * Get stock status for a single product
     */
    private function getStockStatusForProduct(Product $product): string
    {
        if ($product->stock_quantity <= 0) {
            return 'out_of_stock';
        }

        if ($product->stock_quantity <= $product->minimum_stock_level) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}
