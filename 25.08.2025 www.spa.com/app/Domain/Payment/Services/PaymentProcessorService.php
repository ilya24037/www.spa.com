<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Repositories\PaymentRepository;
use App\Domain\Payment\DTOs\CreatePaymentDTO;
use App\Domain\Payment\DTOs\RefundPaymentDTO;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Ad\Models\Ad;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

/**
 * Сервис обработки платежей - основная логика
 */
class PaymentProcessorService
{
    protected PaymentRepository $paymentRepository;
    protected array $gateways = [];

    public function __construct(PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * Зарегистрировать платёжный шлюз
     */
    public function registerGateway(PaymentMethod $method, PaymentGateway $gateway): void
    {
        $this->gateways[$method->value] = $gateway;
    }

    /**
     * Обработать платёж
     */
    public function processPayment(CreatePaymentDTO $dto): Payment
    {
        return DB::transaction(function () use ($dto) {
            // Создаём запись платежа
            $payment = $this->paymentRepository->create([
                'user_id' => $dto->user_id,
                'booking_id' => $dto->booking_id,
                'amount' => $dto->amount,
                'currency' => $dto->currency ?? 'RUB',
                'payment_method' => $dto->payment_method,
                'status' => PaymentStatus::PENDING,
                'description' => $dto->description,
                'metadata' => $dto->metadata ?? []
            ]);

            try {
                // Получаем шлюз для обработки
                $gateway = $this->getGateway($dto->payment_method);
                
                // Обрабатываем платёж через шлюз
                $result = $gateway->processPayment($payment, $dto->toArray());
                
                // Обновляем статус в зависимости от результата
                $status = $result['success'] ? PaymentStatus::COMPLETED : PaymentStatus::FAILED;
                $this->updatePaymentStatus($payment, $status, $result);

                Log::info('Payment processed', [
                    'payment_id' => $payment->id,
                    'status' => $status->value,
                    'amount' => $payment->amount
                ]);

                return $payment;

            } catch (\Exception $e) {
                $this->updatePaymentStatus($payment, PaymentStatus::FAILED, [
                    'error' => $e->getMessage()
                ]);

                Log::error('Payment processing failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Вернуть платёж
     */
    public function refundPayment(Payment $payment, RefundPaymentDTO $dto): Payment
    {
        if ($payment->status !== PaymentStatus::COMPLETED) {
            throw new \InvalidArgumentException('Можно вернуть только успешные платежи');
        }

        return DB::transaction(function () use ($payment, $dto) {
            try {
                $gateway = $this->getGateway($payment->payment_method);
                $result = $gateway->refundPayment($payment, $dto->toArray());

                $status = $result['success'] ? PaymentStatus::REFUNDED : PaymentStatus::REFUND_FAILED;
                $this->updatePaymentStatus($payment, $status, $result);

                Log::info('Payment refunded', [
                    'payment_id' => $payment->id,
                    'refund_amount' => $dto->amount ?? $payment->amount
                ]);

                return $payment;

            } catch (\Exception $e) {
                $this->updatePaymentStatus($payment, PaymentStatus::REFUND_FAILED, [
                    'error' => $e->getMessage()
                ]);

                Log::error('Payment refund failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Обработать webhook от платёжного шлюза
     */
    public function handleWebhook(PaymentMethod $method, array $data): bool
    {
        try {
            $gateway = $this->getGateway($method);
            $webhookResult = $gateway->handleWebhook($data);

            if (isset($webhookResult['payment_id'])) {
                $payment = $this->paymentRepository->findById($webhookResult['payment_id']);
                
                if ($payment && isset($webhookResult['status'])) {
                    $this->updatePaymentStatus($payment, $webhookResult['status'], $webhookResult);
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'method' => $method->value,
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return false;
        }
    }

    /**
     * Получить статус платежа из шлюза
     */
    public function checkPaymentStatus(Payment $payment): Payment
    {
        try {
            $gateway = $this->getGateway($payment->payment_method);
            $status = $gateway->getPaymentStatus($payment);
            
            if ($status && $status !== $payment->status) {
                $this->updatePaymentStatus($payment, $status, ['checked_at' => now()]);
            }

            return $payment;

        } catch (\Exception $e) {
            Log::error('Payment status check failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return $payment;
        }
    }

    /**
     * Получить шлюз для метода платежа
     */
    protected function getGateway(PaymentMethod $method): PaymentGateway
    {
        if (!isset($this->gateways[$method->value])) {
            throw new \InvalidArgumentException("Payment gateway not found for method: {$method->value}");
        }

        return $this->gateways[$method->value];
    }

    /**
     * Обновить статус платежа
     */
    protected function updatePaymentStatus(Payment $payment, PaymentStatus $status, array $data = []): void
    {
        $updateData = [
            'status' => $status,
            'gateway_response' => array_merge($payment->gateway_response ?? [], $data),
            'updated_at' => now()
        ];

        if ($status === PaymentStatus::COMPLETED) {
            $updateData['completed_at'] = now();
        } elseif ($status === PaymentStatus::FAILED) {
            $updateData['failed_at'] = now();
        } elseif ($status === PaymentStatus::REFUNDED) {
            $updateData['refunded_at'] = now();
        }

        $this->paymentRepository->update($payment, $updateData);
    }

    /**
     * Получить доступные методы платежа
     */
    public function getAvailablePaymentMethods(): array
    {
        return array_keys($this->gateways);
    }

    /**
     * Проверить доступность шлюза
     */
    public function isGatewayAvailable(PaymentMethod $method): bool
    {
        try {
            $gateway = $this->getGateway($method);
            return $gateway->isAvailable();
        } catch (\Exception $e) {
            return false;
        }
    }

    // ========== АВТОРИЗАЦИЯ (из PaymentAuthorizationService) ==========

    /**
     * Проверить принадлежность платежа пользователю
     */
    public function authorizePaymentOwnership(Payment $payment, int $userId): void
    {
        if ($payment->user_id !== $userId) {
            abort(403, 'Доступ к платежу запрещен');
        }
    }

    /**
     * Проверить возможность обновления объявления
     */
    public function authorizeAdUpdate(Ad $ad, int $userId): void
    {
        if ($ad->user_id !== $userId) {
            abort(403, 'Доступ к объявлению запрещен');
        }
    }

    /**
     * Проверить статус платежа для обработки
     */
    public function validatePaymentForProcessing(Payment $payment): bool
    {
        return !$payment->isPaid();
    }

    /**
     * Проверить метод платежа для СБП
     */
    public function validateSbpPayment(Payment $payment): bool
    {
        return $payment->payment_method === PaymentMethod::SBP ||
               $payment->payment_method === 'sbp';
    }

    // ========== CALLBACKS (из PaymentCallbackService) ==========

    /**
     * Обработать WebMoney callback
     */
    public function handleWebMoneyCallback(array $data): string
    {
        try {
            // Валидация callback данных
            $this->validateWebMoneyCallback($data);
            
            // Находим платёж
            if (isset($data['payment_id'])) {
                $payment = $this->paymentRepository->findById($data['payment_id']);
                if ($payment) {
                    $this->updatePaymentStatus($payment, PaymentStatus::COMPLETED, $data);
                    
                    Log::info('WebMoney callback processed', [
                        'payment_id' => $payment->id,
                        'amount' => $payment->amount
                    ]);
                }
            }

            return 'YES';
            
        } catch (\InvalidArgumentException $e) {
            Log::warning('WebMoney callback validation failed: ' . $e->getMessage(), $data);
            throw $e;
            
        } catch (\Exception $e) {
            Log::error('WebMoney callback error: ' . $e->getMessage(), $data);
            throw $e;
        }
    }

    /**
     * Обработать активационный код
     */
    public function processActivationCode(string $code, int $userId): array
    {
        try {
            // Валидация кода активации
            $this->validateActivationCode($code);
            
            // Активация кода и пополнение баланса
            $result = $this->activateCode($code, $userId);

            return [
                'success' => true,
                'message' => 'Баланс пополнен на ' . number_format($result['amount'], 0, ',', ' ') . ' ₽',
                'new_balance' => $result['new_balance']
            ];
            
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status_code' => 422
            ];
            
        } catch (\Exception $e) {
            Log::error('Activation code error', [
                'code' => $code,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при активации кода',
                'status_code' => 500
            ];
        }
    }

    /**
     * Проверить статус СБП платежа
     */
    public function checkSbpPaymentStatus(Payment $payment): array
    {
        try {
            if (!$this->validateSbpPayment($payment)) {
                throw new \InvalidArgumentException('Платёж не является СБП платежом');
            }

            $gateway = $this->getGateway($payment->payment_method);
            $status = $gateway->getPaymentStatus($payment);
            
            if ($status === PaymentStatus::COMPLETED) {
                $this->updatePaymentStatus($payment, $status, ['checked_at' => now()]);
                
                Log::info('SBP payment completed', [
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount
                ]);
                
                return ['status' => 'completed'];
            }

            return ['status' => 'pending'];
            
        } catch (\Exception $e) {
            Log::error('SBP status check failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Обработать универсальный callback от платёжных систем
     */
    public function handleUniversalCallback(PaymentMethod $method, array $data): array
    {
        try {
            switch ($method) {
                case PaymentMethod::WEBMONEY:
                    return ['result' => $this->handleWebMoneyCallback($data)];
                    
                case PaymentMethod::SBP:
                    if (isset($data['payment_id'])) {
                        $payment = $this->paymentRepository->findById($data['payment_id']);
                        if ($payment) {
                            return $this->checkSbpPaymentStatus($payment);
                        }
                    }
                    throw new \InvalidArgumentException('Payment not found for SBP callback');
                    
                default:
                    return ['success' => $this->handleWebhook($method, $data)];
            }
            
        } catch (\Exception $e) {
            Log::error('Universal callback failed', [
                'method' => $method->value,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ========== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ==========

    /**
     * Валидировать WebMoney callback данные
     */
    private function validateWebMoneyCallback(array $data): void
    {
        $required = ['payment_id', 'amount', 'purse'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }
    }

    /**
     * Валидировать код активации
     */
    private function validateActivationCode(string $code): void
    {
        if (empty($code) || strlen($code) < 8) {
            throw new \InvalidArgumentException('Неверный формат кода активации');
        }
    }

    /**
     * Активировать код и пополнить баланс
     */
    private function activateCode(string $code, int $userId): array
    {
        // В реальном приложении здесь будет логика проверки кода в базе
        // Пока возвращаем тестовые данные
        return [
            'amount' => 1000,
            'new_balance' => 5000,
            'code' => $code,
            'user_id' => $userId
        ];
    }

    /**
     * Авторизовать пользователя для операции с платежом
     */
    public function authorizePaymentOperation(Payment $payment, int $userId, string $operation = 'view'): bool
    {
        // Проверка базового доступа
        if ($payment->user_id !== $userId) {
            return false;
        }

        // Дополнительные проверки в зависимости от операции
        return match ($operation) {
            'refund' => in_array($payment->status, [PaymentStatus::COMPLETED]),
            'cancel' => in_array($payment->status, [PaymentStatus::PENDING, PaymentStatus::AUTHORIZED]),
            'retry' => $payment->status === PaymentStatus::FAILED,
            default => true
        };
    }

    /**
     * Получить статистику по методам платежей
     */
    public function getPaymentMethodStats(): array
    {
        $stats = [];
        
        foreach ($this->gateways as $method => $gateway) {
            $stats[$method] = [
                'available' => $this->isGatewayAvailable(PaymentMethod::from($method)),
                'name' => $this->getPaymentMethodName($method),
                'fees' => $gateway->getFees() ?? [],
                'limits' => $gateway->getLimits() ?? []
            ];
        }
        
        return $stats;
    }

    /**
     * Получить название метода платежа
     */
    private function getPaymentMethodName(string $method): string
    {
        return match ($method) {
            'card' => 'Банковская карта',
            'yookassa' => 'ЮKassa',
            'sbp' => 'Система быстрых платежей',
            'balance' => 'Баланс аккаунта',
            'webmoney' => 'WebMoney',
            default => ucfirst($method)
        };
    }
}