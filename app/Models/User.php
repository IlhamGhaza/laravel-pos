<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
// use Filament\Tables\Columns\Layout\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable,HasApiTokens,HasRoles, SoftDeletes;
     public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    /**
     * Relationships
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'kasir_id');
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class, 'driver_id');
    }

    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class);
    }

    /**
     * Scopes
     */
    public function scopeKasir($query)
    {
        return $query->role('kasir');
    }

    public function scopeDriver($query)
    {
        return $query->role('driver');
    }

    public function scopeAdmin($query)
    {
        return $query->role('admin');
    }

    /**
     * Accessors
     */
    public function getIsKasirAttribute(): bool
    {
        return $this->hasRole('kasir');
    }

    public function getIsDriverAttribute(): bool
    {
        return $this->hasRole('driver');
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole('admin');
    }
}
