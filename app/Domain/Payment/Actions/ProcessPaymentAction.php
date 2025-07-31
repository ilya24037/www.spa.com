<?php

namespace App\Actions\Payment;

use App\Models\Payment;
use App\Enums\PaymentStatus;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

/**
 * Action для обработки платежа
 */
class ProcessPaymentAction
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Выполнить обработку платежа
     */
    public function execute(Payment $payment, array $options = []): bool
    {
        // Проверяем, можно ли обрабатывать платеж
        if (!$this->canProcessPayment($payment)) {
            Log::warning('Payment cannot be processed', [
                'payment_id' => $payment->id,
                'current_status' => $payment->status->value,
            ]);
            return false;
        }

        // Определяем способ обработки
        $processAsync = $options['async'] ?? $this->shouldProcessAsync($payment);

        if ($processAsync) {
            return $this->processAsync($payment, $options);
        } else {
            return $this->processSync($payment, $options);
        }
    }

    /**
     * Синхронная обработка платежа
     */
    protected function processSync(Payment $payment, array $options = []): bool
    {
        DB::beginTransaction();

        try {
            // Обновляем статус на "в обработке"
            $payment->update(['status' => PaymentStatus::PROCESSING]);

            // Выполняем предварительные проверки
            $this->performPreProcessingChecks($payment);

            // Обрабатываем платеж через сервис
            $result = $this->paymentService->processPayment($payment);

            if ($result) {
                // Выполняем пост-обработку
                $this->performPostProcessing($payment, $options);
                
                Log::info('Payment processed successfully', [
                    'payment_id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                ]);
            }

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Payment processing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Помечаем платеж как неудачный
            $this->paymentService->failPayment($payment, $e->getMessage());

            return false;
        }
    }

    /**
     * Асинхронная обработка платежа
     */
    protected function processAsync(Payment $payment, array $options = []): bool
    {
        try {
            // Ставим платеж в очередь на обработку
            Queue::push('ProcessPaymentJob', [
                'payment_id' => $payment->id,
                'options' => $options,
            ]);

            // Обновляем статус
            $payment->update(['status' => PaymentStatus::PROCESSING]);

            Log::info('Payment queued for processing', [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Payment queueing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Проверить, можно ли обрабатывать платеж
     */
    protected function canProcessPayment(Payment $payment): bool
    {
        // Проверяем статус
        if (!in_array($payment->status, [PaymentStatus::PENDING, PaymentStatus::PROCESSING])) {
            return false;
        }

        // Проверяем, не истек ли платеж
        if ($payment->isExpired()) {
            $payment->update(['status' => PaymentStatus::EXPIRED]);
            return false;
        }

        // Проверяем метод оплаты
        if (!$payment->method->isAvailableForAmount($payment->amount)) {
            return false;
        }

        return true;
    }

    /**
     * Определить, нужно ли обрабатывать асинхронно
     */
    protected function shouldProcessAsync(Payment $payment): bool
    {
        // Асинхронно обрабатываем:
        // - Крупные суммы
        // - Методы с длительной обработкой
        // - Нагруженные периоды

        if ($payment->amount > 50000) {
            return true;
        }

        if (in_array($payment->method, [
            \App\Enums\PaymentMethod::TRANSFER,
            \App\Enums\PaymentMethod::CASH
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Выполнить предварительные проверки
     */
    protected function performPreProcessingChecks(Payment $payment): void
    {
        // Проверяем баланс пользователя для определенных типов
        if ($payment->type === \App\Enums\PaymentType::WITHDRAWAL) {
            $this->checkUserBalance($payment);
        }

        // Проверяем лимиты
        $this->checkPaymentLimits($payment);

        // Проверяем безопасность
        $this->performSecurityChecks($payment);
    }

    /**
     * Проверить баланс пользователя
     */
    protected function checkUserBalance(Payment $payment): void
    {
        $user = $payment->user;
        $balance = $user->balance?->amount ?? 0;

        if ($balance < $payment->amount) {
            throw new \Exception("Insufficient balance. Required: {$payment->amount}, Available: {$balance}");
        }
    }

    /**
     * Проверить лимиты платежей
     */
    protected function checkPaymentLimits(Payment $payment): void
    {
        $user = $payment->user;

        // Дневной лимит
        $dailyLimit = $this->getDailyLimit($user, $payment->method);
        $dailyTotal = $this->getDailyTotal($user, $payment->method);

        if ($dailyTotal + $payment->amount > $dailyLimit) {
            throw new \Exception("Daily limit exceeded. Limit: {$dailyLimit}, Current: {$dailyTotal}");
        }

        // Месячный лимит
        $monthlyLimit = $this->getMonthlyLimit($user, $payment->method);
        $monthlyTotal = $this->getMonthlyTotal($user, $payment->method);

        if ($monthlyTotal + $payment->amount > $monthlyLimit) {
            throw new \Exception("Monthly limit exceeded. Limit: {$monthlyLimit}, Current: {$monthlyTotal}");
        }
    }

    /**
     * Выполнить проверки безопасности
     */
    protected function performSecurityChecks(Payment $payment): void
    {
        // Проверяем на подозрительную активность
        if ($this->isSuspiciousActivity($payment)) {
            throw new \Exception("Suspicious activity detected");
        }

        // Проверяем IP адрес
        if ($this->isBlockedIP($payment)) {
            throw new \Exception("Payment from blocked IP");
        }

        // Проверяем карту на черном списке
        if ($this->isBlockedCard($payment)) {
            throw new \Exception("Payment method is blocked");
        }
    }

    /**
     * Выполнить пост-обработку
     */
    protected function performPostProcessing(Payment $payment, array $options = []): void
    {
        // Отправляем уведомления
        if ($options['send_notifications'] ?? true) {
            $this->sendNotifications($payment);
        }

        // Обновляем связанные сущности
        $this->updateRelatedEntities($payment);

        // Логируем для аналитики
        $this->logForAnalytics($payment);

        // Выполняем webhooks
        if ($options['trigger_webhooks'] ?? true) {
            $this->triggerWebhooks($payment);
        }
    }

    /**
     * Отправить уведомления
     */
    protected function sendNotifications(Payment $payment): void
    {
        // Уведомление пользователю
        Queue::push('SendPaymentNotificationJob', [
            'payment_id' => $payment->id,
            'type' => 'user_notification',
        ]);

        // Уведомление админам для крупных сумм
        if ($payment->amount > 100000) {
            Queue::push('SendPaymentNotificationJob', [
                'payment_id' => $payment->id,
                'type' => 'admin_notification',
            ]);
        }
    }

    /**
     * Обновить связанные сущности
     */
    protected function updateRelatedEntities(Payment $payment): void
    {
        if ($payment->payable) {
            switch ($payment->payable_type) {
                case 'App\Models\Booking':
                    $this->updateBookingStatus($payment);
                    break;

                case 'App\Models\User':
                    $this->updateUserBalance($payment);
                    break;
            }
        }
    }

    /**
     * Обновить статус бронирования
     */
    protected function updateBookingStatus(Payment $payment): void
    {
        $booking = $payment->payable;
        
        if ($payment->type === \App\Enums\PaymentType::BOOKING_DEPOSIT) {
            $booking->update([
                'deposit_paid' => true,
                'payment_status' => 'deposit_paid',
            ]);
        } elseif ($payment->type === \App\Enums\PaymentType::SERVICE_PAYMENT) {
            $booking->update([
                'payment_status' => 'fully_paid',
            ]);
        }
    }

    /**
     * Обновить баланс пользователя
     */
    protected function updateUserBalance(Payment $payment): void
    {
        $user = $payment->user;
        
        if ($payment->type === \App\Enums\PaymentType::TOP_UP) {
            $user->balance()->increment('amount', $payment->amount);
        } elseif ($payment->type === \App\Enums\PaymentType::WITHDRAWAL) {
            $user->balance()->decrement('amount', $payment->amount);
        }
    }

    /**
     * Логировать для аналитики
     */
    protected function logForAnalytics(Payment $payment): void
    {
        Log::channel('analytics')->info('payment_processed', [
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'amount' => $payment->amount,
            'method' => $payment->method->value,
            'type' => $payment->type->value,
            'processing_time' => $payment->processing_time,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Запустить webhooks
     */
    protected function triggerWebhooks(Payment $payment): void
    {
        Queue::push('TriggerWebhookJob', [
            'event' => 'payment.processed',
            'payment_id' => $payment->id,
        ]);
    }

    // Вспомогательные методы для проверок

    protected function getDailyLimit($user, $method): float
    {
        // Логика определения дневного лимита
        return $method->getMaxAmount();
    }

    protected function getDailyTotal($user, $method): float
    {
        return Payment::where('user_id', $user->id)
            ->where('method', $method)
            ->where('status', PaymentStatus::COMPLETED)
            ->whereDate('created_at', today())
            ->sum('amount');
    }

    protected function getMonthlyLimit($user, $method): float
    {
        // Логика определения месячного лимита
        return $method->getMaxAmount() * 30;
    }

    protected function getMonthlyTotal($user, $method): float
    {
        return Payment::where('user_id', $user->id)
            ->where('method', $method)
            ->where('status', PaymentStatus::COMPLETED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
    }

    protected function isSuspiciousActivity(Payment $payment): bool
    {
        // Логика определения подозрительной активности
        $recentPayments = Payment::where('user_id', $payment->user_id)
            ->where('created_at', '>', now()->subHour())
            ->count();

        return $recentPayments > 10; // Более 10 платежей за час
    }

    protected function isBlockedIP(Payment $payment): bool
    {
        // Проверка IP в черном списке
        return false; // Заглушка
    }

    protected function isBlockedCard(Payment $payment): bool
    {
        // Проверка карты в черном списке
        return false; // Заглушка
    }
}