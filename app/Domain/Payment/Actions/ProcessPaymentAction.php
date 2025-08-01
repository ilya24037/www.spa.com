<?php

namespace App\Domain\Payment\Actions;

use App\Domain\Payment\DTOs\PaymentData;
use App\Domain\Payment\Repositories\PaymentRepository;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для обработки платежа
 */
class ProcessPaymentAction
{
    private PaymentRepository $paymentRepository;

    public function __construct(PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * Обработать платеж
     */
    public function execute(PaymentData $paymentData): array
    {
        try {
            return DB::transaction(function () use ($paymentData) {
                // Создаем запись о платеже
                $payment = $this->paymentRepository->create($paymentData->toArray());

                // Здесь должна быть интеграция с платежной системой
                // Для примера - эмулируем успешный платеж
                $paymentResult = $this->processWithPaymentGateway($payment);

                if ($paymentResult['success']) {
                    // Обновляем статус платежа
                    $payment->status = PaymentStatus::COMPLETED;
                    $payment->transaction_id = $paymentResult['transaction_id'];
                    $payment->paid_at = now();
                    $payment->save();

                    // Обновляем связанную сущность
                    $this->updatePayableEntity($payment);

                    Log::info('Payment processed successfully', [
                        'payment_id' => $payment->id,
                        'transaction_id' => $payment->transaction_id,
                        'amount' => $payment->amount,
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Платеж успешно обработан',
                        'payment' => $payment,
                        'transaction_id' => $payment->transaction_id,
                    ];
                } else {
                    // Обновляем статус на неудачный
                    $payment->status = PaymentStatus::FAILED;
                    $payment->failure_reason = $paymentResult['error'] ?? 'Неизвестная ошибка';
                    $payment->save();

                    Log::error('Payment failed', [
                        'payment_id' => $payment->id,
                        'error' => $payment->failure_reason,
                    ]);

                    return [
                        'success' => false,
                        'message' => 'Ошибка при обработке платежа',
                        'error' => $payment->failure_reason,
                    ];
                }
            });
        } catch (\Exception $e) {
            Log::error('Payment processing error', [
                'data' => $paymentData->toArray(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при создании платежа',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Обработка через платежный шлюз (заглушка)
     */
    private function processWithPaymentGateway($payment): array
    {
        // TODO: Реальная интеграция с платежной системой
        
        // Эмулируем успешный платеж
        return [
            'success' => true,
            'transaction_id' => 'TXN_' . uniqid(),
        ];
    }

    /**
     * Обновить связанную сущность после успешного платежа
     */
    private function updatePayableEntity($payment): void
    {
        $entity = $payment->payable;
        
        if (!$entity) {
            return;
        }

        // Обновляем статус в зависимости от типа сущности
        switch ($payment->payable_type) {
            case 'App\\Domain\\Ad\\Models\\Ad':
                $entity->is_paid = true;
                $entity->paid_at = now();
                $entity->expires_at = now()->addDays(30);
                $entity->save();
                break;
                
            case 'App\\Domain\\Booking\\Models\\Booking':
                $entity->is_paid = true;
                $entity->paid_amount = $payment->amount;
                $entity->save();
                break;
        }
    }
}