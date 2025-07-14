<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderItemResource\Pages;
use App\Filament\Resources\PurchaseOrderItemResource\RelationManagers;
use App\Models\PurchaseOrderItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PurchaseOrderItemResource extends Resource
{
    protected static ?string $model = PurchaseOrderItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('purchase_order_id')
                    ->relationship('purchaseOrder', 'id')
                    ->required(),
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                Forms\Components\TextInput::make('quantity_ordered')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('quantity_received')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('unit_cost_price')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('subtotal')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('purchaseOrder.id')
                    ->numeric()
                ->searchable()
                ->toggleable()
               ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->numeric()
                   ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_ordered')
                    ->numeric()
                   ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_received')
                    ->numeric()
                   ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_cost_price')
                    ->numeric()
                   ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->numeric()
                   ->searchable()
                    ->toggleable()
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
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make('Restore')
                    ->color('success'),
                Tables\Actions\ForceDeleteAction::make('ForceDelete')
                    ->color('danger'),
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
            'index' => Pages\ListPurchaseOrderItems::route('/'),
            'create' => Pages\CreatePurchaseOrderItem::route('/create'),
            'view' => Pages\ViewPurchaseOrderItem::route('/{record}'),
            'edit' => Pages\EditPurchaseOrderItem::route('/{record}/edit'),
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
