<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryLogResource\Pages;
use App\Filament\Resources\InventoryLogResource\RelationManagers;
use App\Models\InventoryLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryLogResource extends Resource
{
    protected static ?string $model = InventoryLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?int $navigationSort = 14;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                Forms\Components\Select::make('order_item_id')
                    ->relationship('orderItem', 'id'),
                Forms\Components\Select::make('purchase_order_item_id')
                    ->relationship('purchaseOrderItem', 'id'),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options([
                        'sale' => 'Sale',
                        'restock' => 'Restock',
                        'adjustment_in' => 'Adjustment In',
                        'adjustment_out' => 'Adjustment Out',
                        'sale_return' => 'Sale Return',
                        'sale_adjustment' => 'Sale Adjustment',
                        'spoilage' => 'Spoilage',
                        'expired' => 'Expired',
                        'damaged' => 'Damaged',
                        'lost' => 'Lost',
                        'theft' => 'Theft',
                        'sample' => 'Sample',
                        'waste' => 'Waste',
                        'initial_stock' => 'Initial Stock',
                        'transfer_in' => 'Transfer In',
                        'transfer_out' => 'Transfer Out',
                        'production' => 'Production',
                        'quality_reject' => 'Quality Reject',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('quantity_change')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('stock_before_change')
                    ->numeric(),
                Forms\Components\TextInput::make('stock_after_change')
                    ->numeric(),
                Forms\Components\Textarea::make('reason')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('orderItem.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseOrderItem.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sale' => 'success',
                        'restock' => 'primary',
                        'adjustment_in' => 'info',
                        'adjustment_out' => 'warning',
                        'sale_return' => 'gray',
                        'sale_adjustment' => 'info',
                        'spoilage' => 'danger',
                        'expired' => 'danger',
                        'damaged' => 'danger',
                        'lost' => 'danger',
                        'theft' => 'danger',
                        'sample' => 'warning',
                        'waste' => 'danger',
                        'initial_stock' => 'success',
                        'transfer_in' => 'info',
                        'transfer_out' => 'warning',
                        'production' => 'primary',
                        'quality_reject' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('quantity_change')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_before_change')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_after_change')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                // Tables\Actions\RestoreAction::make('Restore')
                //     ->color('success'),
                // Tables\Actions\ForceDeleteAction::make('ForceDelete')
                //     ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryLogs::route('/'),
            'create' => Pages\CreateInventoryLog::route('/create'),
            'view' => Pages\ViewInventoryLog::route('/{record}'),
            'edit' => Pages\EditInventoryLog::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
