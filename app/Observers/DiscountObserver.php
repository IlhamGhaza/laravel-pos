<?php

namespace App\Observers;

use App\Models\Discount;

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
        if ($discount->expired_date && $discount->expired_date < now()) {
            $discount->status = 'inactive';
        } else {
            $discount->status = 'active';
        }
        $discount->saveQuietly();
    }

    // /**
    //  * Handle the Discount "created" event.
    //  */
    // public function created(Discount $discount): void
    // {
    //     //
    // }

    // /**
    //  * Handle the Discount "updated" event.
    //  */
    // public function updated(Discount $discount): void
    // {
    //     //
    // }

    // /**
    //  * Handle the Discount "deleted" event.
    //  */
    // public function deleted(Discount $discount): void
    // {
    //     //
    // }

    // /**
    //  * Handle the Discount "restored" event.
    //  */
    // public function restored(Discount $discount): void
    // {
    //     //
    // }

    // /**
    //  * Handle the Discount "force deleted" event.
    //  */
    // public function forceDeleted(Discount $discount): void
    // {
    //     //
    // }
}
