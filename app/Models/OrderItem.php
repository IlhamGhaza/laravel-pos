<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    /**
     * Accessors
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    /**
     * Business Logic
     */
    public function calculateTotal(): void
    {
        $this->total_price = $this->quantity * $this->price;
    }

    /**
     * Boot method
     */
    protected static function booted(): void
    {
        static::saving(function (OrderItem $orderItem) {
            if ($orderItem->isDirty('quantity') || $orderItem->isDirty('price')) {
                $orderItem->calculateTotal();
            }
        });

        static::saved(function (OrderItem $orderItem) {
            if ($orderItem->order) { // Pastikan relasi order ada
                $orderItem->order->calculateTotals(); // Ini sudah memanggil save() di model Order
            }

            // Buat Inventory Log ketika OrderItem baru dibuat dan status Order relevan (misal: paid, completed)
            // Kondisi ini penting agar log tidak dibuat untuk order yang masih draft atau dibatalkan.
            // Cara yang lebih robust mungkin menggunakan OrderObserver pada perubahan status order.
            // Namun, untuk contoh ini, kita periksa saat OrderItem disimpan.
            if ($orderItem->wasRecentlyCreated && $orderItem->order &&
                in_array($orderItem->order->status, [Order::STATUS_PAID, Order::STATUS_COMPLETED, Order::STATUS_PROCESSING])) {

                InventoryLog::create([
                    'product_id' => $orderItem->product_id,
                    'order_item_id' => $orderItem->id,
                    'user_id' => $orderItem->order->kasir_id ?? auth()->user_id, // Ambil kasir_id dari order
                    'type' => InventoryLog::TYPE_SALE,
                    'quantity_change' => -$orderItem->quantity, // Negatif untuk pengurangan stok
                    // 'stock_before_change' => null, // Isi jika relevan
                    // 'stock_after_change' => null,  // Isi jika relevan
                    'reason' => 'Penjualan untuk Order ID: ' . $orderItem->order_id,
                ]);

                // Jika aplikasi ini MENGELOLA stok (yang mana TIDAK):
                // if ($orderItem->product) {
                //     $orderItem->product->reduceStock($orderItem->quantity);
                // }
            }
        });
    }
}
