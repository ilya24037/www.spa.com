<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use App\Enums\PaymentType;
use Illuminate\Support\Facades\Log;

/**
 * Сервис обработки возвратов платежей
 */
class RefundProcessorService
{
    /**
     * Обработать созданный возврат
     */
    public function handleRefundCreated(Payment $refund, Payment $originalPayment): void
    {
        Log::info('Refund processing started', [
            'refund_id' => $refund->id,
            'original_payment_id' => $originalPayment->id,
        ]);

        $this->addRefundMetadata($refund, $originalPayment);
    }

    /**
     * Добавить метаданные к возврату
     */
    private function addRefundMetadata(Payment $refund, Payment $originalPayment): void
    {
        $refund->update([
            'metadata' => array_merge($refund->metadata ?? [], [
                'refund_type' => $this->determineRefundType($originalPayment),
                'refund_reason_category' => $this->categorizeRefundReason($refund->notes),
                'processing_priority' => $this->getRefundPriority($refund),
            ])
        ]);
    }

    /**
     * Определить тип возврата
     */
    private function determineRefundType(Payment $originalPayment): string
    {
        if ($originalPayment->isFullyRefunded()) {
            return 'full_refund';
        }
        
        return 'partial_refund';
    }

    /**
     * Категоризировать причину возврата
     */
    private function categorizeRefundReason(?string $reason): string
    {
        if (!$reason) {
            return 'no_reason';
        }

        $reason = mb_strtolower($reason);

        if (str_contains($reason, 'отмен') || str_contains($reason, 'cancel')) {
            return 'cancellation';
        }

        if (str_contains($reason, 'качество') || str_contains($reason, 'quality')) {
            return 'quality_issue';
        }

        if (str_contains($reason, 'время') || str_contains($reason, 'time')) {
            return 'timing_issue';
        }

        if (str_contains($reason, 'техн') || str_contains($reason, 'tech')) {
            return 'technical_issue';
        }

        return 'other';
    }

    /**
     * Получить приоритет обработки возврата
     */
    private function getRefundPriority(Payment $refund): string
    {
        // Высокий приоритет для крупных сумм
        if ($refund->amount > 50000) {
            return 'high';
        }

        // Высокий приоритет для VIP пользователей
        if ($refund->user->is_vip ?? false) {
            return 'high';
        }

        // Средний приоритет для депозитов
        if ($refund->parentPayment->type === PaymentType::BOOKING_DEPOSIT) {
            return 'medium';
        }

        return 'normal';
    }
}