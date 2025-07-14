<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        // 'contact_person',
        'phone_number',
        'email',
        'address',
        'company_name',
        'tax_number',
        'payment_terms',
        'credit_days',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => 'string', // Sesuai dengan enum di migrasi ('active', 'inactive', 'suspended')
        'credit_days' => 'integer',
    ];

    // Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Relationships
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    public function scopeByPhone($query, $phone)
    {
        return $query->where('phone_number', 'like', "%{$phone}%"); // Sesuai dengan migrasi 'phone_number'
    }

    /**
     * Accessors
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getTotalPurchaseOrdersAttribute(): int
    {
        return $this->purchaseOrders()->count();
    }

    public function getTotalPurchaseAmountAttribute(): float
    {
        return $this->purchaseOrders()
            ->where('status', PurchaseOrder::STATUS_RECEIVED)
            ->sum('total_amount');
    }

    public function getFormattedTotalPurchaseAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->total_purchase_amount, 0, ',', '.');
    }

    public function getLastPurchaseOrderAttribute(): ?PurchaseOrder
    {
        return $this->purchaseOrders()->latest()->first();
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?: $this->name;
    }

    /**
     * Business Logic
     */
    public function activate(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function deactivate(): void
    {
        $this->update(['status' => self::STATUS_INACTIVE]);
    }

    public function canBeDeleted(): bool
    {
        return $this->purchaseOrders()->count() === 0;
    }

    public function hasActiveOrders(): bool
    {
        return $this->purchaseOrders()
            ->whereIn('status', [
                PurchaseOrder::STATUS_PENDING,
                PurchaseOrder::STATUS_ORDERED
            ])
            ->exists();
    }

    /**
     * Static methods
     */
    public static function getActiveSuppliers()
    {
        return self::active()->orderBy('name')->get();
    }

    public static function searchByNameOrCompany(string $search)
    {
        return self::where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%");
        });
    }
}
