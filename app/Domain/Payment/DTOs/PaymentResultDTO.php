<?php

namespace App\Domain\Payment\DTOs;

use App\Domain\Payment\Models\Payment;
use App\Enums\PaymentStatus;

/**
 * DTO для результата обработки платежа
 */
class PaymentResultDTO
{
    public function __construct(
        public bool $success,
        public ?Payment $payment = null,
        public ?string $message = null,
        public ?string $errorCode = null,
        public array $data = [],
        public ?string $redirectUrl = null,
        public ?string $transactionId = null,
        public array $gatewayResponse = [],
        public ?float $processedAmount = null,
        public ?string $receiptUrl = null,
    ) {}

    /**
     * Создать успешный результат
     */
    public static function success(
        Payment $payment,
        ?string $message = null,
        array $data = [],
        ?string $transactionId = null,
        ?string $receiptUrl = null
    ): self {
        return new self(
            success: true,
            payment: $payment,
            message: $message ?? 'Payment processed successfully',
            data: $data,
            transactionId: $transactionId,
            processedAmount: $payment->amount,
            receiptUrl: $receiptUrl,
        );
    }

    /**
     * Создать результат с ошибкой
     */
    public static function failure(
        ?Payment $payment = null,
        ?string $message = null,
        ?string $errorCode = null,
        array $data = [],
        array $gatewayResponse = []
    ): self {
        return new self(
            success: false,
            payment: $payment,
            message: $message ?? 'Payment processing failed',
            errorCode: $errorCode,
            data: $data,
            gatewayResponse: $gatewayResponse,
        );
    }

    /**
     * Создать результат требующий редиректа
     */
    public static function redirect(
        Payment $payment,
        string $redirectUrl,
        ?string $message = null,
        array $data = []
    ): self {
        return new self(
            success: true,
            payment: $payment,
            message: $message ?? 'Redirect required',
            data: $data,
            redirectUrl: $redirectUrl,
        );
    }

    /**
     * Создать результат ожидания
     */
    public static function pending(
        Payment $payment,
        ?string $message = null,
        array $data = []
    ): self {
        return new self(
            success: true,
            payment: $payment,
            message: $message ?? 'Payment is pending',
            data: $data,
        );
    }

    /**
     * Создать из ответа шлюза
     */
    public static function fromGatewayResponse(
        Payment $payment,
        array $gatewayResponse,
        ?string $transactionId = null
    ): self {
        $success = $gatewayResponse['success'] ?? false;
        $message = $gatewayResponse['message'] ?? null;
        $errorCode = $gatewayResponse['error_code'] ?? null;
        $redirectUrl = $gatewayResponse['redirect_url'] ?? null;
        $receiptUrl = $gatewayResponse['receipt_url'] ?? null;

        return new self(
            success: $success,
            payment: $payment,
            message: $message,
            errorCode: $errorCode,
            data: $gatewayResponse['data'] ?? [],
            redirectUrl: $redirectUrl,
            transactionId: $transactionId ?? $gatewayResponse['transaction_id'] ?? null,
            gatewayResponse: $gatewayResponse,
            processedAmount: $gatewayResponse['amount'] ?? $payment->amount,
            receiptUrl: $receiptUrl,
        );
    }

    /**
     * Преобразовать в массив
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'payment' => $this->payment ? [
                'id' => $this->payment->id,
                'payment_number' => $this->payment->payment_number,
                'status' => $this->payment->status->value,
                'amount' => $this->payment->amount,
                'currency' => $this->payment->currency,
            ] : null,
            'message' => $this->message,
            'error_code' => $this->errorCode,
            'data' => $this->data,
            'redirect_url' => $this->redirectUrl,
            'transaction_id' => $this->transactionId,
            'processed_amount' => $this->processedAmount,
            'receipt_url' => $this->receiptUrl,
            'gateway_response' => $this->gatewayResponse,
        ];
    }

    /**
     * Преобразовать в JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Получить статус для API ответа
     */
    public function getHttpStatus(): int
    {
        if ($this->success) {
            return 200;
        }

        return match($this->errorCode) {
            'insufficient_funds', 'invalid_amount' => 422,
            'unauthorized', 'invalid_credentials' => 401,
            'forbidden', 'blocked' => 403,
            'not_found' => 404,
            'timeout', 'network_error' => 408,
            'rate_limit' => 429,
            'gateway_error', 'external_error' => 502,
            'maintenance' => 503,
            default => 400,
        };
    }

    /**
     * Проверить требуется ли редирект
     */
    public function requiresRedirect(): bool
    {
        return !empty($this->redirectUrl);
    }

    /**
     * Проверить является ли платеж завершенным
     */
    public function isCompleted(): bool
    {
        return $this->success && 
               $this->payment && 
               $this->payment->status === PaymentStatus::COMPLETED;
    }

    /**
     * Проверить находится ли платеж в ожидании
     */
    public function isPending(): bool
    {
        return $this->success && 
               $this->payment && 
               in_array($this->payment->status, [
                   PaymentStatus::PENDING,
                   PaymentStatus::PROCESSING
               ]);
    }

    /**
     * Получить детальную информацию об ошибке
     */
    public function getErrorDetails(): array
    {
        if ($this->success) {
            return [];
        }

        return [
            'error_code' => $this->errorCode,
            'message' => $this->message,
            'payment_id' => $this->payment?->id,
            'gateway_response' => $this->gatewayResponse,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Получить информацию для уведомления пользователя
     */
    public function getNotificationData(): array
    {
        return [
            'success' => $this->success,
            'title' => $this->getNotificationTitle(),
            'message' => $this->getNotificationMessage(),
            'type' => $this->success ? 'success' : 'error',
            'payment_id' => $this->payment?->id,
            'amount' => $this->processedAmount,
            'receipt_url' => $this->receiptUrl,
        ];
    }

    /**
     * Получить заголовок уведомления
     */
    protected function getNotificationTitle(): string
    {
        if ($this->success) {
            if ($this->isPending()) {
                return 'Платеж в обработке';
            }
            return 'Платеж успешен';
        }

        return 'Ошибка платежа';
    }

    /**
     * Получить сообщение уведомления
     */
    protected function getNotificationMessage(): string
    {
        if ($this->success) {
            if ($this->isPending()) {
                return 'Ваш платеж принят и находится в обработке';
            }
            return 'Платеж успешно обработан';
        }

        return $this->message ?? 'Произошла ошибка при обработке платежа';
    }

    /**
     * Добавить данные
     */
    public function withData(array $data): self
    {
        $clone = clone $this;
        $clone->data = array_merge($this->data, $data);
        return $clone;
    }

    /**
     * Установить URL чека
     */
    public function withReceiptUrl(string $receiptUrl): self
    {
        $clone = clone $this;
        $clone->receiptUrl = $receiptUrl;
        return $clone;
    }

    /**
     * Установить ID транзакции
     */
    public function withTransactionId(string $transactionId): self
    {
        $clone = clone $this;
        $clone->transactionId = $transactionId;
        return $clone;
    }
}