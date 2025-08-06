<?php

namespace App\Domain\Payment\Gateways;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\DTOs\PaymentResultDTO;
use App\Domain\Payment\Services\YooKassaApiClient;
use App\Domain\Payment\Services\YooKassaRequestBuilder;
use App\Domain\Payment\Services\YooKassaWebhookHandler;
use App\Domain\Payment\Services\YooKassaResponseProcessor;
use App\Domain\Payment\Services\YooKassaFeeCalculator;

/**
 * Платежный шлюз YooKassa (ЮKassa) - координатор
 * 
 * Реализация интеграции с платежной системой YooKassa
 * https://yookassa.ru/developers
 */
class YooKassaGateway extends AbstractPaymentGateway
{
    /**
     * @var string Shop ID
     */
    private string $shopId;

    /**
     * @var string Secret key
     */
    private string $secretKey;

    private YooKassaApiClient $apiClient;
    private YooKassaRequestBuilder $requestBuilder;
    private YooKassaWebhookHandler $webhookHandler;
    private YooKassaResponseProcessor $responseProcessor;
    private YooKassaFeeCalculator $feeCalculator;

    /**
     * {@inheritdoc}
     */
    protected function initializeGateway(): void
    {
        $this->gatewayId = 'yookassa';
        $this->displayName = 'ЮKassa';
        $this->supportedCurrencies = ['RUB'];
        $this->minimumAmount = 1.0;
        $this->maximumAmount = 700000.0;
        
        $this->shopId = $this->config['shop_id'] ?? config('payments.yookassa.shop_id');
        $this->secretKey = $this->config['secret_key'] ?? config('payments.yookassa.secret_key');
        
        // Инициализируем сервисы
        $this->apiClient = new YooKassaApiClient($this->shopId, $this->secretKey);
        $this->requestBuilder = new YooKassaRequestBuilder();
        $this->webhookHandler = new YooKassaWebhookHandler();
        $this->responseProcessor = new YooKassaResponseProcessor();
        $this->feeCalculator = new YooKassaFeeCalculator();
    }

    /**
     * {@inheritdoc}
     */
    public function createPayment(Payment $payment): array
    {
        $finalAmount = $this->getFinalAmount($payment);
        $paymentData = $this->requestBuilder->buildPaymentData($payment, $finalAmount, $this->config);
        $idempotencyKey = $this->requestBuilder->generateIdempotencyKey($payment);
        
        $apiResponse = $this->apiClient->createPayment($paymentData, $idempotencyKey);
        
        return $this->responseProcessor->processPaymentCreationResponse($apiResponse, $payment);
    }

    /**
     * {@inheritdoc}
     */
    public function handleWebhook(array $data): bool
    {
        return $this->webhookHandler->handle($data);
    }

    /**
     * {@inheritdoc}
     */
    public function checkPaymentStatus(Payment $payment): array
    {
        if (!$payment->external_payment_id) {
            return $this->responseProcessor->getNotCreatedStatus();
        }

        $apiResponse = $this->apiClient->getPayment($payment->external_payment_id);
        
        return $this->responseProcessor->processPaymentStatusResponse($apiResponse);
    }

    /**
     * {@inheritdoc}
     */
    public function validateWebhook(array $data, array $headers = []): bool
    {
        return $this->webhookHandler->validateWebhook($data, $headers, $this->isTestMode());
    }

    /**
     * {@inheritdoc}
     */
    public function refund(Payment $payment, ?float $amount = null): PaymentResultDTO
    {
        if (!$payment->external_payment_id) {
            return new PaymentResultDTO(
                success: false,
                message: 'No external payment ID found'
            );
        }

        $refundAmount = $amount ?? $payment->amount;
        $refundData = $this->requestBuilder->buildRefundData($payment, $refundAmount);
        $idempotencyKey = $this->requestBuilder->generateIdempotencyKey($payment, '_refund');
        
        $apiResponse = $this->apiClient->createRefund($refundData, $idempotencyKey);
        
        return $this->responseProcessor->processRefundResponse($apiResponse, $payment, $refundAmount);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateFee(float $amount, string $currency = 'RUB'): float
    {
        return $this->feeCalculator->calculateFee($amount, $this->config);
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return !empty($this->shopId) && !empty($this->secretKey);
    }

}