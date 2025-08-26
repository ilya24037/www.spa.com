<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Payment\Enums\PaymentType;
use App\Domain\Payment\DTOs\RefundPaymentDTO;
use App\Domain\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Консолидированный сервис возвратов платежей
 * Объединяет: RefundValidationService, RefundProcessorService, 
 * RefundEntitiesUpdateService, RefundTypesService
 */
class RefundService
{
    // ========== ОСНОВНЫЕ МЕТОДЫ ВОЗВРАТОВ ==========

    /**
     * Частичный возврат
     */
    public function partialRefund(Payment $payment, float $amount, string $reason): Payment
    {
        if ($amount >= $payment->amount) {
            throw new \InvalidArgumentException('Для полного возврата используйте метод fullRefund');
        }

        $this->validateRefund($payment, $amount, $reason);

        return $this->executeRefund($payment, $amount, $reason, 'partial');
    }

    /**
     * Полный возврат
     */
    public function fullRefund(Payment $payment, string $reason): Payment
    {
        $remainingAmount = $this->getRemainingRefundAmount($payment);
        
        if ($remainingAmount <= 0) {
            throw new \InvalidArgumentException('Нет доступной суммы для возврата');
        }

        $this->validateRefund($payment, $remainingAmount, $reason);

        return $this->executeRefund($payment, $remainingAmount, $reason, 'full');
    }

    /**
     * Автоматический возврат
     */
    public function autoRefund(Payment $payment, string $systemReason): Payment
    {
        $remainingAmount = $this->getRemainingRefundAmount($payment);
        $reason = "Автоматический возврат: {$systemReason}";
        
        $this->validateRefund($payment, $remainingAmount, $reason);

        return $this->executeRefund($payment, $remainingAmount, $reason, 'auto');
    }

    /**
     * Принудительный возврат (только для админов)
     */
    public function forceRefund(Payment $payment, float $amount, string $reason, int $adminUserId): Payment
    {
        // Минимальная валидация только параметров
        $this->validateParameters($amount, $reason);

        return $this->executeRefund($payment, $amount, $reason, 'force', [
            'admin_user_id' => $adminUserId,
            'forced' => true
        ]);
    }

    /**
     * Выполнить возврат
     */
    private function executeRefund(Payment $payment, float $amount, string $reason, string $type, array $metadata = []): Payment
    {
        return DB::transaction(function () use ($payment, $amount, $reason, $type, $metadata) {
            // Создаём запись о возврате
            $refund = $this->createRefundRecord($payment, $amount, $reason, $type, $metadata);

            // Обновляем статус оригинального платежа
            $this->updateOriginalPaymentStatus($payment, $amount);

            // Добавляем метаданные
            $this->addRefundMetadata($refund, $payment);

            // Обновляем связанные сущности
            $this->updateRelatedEntities($refund, $payment);

            // Логируем операцию
            $this->logRefund($refund, $payment, $type);

            return $refund;
        });
    }

    /**
     * Создать запись о возврате
     */
    private function createRefundRecord(Payment $payment, float $amount, string $reason, string $type, array $metadata): Payment
    {
        return Payment::create([
            'user_id' => $payment->user_id,
            'parent_payment_id' => $payment->id,
            'amount' => -$amount, // Отрицательная сумма для возврата
            'currency' => $payment->currency,
            'payment_method' => $payment->payment_method,
            'status' => PaymentStatus::COMPLETED,
            'type' => PaymentType::REFUND,
            'description' => $reason,
            'gateway_payment_id' => null,
            'processed_at' => now(),
            'metadata' => array_merge([
                'refund_type' => $type,
                'original_payment_id' => $payment->id
            ], $metadata)
        ]);
    }

    /**
     * Обновить статус оригинального платежа
     */
    private function updateOriginalPaymentStatus(Payment $payment, float $refundAmount): void
    {
        $totalRefunded = ($payment->refunded_amount ?? 0) + $refundAmount;
        
        if ($totalRefunded >= $payment->amount) {
            $status = PaymentStatus::REFUNDED;
        } else {
            $status = PaymentStatus::PARTIALLY_REFUNDED;
        }

        $payment->update([
            'status' => $status,
            'refunded_amount' => $totalRefunded,
            'refunded_at' => now()
        ]);
    }

    // ========== ВАЛИДАЦИЯ (из RefundValidationService) ==========

    /**
     * Полная валидация возврата
     */
    public function validateRefund(Payment $payment, float $amount, ?string $reason): void
    {
        $this->validateParameters($amount, $reason);
        $this->validatePaymentRefundability($payment);
        $this->validateRefundAmount($payment, $amount);
        $this->validateTimeConstraints($payment);
        $this->validateBusinessRules($payment, $amount);
    }

