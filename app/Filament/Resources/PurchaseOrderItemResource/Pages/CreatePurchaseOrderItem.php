<?php

namespace App\Filament\Resources\PurchaseOrderItemResource\Pages;

use App\Filament\Resources\PurchaseOrderItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrderItem extends CreateRecord
{
    protected static string $resource = PurchaseOrderItemResource::class;
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
