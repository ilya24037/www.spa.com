<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Сервис обработки платежных callback'ов
 * Обрабатывает уведомления от платежных систем
 */
class PaymentCallbackService
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Обработать WebMoney callback
     */
    public function handleWebMoneyCallback(array $data): string
    {
        try {
            $this->paymentService->handleWebMoneyCallback($data);
            return 'YES';
            
        } catch (\InvalidArgumentException $e) {
            Log::warning('WebMoney callback: ' . $e->getMessage(), $data);
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
            $result = $this->paymentService->activateCode($code, $userId);

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
        // В реальном приложении здесь будет проверка статуса в банке
        // Пока симулируем случайную успешную оплату
        if (rand(1, 10) === 1) { // 10% шанс успешной оплаты
            $this->paymentService->activateAdAfterPayment($payment);
            return ['status' => 'completed'];
        }

        return ['status' => 'pending'];
    }
}