<?php

namespace App\Domain\Payment\Actions;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Payment\Services\RefundValidationService;
use App\Domain\Payment\Services\RefundProcessorService;
use App\Domain\Payment\Services\RefundEntitiesUpdateService;
use App\Domain\Payment\Services\RefundTypesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для возврата платежа - координатор
 */
class RefundPaymentAction
{
    public function __construct(
        private PaymentService $paymentService,
        private RefundValidationService $validationService,
        private RefundProcessorService $processorService,
        private RefundEntitiesUpdateService $entitiesUpdateService,
        private RefundTypesService $typesService
    ) {}

    /**
     * Выполнить возврат платежа
     */
    public function execute(Payment $payment, float $amount, ?string $reason = null): Payment
    {
        $this->validationService->validateRefund($payment, $amount, $reason);

        return DB::transaction(function () use ($payment, $amount, $reason) {
            // Создаем возврат через сервис
            $refund = $this->paymentService->createRefund($payment, $amount, $reason);

            if (!$refund) {
                throw new \Exception('Failed to create refund');
            }

            // Выполняем дополнительную обработку
            $this->processorService->handleRefundCreated($refund, $payment);

            // Обновляем связанные сущности
            $this->entitiesUpdateService->updateRelatedEntities($refund, $payment);

            Log::info('Refund created successfully', [
                'refund_id' => $refund->id,
                'original_payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
            ]);

            return $refund;
        });
    }

    // === ТИПЫ ВОЗВРАТОВ (делегируем в сервис) ===

    /**
     * Частичный возврат
     */
    public function partialRefund(Payment $payment, float $amount, string $reason): Payment
    {
        return $this->typesService->partialRefund($payment, $amount, $reason);
    }

    /**
     * Полный возврат
     */
    public function fullRefund(Payment $payment, string $reason): Payment
    {
        return $this->typesService->fullRefund($payment, $reason);
    }

    /**
     * Автоматический возврат
     */
    public function autoRefund(Payment $payment, string $systemReason): Payment
    {
        return $this->typesService->autoRefund($payment, $systemReason);
    }
}