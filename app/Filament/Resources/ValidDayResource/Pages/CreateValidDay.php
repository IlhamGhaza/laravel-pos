<?php

namespace App\Filament\Resources\ValidDayResource\Pages;

use App\Filament\Resources\ValidDayResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateValidDay extends CreateRecord
{
    protected static string $resource = ValidDayResource::class;
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
