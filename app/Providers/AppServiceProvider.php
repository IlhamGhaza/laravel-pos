<?php

namespace App\Providers;

use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Observers\DiscountObserver;
use App\Observers\OrderItemObserver;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
use App\Observers\UserObserver;
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
        Order::observe(OrderObserver::class);
        User::observe(UserObserver::class);

    }
}
