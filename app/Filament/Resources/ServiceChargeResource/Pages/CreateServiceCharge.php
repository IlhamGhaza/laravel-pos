<?php

namespace App\Filament\Resources\ServiceChargeResource\Pages;

use App\Filament\Resources\ServiceChargeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceCharge extends CreateRecord
{
    protected static string $resource = ServiceChargeResource::class;
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
