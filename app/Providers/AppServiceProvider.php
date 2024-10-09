<?php

namespace App\Providers;

use App\Models\Discount;
use App\Models\OrderItem;
use App\Observers\DiscountObserver;
use App\Observers\OrderItemObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        OrderItem::observe(OrderItemObserver::class);
        Discount::observe(DiscountObserver::class);

    }
}
