<?php

namespace App\Domain\Payment\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Универсальный сервис для работы с платежными шлюзами
 */
class PaymentGatewayService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Создать платеж в выбранном шлюзе
     */
    public function createPayment(Payment $payment): array
    {
        switch ($payment->payment_method) {
            case 'sbp':
                return $this->createSbpPayment($payment);
            case 'card':
                return $this->createCardPayment($payment);
            case 'yookassa':
                return $this->createYookassaPayment($payment);
            case 'webmoney':
                return $this->createWebMoneyPayment($payment);
            default:
                throw new \Exception('Неподдерживаемый способ оплаты: ' . $payment->payment_method);
        }
    }

    /**
     * Обработать webhook от платежного шлюза
     */
    public function handleWebhook(string $gateway, array $data): bool
    {
        try {
            switch ($gateway) {
                case 'yookassa':
                    return $this->handleYookassaWebhook($data);
                case 'sbp':
                    return $this->handleSbpWebhook($data);
                case 'webmoney':
                    return $this->handleWebMoneyWebhook($data);
                default:
                    Log::warning('Unknown payment gateway webhook', ['gateway' => $gateway]);
                    return false;
            }
        } catch (\Exception $e) {
            Log::error('Payment webhook error', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * Проверить статус платежа в шлюзе
     */
    public function checkPaymentStatus(Payment $payment): array
    {
        switch ($payment->payment_method) {
            case 'yookassa':
                return $this->checkYookassaStatus($payment);
            case 'sbp':
                return $this->checkSbpStatus($payment);
            default:
                return ['status' => 'unknown'];
        }
    }

    // =================== YOOKASSA ===================

    /**
     * Создать платеж в YooKassa (ЮKassa)
     */
    private function createYookassaPayment(Payment $payment): array
    {
                 $finalAmount = $payment->metadata['final_amount'] ?? $payment->amount;
         $data = [
             'amount' => [
                 'value' => number_format($finalAmount, 2, '.', ''),
                 'currency' => 'RUB'
             ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => route('payment.success', $payment)
            ],
            'capture' => true,
            'metadata' => [
                'payment_id' => $payment->payment_id,
                'user_id' => $payment->user_id
            ],
            'description' => $payment->description
        ];

        $response = Http::withBasicAuth(
            config('payments.yookassa.shop_id'),
            config('payments.yookassa.secret_key')
        )->withHeaders([
            'Idempotence-Key' => $payment->payment_id
        ])->post('https://api.yookassa.ru/v3/payments', $data);

        if ($response->successful()) {
            $responseData = $response->json();
            
            // Сохраняем внешний ID
            $payment->update([
                'external_payment_id' => $responseData['id'],
                'metadata' => array_merge($payment->metadata ?? [], [
                    'yookassa_data' => $responseData
                ])
            ]);

            return [
                'success' => true,
                'redirect_url' => $responseData['confirmation']['confirmation_url'],
                'payment_id' => $responseData['id']
            ];
        }

        Log::error('YooKassa payment creation failed', [
            'payment_id' => $payment->payment_id,
            'response' => $response->body()
        ]);

        return [
            'success' => false,
            'error' => 'Ошибка создания платежа'
        ];
    }

    /**
     * Обработать webhook от YooKassa
     */
    private function handleYookassaWebhook(array $data): bool
    {
        if (!isset($data['object']) || $data['object']['status'] !== 'succeeded') {
            return false;
        }

        $paymentData = $data['object'];
        $paymentId = $paymentData['metadata']['payment_id'] ?? null;

        if (!$paymentId) {
            Log::warning('YooKassa webhook without payment_id', $data);
            return false;
        }

        $payment = Payment::where('payment_id', $paymentId)->first();
        if (!$payment) {
            Log::warning('YooKassa webhook: payment not found', ['payment_id' => $paymentId]);
            return false;
        }

        // Обновляем статус платежа
        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
            'external_payment_id' => $paymentData['id'],
            'metadata' => array_merge($payment->metadata ?? [], [
                'yookassa_webhook' => $paymentData
            ])
        ]);

        // Обрабатываем успешный платеж
        $this->processSuccessfulPayment($payment);

        return true;
    }

    /**
     * Проверить статус в YooKassa
     */
    private function checkYookassaStatus(Payment $payment): array
    {
        if (!$payment->external_payment_id) {
            return ['status' => 'not_created'];
        }

        $response = Http::withBasicAuth(
            config('payments.yookassa.shop_id'),
            config('payments.yookassa.secret_key')
        )->get("https://api.yookassa.ru/v3/payments/{$payment->external_payment_id}");

        if ($response->successful()) {
            $data = $response->json();
            return [
                'status' => $data['status'],
                'paid' => $data['status'] === 'succeeded'
            ];
        }

        return ['status' => 'error'];
    }

    // =================== СБП ===================

    /**
     * Создать СБП платеж
     */
    private function createSbpPayment(Payment $payment): array
    {
        // В реальном проекте здесь будет интеграция с банком
        // Пока генерируем тестовый QR-код
        $qrString = $this->generateSbpQrString($payment);

        return [
            'success' => true,
            'qr_code' => $qrString,
            'qr_image' => $this->generateQrCodeImage($qrString)
        ];
    }

    /**
     * Обработать webhook СБП
     */
    private function handleSbpWebhook(array $data): bool
    {
        // Логика обработки СБП webhook
        $paymentId = $data['payment_id'] ?? null;
        
        if (!$paymentId) {
            return false;
        }

        $payment = Payment::where('payment_id', $paymentId)->first();
        if (!$payment) {
            return false;
        }

        if ($data['status'] === 'success') {
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'external_payment_id' => $data['transaction_id'] ?? null
            ]);

            $this->processSuccessfulPayment($payment);
        }

        return true;
    }

    /**
     * Проверить статус СБП
     */
    private function checkSbpStatus(Payment $payment): array
    {
        // В реальном проекте здесь запрос к банку
        // Пока рандомный успешный статус для тестирования
        $isSuccess = rand(1, 10) === 1; // 10% шанс успеха

        if ($isSuccess && $payment->status === 'pending') {
            $payment->update([
                'status' => 'completed',
                'paid_at' => now()
            ]);

            $this->processSuccessfulPayment($payment);

            return ['status' => 'completed', 'paid' => true];
        }

        return ['status' => 'pending', 'paid' => false];
    }

    // =================== БАНКОВСКИЕ КАРТЫ ===================

    /**
     * Создать платеж банковской картой
     */
    private function createCardPayment(Payment $payment): array
    {
        // Интеграция с банком для карточных платежей
        // Пока используем тестовую логику
        return [
            'success' => true,
            'redirect_url' => route('payment.success', $payment),
            'test_mode' => true
        ];
    }

    // =================== WEBMONEY ===================

    /**
     * Создать WebMoney платеж
     */
    private function createWebMoneyPayment(Payment $payment): array
    {
        return [
            'success' => true,
                         'form_data' => [
                 'LMI_PAYEE_PURSE' => config('payments.webmoney.purse'),
                 'LMI_PAYMENT_AMOUNT' => $payment->metadata['final_amount'] ?? $payment->amount,
                'LMI_PAYMENT_NO' => $payment->payment_id,
                'LMI_PAYMENT_DESC' => $payment->description,
                'LMI_SUCCESS_URL' => route('payment.success', $payment),
                'LMI_FAIL_URL' => route('payment.fail', $payment),
                'LMI_RESULT_URL' => route('payment.webmoney.callback')
            ],
            'action_url' => 'https://merchant.webmoney.com/lmi/payment_utf.asp'
        ];
    }

    /**
     * Обработать WebMoney webhook
     */
    private function handleWebMoneyWebhook(array $data): bool
    {
        // Проверяем подпись
        if (!$this->verifyWebMoneySignature($data)) {
            return false;
        }

        $paymentId = $data['LMI_PAYMENT_NO'];
        $payment = Payment::where('payment_id', $paymentId)->first();

        if (!$payment) {
            return false;
        }

        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
            'external_payment_id' => $data['LMI_SYS_PAYMENT_ID']
        ]);

        $this->processSuccessfulPayment($payment);
        return true;
    }

    // =================== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ===================

    /**
     * Обработать успешный платеж
     */
    private function processSuccessfulPayment(Payment $payment): void
    {
        try {
            // Обновляем связанные сущности в зависимости от типа платежа
            switch ($payment->purchase_type) {
                case 'ad_placement':
                    $this->activateAd($payment);
                    break;
                case 'balance_top_up':
                    $this->topUpUserBalance($payment);
                    break;
                case 'service_booking':
                    $this->confirmBooking($payment);
                    break;
            }

            // Отправляем уведомления
            $this->notificationService->sendPaymentCompleted($payment);

            Log::info('Payment processed successfully', ['payment_id' => $payment->payment_id]);

        } catch (\Exception $e) {
            Log::error('Error processing successful payment', [
                'payment_id' => $payment->payment_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Активировать объявление после оплаты
     */
    private function activateAd(Payment $payment): void
    {
        if ($payment->ad && $payment->adPlan) {
            $payment->ad->update([
                'status' => 'active',
                'is_paid' => true,
                'paid_at' => now(),
                'expires_at' => now()->addDays($payment->adPlan->days)
            ]);
        }
    }

    /**
     * Пополнить баланс пользователя
     */
         private function topUpUserBalance(Payment $payment): void
     {
         $balance = $payment->user->getBalance();
         $finalAmount = $payment->metadata['final_amount'] ?? $payment->amount;
         $balance->addFunds($finalAmount);
     }

    /**
     * Подтвердить бронирование после оплаты
     */
    private function confirmBooking(Payment $payment): void
    {
        if ($payment->booking) {
            $payment->booking->update([
                'payment_status' => 'paid',
                'status' => 'confirmed'
            ]);
        }
    }

    /**
     * Генерировать строку для СБП QR-кода
     */
    private function generateSbpQrString(Payment $payment): string
    {
                 // Формат СБП QR-кода
         $finalAmount = $payment->metadata['final_amount'] ?? $payment->amount;
         return "https://qr.nspk.ru/AS10000{$payment->payment_id}?type=02&bank=100000000111&sum={$finalAmount}&cur=RUB&crc=1234";
    }

    /**
     * Генерировать изображение QR-кода
     */
    private function generateQrCodeImage(string $qrString): string
    {
        // В реальном проекте здесь будет генерация QR-кода
        // Пока возвращаем base64 заглушку
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    }

    /**
     * Проверить подпись WebMoney
     */
    private function verifyWebMoneySignature(array $data): bool
    {
        $secretKey = config('payments.webmoney.secret_key');
        
        $signString = $data['LMI_PAYEE_PURSE'] . 
                     $data['LMI_PAYMENT_AMOUNT'] . 
                     $data['LMI_PAYMENT_NO'] . 
                     $data['LMI_MODE'] . 
                     $data['LMI_SYS_INVS_NO'] . 
                     $data['LMI_SYS_TRANS_NO'] . 
                     $data['LMI_SYS_TRANS_DATE'] . 
                     $secretKey . 
                     $data['LMI_PAYER_PURSE'] . 
                     $data['LMI_PAYER_WM'];

        $expectedHash = strtoupper(hash('sha256', $signString));
        $receivedHash = strtoupper($data['LMI_HASH']);

        return hash_equals($expectedHash, $receivedHash);
    }
} 