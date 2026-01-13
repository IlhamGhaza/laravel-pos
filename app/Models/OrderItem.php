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
        'product_name',
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
            // InventoryLog creation is handled by OrderItemObserver
        });
    }
}

