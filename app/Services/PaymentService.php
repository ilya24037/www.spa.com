<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use App\Repositories\PaymentRepository;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\PaymentProcessorInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

/**
 * Сервис для работы с платежами
 */
class PaymentService
{
    protected PaymentRepository $repository;
    protected PaymentGatewayFactory $gatewayFactory;

    public function __construct(
        PaymentRepository $repository,
        PaymentGatewayFactory $gatewayFactory
    ) {
        $this->repository = $repository;
        $this->gatewayFactory = $gatewayFactory;
    }

    /**
     * Создать новый платеж
     */
    public function createPayment(array $data): Payment
    {
        $data = $this->preparePaymentData($data);
        
        DB::beginTransaction();
        
        try {
            $payment = $this->repository->create($data);
            
            // Логируем создание платежа
            Log::info('Payment created', [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'amount' => $payment->amount,
                'user_id' => $payment->user_id,
            ]);
            
            // Генерируем событие
            Event::dispatch('payment.created', $payment);
            
            DB::commit();
            
            return $payment;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Payment creation failed', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Обработать платеж
     */
    public function processPayment(Payment $payment): bool
    {
        if (!$payment->status->canTransitionTo(PaymentStatus::PROCESSING)) {
            return false;
        }
        
        DB::beginTransaction();
        
        try {
            // Обновляем статус на "в обработке"
            $payment->startProcessing();
            
            // Получаем процессор для метода оплаты
            $processor = $this->gatewayFactory->getProcessor($payment->method);
            
            // Обрабатываем платеж через внешний сервис
            $result = $processor->processPayment($payment);
            
            if ($result['success']) {
                $this->confirmPayment($payment, $result);
            } else {
                $this->failPayment($payment, $result['error'] ?? 'Unknown error');
            }
            
            DB::commit();
            
            return $result['success'];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Payment processing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            
            $this->failPayment($payment, $e->getMessage());
            
            return false;
        }
    }

    /**
     * Подтвердить платеж
     */
    public function confirmPayment(Payment $payment, array $gatewayData = []): bool
    {
        DB::beginTransaction();
        
        try {
            $updateData = [
                'status' => PaymentStatus::COMPLETED,
                'confirmed_at' => now(),
            ];
            
            if (!empty($gatewayData)) {
                $updateData['gateway_response'] = $gatewayData;
                $updateData['external_id'] = $gatewayData['transaction_id'] ?? null;
            }
            
            $this->repository->update($payment, $updateData);
            
            // Обрабатываем успешный платеж
            $this->handleSuccessfulPayment($payment);
            
            // Генерируем событие
            Event::dispatch('payment.confirmed', $payment);
            
            Log::info('Payment confirmed', [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
            ]);
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Payment confirmation failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Отклонить платеж
     */
    public function failPayment(Payment $payment, ?string $reason = null): bool
    {
        DB::beginTransaction();
        
        try {
            $this->repository->update($payment, [
                'status' => PaymentStatus::FAILED,
                'failed_at' => now(),
                'notes' => $reason,
            ]);
            
            // Генерируем событие
            Event::dispatch('payment.failed', $payment, $reason);
            
            Log::warning('Payment failed', [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'reason' => $reason,
            ]);
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Payment failure handling failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Отменить платеж
     */
    public function cancelPayment(Payment $payment, ?string $reason = null): bool
    {
        if (!$payment->isCancellable()) {
            return false;
        }
        
        DB::beginTransaction();
        
        try {
            $this->repository->update($payment, [
                'status' => PaymentStatus::CANCELLED,
                'notes' => $reason,
            ]);
            
            // Генерируем событие
            Event::dispatch('payment.cancelled', $payment, $reason);
            
            Log::info('Payment cancelled', [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'reason' => $reason,
            ]);
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Payment cancellation failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Создать возврат
     */
    public function createRefund(
        Payment $payment, 
        float $amount, 
        ?string $reason = null
    ): ?Payment {
        
        if (!$payment->isRefundable() || $amount > $payment->getRemainingRefundAmount()) {
            return null;
        }
        
        DB::beginTransaction();
        
        try {
            $refund = $this->repository->create([
                'payment_number' => Payment::generatePaymentNumber(),
                'user_id' => $payment->user_id,
                'payable_type' => $payment->payable_type,
                'payable_id' => $payment->payable_id,
                'parent_payment_id' => $payment->id,
                'type' => PaymentType::REFUND,
                'method' => $payment->method,
                'status' => PaymentStatus::PENDING,
                'amount' => $amount,
                'fee' => 0,
                'total_amount' => $amount,
                'currency' => $payment->currency,
                'description' => "Возврат по платежу {$payment->payment_number}",
                'notes' => $reason,
                'gateway' => $payment->gateway,
            ]);
            
            // Обрабатываем возврат через платежный шлюз
            $this->processRefund($refund);
            
            // Обновляем статус основного платежа
            $this->updateOriginalPaymentStatus($payment);
            
            // Генерируем событие
            Event::dispatch('payment.refund_created', $refund, $payment);
            
            Log::info('Refund created', [
                'refund_id' => $refund->id,
                'original_payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
            ]);
            
            DB::commit();
            
            return $refund;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Refund creation failed', [
                'payment_id' => $payment->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Обработать возврат
     */
    public function processRefund(Payment $refund): bool
    {
        try {
            $originalPayment = $refund->parentPayment;
            
            if (!$originalPayment) {
                throw new \Exception('Original payment not found');
            }
            
            // Получаем процессор для метода оплаты
            $processor = $this->gatewayFactory->getProcessor($originalPayment->method);
            
            // Обрабатываем возврат через внешний сервис
            $result = $processor->processRefund($refund, $originalPayment);
            
            if ($result['success']) {
                $this->repository->update($refund, [
                    'status' => PaymentStatus::COMPLETED,
                    'confirmed_at' => now(),
                    'external_id' => $result['refund_id'] ?? null,
                    'gateway_response' => $result,
                ]);
                
                Event::dispatch('payment.refund_completed', $refund);
                
                return true;
            } else {
                $this->repository->update($refund, [
                    'status' => PaymentStatus::FAILED,
                    'failed_at' => now(),
                    'notes' => $result['error'] ?? 'Refund failed',
                ]);
                
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error('Refund processing failed', [
                'refund_id' => $refund->id,
                'error' => $e->getMessage(),
            ]);
            
            $this->repository->update($refund, [
                'status' => PaymentStatus::FAILED,
                'failed_at' => now(),
                'notes' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Заморозить платеж
     */
    public function holdPayment(Payment $payment, ?string $reason = null): bool
    {
        return $this->repository->update($payment, [
            'status' => PaymentStatus::HELD,
            'notes' => $reason,
        ]);
    }

    /**
     * Разморозить платеж
     */
    public function unholdPayment(Payment $payment): bool
    {
        if ($payment->status !== PaymentStatus::HELD) {
            return false;
        }
        
        return $this->repository->update($payment, [
            'status' => PaymentStatus::PENDING,
            'notes' => null,
        ]);
    }

    /**
     * Получить доступные методы оплаты для суммы
     */
    public function getAvailableMethodsForAmount(float $amount): array
    {
        return PaymentMethod::getAvailableForAmount($amount);
    }

    /**
     * Рассчитать комиссию
     */
    public function calculateFee(float $amount, PaymentMethod $method): float
    {
        return $method->calculateFee($amount);
    }

    /**
     * Рассчитать общую сумму с комиссией
     */
    public function calculateTotalWithFee(float $amount, PaymentMethod $method): float
    {
        return $method->getTotalWithFee($amount);
    }

    /**
     * Проверить статус платежа во внешней системе
     */
    public function checkPaymentStatus(Payment $payment): array
    {
        try {
            if (!$payment->external_id || !$payment->gateway) {
                return ['status' => 'unknown', 'message' => 'No external data'];
            }
            
            $processor = $this->gatewayFactory->getProcessor($payment->method);
            $result = $processor->checkStatus($payment);
            
            // Синхронизируем статус если он изменился
            if ($result['status'] !== $payment->status->value) {
                $this->syncPaymentStatus($payment, $result);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Payment status check failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Синхронизировать статус платежа
     */
    protected function syncPaymentStatus(Payment $payment, array $externalData): void
    {
        $newStatus = PaymentStatus::tryFrom($externalData['status']);
        
        if ($newStatus && $payment->status->canTransitionTo($newStatus)) {
            $this->repository->update($payment, [
                'status' => $newStatus,
                'gateway_response' => $externalData,
            ]);
            
            Log::info('Payment status synchronized', [
                'payment_id' => $payment->id,
                'old_status' => $payment->status->value,
                'new_status' => $newStatus->value,
            ]);
        }
    }

    /**
     * Обработать успешный платеж
     */
    protected function handleSuccessfulPayment(Payment $payment): void
    {
        // Здесь можно добавить логику для обработки успешного платежа:
        // - Активация услуг
        // - Начисление бонусов
        // - Отправка уведомлений
        // - Обновление балансов
        
        switch ($payment->type) {
            case PaymentType::SERVICE_PAYMENT:
                $this->handleServicePayment($payment);
                break;
                
            case PaymentType::BOOKING_DEPOSIT:
                $this->handleDepositPayment($payment);
                break;
                
            case PaymentType::SUBSCRIPTION:
                $this->handleSubscriptionPayment($payment);
                break;
                
            case PaymentType::TOP_UP:
                $this->handleTopUpPayment($payment);
                break;
        }
    }

    /**
     * Обработать оплату услуги
     */
    protected function handleServicePayment(Payment $payment): void
    {
        // Логика обработки оплаты услуги
        if ($payment->payable_type === 'App\Models\Booking') {
            // Подтверждаем бронирование
            $booking = $payment->payable;
            if ($booking) {
                $booking->update(['payment_status' => 'paid']);
            }
        }
    }

    /**
     * Обработать депозитный платеж
     */
    protected function handleDepositPayment(Payment $payment): void
    {
        // Логика обработки депозита
    }

    /**
     * Обработать платеж подписки
     */
    protected function handleSubscriptionPayment(Payment $payment): void
    {
        // Логика обработки подписки
    }

    /**
     * Обработать пополнение баланса
     */
    protected function handleTopUpPayment(Payment $payment): void
    {
        // Пополняем баланс пользователя
        $user = $payment->user;
        if ($user && $user->balance) {
            $user->balance->increment('amount', $payment->amount);
        }
    }

    /**
     * Обновить статус основного платежа при возврате
     */
    protected function updateOriginalPaymentStatus(Payment $payment): void
    {
        $payment->refresh();
        
        if ($payment->isFullyRefunded()) {
            $this->repository->update($payment, ['status' => PaymentStatus::REFUNDED]);
        } elseif ($payment->isPartiallyRefunded()) {
            $this->repository->update($payment, ['status' => PaymentStatus::PARTIALLY_REFUNDED]);
        }
    }

    /**
     * Подготовить данные платежа
     */
    protected function preparePaymentData(array $data): array
    {
        // Устанавливаем значения по умолчанию
        $defaults = [
            'status' => PaymentStatus::PENDING,
            'currency' => 'RUB',
            'fee' => 0,
        ];
        
        $data = array_merge($defaults, $data);
        
        // Рассчитываем комиссию если не указана
        if (isset($data['method']) && isset($data['amount']) && !$data['fee']) {
            $method = PaymentMethod::tryFrom($data['method']);
            if ($method) {
                $data['fee'] = $method->calculateFee($data['amount']);
            }
        }
        
        // Рассчитываем общую сумму
        $data['total_amount'] = ($data['amount'] ?? 0) + ($data['fee'] ?? 0);
        
        return $data;
    }

    /**
     * Очистка истекших платежей
     */
    public function cleanupExpiredPayments(): int
    {
        return $this->repository->cleanupExpired();
    }

    /**
     * Получить статистику платежей
     */
    public function getStatistics(array $filters = []): array
    {
        return $this->repository->getStatistics($filters);
    }
}