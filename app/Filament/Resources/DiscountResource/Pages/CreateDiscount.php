<?php

namespace App\Filament\Resources\DiscountResource\Pages;

use App\Filament\Resources\DiscountResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateDiscount extends CreateRecord
{
    protected static string $resource = DiscountResource::class;

    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validate time fields
        if (isset($data['start_time']) && isset($data['end_time'])) {
            $startTime = \Carbon\Carbon::parse($data['start_time']);
            $endTime = \Carbon\Carbon::parse($data['end_time']);

            if ($startTime->greaterThan($endTime)) {
                throw ValidationException::withMessages([
                    'start_time' => 'Start time cannot be greater than end time.',
                ]);
            }
        }

        return $data;
    }
}
