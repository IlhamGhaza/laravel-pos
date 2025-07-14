<?php

namespace App\Filament\Resources\MobileSyncValidationIssueResource\Pages;

use App\Filament\Resources\MobileSyncValidationIssueResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMobileSyncValidationIssue extends CreateRecord
{
    protected static string $resource = MobileSyncValidationIssueResource::class;
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
