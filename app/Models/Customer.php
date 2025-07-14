<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'address',
        'city',
        'state',
        'postal_code',
        'customer_type',
    ];

    /**
     * Relationships
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->has('orders');
    }

    public function scopeByPhone($query, $phone)
    {
        return $query->where('phone_number', 'like', "%{$phone}%");
    }

    /**
     * Accessors
     */
    public function getTotalOrdersAttribute(): int
    {
        return $this->orders()->count();
    }

    public function getTotalSpentAttribute(): float
    {
        return $this->orders()->where('status', 'paid')->sum('total_price');
    }

    public function getFormattedTotalSpentAttribute(): string
    {
        return 'Rp ' . number_format($this->total_spent, 0, ',', '.');
    }
}
