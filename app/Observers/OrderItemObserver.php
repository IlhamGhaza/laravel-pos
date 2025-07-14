<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\User;

class OrderItemObserver
{
    /**
     * Handle the OrderItem "created" event.
     */
    public function created(OrderItem $orderItem): void
    {
        // Create inventory log for sale
        InventoryLog::create([
            'product_id' => $orderItem->product_id,
            'order_item_id' => $orderItem->id,
            'purchase_order_item_id' => null,
            'user_id' => $orderItem->order->kasir_id ?? User::first()->id,
            'type' => 'sale',
            'quantity_change' => -$orderItem->quantity, // Negative for sale
            'reason' => "Sale from order #{$orderItem->order_id}"
        ]);
    }

    /**
     * Handle the OrderItem "updated" event.
     */
    public function updated(OrderItem $orderItem): void
    {
        // If quantity changed, create adjustment log
        if ($orderItem->wasChanged('quantity')) {
            $oldQuantity = $orderItem->getOriginal('quantity');
            $newQuantity = $orderItem->quantity;
            $difference = $newQuantity - $oldQuantity;

            if ($difference != 0) {
                InventoryLog::create([
                    'product_id' => $orderItem->product_id,
                    'order_item_id' => $orderItem->id,
                    'purchase_order_item_id' => null,
                    'user_id' => User::first()->id,
                    'type' => 'sale_adjustment',
                    'quantity_change' => -$difference, // Negative for additional sale
                    'reason' => "Order item quantity adjusted from {$oldQuantity} to {$newQuantity}"
                ]);
            }
        }
    }

    /**
     * Handle the OrderItem "deleted" event.
     */
    public function deleted(OrderItem $orderItem): void
    {
        // Create return log
        InventoryLog::create([
            'product_id' => $orderItem->product_id,
            'order_item_id' => $orderItem->id,
            'purchase_order_item_id' => null,
            'user_id' => User::first()->id,
            'type' => 'sale_return',
            'quantity_change' => $orderItem->quantity, // Positive for return
            'reason' => "Order item deleted/returned from order #{$orderItem->order_id}"
        ]);
    }
}
