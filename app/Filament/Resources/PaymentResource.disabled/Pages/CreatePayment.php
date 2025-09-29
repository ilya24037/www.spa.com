<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Автоматически генерируем номер платежа если не указан
        if (empty($data['payment_number'])) {
            $data['payment_number'] = \App\Domain\Payment\Models\Payment::generatePaymentNumber();
        }

        // Рассчитываем итоговую сумму если не указана
        if (empty($data['total_amount'])) {
            $data['total_amount'] = ($data['amount'] ?? 0) + ($data['fee'] ?? 0);
        }

        return $data;
    }
}