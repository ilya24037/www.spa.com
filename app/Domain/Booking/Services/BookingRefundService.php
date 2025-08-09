<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Services\PaymentService;
use Illuminate\Support\Facades\Log;

/**
 * Сервис обработки возвратов при отмене бронирования
 */
class BookingRefundService
{
    private ?PaymentService $paymentService;

    public function __construct(?PaymentService $paymentService = null)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Обработать возврат средств
     */
    public function processRefund(Booking $booking, float $cancellationFee): array
    {
        $paidAmount = $booking->paid_amount ?? 0;
        
        // Проверяем, была ли оплата
        if ($paidAmount <= 0) {
            return $this->noPaymentResponse();
        }

        // Рассчитываем сумму возврата
        $refundAmount = $this->calculateRefundAmount($paidAmount, $cancellationFee);
        
        if ($refundAmount <= 0) {
            return $this->noRefundResponse($cancellationFee, $paidAmount);
        }

        // Пытаемся выполнить автоматический возврат
        return $this->executeRefund($booking, $refundAmount, $cancellationFee);
    }

    /**
     * Рассчитать сумму возврата
     */
    public function calculateRefundAmount(float $paidAmount, float $cancellationFee): float
    {
        return max(0, $paidAmount - $cancellationFee);
    }

    /**
     * Выполнить возврат
     */
    private function executeRefund(Booking $booking, float $refundAmount, float $cancellationFee): array
    {
        try {
            // Если есть сервис платежей, пытаемся автоматический возврат
            if ($this->paymentService && $booking->payment_id) {
                $result = $this->paymentService->refund(
                    $booking->payment_id,
                    $refundAmount,
                    'Возврат за отмененное бронирование #' . $booking->id
                );
                
                if ($result['success']) {
                    return $this->automaticRefundResponse($refundAmount, $cancellationFee, $result['transaction_id']);
                }
            }
            
            // Если автоматический возврат не удался, требуется ручной возврат
            return $this->manualRefundResponse($refundAmount, $cancellationFee);
            
        } catch (\Exception $e) {
            Log::error('Refund processing failed', [
                'booking_id' => $booking->id,
                'refund_amount' => $refundAmount,
                'error' => $e->getMessage(),
            ]);
            
            return $this->manualRefundResponse($refundAmount, $cancellationFee, $e->getMessage());
        }
    }

    /**
     * Ответ при отсутствии оплаты
     */
    private function noPaymentResponse(): array
    {
        return [
            'type' => 'no_payment',
            'status' => 'completed',
            'refund_amount' => 0,
            'fee_amount' => 0,
            'message' => 'Возврат не требуется - оплата не производилась',
        ];
    }

    /**
     * Ответ при полном удержании в качестве штрафа
     */
    private function noRefundResponse(float $cancellationFee, float $paidAmount): array
    {
        return [
            'type' => 'no_refund',
            'status' => 'completed',
            'refund_amount' => 0,
            'fee_amount' => $cancellationFee,
            'paid_amount' => $paidAmount,
            'message' => 'Возврат не производится - весь платеж удержан в качестве штрафа',
        ];
    }

    /**
     * Ответ при успешном автоматическом возврате
     */
    private function automaticRefundResponse(float $refundAmount, float $cancellationFee, string $transactionId): array
    {
        return [
            'type' => 'automatic',
            'status' => 'processed',
            'refund_amount' => $refundAmount,
            'fee_amount' => $cancellationFee,
            'transaction_id' => $transactionId,
            'message' => "Возврат {$refundAmount} руб. будет зачислен в течение 3-5 рабочих дней",
            'processed_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Ответ при необходимости ручного возврата
     */
    private function manualRefundResponse(float $refundAmount, float $cancellationFee, ?string $error = null): array
    {
        $response = [
            'type' => 'manual',
            'status' => 'pending',
            'refund_amount' => $refundAmount,
            'fee_amount' => $cancellationFee,
            'message' => 'Возврат будет обработан вручную в течение 24 часов',
            'requires_action' => true,
        ];
        
        if ($error) {
            $response['error'] = $error;
        }
        
        return $response;
    }

    /**
     * Создать заявку на ручной возврат
     */
    public function createManualRefundRequest(Booking $booking, float $amount, string $reason): array
    {
        // Здесь можно создать запись в таблице refund_requests
        // или отправить уведомление в финансовый отдел
        
        Log::info('Manual refund request created', [
            'booking_id' => $booking->id,
            'amount' => $amount,
            'reason' => $reason,
        ]);
        
        return [
            'request_id' => uniqid('refund_'),
            'status' => 'created',
            'amount' => $amount,
            'booking_id' => $booking->id,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}