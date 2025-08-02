<?php

namespace App\Domain\Payment\Actions;

use App\Domain\Payment\Models\Payment;
use App\Domain\User\Models\User;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Domain\Payment\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Action для создания платежа
 */
class CreatePaymentAction
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Выполнить создание платежа
     */
    public function execute(array $data): Payment
    {
        $this->validateData($data);
        
        DB::beginTransaction();
        
        try {
            // Подготавливаем данные платежа
            $paymentData = $this->preparePaymentData($data);
            
            // Проверяем доступность метода оплаты для суммы
            $this->validatePaymentMethod($paymentData);
            
            // Создаем платеж
            $payment = $this->paymentService->createPayment($paymentData);
            
            // Выполняем дополнительные действия в зависимости от типа
            $this->handlePaymentTypeSpecificActions($payment, $data);
            
            DB::commit();
            
            Log::info('Payment created successfully', [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'user_id' => $payment->user_id,
                'amount' => $payment->amount,
                'type' => $payment->type->value,
            ]);
            
            return $payment;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Payment creation failed', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Валидация входных данных
     */
    protected function validateData(array $data): void
    {
        $validator = Validator::make($data, [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|string',
            'method' => 'required|string',
            'currency' => 'sometimes|string|size:3',
            'description' => 'sometimes|string|max:255',
            'payable_type' => 'sometimes|string',
            'payable_id' => 'sometimes|integer',
            'metadata' => 'sometimes|array',
            
            // Дополнительные поля в зависимости от типа
            'booking_id' => 'sometimes|exists:bookings,id',
            'service_id' => 'sometimes|exists:services,id',
            'subscription_id' => 'sometimes|exists:subscriptions,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Валидация enum значений
        if (!PaymentType::tryFrom($data['type'])) {
            throw new ValidationException($validator->errors()->add('type', 'Invalid payment type'));
        }

        if (!PaymentMethod::tryFrom($data['method'])) {
            throw new ValidationException($validator->errors()->add('method', 'Invalid payment method'));
        }
    }

    /**
     * Подготовить данные платежа
     */
    protected function preparePaymentData(array $data): array
    {
        $type = PaymentType::from($data['type']);
        $method = PaymentMethod::from($data['method']);
        $user = User::find($data['user_id']);

        // Базовые данные
        $paymentData = [
            'user_id' => $data['user_id'],
            'type' => $type,
            'method' => $method,
            'status' => PaymentStatus::PENDING,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'RUB',
            'description' => $data['description'] ?? $this->generateDescription($type, $user),
            'metadata' => $data['metadata'] ?? [],
        ];

        // Полиморфные связи
        if (isset($data['payable_type']) && isset($data['payable_id'])) {
            $paymentData['payable_type'] = $data['payable_type'];
            $paymentData['payable_id'] = $data['payable_id'];
        } else {
            // Автоопределение payable на основе типа платежа и дополнительных данных
            $this->setPayableFromContext($paymentData, $data, $type);
        }

        // Рассчитываем комиссию
        $paymentData['fee'] = $method->calculateFee($paymentData['amount']);
        $paymentData['total_amount'] = $paymentData['amount'] + $paymentData['fee'];

        return $paymentData;
    }

    /**
     * Установить payable на основе контекста
     */
    protected function setPayableFromContext(array &$paymentData, array $data, PaymentType $type): void
    {
        switch ($type) {
            case PaymentType::SERVICE_PAYMENT:
            case PaymentType::BOOKING_DEPOSIT:
                if (isset($data['booking_id'])) {
                    $paymentData['payable_type'] = 'App\Domain\Booking\Models\Booking';
                    $paymentData['payable_id'] = $data['booking_id'];
                }
                break;

            case PaymentType::SUBSCRIPTION:
                if (isset($data['subscription_id'])) {
                    $paymentData['payable_type'] = 'App\Domain\User\Models\Subscription';
                    $paymentData['payable_id'] = $data['subscription_id'];
                }
                break;

            case PaymentType::TOP_UP:
                // Для пополнения баланса payable = User
                $paymentData['payable_type'] = 'App\Domain\User\Models\User';
                $paymentData['payable_id'] = $data['user_id'];
                break;
        }
    }

    /**
     * Валидация метода оплаты
     */
    protected function validatePaymentMethod(array $paymentData): void
    {
        $method = $paymentData['method'];
        $type = $paymentData['type'];
        $amount = $paymentData['amount'];

        // Проверяем доступность метода для суммы
        if (!$method->isAvailableForAmount($amount)) {
            throw new \InvalidArgumentException(
                "Payment method {$method->value} is not available for amount {$amount}. " .
                "Min: {$method->getMinAmount()}, Max: {$method->getMaxAmount()}"
            );
        }

        // Проверяем совместимость метода с типом платежа
        $availableMethods = $type->getAvailablePaymentMethods();
        if (!in_array($method, $availableMethods)) {
            throw new \InvalidArgumentException(
                "Payment method {$method->value} is not available for payment type {$type->value}"
            );
        }

        // Дополнительные проверки для конкретных методов
        if ($method->requiresConfirmation() && !isset($paymentData['confirmation_required'])) {
            $paymentData['metadata']['requires_confirmation'] = true;
        }
    }

    /**
     * Обработать специфичные для типа платежа действия
     */
    protected function handlePaymentTypeSpecificActions(Payment $payment, array $originalData): void
    {
        switch ($payment->type) {
            case PaymentType::BOOKING_DEPOSIT:
                $this->handleBookingDeposit($payment, $originalData);
                break;

            case PaymentType::SERVICE_PAYMENT:
                $this->handleServicePayment($payment, $originalData);
                break;

            case PaymentType::SUBSCRIPTION:
                $this->handleSubscriptionPayment($payment, $originalData);
                break;

            case PaymentType::TOP_UP:
                $this->handleTopUpPayment($payment, $originalData);
                break;
        }
    }

    /**
     * Обработать депозит за бронирование
     */
    protected function handleBookingDeposit(Payment $payment, array $data): void
    {
        if ($payment->payable_type === 'App\Domain\Booking\Models\Booking') {
            $booking = $payment->payable;
            if ($booking) {
                $booking->update([
                    'deposit_amount' => $payment->amount,
                    'deposit_paid' => false, // Будет true после подтверждения платежа
                    'payment_status' => 'deposit_pending',
                ]);
                
                Log::info('Booking deposit payment created', [
                    'payment_id' => $payment->id,
                    'booking_id' => $booking->id,
                    'deposit_amount' => $payment->amount,
                ]);
            }
        }
    }

    /**
     * Обработать оплату услуги
     */
    protected function handleServicePayment(Payment $payment, array $data): void
    {
        if ($payment->payable_type === 'App\Domain\Booking\Models\Booking') {
            $booking = $payment->payable;
            if ($booking) {
                $booking->update([
                    'total_amount' => $payment->amount,
                    'payment_status' => 'payment_pending',
                ]);
                
                Log::info('Service payment created', [
                    'payment_id' => $payment->id,
                    'booking_id' => $booking->id,
                    'service_amount' => $payment->amount,
                ]);
            }
        }
    }

    /**
     * Обработать платеж подписки
     */
    protected function handleSubscriptionPayment(Payment $payment, array $data): void
    {
        // Логика для подписок
        Log::info('Subscription payment created', [
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'amount' => $payment->amount,
        ]);
    }

    /**
     * Обработать пополнение баланса
     */
    protected function handleTopUpPayment(Payment $payment, array $data): void
    {
        // Логика для пополнения баланса
        Log::info('Top-up payment created', [
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'top_up_amount' => $payment->amount,
        ]);
    }

    /**
     * Сгенерировать описание платежа
     */
    protected function generateDescription(PaymentType $type, User $user): string
    {
        $userInfo = $user->name ?? "Пользователь #{$user->id}";
        
        return match($type) {
            PaymentType::SERVICE_PAYMENT => "Оплата услуги - {$userInfo}",
            PaymentType::BOOKING_DEPOSIT => "Депозит за бронирование - {$userInfo}",
            PaymentType::SUBSCRIPTION => "Подписка - {$userInfo}",
            PaymentType::TOP_UP => "Пополнение баланса - {$userInfo}",
            PaymentType::WITHDRAWAL => "Вывод средств - {$userInfo}",
            PaymentType::REFUND => "Возврат средств - {$userInfo}",
            PaymentType::COMMISSION => "Комиссия с транзакции - {$userInfo}",
            PaymentType::PENALTY => "Штрафные санкции - {$userInfo}",
            PaymentType::BONUS => "Бонусные начисления - {$userInfo}",
            PaymentType::PROMOTION => "Промо-платеж - {$userInfo}",
        };
    }

    /**
     * Создать быстрый платеж (упрощенный метод)
     */
    public function createQuick(
        int $userId,
        float $amount,
        PaymentType $type,
        PaymentMethod $method,
        array $options = []
    ): Payment {
        
        $data = array_merge([
            'user_id' => $userId,
            'amount' => $amount,
            'type' => $type->value,
            'method' => $method->value,
        ], $options);

        return $this->execute($data);
    }

    /**
     * Создать платеж за услугу
     */
    public function createForService(
        int $userId,
        int $bookingId,
        float $amount,
        PaymentMethod $method
    ): Payment {
        
        return $this->execute([
            'user_id' => $userId,
            'amount' => $amount,
            'type' => PaymentType::SERVICE_PAYMENT->value,
            'method' => $method->value,
            'booking_id' => $bookingId,
            'description' => 'Оплата за массажную услугу',
        ]);
    }

    /**
     * Создать депозитный платеж
     */
    public function createDeposit(
        int $userId,
        int $bookingId,
        float $amount,
        PaymentMethod $method
    ): Payment {
        
        return $this->execute([
            'user_id' => $userId,
            'amount' => $amount,
            'type' => PaymentType::BOOKING_DEPOSIT->value,
            'method' => $method->value,
            'booking_id' => $bookingId,
            'description' => 'Депозит за бронирование услуги',
        ]);
    }
}