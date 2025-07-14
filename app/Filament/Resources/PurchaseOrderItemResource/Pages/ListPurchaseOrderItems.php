<?php

namespace App\Filament\Resources\PurchaseOrderItemResource\Pages;

use App\Filament\Resources\PurchaseOrderItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseOrderItems extends ListRecords
{
    protected static string $resource = PurchaseOrderItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
