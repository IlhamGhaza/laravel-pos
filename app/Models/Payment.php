<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'amount',
        'payment_method',
        'status',
        'transaction_id',
        'payment_details',
        'paid_at',
        'confirmed_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array',
        'paid_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_CHALLENGE = 'challenge';

    // Payment methods
    const METHOD_CASH = 'cash';
    const METHOD_TRANSFER = 'transfer';
    const METHOD_MIDTRANS = 'midtrans';
    const METHOD_QRIS = 'qris';
    const METHOD_CREDIT_CARD = 'credit_card';
    const METHOD_DEBIT_CARD = 'debit_card';

    /**
     * Get the order that owns the payment.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who confirmed the payment.
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Scope a query to only include pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include paid payments.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payment is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if payment is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark payment as paid.
     */
    public function markAsPaid(int $confirmedBy = null): bool
    {
        return $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'confirmed_by' => $confirmedBy,
        ]);
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'notes' => $notes,
        ]);
    }
}
