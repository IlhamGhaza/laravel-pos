<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'transaction_time',
        'payment_amount',
        'sub_total',
        'tax',
        'discount',
        'service_charge',
        'total_price',
        'total_item',
        'kasir_id',
        'payment_method',
    ];

    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }
}
