<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MobileSyncValidationIssueResource\Pages;
use App\Filament\Resources\MobileSyncValidationIssueResource\RelationManagers;
use App\Models\MobileSyncValidationIssue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MobileSyncValidationIssueResource extends Resource
{
    protected static ?string $model = MobileSyncValidationIssue::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationGroup = 'Validate Management';
    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'id'),
                Forms\Components\TextInput::make('field_name')
                    ->maxLength(255),
                Forms\Components\Textarea::make('issue_description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('mobile_value')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('server_calculated_value')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('logged_at')
                    ->required(),
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
                Tables\Columns\TextColumn::make('field_name')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('logged_at')
                    ->dateTime()
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
            'index' => Pages\ListMobileSyncValidationIssues::route('/'),
            'create' => Pages\CreateMobileSyncValidationIssue::route('/create'),
            'view' => Pages\ViewMobileSyncValidationIssue::route('/{record}'),
            'edit' => Pages\EditMobileSyncValidationIssue::route('/{record}/edit'),
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
