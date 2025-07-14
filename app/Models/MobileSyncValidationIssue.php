<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileSyncValidationIssue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'field_name',
        'issue_description',
        'mobile_value',
        'server_calculated_value',
        'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scopes
     */
    public function scopeByOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByField($query, $fieldName)
    {
        return $query->where('field_name', $fieldName);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('logged_at', 'desc');
    }

    /**
     * Accessors
     */
    public function getIsResolvedAttribute(): bool
    {
        return $this->mobile_value === $this->server_calculated_value;
    }

    public function getVarianceAttribute(): ?float
    {
        if (is_numeric($this->mobile_value) && is_numeric($this->server_calculated_value)) {
            return abs($this->mobile_value - $this->server_calculated_value);
        }
        return null;
    }
}
