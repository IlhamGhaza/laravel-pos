<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountResource\Pages;
use App\Models\Discount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup = 'Product Management';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Select::make('type')
                    ->options([
                        'fixed' => 'Fixed',
                        'percentage' => 'Percentage',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('value')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('expired_date')
                    // ->preload()
                    // ->searchable()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->toggleable()->sortable()
                    ->badge()
                    ->color(
                        fn(string $state): string => match ($state) {
                            'fixed' => 'primary',
                            'percentage' => 'warning',
                        }
                    )
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->numeric()
                    ->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->toggleable()->sortable() ->badge()
                    //color for active or not
                    ->color(
                        fn(string $state): string => match ($state) {
                            'active' => 'success',
                            'inactive' => 'danger',
                        }
                    )
                    ->searchable(),
                Tables\Columns\TextColumn::make('expired_date')
                    ->date()
                    ->toggleable()->sortable()
                    ->searchable(),
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
            'index' => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'view' => Pages\ViewDiscount::route('/{record}'),
            'edit' => Pages\EditDiscount::route('/{record}/edit'),
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
