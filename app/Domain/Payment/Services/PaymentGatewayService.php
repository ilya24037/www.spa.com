<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Services\Gateways\PaymentGatewayInterface;
use App\Domain\Payment\Services\Gateways\YookassaGateway;
use App\Domain\Payment\Services\Gateways\SbpGateway;
use Illuminate\Support\Facades\Log;

/**
 * Универсальный сервис для работы с платежными шлюзами - координатор
 */
class PaymentGatewayService
{
    protected PaymentProcessor $processor;
    protected array $gateways = [];

    public function __construct(PaymentProcessor $processor)
    {
        $this->processor = $processor;
        $this->initializeGateways();
    }

    /**
     * Инициализировать шлюзы
     */
    private function initializeGateways(): void
    {
        $this->gateways = [
            'yookassa' => new YookassaGateway(),
            'sbp' => new SbpGateway(),
        ];
    }

    /**
     * Создать платеж в выбранном шлюзе
     */
    public function createPayment(Payment $payment): array
    {
        $gateway = $this->getGateway($payment->payment_method);
        
        if (!$gateway) {
            throw new \Exception('Неподдерживаемый способ оплаты: ' . $payment->payment_method);
        }

        $result = $gateway->createPayment($payment);
        
        if ($result['success'] && $payment->status === 'completed') {
            $this->processor->processSuccessfulPayment($payment);
        }
        
        return $result;
    }

    /**
     * Обработать webhook от платежного шлюза
     */
    public function handleWebhook(string $gatewayName, array $data): bool
    {
        try {
            $gateway = $this->getGateway($gatewayName);
            
            if (!$gateway) {
                Log::warning('Unknown payment gateway webhook', ['gateway' => $gatewayName]);
                return false;
            }

            $result = $gateway->handleWebhook($data);
            
            if ($result) {
                $payment = $this->findPaymentFromWebhook($gatewayName, $data);
                if ($payment && $payment->status === 'completed') {
                    $this->processor->processSuccessfulPayment($payment);
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Payment webhook error', [
                'gateway' => $gatewayName,
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
        $gateway = $this->getGateway($payment->payment_method);
        
        if (!$gateway) {
            return ['status' => 'unknown'];
        }

        $result = $gateway->checkStatus($payment);
        
        if ($result['paid'] && $payment->status !== 'completed') {
            $this->processor->processSuccessfulPayment($payment);
        }
        
        return $result;
    }

    /**
     * Получить шлюз по имени
     */
    private function getGateway(string $name): ?PaymentGatewayInterface
    {
        return $this->gateways[$name] ?? null;
    }

    /**
     * Найти платеж из webhook
     */
    private function findPaymentFromWebhook(string $gateway, array $data): ?Payment
    {
        $paymentId = null;
        
        switch ($gateway) {
            case 'yookassa':
                $paymentId = $data['object']['metadata']['payment_id'] ?? null;
                break;
            case 'sbp':
                $paymentId = $data['payment_id'] ?? null;
                break;
        }
        
        return $paymentId ? Payment::where('payment_id', $paymentId)->first() : null;
    }

} 