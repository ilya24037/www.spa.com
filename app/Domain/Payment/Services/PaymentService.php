<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Models\Transaction;
use App\Domain\Payment\Repositories\PaymentRepository;
use App\Domain\Payment\Gateways\PaymentGatewayManager;
use App\Domain\Payment\DTOs\CreatePaymentDTO;
use App\Domain\Payment\DTOs\RefundPaymentDTO;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Payment\Enums\TransactionType;
use App\Domain\User\Models\User;
use App\Domain\Booking\Models\Booking;
use App\Domain\Ad\Models\Ad;
use App\Infrastructure\Notification\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * Главный сервис платежей - координатор
 * Консолидирует: PaymentService, PaymentProcessorService, TransactionService, 
 * PaymentAuthorizationService, PaymentCallbackService
 */
class PaymentService
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private PaymentGatewayManager $gatewayManager
    ) {}

    // ========== СОЗДАНИЕ И ОБРАБОТКА ПЛАТЕЖЕЙ ==========

    /**
     * Создать платёж
     */
    public function createPayment(CreatePaymentDTO $dto): Payment
    {
        $this->validateCreatePayment($dto);

        return DB::transaction(function () use ($dto) {
            // Создаём запись платежа
            $payment = $this->paymentRepository->create([
                'user_id' => $dto->user_id,
                'booking_id' => $dto->booking_id,
                'ad_id' => $dto->ad_id ?? null,
                'amount' => $dto->amount,
                'currency' => $dto->currency ?? 'RUB',
                'payment_method' => $dto->payment_method,
                'status' => PaymentStatus::PENDING,
                'description' => $dto->description,
                'metadata' => $dto->metadata ?? []
            ]);

            try {
                // Обрабатываем через шлюз
                $result = $this->gatewayManager->processPayment($payment);
                
                // Обновляем статус
                $this->updatePaymentStatus($payment, $result['status'], $result);

                // Создаём транзакцию
                $this->createTransaction($payment, TransactionType::PAYMENT, $result);

                // Отправляем уведомление
                $this->sendPaymentNotification($payment);

                Log::info('Payment created', [
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'status' => $payment->status->value
                ]);

                return $payment;

            } catch (\Exception $e) {
                $this->handlePaymentError($payment, $e);
                throw $e;
            }
        });
    }

    /**
     * Создать платёж для бронирования
     */
    public function createPaymentForBooking(Booking $booking, PaymentMethod $method, array $options = []): Payment
    {
        $dto = new CreatePaymentDTO([
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'amount' => $booking->total_price ?? $booking->price,
            'payment_method' => $method,
            'description' => "Оплата бронирования #{$booking->id}",
            'metadata' => array_merge([
                'booking_id' => $booking->id,
                'master_id' => $booking->master_id,
                'service_id' => $booking->service_id
            ], $options)
        ]);

        return $this->createPayment($dto);
    }

    /**
     * Создать платёж для объявления
     */
    public function createPaymentForAd(Ad $ad, PaymentMethod $method, array $options = []): Payment
    {
        $dto = new CreatePaymentDTO([
            'user_id' => $ad->user_id,
            'ad_id' => $ad->id,
            'amount' => $options['amount'] ?? $ad->promotion_price,
            'payment_method' => $method,
            'description' => "Оплата продвижения объявления #{$ad->id}",
            'metadata' => array_merge([
                'ad_id' => $ad->id,
                'promotion_type' => $options['promotion_type'] ?? 'standard'
            ], $options)
        ]);

        return $this->createPayment($dto);
    }

    /**
     * Авторизовать платёж (холдирование средств)
     */
    public function authorizePayment(CreatePaymentDTO $dto): Payment
    {
        $this->validateAuthorization($dto);

        $payment = $this->createPayment($dto);
        $payment->update([
            'status' => PaymentStatus::AUTHORIZED,
            'authorized_at' => now(),
            'authorization_expires_at' => now()->addDays(7)
        ]);

        Log::info('Payment authorized', ['payment_id' => $payment->id]);

        return $payment;
    }

    /**
     * Подтвердить авторизованный платёж
     */
    public function capturePayment(Payment $payment, ?float $amount = null): Payment
    {
        if ($payment->status !== PaymentStatus::AUTHORIZED) {
            throw new \InvalidArgumentException('Можно подтвердить только авторизованные платежи');
        }

        if ($payment->authorization_expires_at && $payment->authorization_expires_at->isPast()) {
            throw new \InvalidArgumentException('Авторизация платежа истекла');
        }

        $captureAmount = $amount ?? $payment->amount;
        
        $result = $this->gatewayManager->gateway($payment->gateway)->capturePayment($payment, $captureAmount);
        
        $payment->update([
            'status' => PaymentStatus::COMPLETED,
            'captured_amount' => $captureAmount,
            'captured_at' => now()
        ]);

        $this->createTransaction($payment, TransactionType::CAPTURE, $result);
        $this->sendCaptureNotification($payment);

        return $payment;
    }

    // ========== ВОЗВРАТЫ И ОТМЕНЫ ==========

    /**
     * Вернуть платёж
     */
    public function refundPayment(Payment $payment, ?float $amount = null, ?string $reason = null): Payment
    {
        $this->validationService->validateRefund($payment, $amount);

        $refundAmount = $amount ?? $payment->amount;

        return DB::transaction(function () use ($payment, $refundAmount, $reason) {
            $dto = new RefundPaymentDTO([
                'amount' => $refundAmount,
                'reason' => $reason ?? 'Возврат по запросу'
            ]);

            $result = $this->gatewayManager->refundPayment($payment, $dto->amount);
            
            $payment->update([
                'status' => $refundAmount < $payment->amount ? 
                    PaymentStatus::PARTIALLY_REFUNDED : 
                    PaymentStatus::REFUNDED,
                'refunded_amount' => ($payment->refunded_amount ?? 0) + $refundAmount,
                'refunded_at' => now(),
                'refund_reason' => $reason
            ]);

            $this->createTransaction($payment, TransactionType::REFUND, array_merge($result, [
                'amount' => $refundAmount,
                'reason' => $reason
            ]));

            $this->sendRefundNotification($payment, $refundAmount);

            Log::info('Payment refunded', [
                'payment_id' => $payment->id,
                'amount' => $refundAmount,
                'reason' => $reason
            ]);

            return $payment;
        });
    }

    /**
     * Отменить платёж
     */
    public function cancelPayment(Payment $payment, ?string $reason = null): Payment
    {
        if (!in_array($payment->status, [PaymentStatus::PENDING, PaymentStatus::AUTHORIZED])) {
            throw new \InvalidArgumentException('Можно отменить только ожидающие или авторизованные платежи');
        }

        if ($payment->status === PaymentStatus::AUTHORIZED) {
            $this->gatewayManager->cancelPayment($payment, 'Payment cancelled');
        }

        $payment->update([
            'status' => PaymentStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason
        ]);

        $this->createTransaction($payment, TransactionType::VOID, ['reason' => $reason]);
        $this->sendCancellationNotification($payment);

        return $payment;
    }

    // ========== WEBHOOKS И CALLBACKS ==========

    /**
     * Обработать webhook от платёжной системы
     */
    public function handleWebhook(PaymentMethod $method, array $data): bool
    {
        try {
            $result = $this->gatewayManager->handleWebhook($method, $data);
            
            if ($result['payment_id']) {
                $payment = $this->paymentRepository->find($result['payment_id']);
                if ($payment) {
                    $this->updatePaymentFromWebhook($payment, $result);
                }
            }

            Log::info('Webhook processed', [
                'method' => $method->value,
                'payment_id' => $result['payment_id'] ?? null
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'method' => $method->value,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Обработать callback после возврата пользователя
     */
    public function handleCallback(PaymentMethod $method, array $data): Payment
    {
        $result = $this->gatewayManager->handleWebhook($method, $data);
        
        $payment = $this->paymentRepository->find($result['payment_id']);
        if (!$payment) {
            throw new \InvalidArgumentException('Платёж не найден');
        }

        $this->updatePaymentStatus($payment, $result['status'], $result);
        
        return $payment;
    }

    // ========== ПРОВЕРКА СТАТУСА ==========

    /**
     * Проверить статус платежа
     */
    public function checkPaymentStatus(Payment $payment): Payment
    {
        $result = $this->gatewayManager->checkPaymentStatus($payment);
        
        if ($result['status'] !== $payment->status) {
            $this->updatePaymentStatus($payment, $result['status'], $result);
        }

        return $payment;
    }

    /**
     * Синхронизировать статусы платежей
     */
    public function syncPaymentStatuses(): int
    {
        $payments = $this->paymentRepository->getPendingPayments();
        $synced = 0;

        foreach ($payments as $payment) {
            try {
                $this->checkPaymentStatus($payment);
                $synced++;
            } catch (\Exception $e) {
                Log::error('Failed to sync payment status', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $synced;
    }

    // ========== ТРАНЗАКЦИИ ==========

    /**
     * Создать транзакцию
     */
    private function createTransaction(Payment $payment, TransactionType $type, array $data = []): Transaction
    {
        return Transaction::create([
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'type' => $type,
            'amount' => $data['amount'] ?? $payment->amount,
            'currency' => $payment->currency,
            'status' => $data['status'] ?? 'completed',
            'gateway_transaction_id' => $data['transaction_id'] ?? null,
            'gateway_response' => $data,
            'created_at' => now()
        ]);
    }

    /**
     * Получить транзакции платежа
     */
    public function getPaymentTransactions(Payment $payment): Collection
    {
        return Transaction::where('payment_id', $payment->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // ========== ПОЛУЧЕНИЕ ДАННЫХ ==========

    /**
     * Получить платёж по ID
     */
    public function findById(int $id): ?Payment
    {
        return $this->paymentRepository->find($id);
    }

    /**
     * Получить платежи пользователя
     */
    public function getUserPayments(User $user, array $filters = []): Collection
    {
        return $this->paymentRepository->getUserPayments($user->id, $filters);
    }

    /**
     * Получить платежи по бронированию
     */
    public function getBookingPayments(Booking $booking): Collection
    {
        return $this->paymentRepository->findByBooking($booking->id);
    }

    /**
     * Получить платежи по объявлению
     */
    public function getAdPayments(Ad $ad): Collection
    {
        return $this->paymentRepository->findByAd($ad->id);
    }

    /**
     * Поиск платежей
     */
    public function searchPayments(array $criteria): Collection
    {
        return $this->paymentRepository->search($criteria);
    }

    // ========== СТАТИСТИКА ==========

    /**
     * Получить статистику платежей
     */
    public function getPaymentStats(array $filters = []): array
    {
        return [
            'total_amount' => $this->paymentRepository->getTotalAmount($filters),
            'total_count' => $this->paymentRepository->getTotalCount($filters),
            'average_amount' => $this->paymentRepository->getAverageAmount($filters),
            'success_rate' => $this->paymentRepository->getSuccessRate($filters),
            'refund_rate' => $this->paymentRepository->getRefundRate($filters),
            'by_method' => $this->paymentRepository->getStatsByMethod($filters),
            'by_status' => $this->paymentRepository->getStatsByStatus($filters)
        ];
    }

    /**
     * Получить статистику пользователя
     */
    public function getUserPaymentStats(User $user): array
    {
        return $this->getPaymentStats(['user_id' => $user->id]);
    }

    // ========== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ==========

    /**
     * Обновить статус платежа
     */
    private function updatePaymentStatus(Payment $payment, PaymentStatus $status, array $data = []): void
    {
        $payment->update([
            'status' => $status,
            'gateway_payment_id' => $data['payment_id'] ?? $payment->gateway_payment_id,
            'gateway_response' => $data,
            'processed_at' => $status === PaymentStatus::COMPLETED ? now() : null,
            'failed_at' => $status === PaymentStatus::FAILED ? now() : null
        ]);
    }

    /**
     * Обновить платёж из webhook
     */
    private function updatePaymentFromWebhook(Payment $payment, array $data): void
    {
        $newStatus = PaymentStatus::from($data['status']);
        
        if ($payment->status !== $newStatus) {
            $this->updatePaymentStatus($payment, $newStatus, $data);
            
            // Отправляем соответствующее уведомление
            match ($newStatus) {
                PaymentStatus::COMPLETED => $this->sendPaymentNotification($payment),
                PaymentStatus::FAILED => $this->sendFailedPaymentNotification($payment),
                PaymentStatus::REFUNDED => $this->sendRefundNotification($payment),
                default => null
            };
        }
    }

    /**
     * Обработать ошибку платежа
     */
    private function handlePaymentError(Payment $payment, \Exception $e): void
    {
        $this->updatePaymentStatus($payment, PaymentStatus::FAILED, [
            'error' => $e->getMessage(),
            'error_code' => $e->getCode()
        ]);

        $this->sendFailedPaymentNotification($payment);

        Log::error('Payment failed', [
            'payment_id' => $payment->id,
            'error' => $e->getMessage()
        ]);
    }

    /**
     * Получить доступные методы оплаты
     */
    public function getAvailablePaymentMethods(): array
    {
        return $this->gatewayManager->getAvailableMethods();
    }

    /**
     * Повторить неудачный платёж
     */
    public function retryPayment(Payment $payment): Payment
    {
        if ($payment->status !== PaymentStatus::FAILED) {
            throw new \InvalidArgumentException('Можно повторить только неудачные платежи');
        }

        $dto = new CreatePaymentDTO([
            'user_id' => $payment->user_id,
            'booking_id' => $payment->booking_id,
            'ad_id' => $payment->ad_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payment_method' => $payment->payment_method,
            'description' => $payment->description,
            'metadata' => array_merge($payment->metadata ?? [], [
                'retry_of' => $payment->id,
                'retry_attempt' => ($payment->metadata['retry_attempt'] ?? 0) + 1
            ])
        ]);

        return $this->createPayment($dto);
    }

    // ========== ВАЛИДАЦИЯ (из PaymentValidationService) ==========


    // ========== ПРОВАЙДЕР ДАННЫХ (из PaymentDataProvider) ==========

    /**
     * Получить тарифы пополнения баланса
     */
    public function getTopUpPlans(): array
    {
        return [
            ['amount' => 1000, 'price' => 950, 'discount' => 5],
            ['amount' => 2000, 'price' => 1750, 'discount' => 12.5],
            ['amount' => 5000, 'price' => 3750, 'discount' => 25],
            ['amount' => 10000, 'price' => 7500, 'discount' => 25],
            ['amount' => 15000, 'price' => 11000, 'discount' => 27]
        ];
    }

    /**
     * Подготовить данные платежа для view
     */
    public function preparePaymentData(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'payment_id' => $payment->payment_id,
            'amount' => $payment->amount,
            'formatted_amount' => number_format($payment->amount, 0, ',', ' ') . ' ₽',
            'status' => $payment->status,
            'status_label' => $this->getStatusLabel($payment->status),
            'paid_at' => $payment->processed_at?->format('d.m.Y H:i'),
            'created_at' => $payment->created_at->format('d.m.Y H:i'),
            'payment_method' => $payment->payment_method,
            'description' => $payment->description
        ];
    }

    /**
     * Подготовить данные объявления для платежа
     */
    public function prepareAdPaymentData(Ad $ad): array
    {
        return [
            'id' => $ad->id,
            'title' => $ad->title,
            'price' => $ad->price ?? null,
            'formatted_price' => $ad->price ? number_format($ad->price, 0, ',', ' ') . ' ₽' : null,
            'address' => $ad->address ?? null,
            'created_at' => $ad->created_at->format('d.m.Y'),
            'expires_at' => $ad->expires_at?->format('d.m.Y')
        ];
    }

    /**
     * Подготовить данные баланса пользователя
     */
    public function prepareBalanceData($balance): array
    {
        if (!$balance) {
            return [
                'rub_balance' => 0,
                'formatted_balance' => '0 ₽',
                'loyalty_level' => 'bronze',
                'loyalty_discount_percent' => 0
            ];
        }

        return [
            'rub_balance' => $balance->rub_balance ?? 0,
            'formatted_balance' => number_format($balance->rub_balance ?? 0, 0, ',', ' ') . ' ₽',
            'loyalty_level' => $balance->loyalty_level ?? 'bronze',
            'loyalty_discount_percent' => $balance->loyalty_discount_percent ?? 0
        ];
    }

    /**
     * Получить метку статуса платежа
     */
    private function getStatusLabel(PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::PENDING => 'Ожидает оплаты',
            PaymentStatus::PROCESSING => 'Обрабатывается',
            PaymentStatus::COMPLETED => 'Завершён',
            PaymentStatus::FAILED => 'Ошибка',
            PaymentStatus::CANCELLED => 'Отменён',
            PaymentStatus::REFUNDED => 'Возвращён',
            PaymentStatus::PARTIALLY_REFUNDED => 'Частично возвращён',
            PaymentStatus::AUTHORIZED => 'Авторизован',
            default => 'Неизвестно'
        };
    }

    // ========== РАСШИРЕННЫЕ УВЕДОМЛЕНИЯ (из NotificationService) ==========

    /**
     * Отправить уведомление об успешном платеже
     */
    public function sendPaymentSuccessNotification(Payment $payment): void
    {
        $user = User::find($payment->user_id);
        if (!$user) return;

        $data = [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'formatted_amount' => number_format($payment->amount, 0, ',', ' ') . ' ₽',
            'description' => $payment->description,
            'payment_method' => $payment->payment_method->value
        ];

        $this->sendByTemplate($user, 'payment_success', $data);
    }

    /**
     * Отправить квитанцию по email
     */
    public function sendPaymentReceipt(Payment $payment): void
    {
        $user = User::find($payment->user_id);
        if (!$user) return;

        $data = $this->preparePaymentData($payment);
        $data['receipt_number'] = 'R-' . str_pad($payment->id, 8, '0', STR_PAD_LEFT);
        $data['company_name'] = config('app.company_name', 'SPA Platform');
        $data['company_inn'] = config('app.company_inn');

        $this->sendByTemplate($user, 'payment_receipt', $data);
    }

    /**
     * Отправить напоминание об истекающей подписке
     */
    public function sendSubscriptionExpiringNotification(int $userId, array $subscriptionData): void
    {
        $user = User::find($userId);
        if (!$user) return;

        $this->sendNotificationByTemplate($user, 'subscription_expiring', $subscriptionData);
    }

    // ========== ВСТРОЕННЫЕ МЕТОДЫ ВАЛИДАЦИИ ==========

    /**
     * Валидация создания платежа
     */
    private function validateCreatePayment(CreatePaymentDTO $dto): void
    {
        if (empty($dto->user_id)) {
            throw new ValidationException(Validator::make([], []), ['user_id' => 'User ID is required']);
        }

        if ($dto->amount <= 0) {
            throw new ValidationException(Validator::make([], []), ['amount' => 'Amount must be greater than 0']);
        }

        if (empty($dto->currency) || !in_array($dto->currency, ['RUB', 'USD', 'EUR'])) {
            throw new ValidationException(Validator::make([], []), ['currency' => 'Invalid currency']);
        }

        // Проверка существования пользователя
        if (!User::find($dto->user_id)) {
            throw new ValidationException(Validator::make([], []), ['user_id' => 'User not found']);
        }
    }

    /**
     * Валидация авторизации
     */
    private function validateAuthorization($dto): void
    {
        if (!$dto || empty($dto->user_id)) {
            throw new ValidationException(Validator::make([], []), ['authorization' => 'Invalid authorization data']);
        }
    }

    /**
     * Валидация возврата
     */
    private function validateRefund(Payment $payment, ?float $amount): void
    {
        if ($payment->status !== PaymentStatus::COMPLETED) {
            throw new ValidationException(Validator::make([], []), ['payment' => 'Only completed payments can be refunded']);
        }

        if ($amount !== null && $amount <= 0) {
            throw new ValidationException(Validator::make([], []), ['amount' => 'Refund amount must be greater than 0']);
        }

        if ($amount !== null && $amount > $payment->amount) {
            throw new ValidationException(Validator::make([], []), ['amount' => 'Refund amount cannot exceed payment amount']);
        }
    }

    // ========== ВСТРОЕННЫЕ МЕТОДЫ УВЕДОМЛЕНИЙ ==========

    /**
     * Отправить уведомление о платеже
     */
    private function sendPaymentNotification(Payment $payment): void
    {
        try {
            $user = $payment->user;
            if (!$user) return;

            $data = [
                'payment_id' => $payment->payment_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status->value,
                'created_at' => $payment->created_at->format('d.m.Y H:i')
            ];

            Log::info("Payment notification sent", ['user_id' => $user->id, 'payment_id' => $payment->id]);
            
            // Здесь можно интегрировать с реальной системой уведомлений
            // например, отправка email, SMS или push-уведомлений
            
        } catch (\Exception $e) {
            Log::error("Failed to send payment notification", ['error' => $e->getMessage(), 'payment_id' => $payment->id]);
        }
    }

    /**
     * Отправить уведомление о захвате платежа
     */
    private function sendCaptureNotification(Payment $payment): void
    {
        try {
            Log::info("Capture notification", ['payment_id' => $payment->id]);
        } catch (\Exception $e) {
            Log::error("Failed to send capture notification", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Отправить уведомление о возврате
     */
    private function sendRefundNotification(Payment $payment, ?float $amount = null): void
    {
        try {
            Log::info("Refund notification", [
                'payment_id' => $payment->id, 
                'refund_amount' => $amount ?? $payment->amount
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send refund notification", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Отправить уведомление об отмене
     */
    private function sendCancellationNotification(Payment $payment): void
    {
        try {
            Log::info("Cancellation notification", ['payment_id' => $payment->id]);
        } catch (\Exception $e) {
            Log::error("Failed to send cancellation notification", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Отправить уведомление о неудачном платеже
     */
    private function sendFailedPaymentNotification(Payment $payment): void
    {
        try {
            Log::info("Failed payment notification", ['payment_id' => $payment->id]);
        } catch (\Exception $e) {
            Log::error("Failed to send failed payment notification", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Отправить уведомление по шаблону
     */
    private function sendNotificationByTemplate(User $user, string $template, array $data): void
    {
        try {
            Log::info("Template notification sent", [
                'user_id' => $user->id,
                'template' => $template,
                'data' => $data
            ]);
            
            // Здесь можно интегрировать с реальной системой шаблонов уведомлений
            
        } catch (\Exception $e) {
            Log::error("Failed to send template notification", [
                'error' => $e->getMessage(),
                'template' => $template
            ]);
        }
    }
}