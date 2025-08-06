<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Repositories\PaymentRepository;
use App\Domain\Payment\DTOs\CreatePaymentDTO;
use App\Domain\Payment\DTOs\RefundPaymentDTO;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Payment\Contracts\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
}