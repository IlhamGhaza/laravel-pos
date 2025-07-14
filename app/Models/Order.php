<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    // Define constants for decimal precision
    protected const DECIMAL_PRECISION = 'decimal:2';

    // Constants for status
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';

    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_CARD = 'card';
    const PAYMENT_METHOD_TRANSFER = 'transfer';
    const PAYMENT_METHOD_QRIS = 'qris';

    /**
     * Get the order items for the order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    const ORDER_TYPE_IN_PERSON = 'in-person';
    const ORDER_TYPE_PHONE = 'phone';
    const ORDER_TYPE_MOBILE_APP = 'mobile_app';

    const SYNC_STATUS_PENDING = 'pending_validation';
    const SYNC_STATUS_VALIDATED = 'validated_ok';
    const SYNC_STATUS_FAILED = 'validation_failed';
    const SYNC_STATUS_REVIEW = 'requires_review';

    protected $fillable = [
        'transaction_time',
        'kasir_id',
        'customer_id',
        'kasir_name',
        'customer_name',
        'sub_total',
        'tax_id',
        'tax_rate',
        'tax_amount',
        'service_charge_id',
        'service_charge_rate',
        'service_charge',
        'discount_id',
        'discount_details',
        'discount_amount',
        'total_price',
        'total_item',
        'payment_method',
        'payment_amount',
        'change_amount',
        'status',
        'order_type',
        'customer_order_notes',
        'midtrans_transaction_id',
        'midtrans_order_id',
        'payment_gateway_response',
        'paid_at',
        'is_synced_from_mobile',
        'mobile_sync_validation_status',
        'mobile_sync_notes',
        'mobile_synced_at',
    ];

    protected $casts = [
        'transaction_time' => 'datetime',
        'paid_at' => 'datetime',
        'mobile_synced_at' => 'datetime',
        'sub_total' => self::DECIMAL_PRECISION, // decimal(15,2)
        'tax_rate' => self::DECIMAL_PRECISION, // decimal(5,2)
        'tax_amount' => self::DECIMAL_PRECISION, // decimal(15,2)
        'service_charge_rate' => self::DECIMAL_PRECISION, // decimal(5,2)
        'service_charge' => self::DECIMAL_PRECISION, // decimal(15,2)
        'discount_amount' => self::DECIMAL_PRECISION, // decimal(15,2)
        'total_price' => self::DECIMAL_PRECISION, // decimal(15,2)
        'payment_amount' => self::DECIMAL_PRECISION, // decimal(15,2)
        'change_amount' => self::DECIMAL_PRECISION, // decimal(15,2)
        'discount_details' => 'array',
        'payment_gateway_response' => 'array',
        'is_synced_from_mobile' => 'boolean',
        'total_item' => 'integer',
    ];

    /**
     * Relationships
     */
    /**
     * Relationship with the Tax model
     */
    public function tax()
    {
        return $this->belongsTo(Tax::class)->withTrashed();
    }

    /**
     * Relationship with the ServiceCharge model
     */
    public function serviceCharge()
    {
        return $this->belongsTo(ServiceCharge::class, 'service_charge_id')->withTrashed();
    }

    /**
     * Relationship with the Discount model
     */
    public function discount()
    {
        return $this->belongsTo(Discount::class)->withTrashed();
    }

    /**
     * Relationship with the User (kasir) model
     */
    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }

    /**
     * Relationship with the Customer model
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship with OrderItem model
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }

    public function mobileSyncValidationIssues()
    {
        return $this->hasMany(MobileSyncValidationIssue::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scopes
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('transaction_time', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('transaction_time', now()->month)
            ->whereYear('transaction_time', now()->year);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeMobileSync($query)
    {
        return $query->where('is_synced_from_mobile', true);
    }

    /**
     * Accessors & Mutators
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }

    public function getFormattedSubTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->sub_total, 0, ',', '.');
    }

    public function getIsPaidAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_COMPLETED]);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getHasDeliveryAttribute(): bool
    {
        return $this->delivery()->exists();
    }

    /**
     * Business Logic Methods
     */
    public function calculateTotals(): self
    {
        // Calculate subtotal from order items
        $this->sub_total = $this->orderItems->sum(function ($item) {
            return $item->quantity * $item->price;
        });

        // Apply discount if exists
        if ($this->discount_details) {
            $this->discount_amount = $this->calculateDiscountAmount(
                $this->sub_total,
                $this->discount_details
            );
        } else {
            $this->discount_amount = $this->discount_amount ?? 0;
        }

        $taxableAmount = $this->sub_total - $this->discount_amount;

        // Calculate tax
        $this->tax_amount = $this->tax_rate > 0 ?
            $taxableAmount * ($this->tax_rate / 100) : 0;

        // Calculate service charge
        $this->service_charge = $this->service_charge_rate > 0 ?
            $taxableAmount * ($this->service_charge_rate / 100) : 0;

        // Calculate total price
        $this->total_price = $taxableAmount + $this->tax_amount + $this->service_charge;

        // Calculate change if payment amount is set
        if (isset($this->payment_amount)) {
            $this->change_amount = max(0, $this->payment_amount - $this->total_price);
        }

        return $this;
    }

    /**
     * Calculate discount amount based on discount details.
     *
     * @param float $subTotal
     * @param array $discountDetails
     * @return float
     */
    protected function calculateDiscountAmount(float $subTotal, array $discountDetails): float
    {
        if (empty($discountDetails) || !isset($discountDetails['type'], $discountDetails['value'])) {
            return 0;
        }

        $discountAmount = 0;
        $maxDiscount = $discountDetails['max_amount'] ?? null;

        if ($discountDetails['type'] === 'percentage') {
            $discountAmount = $subTotal * ($discountDetails['value'] / 100);
            if ($maxDiscount !== null && $discountAmount > $maxDiscount) {
                $discountAmount = $maxDiscount;
            }
        } else {
            $discountAmount = min($discountDetails['value'], $subTotal);
        }

        return round($discountAmount, 2);
    }

    /**
     * Apply tax and service charge rates to the order.
     *
     * @param Tax|null $tax
     * @param ServiceCharge|null $serviceCharge
     * @return $this
     */
    public function applyRates(?Tax $tax, ?ServiceCharge $serviceCharge): self
    {
        if ($tax) {
            $this->tax_id = $tax->id;
            $this->tax_rate = $tax->rate;
        }

        if ($serviceCharge) {
            $this->service_charge_id = $serviceCharge->id;
            $this->service_charge_rate = $serviceCharge->rate;
        }

        return $this;
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now()
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    public function applyDiscount(Discount $discount, float $amount): void
    {
        $this->update([
            'discount_id' => $discount->id,
            'discount_amount' => $amount,
        ]);

        $this->calculateTotals();
        $this->save();
    }

    /**
     * Static methods
     */
    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return "ORD-{$date}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            // Only generate midtrans_order_id for non-cash payments if not already set
            if (!$order->midtrans_order_id && $order->payment_method !== self::PAYMENT_METHOD_CASH) {
                $order->midtrans_order_id = self::generateOrderNumber();
            } elseif ($order->payment_method === self::PAYMENT_METHOD_CASH) {
                // Ensure midtrans_order_id is null for cash payments
                $order->midtrans_order_id = null;
            }
        });

        static::saved(function ($order) {
            // Recalculate totals when order is saved
            if ($order->wasChanged(['tax_rate', 'service_charge_rate', 'discount_amount'])) {
                $order->calculateTotals();
            }
        });
    }
}
