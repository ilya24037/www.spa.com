<?php

namespace App\Domain\Payment\Actions;

use App\Domain\Payment\Models\Payment;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Domain\Payment\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Action для возврата платежа
 */
class RefundPaymentAction
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Выполнить возврат платежа
     */
    public function execute(Payment $payment, float $amount, ?string $reason = null): Payment
    {
        $this->validateRefund($payment, $amount, $reason);

        DB::beginTransaction();

        try {
            // Создаем возврат через сервис
            $refund = $this->paymentService->createRefund($payment, $amount, $reason);

            if (!$refund) {
                throw new \Exception('Failed to create refund');
            }

            // Выполняем дополнительную обработку
            $this->handleRefundCreated($refund, $payment);

            // Обновляем связанные сущности
            $this->updateRelatedEntities($refund, $payment);

            DB::commit();

            Log::info('Refund created successfully', [
                'refund_id' => $refund->id,
                'original_payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
            ]);

            return $refund;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Refund creation failed', [
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Валидация возврата
     */
    protected function validateRefund(Payment $payment, float $amount, ?string $reason): void
    {
        // Базовая валидация параметров
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

        // Проверяем, можно ли вернуть платеж
        if (!$payment->isRefundable()) {
            throw new \InvalidArgumentException('Payment is not refundable');
        }

        // Проверяем сумму возврата
        $remainingAmount = $payment->getRemainingRefundAmount();
        if ($amount > $remainingAmount) {
            throw new \InvalidArgumentException(
                "Refund amount ({$amount}) exceeds remaining refundable amount ({$remainingAmount})"
            );
        }

        // Проверяем временные ограничения
        $this->validateRefundTimeConstraints($payment);

        // Проверяем бизнес-правила
        $this->validateBusinessRules($payment, $amount);
    }

    /**
     * Проверить временные ограничения на возврат
     */
    protected function validateRefundTimeConstraints(Payment $payment): void
    {
        $refundDeadline = $this->getRefundDeadline($payment);
        
        if ($refundDeadline && now()->isAfter($refundDeadline)) {
            throw new \InvalidArgumentException(
                "Refund deadline has passed. Deadline was: {$refundDeadline->format('d.m.Y H:i')}"
            );
        }
    }

    /**
     * Получить крайний срок для возврата
     */
    protected function getRefundDeadline(Payment $payment): ?\Carbon\Carbon
    {
        // Различные сроки в зависимости от типа платежа
        return match($payment->type) {
            PaymentType::SERVICE_PAYMENT => $payment->confirmed_at?->addDays(14), // 14 дней на услуги
            PaymentType::BOOKING_DEPOSIT => $payment->confirmed_at?->addDays(7),  // 7 дней на депозиты
            PaymentType::SUBSCRIPTION => $payment->confirmed_at?->addDays(30),    // 30 дней на подписки
            default => $payment->confirmed_at?->addDays(30), // 30 дней по умолчанию
        };
    }

    /**
     * Валидация бизнес-правил
     */
    protected function validateBusinessRules(Payment $payment, float $amount): void
    {
        // Проверяем специфичные правила для разных типов платежей
        switch ($payment->type) {
            case PaymentType::SERVICE_PAYMENT:
                $this->validateServicePaymentRefund($payment, $amount);
                break;

            case PaymentType::BOOKING_DEPOSIT:
                $this->validateDepositRefund($payment, $amount);
                break;

            case PaymentType::SUBSCRIPTION:
                $this->validateSubscriptionRefund($payment, $amount);
                break;
        }

        // Проверяем лимиты на возвраты
        $this->validateRefundLimits($payment);
    }

    /**
     * Валидация возврата за услугу
     */
    protected function validateServicePaymentRefund(Payment $payment, float $amount): void
    {
        if ($payment->payable_type === 'App\Models\Booking') {
            $booking = $payment->payable;
            
            // Нельзя вернуть после завершения услуги
            if ($booking && $booking->status === 'completed') {
                throw new \InvalidArgumentException('Cannot refund completed service');
            }

            // Нельзя вернуть если услуга началась
            if ($booking && $booking->status === 'in_progress') {
                throw new \InvalidArgumentException('Cannot refund service in progress');
            }
        }
    }

    /**
     * Валидация возврата депозита
     */
    protected function validateDepositRefund(Payment $payment, float $amount): void
    {
        if ($payment->payable_type === 'App\Models\Booking') {
            $booking = $payment->payable;
            
            // Депозит можно вернуть только до начала услуги
            if ($booking && $booking->start_time && now()->isAfter($booking->start_time)) {
                throw new \InvalidArgumentException('Cannot refund deposit after service start time');
            }
        }
    }

    /**
     * Валидация возврата подписки
     */
    protected function validateSubscriptionRefund(Payment $payment, float $amount): void
    {
        // Пропорциональный возврат в зависимости от использованного времени
        // Реализация зависит от бизнес-логики подписок
    }

    /**
     * Проверить лимиты на возвраты
     */
    protected function validateRefundLimits(Payment $payment): void
    {
        $user = $payment->user;
        
        // Дневной лимит возвратов
        $dailyRefunds = Payment::where('user_id', $user->id)
            ->where('type', PaymentType::REFUND)
            ->whereDate('created_at', today())
            ->count();

        if ($dailyRefunds >= 5) { // Максимум 5 возвратов в день
            throw new \InvalidArgumentException('Daily refund limit exceeded');
        }

        // Месячный лимит суммы возвратов
        $monthlyRefundAmount = Payment::where('user_id', $user->id)
            ->where('type', PaymentType::REFUND)
            ->where('status', PaymentStatus::COMPLETED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        if ($monthlyRefundAmount > 500000) { // 500к рублей в месяц
            throw new \InvalidArgumentException('Monthly refund amount limit exceeded');
        }
    }

    /**
     * Обработать созданный возврат
     */
    protected function handleRefundCreated(Payment $refund, Payment $originalPayment): void
    {
        // Логируем создание возврата
        Log::info('Refund processing started', [
            'refund_id' => $refund->id,
            'original_payment_id' => $originalPayment->id,
        ]);

        // Добавляем метаданные
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
    protected function determineRefundType(Payment $originalPayment): string
    {
        if ($originalPayment->isFullyRefunded()) {
            return 'full_refund';
        }
        
        return 'partial_refund';
    }

    /**
     * Категоризировать причину возврата
     */
    protected function categorizeRefundReason(?string $reason): string
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
    protected function getRefundPriority(Payment $refund): string
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

    /**
     * Обновить связанные сущности
     */
    protected function updateRelatedEntities(Payment $refund, Payment $originalPayment): void
    {
        // Обновляем статус бронирования
        if ($originalPayment->payable_type === 'App\Models\Booking') {
            $this->updateBookingAfterRefund($originalPayment->payable, $refund);
        }

        // Обновляем статистику пользователя
        $this->updateUserStatistics($refund->user, $refund);

        // Обновляем статистику мастера
        if ($originalPayment->payable_type === 'App\Models\Booking') {
            $booking = $originalPayment->payable;
            if ($booking && $booking->master) {
                $this->updateMasterStatistics($booking->master, $refund);
            }
        }
    }

    /**
     * Обновить бронирование после возврата
     */
    protected function updateBookingAfterRefund($booking, Payment $refund): void
    {
        if (!$booking) return;

        $originalPayment = $refund->parentPayment;

        if ($originalPayment->type === PaymentType::BOOKING_DEPOSIT) {
            $booking->update([
                'deposit_refunded' => true,
                'refund_amount' => $refund->amount,
                'status' => 'cancelled_by_client',
            ]);
        } elseif ($originalPayment->type === PaymentType::SERVICE_PAYMENT) {
            if ($originalPayment->isFullyRefunded()) {
                $booking->update([
                    'payment_status' => 'refunded',
                    'status' => 'cancelled_by_client',
                ]);
            } else {
                $booking->update([
                    'payment_status' => 'partially_refunded',
                ]);
            }
        }
    }

    /**
     * Обновить статистику пользователя
     */
    protected function updateUserStatistics($user, Payment $refund): void
    {
        // Увеличиваем счетчик возвратов
        $user->increment('refunds_count');
        $user->increment('refunds_amount', $refund->amount);
    }

    /**
     * Обновить статистику мастера
     */
    protected function updateMasterStatistics($master, Payment $refund): void
    {
        // Уменьшаем доходы мастера
        if ($master->balance) {
            $master->balance->decrement('amount', $refund->amount);
        }

        // Обновляем статистику
        $master->increment('cancelled_bookings_count');
    }

    /**
     * Частичный возврат
     */
    public function partialRefund(Payment $payment, float $amount, string $reason): Payment
    {
        if ($amount >= $payment->amount) {
            throw new \InvalidArgumentException('Use full refund for amounts equal or greater than original payment');
        }

        return $this->execute($payment, $amount, $reason);
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

        return $this->execute($payment, $remainingAmount, $reason);
    }

    /**
     * Автоматический возврат (например, при отмене бронирования)
     */
    public function autoRefund(Payment $payment, string $systemReason): Payment
    {
        $remainingAmount = $payment->getRemainingRefundAmount();
        
        return $this->execute($payment, $remainingAmount, "Автоматический возврат: {$systemReason}");
    }
}