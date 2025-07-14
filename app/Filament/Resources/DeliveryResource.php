<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryResource\Pages;
use App\Filament\Resources\DeliveryResource\RelationManagers;
use App\Models\Delivery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Order Management';
    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'id')
                    ->required(),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required(),
                Forms\Components\Select::make('driver_id')
                    ->relationship('driver', 'name'),
                Forms\Components\TextInput::make('tracking_number')
                    ->maxLength(255),
                Forms\Components\TextInput::make('recipient_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('recipient_phone')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('recipient_address')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('recipient_city')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('recipient_state')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('recipient_postal_code')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('scheduled_delivery_datetime'),
                Forms\Components\DateTimePicker::make('dispatched_at'),
                Forms\Components\DateTimePicker::make('delivery_at'),
                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'pending' => 'Pending',
                        'scheduled' => 'Scheduled',
                        'in_transit' => 'In Transit',
                        'dispatched' => 'Dispatched',
                        'delivered' => 'Delivered',
                        'returned' => 'Returned',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
                Forms\Components\FileUpload::make('proof_of_delivery_image_path')
                    ->image(),
                Forms\Components\TextInput::make('total_weight')
                    ->numeric(),
                Forms\Components\Toggle::make('requires_special_handling')
                    ->required(),
                Forms\Components\Textarea::make('delivery_notes_internal')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('delivery_notes_customer')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.id')
                    ->numeric()
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_id')
                    ->numeric()
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('driver.name')
                    ->numeric()
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tracking_number')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipient_name')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipient_phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('recipient_city')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipient_state')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipient_postal_code')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scheduled_delivery_datetime')
                    ->dateTime()
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dispatched_at')
                    ->dateTime()
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_at')
                    ->dateTime()
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()->badge()
                    ->color(function (Delivery $record) {
                        return match ($record->status) {
                            'pending' => 'gray',
                            'scheduled' => 'info',
                            'in_transit' => 'warning',
                            'dispatched' => 'info',
                            'delivered' => 'success',
                            'returned' => 'warning',
                            'failed' => 'danger',
                            'cancelled' => 'gray',
                        };
                    })
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('proof_of_delivery_image_path')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_weight')
                    ->numeric()
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('requires_special_handling')
                    ->boolean()
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveries::route('/'),
            'create' => Pages\CreateDelivery::route('/create'),
            'view' => Pages\ViewDelivery::route('/{record}'),
            'edit' => Pages\EditDelivery::route('/{record}/edit'),
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
