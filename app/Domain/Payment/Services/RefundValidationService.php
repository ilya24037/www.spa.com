<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Сервис валидации возвратов платежей
 */
class RefundValidationService
{
    /**
     * Валидация возврата
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
        if (!$payment->isRefundable()) {
            throw new \InvalidArgumentException('Payment is not refundable');
        }
    }

    /**
     * Проверка суммы возврата
     */
    private function validateRefundAmount(Payment $payment, float $amount): void
    {
        $remainingAmount = $payment->getRemainingRefundAmount();
        if ($amount > $remainingAmount) {
            throw new \InvalidArgumentException(
                "Refund amount ({$amount}) exceeds remaining refundable amount ({$remainingAmount})"
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
                "Refund deadline has passed. Deadline was: {$refundDeadline->format('d.m.Y H:i')}"
            );
        }
    }

    /**
     * Получить крайний срок для возврата
     */
    private function getRefundDeadline(Payment $payment): ?\Carbon\Carbon
    {
        return match($payment->type) {
            PaymentType::SERVICE_PAYMENT => $payment->confirmed_at?->addDays(14),
            PaymentType::BOOKING_DEPOSIT => $payment->confirmed_at?->addDays(7),
            PaymentType::SUBSCRIPTION => $payment->confirmed_at?->addDays(30),
            default => $payment->confirmed_at?->addDays(30),
        };
    }

    /**
     * Валидация бизнес-правил
     */
    private function validateBusinessRules(Payment $payment, float $amount): void
    {
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

        $this->validateRefundLimits($payment);
    }

    /**
     * Валидация возврата за услугу
     */
    private function validateServicePaymentRefund(Payment $payment, float $amount): void
    {
        if ($payment->payable_type === 'App\Domain\Booking\Models\Booking') {
            $booking = $payment->payable;
            
            if ($booking && $booking->status === 'completed') {
                throw new \InvalidArgumentException('Cannot refund completed service');
            }

            if ($booking && $booking->status === 'in_progress') {
                throw new \InvalidArgumentException('Cannot refund service in progress');
            }
        }
    }

    /**
     * Валидация возврата депозита
     */
    private function validateDepositRefund(Payment $payment, float $amount): void
    {
        if ($payment->payable_type === 'App\Domain\Booking\Models\Booking') {
            $booking = $payment->payable;
            
            if ($booking && $booking->start_time && now()->isAfter($booking->start_time)) {
                throw new \InvalidArgumentException('Cannot refund deposit after service start time');
            }
        }
    }

    /**
     * Валидация возврата подписки
     */
    private function validateSubscriptionRefund(Payment $payment, float $amount): void
    {
        // Пропорциональный возврат в зависимости от использованного времени
        // Реализация зависит от бизнес-логики подписок
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
            throw new \InvalidArgumentException('Daily refund limit exceeded');
        }

        // Месячный лимит суммы возвратов
        $monthlyRefundAmount = Payment::where('user_id', $user->id)
            ->where('type', PaymentType::REFUND)
            ->where('status', PaymentStatus::COMPLETED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        if ($monthlyRefundAmount > 500000) {
            throw new \InvalidArgumentException('Monthly refund amount limit exceeded');
        }
    }
}