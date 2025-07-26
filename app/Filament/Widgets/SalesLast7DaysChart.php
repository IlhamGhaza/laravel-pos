<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Widgets\ChartWidget;

class SalesLast7DaysChart extends ChartWidget
{
    protected static ?string $heading = 'Penjualan 7 Hari Terakhir';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'half';

    public static function canView(): bool
    {
        return auth()->user()->can('widget_SalesLast7DaysChart');
    }

    public ?string $period = '7';

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('period')
                ->label('Periode')
                ->options([
                    '7' => '7 Hari',
                    '14' => '14 Hari',
                    '30' => '30 Hari',
                    '60' => '60 Hari',
                ])
                ->default('7')
                ->reactive()
        ];
    }

    public function getHeading(): string
    {
        return 'Penjualan ' . $this->period . ' Hari Terakhir';
    }

    protected function getData(): array
    {
        $salesData = [];
        $qtyData = [];
        $labels = [];
        $days = (int) $this->period;

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = \Carbon\Carbon::today()->subDays($i);
            $labels[] = $date->isoFormat('ddd, D MMM');

            // Total penjualan (uang)
            $sales = \App\Models\Order::whereDate('transaction_time', $date)
                ->where('status', '!=', 'cancelled')
                ->sum('total_price');
            $salesData[] = $sales;

            // Total produk terjual (qty)
            $qty = \App\Models\OrderItem::whereHas('order', function($q) use ($date) {
                $q->whereDate('transaction_time', $date)
                  ->where('status', '!=', 'cancelled');
            })->sum('quantity');
            $qtyData[] = $qty;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan (Rp)',
                    'data' => $salesData,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'borderWidth' => 2,
                    'fill' => 'start',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Total Produk Terjual (Qty)',
                    'data' => $qtyData,
                    'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
                    'borderColor' => 'rgb(255, 159, 64)',
                    'borderWidth' => 2,
                    'fill' => false,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
            'options' => [
                'scales' => [
                    'y' => [
                        'type' => 'linear',
                        'position' => 'left',
                        'title' => ['display' => true, 'text' => 'Penjualan (Rp)'],
                    ],
                    'y1' => [
                        'type' => 'linear',
                        'position' => 'right',
                        'grid' => ['drawOnChartArea' => false],
                        'title' => ['display' => true, 'text' => 'Qty Produk'],
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
