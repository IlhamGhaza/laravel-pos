<?php

namespace App\Filament\Resources\ServiceChargeResource\Pages;

use App\Filament\Resources\ServiceChargeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceCharge extends EditRecord
{
    protected static string $resource = ServiceChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