    /**
     * Базовая валидация параметров
     */
    private function validateParameters(float $amount, ?string $reason): void
    {
        $validator = Validator::make([
            'amount' => $amount,
            'reason' => $reason,
        ], [
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Проверка возможности возврата платежа
     */
    private function validatePaymentRefundability(Payment $payment): void
    {
        if (!$this->isRefundable($payment)) {
            throw new \InvalidArgumentException('Платёж не подлежит возврату');
        }
    }

    /**
     * Проверка суммы возврата
     */
    private function validateRefundAmount(Payment $payment, float $amount): void
    {
        $remainingAmount = $this->getRemainingRefundAmount($payment);
        
        if ($amount > $remainingAmount) {
            throw new \InvalidArgumentException(
                "Сумма возврата ({$amount}) превышает доступную для возврата сумму ({$remainingAmount})"
            );
        }
    }

    /**
     * Проверка временных ограничений
     */
    private function validateTimeConstraints(Payment $payment): void
    {
        $refundDeadline = $this->getRefundDeadline($payment);
        
        if ($refundDeadline && now()->isAfter($refundDeadline)) {
            throw new \InvalidArgumentException(
                "Срок возврата истёк. Крайний срок: {$refundDeadline->format('d.m.Y H:i')}"
            );
        }
    }

    /**
     * Валидация бизнес-правил
     */
    private function validateBusinessRules(Payment $payment, float $amount): void
    {
        // Валидация по типу платежа
        $paymentType = $payment->metadata['type'] ?? 'service_payment';
        
        switch ($paymentType) {
            case 'service_payment':
                $this->validateServicePaymentRefund($payment, $amount);
                break;
            case 'booking_deposit':
                $this->validateDepositRefund($payment, $amount);
                break;
            case 'subscription':
                $this->validateSubscriptionRefund($payment, $amount);
                break;
        }

        // Проверка лимитов
        $this->validateRefundLimits($payment);
    }

    /**
     * Валидация возврата за услугу
     */
    private function validateServicePaymentRefund(Payment $payment, float $amount): void
    {
        if ($payment->booking_id) {
            // Предполагается что у Payment есть связь с Booking
            $booking = $payment->booking;
            
            if ($booking && $booking->status === 'completed') {
                throw new \InvalidArgumentException('Нельзя вернуть деньги за завершённую услугу');
            }

            if ($booking && $booking->status === 'in_progress') {
                throw new \InvalidArgumentException('Нельзя вернуть деньги за услугу в процессе выполнения');
            }
        }
    }

    /**
     * Валидация возврата депозита
     */
    private function validateDepositRefund(Payment $payment, float $amount): void
    {
        if ($payment->booking_id) {
            $booking = $payment->booking;
            
            if ($booking && $booking->booking_date && now()->isAfter($booking->booking_date)) {
                throw new \InvalidArgumentException('Нельзя вернуть депозит после начала услуги');
            }
        }
    }

    /**
     * Валидация возврата подписки
     */
    private function validateSubscriptionRefund(Payment $payment, float $amount): void
    {
        // Пропорциональный возврат в зависимости от использованного времени
        // Базовая проверка - подписка не должна быть полностью использована
    }

    /**
     * Проверка лимитов на возвраты
     */
    private function validateRefundLimits(Payment $payment): void
    {
        $user = $payment->user;
        
        // Дневной лимит возвратов
        $dailyRefunds = Payment::where('user_id', $user->id)
            ->where('type', PaymentType::REFUND)
            ->whereDate('created_at', today())
            ->count();

        if ($dailyRefunds >= 5) {
            throw new \InvalidArgumentException('Превышен дневной лимит возвратов');
        }

        // Месячный лимит суммы возвратов
        $monthlyRefundAmount = Payment::where('user_id', $user->id)
            ->where('type', PaymentType::REFUND)
            ->where('status', PaymentStatus::COMPLETED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        if (abs($monthlyRefundAmount) > 500000) {
            throw new \InvalidArgumentException('Превышен месячный лимит суммы возвратов');
        }
    }

    // ========== ОБРАБОТКА МЕТАДАННЫХ (из RefundProcessorService) ==========

    /**
     * Добавить метаданные к возврату
     */
    private function addRefundMetadata(Payment $refund, Payment $originalPayment): void
    {
        $metadata = array_merge($refund->metadata ?? [], [
            'refund_reason_category' => $this->categorizeRefundReason($refund->description),
            'processing_priority' => $this->getRefundPriority($refund),
            'estimated_processing_time' => $this->getEstimatedProcessingTime($refund),
            'gateway_refund_method' => $this->determineGatewayRefundMethod($originalPayment)
        ]);

        $refund->update(['metadata' => $metadata]);
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
        $amount = abs($refund->amount);

        // Высокий приоритет для крупных сумм
        if ($amount > 50000) {
            return 'high';
        }

        // Высокий приоритет для VIP пользователей
        if ($refund->user->is_vip ?? false) {
            return 'high';
        }

        // Средний приоритет для средних сумм
        if ($amount > 10000) {
            return 'medium';
        }

        return 'normal';
    }

    /**
     * Получить ожидаемое время обработки
     */
    private function getEstimatedProcessingTime(Payment $refund): string
    {
        $priority = $this->getRefundPriority($refund);
        
        return match($priority) {
            'high' => '1-2 hours',
            'medium' => '2-6 hours', 
            'normal' => '6-24 hours',
            default => '24-48 hours'
        };
    }

    /**
     * Определить метод возврата через шлюз
     */
    private function determineGatewayRefundMethod(Payment $originalPayment): string
    {
        return match($originalPayment->payment_method) {
            PaymentMethod::CARD => 'card_refund',
            PaymentMethod::YOOKASSA => 'yookassa_refund',
            PaymentMethod::SBP => 'sbp_refund',
            PaymentMethod::BALANCE => 'balance_refund',
            default => 'manual_refund'
        };
    }

    // ========== ОБНОВЛЕНИЕ СВЯЗАННЫХ СУЩНОСТЕЙ (из RefundEntitiesUpdateService) ==========

    /**
     * Обновить связанные сущности
     */
    private function updateRelatedEntities(Payment $refund, Payment $originalPayment): void
    {
        // Обновляем бронирование
        if ($originalPayment->booking_id) {
            $this->updateBookingAfterRefund($originalPayment->booking, $refund);
        }

        // Обновляем объявление
        if ($originalPayment->ad_id) {
            $this->updateAdAfterRefund($originalPayment->ad, $refund);
        }

        // Обновляем статистику пользователя
        $this->updateUserStatistics($refund->user, $refund);

        // Обновляем баланс если необходимо
        if ($originalPayment->payment_method === PaymentMethod::BALANCE) {
            $this->updateUserBalance($refund->user, abs($refund->amount));
        }
    }

    /**
     * Обновить бронирование после возврата
     */
    private function updateBookingAfterRefund($booking, Payment $refund): void
    {
        if (!$booking) return;

        $originalPayment = $refund->parentPayment ?? Payment::find($refund->metadata['original_payment_id']);

        if ($originalPayment->isFullyRefunded()) {
            $booking->update([
                'payment_status' => 'refunded',
                'status' => 'cancelled_by_client',
                'refund_amount' => abs($refund->amount)
            ]);
        } else {
            $booking->update([
                'payment_status' => 'partially_refunded',
                'refund_amount' => abs($refund->amount)
            ]);
        }
    }

    /**
     * Обновить объявление после возврата
     */
    private function updateAdAfterRefund($ad, Payment $refund): void
    {
        if (!$ad) return;

        // Если это был платёж за продвижение, отменяем продвижение
        if ($refund->metadata['refund_reason_category'] === 'promotion_refund') {
            $ad->update([
                'is_promoted' => false,
                'promoted_until' => null
            ]);
        }
    }

    /**
     * Обновить статистику пользователя
     */
    private function updateUserStatistics(User $user, Payment $refund): void
    {
        $user->increment('refunds_count');
        $user->increment('refunds_amount', abs($refund->amount));
        
        // Обновляем последнюю активность
        $user->update(['last_refund_at' => now()]);
    }

    /**
     * Обновить баланс пользователя
     */
    private function updateUserBalance(User $user, float $amount): void
    {
        $balance = $user->balance;
        if ($balance) {
            $balance->increment('amount', $amount);
        }
    }

    // ========== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ==========

    /**
     * Проверить возможность возврата
     */
    public function isRefundable(Payment $payment): bool
    {
        return in_array($payment->status, [
            PaymentStatus::COMPLETED,
            PaymentStatus::PARTIALLY_REFUNDED
        ]) && $this->getRemainingRefundAmount($payment) > 0;
    }

    /**
     * Получить оставшуюся сумму для возврата
     */
    public function getRemainingRefundAmount(Payment $payment): float
    {
        return $payment->amount - ($payment->refunded_amount ?? 0);
    }

    /**
     * Получить крайний срок для возврата
     */
    private function getRefundDeadline(Payment $payment): ?Carbon
    {
        $paymentType = $payment->metadata['type'] ?? 'service_payment';
        
        return match($paymentType) {
            'service_payment' => $payment->processed_at?->addDays(14),
            'booking_deposit' => $payment->processed_at?->addDays(7),
            'subscription' => $payment->processed_at?->addDays(30),
            default => $payment->processed_at?->addDays(30),
        };
    }

    /**
     * Логировать возврат
     */
    private function logRefund(Payment $refund, Payment $originalPayment, string $type): void
    {
        Log::info('Refund processed', [
            'refund_id' => $refund->id,
            'original_payment_id' => $originalPayment->id,
            'type' => $type,
            'amount' => abs($refund->amount),
            'user_id' => $refund->user_id,
            'reason_category' => $refund->metadata['refund_reason_category'] ?? 'unknown'
        ]);
    }

    /**
     * Получить статистику возвратов пользователя
     */
    public function getUserRefundStats(User $user): array
    {
        $refunds = Payment::where('user_id', $user->id)
            ->where('type', PaymentType::REFUND)
            ->where('status', PaymentStatus::COMPLETED)
            ->get();

        return [
            'total_refunds' => $refunds->count(),
            'total_amount' => abs($refunds->sum('amount')),
            'average_amount' => $refunds->count() > 0 ? abs($refunds->avg('amount')) : 0,
            'last_refund_date' => $refunds->max('created_at'),
            'refunds_this_month' => $refunds->where('created_at', '>=', now()->startOfMonth())->count(),
            'refunds_this_year' => $refunds->where('created_at', '>=', now()->startOfYear())->count()
        ];
    }
}