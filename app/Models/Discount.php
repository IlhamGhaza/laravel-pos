<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'status',
        'expired_date',
    ];

    public function isExpired()
    {
        return $this->expired_date && $this->expired_date < now();
    }
}
