<?php

namespace App\Filament\Resources\PurchaseOrderItemResource\Pages;

use App\Filament\Resources\PurchaseOrderItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrderItem extends EditRecord
{
    protected static string $resource = PurchaseOrderItemResource::class;

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
