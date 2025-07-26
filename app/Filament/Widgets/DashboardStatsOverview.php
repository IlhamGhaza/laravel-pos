<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()->can('widget_DashboardStatsOverview');
    }

    protected function getStats(): array
    {
        $today = Carbon::today();

        $totalSalesToday = Order::whereDate('transaction_time', $today)
            ->where('status', '!=', 'cancelled')
            ->sum('total_price');

        $totalOrdersToday = Order::whereDate('transaction_time', $today)->count();

        $totalCustomers = Customer::count();

        // Asumsi ada kolom 'stock' dan batas minimal stok adalah 10
        $lowStockProductsCount = Product::where('stock', '<', 10)->count();

        return [
            Stat::make('Total Penjualan Hari Ini', 'Rp ' . number_format($totalSalesToday, 0, ',', '.'))
                ->description('Semua penjualan hari ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Total Pesanan Hari Ini', $totalOrdersToday)
                ->description('Jumlah pesanan masuk hari ini')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),
            Stat::make('Total Pelanggan', $totalCustomers)
                ->description('Jumlah pelanggan terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),
        ];
    }
}
