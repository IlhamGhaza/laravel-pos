<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User; // Assuming User model exists for notifications
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail; // Example for email notifications
// use App\Mail\OrderCreatedNotification; // Example Mail class
// use App\Mail\OrderStatusUpdatedNotification; // Example Mail class

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // Logika setelah order dibuat
        Log::info("Order created: {$order->id} by User: {$order->user_id}");

        // Contoh: Kirim notifikasi ke admin
        // $adminUsers = User::whereHasRole('admin')->get(); // Assuming you have roles
        // foreach ($adminUsers as $admin) {
        //     Mail::to($admin->email)->send(new OrderCreatedNotification($order));
        // }

        // Contoh: Kirim notifikasi ke pelanggan
        // if ($order->user) {
        //     Mail::to($order->user->email)->send(new OrderConfirmation($order));
        // }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Logika setelah order diupdate
        Log::info("Order updated: {$order->id}, Status: {$order->status}");

        if ($order->isDirty('status')) {
            $newStatus = $order->status;
            $originalStatus = $order->getOriginal('status');

            Log::info("Order {$order->id} status changed from {$originalStatus} to {$newStatus}");

            // if ($newStatus === 'completed' && $order->user) {
            //     Mail::to($order->user->email)->send(new OrderStatusUpdatedNotification($order, 'Order Completed'));
            //     // Potentially generate invoice here or queue a job for it
            // } elseif ($newStatus === 'shipped' && $order->user) {
            //     Mail::to($order->user->email)->send(new OrderStatusUpdatedNotification($order, 'Order Shipped'));
            // } elseif ($newStatus === 'cancelled') {
            //     // Logika untuk restock item jika diperlukan dan belum ditangani OrderItemObserver
            //     // foreach ($order->orderItems as $item) {
            //     //     $product = $item->product;
            //     //     $product->stock += $item->quantity;
            //     //     $product->save();
            //     // }
            //     Log::info("Order {$order->id} cancelled. Items potentially restocked.");
            // }
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        Log::info("Order deleted: {$order->id}");
        // Pastikan order items juga dihapus atau ditangani (cascade delete di DB atau di sini)
        // Jika tidak ada cascade delete, dan OrderItemObserver tidak menangani restock pada deletion,
        // Anda mungkin perlu melakukannya di sini.
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        Log::info("Order restored: {$order->id}");
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        Log::info("Order force deleted: {$order->id}");
    }
}
