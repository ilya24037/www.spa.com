<?php

namespace App\Domain\Payment\Services\Gateways;

use App\Domain\Payment\Models\Payment;
use Illuminate\Support\Facades\Log;

/**
 * Адаптер для работы с СБП
 */
class SbpGateway implements PaymentGatewayInterface
{
    /**
     * Создать платеж
     */
    public function createPayment(Payment $payment): array
    {
        $qrString = $this->generateQrString($payment);

        return [
            'success' => true,
            'qr_code' => $qrString,
            'qr_image' => $this->generateQrCodeImage($qrString)
        ];
    }

    /**
     * Обработать webhook
     */
    public function handleWebhook(array $data): bool
    {
        $paymentId = $data['payment_id'] ?? null;
        
        if (!$paymentId) {
            return false;
        }

        $payment = Payment::where('payment_id', $paymentId)->first();
        if (!$payment) {
            return false;
        }

        if ($data['status'] === 'success') {
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'external_payment_id' => $data['transaction_id'] ?? null
            ]);

            return true;
        }

        return false;
    }

    /**
     * Проверить статус платежа
     */
    public function checkStatus(Payment $payment): array
    {
        // В реальном проекте здесь запрос к банку
        $isSuccess = rand(1, 10) === 1; // 10% шанс успеха для тестирования

        if ($isSuccess && $payment->status === 'pending') {
            $payment->update([
                'status' => 'completed',
                'paid_at' => now()
            ]);

            return ['status' => 'completed', 'paid' => true];
        }

        return ['status' => 'pending', 'paid' => false];
    }

    /**
     * Генерировать строку для QR-кода
     */
    private function generateQrString(Payment $payment): string
    {
        $finalAmount = $payment->metadata['final_amount'] ?? $payment->amount;
        return "https://qr.nspk.ru/AS10000{$payment->payment_id}?type=02&bank=100000000111&sum={$finalAmount}&cur=RUB&crc=1234";
    }

    /**
     * Генерировать изображение QR-кода
     */
    private function generateQrCodeImage(string $qrString): string
    {
        // В реальном проекте здесь будет генерация QR-кода
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    }
}