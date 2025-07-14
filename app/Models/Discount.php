<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Discount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'min_quantity',
        'max_quantity',
        'min_amount',

        'buy_quantity',
        'get_quantity',
        'quantity_tiers',
        'apply_to',
        'applicable_items',
        'customer_type',
        'combinable',

        'usage_limit',
        'usage_count',
        'status',
        'start_date',
        'expired_date',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        // 'max_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'buy_quantity' => 'integer',
        'get_quantity' => 'integer',
        'applicable_items' => 'array',
        'start_date' => 'date',
        'expired_date' => 'date',
        'start_time' => 'string',
        'end_time' => 'string',
        'quantity_tiers' => 'array',
    ];


    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED = 'fixed';
    const TYPE_BUY_GET = 'buy_x_get_y';
    const TYPE_QUANTITY = 'quantity_based';
    const TYPE_BULK = 'bulk_discount';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    const APPLY_ALL = 'all';
    const APPLY_CATEGORY = 'category';
    const APPLY_PRODUCT = 'product';

    const CUSTOMER_RETAIL = 'retail'; // Sesuai enum migrasi
    const CUSTOMER_WHOLESALE = 'wholesale'; // Sesuai enum migrasi
    const CUSTOMER_MEMBER = 'member'; // Sesuai enum migrasi

    /**
     * Relationships
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function validDays(): HasMany
    {
        return $this->hasMany(ValidDay::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeValid($query)
    {
        $now = now();
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $now->toDateString());
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expired_date')
                    ->orWhere('expired_date', '>=', $now->toDateString());
            });
    }

    public function scopeActiveForSync(Builder $query): Builder
    {
        $now = now();
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $q) use ($now) { // Date validity
                $q->whereNull('start_date')
                  ->orWhereDate('start_date', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('expired_date')
                  ->orWhereDate('expired_date', '>=', $now);
            })
            ->where(function (Builder $q) { // Usage limit validity
                $q->whereNull('usage_limit')
                  ->orWhereColumn('usage_count', '<', 'usage_limit');
            });
    }

    public function scopeValidToday(Builder $query): Builder
    {
        $now = now();
        // Assuming day_of_week in valid_days table is ISO-8601 (1 for Monday, 7 for Sunday)
        // Carbon's dayOfWeekIso returns this format.
        $todayDayOfWeek = $now->dayOfWeekIso;

        return $query->activeForSync() // Leverage the existing activeForSync scope
            ->where(function (Builder $q) use ($now) {
                // Check time validity if start_time and end_time are present
                $q->where(function (Builder $timeQuery) use ($now) {
                    $timeQuery->whereNull('start_time')
                              ->orWhereTime('start_time', '<=', $now); // Use $now directly with whereTime
                })
                ->where(function (Builder $timeQuery) use ($now) {
                    $timeQuery->whereNull('end_time')
                              ->orWhereTime('end_time', '>=', $now);
                });
            })
            ->whereHas('validDays', function (Builder $vdQuery) use ($todayDayOfWeek) {
                $vdQuery->where('day_of_week', $todayDayOfWeek);
            });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Accessors
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expired_date && Carbon::parse($this->expired_date)->isPast();
    }

    public function getIsValidAttribute(): bool
    {
        $now = now();


        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }


        if ($this->start_date && Carbon::parse($this->start_date)->isFuture()) {
            return false;
        }

        if ($this->expired_date && Carbon::parse($this->expired_date)->isPast()) {
            return false;
        }


        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function getFormattedValueAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PERCENTAGE => rtrim(rtrim(number_format($this->value, 2, '.', ''), '0'), '.') . '%',
            self::TYPE_FIXED => 'Rp ' . number_format($this->value, 0, ',', '.'),
            default => $this->value
        };
    }

    /**
     * Business Logic
     */
    public function calculateDiscount(float $amount, int $quantity = 1): float
    {
        if (!$this->is_valid) {
            return 0;
        }

        if ($this->min_amount && $amount < $this->min_amount) {
            return 0;
        }

        $discount = match ($this->type) {
            self::TYPE_PERCENTAGE => ($amount * $this->value) / 100,
            self::TYPE_FIXED => $this->value,
            self::TYPE_QUANTITY => $quantity >= $this->buy_quantity ? $this->value : 0,
            default => 0
        };


        // if ($this->max_discount && $discount > $this->max_discount) {
        //     $discount = $this->max_discount;
        // }

        return $discount;
    }

    public function incrementUsage(): void
    {
        if ($this->usage_limit !== null) { // Hanya increment jika ada batasan
            $this->increment('usage_count');
        }
    }

    public function canBeUsed(): bool
    {
        return $this->is_valid && (!$this->usage_limit || $this->usage_count < $this->usage_limit);
    }
}
