<?php

namespace App\Filament\Resources\MobileSyncValidationIssueResource\Pages;

use App\Filament\Resources\MobileSyncValidationIssueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMobileSyncValidationIssue extends EditRecord
{
    protected static string $resource = MobileSyncValidationIssueResource::class;

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
