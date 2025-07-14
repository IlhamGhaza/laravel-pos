<?php

namespace App\Filament\Resources\ValidDayResource\Pages;

use App\Filament\Resources\ValidDayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListValidDays extends ListRecords
{
    protected static string $resource = ValidDayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
