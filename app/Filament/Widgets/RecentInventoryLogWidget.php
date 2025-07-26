<?php

namespace App\Filament\Widgets;

use App\Models\InventoryLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentInventoryLogWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->can('widget_RecentInventoryLogWidget');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InventoryLog::query()->latest('created_at')->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID'),
                Tables\Columns\TextColumn::make('product.name')->label('Produk'),
                Tables\Columns\TextColumn::make('type')->label('Tipe'),
                Tables\Columns\TextColumn::make('quantity')->label('Qty'),
                Tables\Columns\TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i'),
            ])
            ->emptyStateHeading('Tidak ada log inventory terbaru');
    }
}
