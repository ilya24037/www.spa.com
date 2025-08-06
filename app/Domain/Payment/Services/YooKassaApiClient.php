<?php

namespace App\Domain\Payment\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP клиент для работы с API YooKassa
 */
class YooKassaApiClient
{
    /**
     * @var string API endpoint
     */
    private const API_URL = 'https://api.yookassa.ru/v3';

    /**
     * @var string Shop ID
     */
    private string $shopId;

    /**
     * @var string Secret key
     */
    private string $secretKey;

    public function __construct(string $shopId, string $secretKey)
    {
        $this->shopId = $shopId;
        $this->secretKey = $secretKey;
    }

    /**
     * Создать платеж
     */
    public function createPayment(array $data, string $idempotencyKey): array
    {
        try {
            $response = Http::withBasicAuth($this->shopId, $this->secretKey)
                ->withHeaders([
                    'Idempotence-Key' => $idempotencyKey
                ])
                ->post(self::API_URL . '/payments', $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            $error = $response->json();
            Log::error('YooKassa payment creation failed', [
                'error' => $error,
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'error' => $error['description'] ?? 'Ошибка создания платежа',
                'code' => $error['code'] ?? 'unknown_error'
            ];

        } catch (\Exception $e) {
            Log::error('YooKassa payment creation exception', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Ошибка соединения с платежной системой'
            ];
        }
    }

    /**
     * Получить информацию о платеже
     */
    public function getPayment(string $paymentId): array
    {
        try {
            $response = Http::withBasicAuth($this->shopId, $this->secretKey)
                ->get(self::API_URL . "/payments/{$paymentId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get payment info'
            ];

        } catch (\Exception $e) {
            Log::error('YooKassa payment info error', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Создать возврат
     */
    public function createRefund(array $data, string $idempotencyKey): array
    {
        try {
            $response = Http::withBasicAuth($this->shopId, $this->secretKey)
                ->withHeaders([
                    'Idempotence-Key' => $idempotencyKey
                ])
                ->post(self::API_URL . '/refunds', $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            $error = $response->json();
            return [
                'success' => false,
                'error' => $error['description'] ?? 'Refund failed'
            ];

        } catch (\Exception $e) {
            Log::error('YooKassa refund error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Refund processing failed'
            ];
        }
    }
}