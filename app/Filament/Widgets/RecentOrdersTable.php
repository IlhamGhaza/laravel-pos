<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentOrdersTable extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()->latest('transaction_time')->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID Pesanan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->placeholder('N/A')
                    ->searchable(),
                Tables\Columns\TextColumn::make('kasir.name')
                    ->label('Kasir')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid', 'completed', 'validated_ok' => 'success',
                        'failed', 'cancelled', 'validation_failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('transaction_time')
                    ->label('Waktu Transaksi')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-m-eye'),
            ])
            ->emptyStateHeading('Tidak ada pesanan terbaru');
    }
}