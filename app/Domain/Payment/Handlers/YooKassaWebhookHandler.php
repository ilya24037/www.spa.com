<?php

namespace App\Domain\Payment\Handlers;

use App\Domain\Payment\Contracts\WebhookHandlerInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик webhook для YooKassa (ЮKassa)
 */
class YooKassaWebhookHandler implements WebhookHandlerInterface
{
    /**
     * Поддерживаемые события
     */
    protected array $supportedEvents = [
        'payment.succeeded',
        'payment.waiting_for_capture',
        'payment.canceled',
        'refund.succeeded',
        'deal.closed',
        'payout.succeeded',
        'payout.canceled',
    ];

    /**
     * Проверить подпись webhook
     */
    public function verifySignature(Request $request): bool
    {
        // YooKassa использует Basic Auth для webhook
        $auth = $request->header('Authorization');
        
        if (!$auth || !str_starts_with($auth, 'Basic ')) {
            return false;
        }

        $credentials = base64_decode(substr($auth, 6));
        [$username, $password] = explode(':', $credentials, 2);

        $expectedUsername = config('services.yookassa.webhook_username');
        $expectedPassword = config('services.yookassa.webhook_password');

        return $username === $expectedUsername && $password === $expectedPassword;
    }

    /**
     * Обработать webhook
     */
    public function handle(Request $request): array
    {
        $event = $request->all();

        // YooKassa отправляет данные в определенном формате
        return [
            'success' => true,
            'event_type' => $event['event'] ?? null,
            'object' => $event['object'] ?? [],
        ];
    }

    /**
     * Получить тип события из webhook
     */
    public function getEventType(Request $request): ?string
    {
        return $request->input('event');
    }

    /**
     * Получить ID платежа из webhook
     */
    public function getPaymentId(Request $request): ?string
    {
        return $request->input('object.id');
    }

    /**
     * Получить статус платежа из webhook
     */
    public function getPaymentStatus(Request $request): ?string
    {
        $status = $request->input('object.status');

        // Преобразовать статус YooKassa в наш формат
        return match($status) {
            'succeeded' => 'completed',
            'waiting_for_capture' => 'processing',
            'pending' => 'pending',
            'canceled' => 'cancelled',
            default => $status,
        };
    }

    /**
     * Поддерживает ли обработчик данный тип события
     */
    public function supportsEvent(string $eventType): bool
    {
        return in_array($eventType, $this->supportedEvents);
    }

    /**
     * Получить название платежного гейтвея
     */
    public function getGatewayName(): string
    {
        return 'yookassa';
    }
}