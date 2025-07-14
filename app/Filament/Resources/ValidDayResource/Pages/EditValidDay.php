<?php

namespace App\Filament\Resources\ValidDayResource\Pages;

use App\Filament\Resources\ValidDayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditValidDay extends EditRecord
{
    protected static string $resource = ValidDayResource::class;

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
