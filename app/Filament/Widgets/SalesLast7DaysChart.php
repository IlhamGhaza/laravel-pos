<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class SalesLast7DaysChart extends ChartWidget
{
    protected static ?string $heading = 'Penjualan 7 Hari Terakhir';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->isoFormat('ddd, D MMM'); // Format: Rab, 5 Jun

            $sales = Order::whereDate('transaction_time', $date)
                ->where('status', '!=', 'cancelled')
                ->sum('total_price');
            $data[] = $sales;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan',
                    'data' => $data,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'borderWidth' => 1,
                    'fill' => 'start',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}