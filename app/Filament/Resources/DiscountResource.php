<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountResource\Pages;
use App\Filament\Resources\DiscountResource\RelationManagers;
use App\Models\Discount;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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
                Forms\Components\TextInput::make('min_quantity')
                    ->numeric(),
                Forms\Components\TextInput::make('max_quantity')
                    ->numeric(),
                Forms\Components\TextInput::make('min_amount')
                    ->numeric(),
                Forms\Components\TextInput::make('buy_quantity')
                    ->numeric(),
                Forms\Components\TextInput::make('get_quantity')
                    ->numeric(),
                Forms\Components\TextInput::make('quantity_tiers'),
                Forms\Components\Select::make('apply_to')
                    ->options([
                        'all' => 'All',
                        'category' => 'Category',
                        'product' => 'Product',
                    ])
                    ->required()
                    ->live(),
                Forms\Components\Select::make('applicable_items')
                    ->options(function (callable $get) {
                        $applyTo = $get('apply_to');

                        if ($applyTo === 'category') {
                            return Category::pluck('name', 'id')->toArray();
                        }

                        if ($applyTo === 'product') {
                            return Product::pluck('name', 'id')->toArray();
                        }

                        return [];
                    })
                    ->multiple()
                    ->visible(fn(callable $get) => $get('apply_to') !== 'all')
                    ->required(fn(callable $get) => $get('apply_to') !== 'all'),
                Forms\Components\Select::make('customer_type')
                    ->options([
                        'all' => 'All',
                        'retail' => 'Retail',
                        'wholesale' => 'Wholesale',
                        'member' => 'Member',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('combinable')
                    ->required(),
                Forms\Components\TextInput::make('usage_limit')
                    ->numeric(),
                Forms\Components\TextInput::make('usage_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
                Forms\Components\DatePicker::make('start_date'),
                Forms\Components\DatePicker::make('expired_date'),
                Forms\Components\TimePicker::make('start_time')
                    ->seconds(false)
                    ->rules(['before:end_time'])
                    ->validationMessages([
                        'before' => 'Start time must be before end time.',
                    ]),
                Forms\Components\TimePicker::make('end_time')
                    ->seconds(false)
                    ->rules(['after:start_time'])
                    ->validationMessages([
                        'after' => 'End time must be after start time.',
                    ]),
                Repeater::make('validDays')
                    ->relationship('validDays')
                    ->schema([
                        Select::make('day_of_week')
                            ->options([
                                '1' => 'Monday',
                                '2' => 'Tuesday',
                                '3' => 'Wednesday',
                                '4' => 'Thursday',
                                '5' => 'Friday',
                                '6' => 'Saturday',
                                '7' => 'Sunday',
                            ])
                            ->required(),
                    ])
                    ->columnSpanFull()
                    ->label('Valid Days')
                    ->addActionLabel('Add Day')
                    ->collapsible(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->toggleable()->sortable()
                    ->badge()
                    ->color(
                        fn(string $state): string => match ($state) {
                            'fixed' => 'primary',
                            'percentage' => 'warning',
                            'buy_x_get_y' => 'danger',
                            'quantity_based' => 'success',
                            'bulk_discount' => 'info',
                        }
                    )
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->numeric()
                    ->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('min_quantity')
                    ->numeric()
                    ->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('max_quantity')
                    ->numeric()
                    ->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('min_amount')
                    ->numeric()
                    ->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('buy_quantity')
                    ->numeric()
                    ->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('get_quantity')
                    ->numeric()
                    ->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('apply_to')
                    ->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_type')
                    ->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('combinable')
                    ->boolean()
                    ->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('usage_limit')
                    ->numeric()->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('usage_count')
                    ->numeric()->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->toggleable()->sortable()->badge()
                    //color for active or not
                    ->color(
                        fn(string $state): string => match ($state) {
                            'active' => 'success',
                            'inactive' => 'danger',
                        }
                    )
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('expired_date')
                    ->date()
                    ->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->toggleable()->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->toggleable()->sortable()
                    ->searchable(),
                //valid days from another table
                Tables\Columns\TextColumn::make('validDays.day_of_week')
                    ->label('Valid Days')
                    ->badge()
                    ->separator(',')
                    ->toggleable()
                    ->sortable()
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
