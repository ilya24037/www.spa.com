<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;

/**
 * Сервис различных типов возвратов
 */
class RefundTypesService
{
    public function __construct(
        private RefundValidationService $validationService,
        private RefundProcessorService $processorService,
        private RefundEntitiesUpdateService $entitiesUpdateService
    ) {}

    /**
     * Частичный возврат
     */
    public function partialRefund(Payment $payment, float $amount, string $reason): Payment
    {
        if ($amount >= $payment->amount) {
            throw new \InvalidArgumentException('Use full refund for amounts equal or greater than original payment');
        }

        return $this->executeRefund($payment, $amount, $reason);
    }

    /**
     * Полный возврат
     */
    public function fullRefund(Payment $payment, string $reason): Payment
    {
        $remainingAmount = $payment->getRemainingRefundAmount();
        
        if ($remainingAmount <= 0) {
            throw new \InvalidArgumentException('No amount available for refund');
        }

        return $this->executeRefund($payment, $remainingAmount, $reason);
    }

    /**
     * Автоматический возврат
     */
    public function autoRefund(Payment $payment, string $systemReason): Payment
    {
        $remainingAmount = $payment->getRemainingRefundAmount();
        
        return $this->executeRefund($payment, $remainingAmount, "Автоматический возврат: {$systemReason}");
    }

    /**
     * Выполнить возврат (делегируем в главный Action)
     */
    private function executeRefund(Payment $payment, float $amount, string $reason): Payment
    {
        // Этот метод будет вызывать главный RefundPaymentAction::execute
        return app(\App\Domain\Payment\Actions\RefundPaymentAction::class)
            ->execute($payment, $amount, $reason);
    }
}