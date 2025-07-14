<?php

namespace App\Filament\Resources\MobileSyncValidationIssueResource\Pages;

use App\Filament\Resources\MobileSyncValidationIssueResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMobileSyncValidationIssue extends ViewRecord
{
    protected static string $resource = MobileSyncValidationIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
