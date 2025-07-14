<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'order_item_id',
        'purchase_order_item_id',
        'user_id',
        'type',
        'quantity_change',
        'stock_before_change',
        'stock_after_change',
        'reason',
    ];

    protected $casts = [
        'quantity_change' => 'decimal:2',
        'stock_before_change' => 'decimal:2',
        'stock_after_change' => 'decimal:2',
    ];

    // Constants
    const TYPE_SALE = 'sale';
    const TYPE_RESTOCK = 'restock';
    const TYPE_ADJUSTMENT_IN = 'adjustment_in';
    const TYPE_ADJUSTMENT_OUT = 'adjustment_out';
    const TYPE_SALE_RETURN = 'sale_return';
    const TYPE_SALE_ADJUSTMENT = 'sale_adjustment';
    const TYPE_SPOILAGE = 'spoilage';
    const TYPE_EXPIRED = 'expired';
    const TYPE_DAMAGED = 'damaged';
    const TYPE_LOST = 'lost';
    const TYPE_THEFT = 'theft';
    const TYPE_SAMPLE = 'sample';
    const TYPE_WASTE = 'waste';
    const TYPE_INITIAL_STOCK = 'initial_stock';
    const TYPE_TRANSFER_IN = 'transfer_in';
    const TYPE_TRANSFER_OUT = 'transfer_out';
    const TYPE_PRODUCTION = 'production';
    const TYPE_QUALITY_REJECT = 'quality_reject';

    /**
     * Relationships
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeStockIncrease($query)
    {
        return $query->where('quantity_change', '>', 0);
    }

    public function scopeStockDecrease($query)
    {
        return $query->where('quantity_change', '<', 0);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    /**
     * Accessors
     */
    public function getIsStockIncreaseAttribute(): bool
    {
        return $this->quantity_change > 0;
    }

    public function getIsStockDecreaseAttribute(): bool
    {
        return $this->quantity_change < 0;
    }

    public function getFormattedQuantityChangeAttribute(): string
    {
        $prefix = $this->quantity_change > 0 ? '+' : '';
        return $prefix . number_format($this->quantity_change, 2);
    }

    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_SALE => 'Penjualan',
            self::TYPE_RESTOCK => 'Restock',
            self::TYPE_ADJUSTMENT_IN => 'Penyesuaian Masuk',
            self::TYPE_ADJUSTMENT_OUT => 'Penyesuaian Keluar',
            self::TYPE_SALE_RETURN => 'Return Penjualan',
            self::TYPE_SALE_ADJUSTMENT => 'Penyesuaian Penjualan',
            self::TYPE_SPOILAGE => 'Rusak',
            self::TYPE_EXPIRED => 'Kadaluarsa',
            self::TYPE_DAMAGED => 'Rusak Fisik',
            self::TYPE_LOST => 'Hilang',
            self::TYPE_THEFT => 'Pencurian',
            self::TYPE_SAMPLE => 'Sample',
            self::TYPE_WASTE => 'Terbuang',
            self::TYPE_INITIAL_STOCK => 'Stock Awal',
            default => ucfirst(str_replace('_', ' ', $this->type))
        };
    }
}
