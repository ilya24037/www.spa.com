<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\DTOs\PaymentResultDTO;

/**
 * Сервис обработки ответов от API YooKassa
 */
class YooKassaResponseProcessor
{
    /**
     * Обработать ответ создания платежа
     */
    public function processPaymentCreationResponse(array $apiResponse, Payment $payment): array
    {
        if (!$apiResponse['success']) {
            return $apiResponse;
        }

        $responseData = $apiResponse['data'];
        
        // Сохраняем внешний ID платежа
        $payment->update([
            'external_payment_id' => $responseData['id'],
            'metadata' => array_merge($payment->metadata ?? [], [
                'yookassa_data' => $responseData
            ])
        ]);

        return [
            'success' => true,
            'redirect_url' => $responseData['confirmation']['confirmation_url'],
            'payment_id' => $responseData['id'],
            'status' => $responseData['status']
        ];
    }

    /**
     * Обработать ответ проверки статуса платежа
     */
    public function processPaymentStatusResponse(array $apiResponse): array
    {
        if (!$apiResponse['success']) {
            return [
                'status' => 'error',
                'paid' => false,
                'error' => $apiResponse['error'] ?? 'Failed to check payment status'
            ];
        }

        $data = $apiResponse['data'];
        
        return [
            'status' => $data['status'],
            'paid' => $data['status'] === 'succeeded',
            'amount' => $data['amount']['value'] ?? 0,
            'refundable' => $data['refundable'] ?? false,
            'transaction_id' => $data['id'],
            'raw_data' => $data
        ];
    }

    /**
     * Обработать ответ возврата средств
     */
    public function processRefundResponse(array $apiResponse, Payment $payment, float $refundAmount): PaymentResultDTO
    {
        if (!$apiResponse['success']) {
            return new PaymentResultDTO(
                success: false,
                message: $apiResponse['error'] ?? 'Refund failed'
            );
        }

        $refundData = $apiResponse['data'];
        
        // Сохраняем информацию о возврате
        $payment->update([
            'metadata' => array_merge($payment->metadata ?? [], [
                'refund_data' => $refundData,
                'refunded_at' => now()->toIso8601String(),
                'refunded_amount' => $refundAmount
            ])
        ]);
        
        return new PaymentResultDTO(
            success: true,
            transactionId: $refundData['id'],
            message: 'Refund initiated successfully'
        );
    }

    /**
     * Получить статус платежа для несозданного платежа
     */
    public function getNotCreatedStatus(): array
    {
        return [
            'status' => 'not_created',
            'paid' => false
        ];
    }
}