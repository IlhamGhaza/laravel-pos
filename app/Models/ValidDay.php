<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ValidDay extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'discount_id',
        'day_of_week'
    ];

    protected $casts = [
        'day_of_week' => 'integer'
    ];

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    // Helper method untuk nama hari
    public function getDayNameAttribute()
    {
        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu'
        ];

        return $days[$this->day_of_week] ?? '';
    }

    // Scope untuk hari tertentu
    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }
}
