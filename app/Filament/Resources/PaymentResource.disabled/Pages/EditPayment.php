<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Рассчитываем итоговую сумму если не указана
        if (empty($data['total_amount'])) {
            $data['total_amount'] = ($data['amount'] ?? 0) + ($data['fee'] ?? 0);
        }

        return $data;
    }
}