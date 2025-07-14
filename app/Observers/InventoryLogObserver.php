<?php

namespace App\Observers;

use App\Models\InventoryLog;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class InventoryLogObserver
{
    /**
     * Handle the InventoryLog "creating" event.
     */
    public function creating(InventoryLog $inventoryLog): void
    {
        $product = Product::find($inventoryLog->product_id);

        if ($product) {
            // Set stock before change
            $inventoryLog->stock_before_change = $product->stock;

            // Calculate stock after change based on type
            $inventoryLog->stock_after_change = $this->calculateStockAfterChange($product, $inventoryLog);
        }
    }

    /**
     * Handle the InventoryLog "created" event.
     */
    public function created(InventoryLog $inventoryLog): void
    {
        // Update product stock
        $product = Product::find($inventoryLog->product_id);

        if ($product) {
            $product->update([
                'stock' => $inventoryLog->stock_after_change
            ]);

            // Handle special cases based on type
            $this->handleSpecialCases($product, $inventoryLog);
        }
    }

    /**
     * Handle the InventoryLog "updated" event.
     */
    public function updated(InventoryLog $inventoryLog): void
    {
        // Recalculate stock if quantity_change is modified
        if ($inventoryLog->wasChanged('quantity_change')) {
            $product = Product::find($inventoryLog->product_id);

            if ($product) {
                $oldChange = $inventoryLog->getOriginal('quantity_change');
                $newChange = $inventoryLog->quantity_change;
                $difference = $newChange - $oldChange;

                // Apply the difference to current stock
                $newStock = $product->stock + $difference;
                $product->update(['stock' => max(0, $newStock)]); // Prevent negative stock

                // Update stock_after_change
                $inventoryLog->update([
                    'stock_after_change' => $product->fresh()->stock
                ]);
            }
        }
    }

    /**
     * Handle the InventoryLog "deleted" event.
     */
    public function deleted(InventoryLog $inventoryLog): void
    {
        // Reverse the stock change
        $product = Product::find($inventoryLog->product_id);

        if ($product) {
            $reverseChange = -$inventoryLog->quantity_change;
            $newStock = $product->stock + $reverseChange;
            $product->update(['stock' => max(0, $newStock)]); // Prevent negative stock
        }
    }

    /**
     * Calculate stock after change based on inventory type
     */
    private function calculateStockAfterChange(Product $product, InventoryLog $inventoryLog): float
    {
        $currentStock = $product->stock;
        $quantityChange = $inventoryLog->quantity_change;

        switch ($inventoryLog->type) {
            // Stock increase operations
            case 'restock':           // Supplier masuk
            case 'adjustment_in':     // Koreksi tambah
            case 'sale_return':       // Return dari customer
            case 'initial_stock':     // Stock awal
                return $currentStock + abs($quantityChange);

                // Stock decrease operations
            case 'sale':              // Barang terjual
            case 'adjustment_out':    // Koreksi kurang
            case 'spoilage':          // Rusak/expired
            case 'sale_adjustment':   // Adjustment penjualan
            case 'expired':           // Kadaluarsa
            case 'damaged':           // Rusak
            case 'lost':              // Hilang
            case 'theft':             // Pencurian
            case 'sample':            // Sample gratis
            case 'waste':             // Terbuang
                return max(0, $currentStock - abs($quantityChange)); // Prevent negative

            default:
                // For custom types, use the quantity_change as is
                return max(0, $currentStock + $quantityChange);
        }
    }

    /**
     * Handle special cases based on inventory type
     */
    private function handleSpecialCases(Product $product, InventoryLog $inventoryLog): void
    {
        switch ($inventoryLog->type) {
            case 'expired':
                // Mark product as needs attention if stock is low after expiry
                if ($product->stock <= 10) {
                    // Could trigger notification or flag
                    Log::warning("Product {$product->name} has low stock after expiry removal");
                }
                break;

            case 'spoilage':
            case 'damaged':
                // Log quality issues for reporting
                Log::info("Quality issue recorded for product {$product->name}: {$inventoryLog->type}");
                break;

            case 'restock':
                // Update last restock date if needed
                $product->update(['last_restocked_at' => now()]);
                break;

            case 'theft':
            case 'lost':
                // Security incident logging
                Log::warning("Security incident: {$inventoryLog->type} for product {$product->name}");
                break;
        }
    }
}
