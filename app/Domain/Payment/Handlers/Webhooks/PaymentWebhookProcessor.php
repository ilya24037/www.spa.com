<?php

namespace App\Domain\Payment\Handlers\Webhooks;

use App\Domain\Payment\Contracts\WebhookHandlerInterface;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Models\Transaction;
use App\Domain\Payment\Services\TransactionService;
use App\Enums\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик вебхуков платежей
 */
class PaymentWebhookProcessor
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * Обработать успешный платеж
     */
    public function handleSuccess(WebhookHandlerInterface $handler, Request $request): array
    {
        $externalId = $handler->getPaymentId($request);
        
        if (!$externalId) {
            throw new \Exception('Payment ID not found in webhook');
        }

        // Найти платеж
        $payment = Payment::where('external_id', $externalId)
            ->where('gateway', $handler->getGatewayName())
            ->first();

        if (!$payment) {
            Log::warning('Payment not found for webhook', [
                'external_id' => $externalId,
                'gateway' => $handler->getGatewayName(),
            ]);

            return [
                'success' => true,
                'message' => 'Payment not found',
            ];
        }

        // Обновить статус платежа
        if ($payment->status !== PaymentStatus::COMPLETED) {
            $payment->update([
                'status' => PaymentStatus::COMPLETED,
                'confirmed_at' => now(),
                'gateway_response' => $request->all(),
            ]);

            // Создать/обновить транзакцию
            $transaction = Transaction::where('payment_id', $payment->id)->first();
            
            if ($transaction) {
                $this->transactionService->processSuccessfulTransaction($transaction);
            } else {
                $this->transactionService->createPaymentTransaction($payment);
            }
        }

        return [
            'success' => true,
            'payment_id' => $payment->id,
            'status' => 'completed',
        ];
    }

    /**
     * Обработать неудачный платеж
     */
    public function handleFailed(WebhookHandlerInterface $handler, Request $request): array
    {
        $externalId = $handler->getPaymentId($request);
        
        if (!$externalId) {
            throw new \Exception('Payment ID not found in webhook');
        }

        // Найти платеж
        $payment = Payment::where('external_id', $externalId)
            ->where('gateway', $handler->getGatewayName())
            ->first();

        if (!$payment) {
            return [
                'success' => true,
                'message' => 'Payment not found',
            ];
        }

        // Обновить статус платежа
        $payment->update([
            'status' => PaymentStatus::FAILED,
            'failed_at' => now(),
            'gateway_response' => $request->all(),
        ]);

        // Обновить транзакцию
        $transaction = Transaction::where('payment_id', $payment->id)->first();
        
        if ($transaction) {
            $this->transactionService->processFailedTransaction(
                $transaction, 
                'Payment failed: ' . ($request->input('error.message') ?? 'Unknown error')
            );
        }

        return [
            'success' => true,
            'payment_id' => $payment->id,
            'status' => 'failed',
        ];
    }

    /**
     * Обработать возврат
     */
    public function handleRefund(WebhookHandlerInterface $handler, Request $request): array
    {
        $externalId = $handler->getPaymentId($request);
        $refundAmount = $request->input('amount', 0) / 100; // Обычно в копейках

        // Найти оригинальный платеж
        $payment = Payment::where('external_id', $externalId)
            ->where('gateway', $handler->getGatewayName())
            ->first();

        if (!$payment) {
            return [
                'success' => true,
                'message' => 'Payment not found',
            ];
        }

        // Создать транзакцию возврата
        $this->transactionService->createRefundTransaction(
            $payment,
            $refundAmount,
            $request->input('reason', 'Refund processed via webhook')
        );

        // Обновить статус платежа
        if ($refundAmount >= $payment->amount) {
            $payment->update([
                'status' => PaymentStatus::REFUNDED,
                'refunded_at' => now(),
            ]);
        }

        return [
            'success' => true,
            'payment_id' => $payment->id,
            'refund_amount' => $refundAmount,
        ];
    }
}