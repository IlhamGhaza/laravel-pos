<?php

namespace App\Filament\Resources\ValidDayResource\Pages;

use App\Filament\Resources\ValidDayResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewValidDay extends ViewRecord
{
    protected static string $resource = ValidDayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
