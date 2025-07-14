<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(100),
                // Forms\Components\TextInput::make('contact_person')
                //     ->maxLength(255),
                Forms\Components\TextInput::make('phone_number')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('company_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('tax_number')
                    ->maxLength(255),
                Forms\Components\Select::make('payment_terms')
                    ->options([
                        'cash' => 'Cash',
                        'credit' => 'Credit',
                    ])
                    ->required()
                    ->default('cash'),
                Forms\Components\TextInput::make('credit_days')
                    ->numeric(),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ])
                    ->required()
                    ->default('active'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('contact_person')
                //     ->searchable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_terms')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'cash' => 'success',
                        'credit' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('credit_days')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'suspended' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view' => Pages\ViewSupplier::route('/{record}'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
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
