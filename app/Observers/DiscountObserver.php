<?php

namespace App\Observers;

use App\Models\Discount;
use Carbon\Carbon;

class DiscountObserver
{
    public function saving(Discount $discount)
    {
        $this->updateStatus($discount);
    }

    public function saved(Discount $discount)
    {
        $this->updateStatus($discount);
    }

    private function updateStatus(Discount $discount)
    {
        $now = now();
        $isActive = true;

        // Cek tanggal expired
        if ($discount->expired_date && Carbon::parse($discount->expired_date)->endOfDay() < $now) {
            $isActive = false;
        }

        // Cek tanggal mulai
        if ($discount->start_date && Carbon::parse($discount->start_date)->startOfDay() > $now) {
            $isActive = false;
        }

        // Cek usage limit
        if ($discount->usage_limit && $discount->usage_count >= $discount->usage_limit) {
            $isActive = false;
        }

        $discount->status = $isActive ? 'active' : 'inactive';

        if ($discount->isDirty('status')) {
            $discount->saveQuietly();
        }
    }

    /**
     * Method untuk fetch diskon aktif dengan relasi validDays
     */
    public static function getActiveDiscountsForSync()
    {
        return Discount::activeForSync()->get();
    }
}
