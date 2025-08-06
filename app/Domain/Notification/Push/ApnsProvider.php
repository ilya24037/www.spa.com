<?php

namespace App\Domain\Notification\Push;

use App\Domain\Notification\Models\Notification;
use Illuminate\Support\Facades\Http;

/**
 * Apple Push Notification Service провайдер
 */
class ApnsProvider implements PushProviderInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(string $token, array $payload, Notification $notification): array
    {
        $url = ($this->config['production'] ? $this->config['url'] : $this->config['sandbox_url']) . $token;
        
        $apnsPayload = [
            'aps' => [
                'alert' => [
                    'title' => $payload['title'],
                    'body' => $payload['body'],
                ],
                'sound' => 'default',
                'badge' => $this->getUnreadCount($notification->user_id),
            ]
        ];

        // Добавить custom data
        if (!empty($payload['data'])) {
            $apnsPayload = array_merge($apnsPayload, $payload['data']);
        }

        // Генерировать JWT token для авторизации
        $jwt = $this->generateJwt();
        
        $response = Http::withHeaders([
                'authorization' => 'bearer ' . $jwt,
                'apns-topic' => $this->config['bundle_id'],
                'apns-priority' => $this->getPriority($notification->priority),
                'apns-expiration' => time() + $this->getTtl($notification),
            ])
            ->timeout(30)
            ->post($url, $apnsPayload);

        if (!$response->successful()) {
            $error = $response->json()['reason'] ?? 'APNS error';
            throw new \Exception("APNS error: {$error}");
        }

        return [
            'success' => true,
            'external_id' => $response->header('apns-id'),
            'provider' => 'apns',
            'token' => $token,
        ];
    }

    public function handleCallback(array $data): bool
    {
        // APNS может отправлять feedback о неактивных токенах
        
        if (isset($data['reason']) && in_array($data['reason'], ['BadDeviceToken', 'Unregistered'])) {
            $this->deactivateToken($data['device_token'] ?? null);
        }
        
        return true;
    }

    protected function getPriority(string $priority): string
    {
        return match($priority) {
            'urgent', 'high' => '10',
            'medium', 'low' => '5',
            default => '5',
        };
    }

    protected function getTtl(Notification $notification): int
    {
        if ($notification->expires_at) {
            return max(0, $notification->expires_at->timestamp - time());
        }

        return match($notification->priority) {
            'urgent' => 3600, // 1 час
            'high' => 86400, // 1 день
            'medium' => 259200, // 3 дня
            'low' => 604800, // 1 неделя
            default => 86400,
        };
    }

    protected function getUnreadCount(int $userId): int
    {
        return \App\Domain\Notification\Models\Notification::forUser($userId)
            ->unread()
            ->count();
    }

    protected function generateJwt(): string
    {
        // Упрощенная реализация
        // В реальном проекте используйте библиотеку типа firebase/php-jwt
        
        $header = [
            'alg' => 'ES256',
            'kid' => $this->config['key_id'],
        ];

        $payload = [
            'iss' => $this->config['team_id'],
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        $headerEncoded = $this->base64url_encode(json_encode($header));
        $payloadEncoded = $this->base64url_encode(json_encode($payload));
        
        // Подписать с использованием private key (упрощенно)
        $signature = $this->base64url_encode('signature_placeholder');
        
        return "{$headerEncoded}.{$payloadEncoded}.{$signature}";
    }

    protected function deactivateToken(?string $token): void
    {
        if (!$token) {
            return;
        }

        \App\Domain\User\Models\PushDevice::where('token', $token)
            ->update(['is_active' => false, 'deactivated_at' => now()]);
    }

    protected function base64url_encode($data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}