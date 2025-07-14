<?php

namespace App\Filament\Resources\PurchaseOrderItemResource\Pages;

use App\Filament\Resources\PurchaseOrderItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrderItem extends ViewRecord
{
    protected static string $resource = PurchaseOrderItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
