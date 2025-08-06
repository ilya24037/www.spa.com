<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;

/**
 * Сервис подготовки данных для запросов к YooKassa
 */
class YooKassaRequestBuilder
{
    /**
     * Подготовить данные для создания платежа
     */
    public function buildPaymentData(Payment $payment, float $finalAmount, array $config = []): array
    {
        $data = [
            'amount' => [
                'value' => number_format($finalAmount, 2, '.', ''),
                'currency' => 'RUB'
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => route('payment.success', $payment)
            ],
            'capture' => true,
            'metadata' => [
                'payment_id' => $payment->payment_id,
                'user_id' => $payment->user_id
            ],
            'description' => $this->prepareDescription($payment->description)
        ];

        // Добавляем чек для маркетплейсов
        if (!empty($config['marketplace_mode'])) {
            $data['receipt'] = $this->buildReceiptData($payment, $finalAmount);
        }

        return $data;
    }

    /**
     * Подготовить данные для возврата
     */
    public function buildRefundData(Payment $payment, float $refundAmount): array
    {
        return [
            'amount' => [
                'value' => number_format($refundAmount, 2, '.', ''),
                'currency' => 'RUB'
            ],
            'payment_id' => $payment->external_payment_id
        ];
    }

    /**
     * Подготовить описание платежа
     */
    public function prepareDescription(string $description): string
    {
        // Ограничение YooKassa на длину описания
        if (mb_strlen($description) > 128) {
            return mb_substr($description, 0, 125) . '...';
        }

        return $description;
    }

    /**
     * Построить данные чека
     */
    public function buildReceiptData(Payment $payment, float $amount): array
    {
        return [
            'customer' => [
                'email' => $payment->user->email ?? null,
                'phone' => $payment->user->phone ?? null
            ],
            'items' => [
                [
                    'description' => $this->prepareDescription($payment->description),
                    'amount' => [
                        'value' => number_format($amount, 2, '.', ''),
                        'currency' => 'RUB'
                    ],
                    'vat_code' => 1, // НДС не облагается
                    'quantity' => 1
                ]
            ]
        ];
    }

    /**
     * Генерировать ключ идемпотентности
     */
    public function generateIdempotencyKey(Payment $payment, string $suffix = ''): string
    {
        return md5($payment->payment_id . '_' . $payment->created_at->timestamp . $suffix);
    }
}