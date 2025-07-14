<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'driver_id',
        'tracking_number',
        'recipient_name',
        'recipient_phone',
        'recipient_address',
        'recipient_city',
        'recipient_state',
        'recipient_postal_code',
        'scheduled_delivery_datetime',
        'dispatched_at',
        'delivery_at',
        'status',
        'proof_of_delivery_image_path',
        'total_weight',
        'requires_special_handling',
        'delivery_notes_internal',
        'delivery_notes_customer',
    ];

    protected $casts = [
        'scheduled_delivery_datetime' => 'datetime',
        'dispatched_at' => 'datetime',
        'delivery_at' => 'datetime',
        'total_weight' => 'decimal:2',
        'requires_special_handling' => 'boolean',
    ];

    // Constants
    const STATUS_PENDING = 'pending';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_DISPATCHED = 'dispatched';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_RESCHEDULED = 'rescheduled';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Relationships
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SCHEDULED,
            self::STATUS_DISPATCHED,
            self::STATUS_IN_TRANSIT
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_delivery_datetime', today());
    }

    public function scopeByDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    /**
     * Accessors
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function getIsInProgressAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_SCHEDULED,
            self::STATUS_DISPATCHED,
            self::STATUS_IN_TRANSIT
        ]);
    }

    public function getFullAddressAttribute(): string
    {
        return trim("{$this->recipient_address}, {$this->recipient_city}, {$this->recipient_state} {$this->recipient_postal_code}");
    }

    public function getProofImageUrlAttribute(): ?string
    {
        return $this->proof_of_delivery_image_path
            ? asset('storage/delivery_proofs/' . $this->proof_of_delivery_image_path)
            : null;
    }

    /**
     * Business Logic
     */
    public function dispatch(): void
    {
        $this->update([
            'status' => self::STATUS_DISPATCHED,
            'dispatched_at' => now(),
        ]);
    }

    public function markAsDelivered(string $proofImagePath = null): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivery_at' => now(),
            'proof_of_delivery_image_path' => $proofImagePath,
        ]);
    }

    public function reschedule(\DateTime $newDateTime, string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_RESCHEDULED,
            'scheduled_delivery_datetime' => $newDateTime,
            'delivery_notes_internal' => $this->delivery_notes_internal . "\nRescheduled: " . $reason,
        ]);
    }

    public function fail(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'delivery_notes_internal' => $this->delivery_notes_internal . "\nFailed: " . $reason,
        ]);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($delivery) {
            if (!$delivery->tracking_number) {
                $delivery->tracking_number = self::generateTrackingNumber();
            }
        });
    }

    public static function generateTrackingNumber(): string
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return "DEL-{$date}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
