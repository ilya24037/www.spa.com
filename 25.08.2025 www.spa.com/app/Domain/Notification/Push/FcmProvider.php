<?php

namespace App\Domain\Notification\Push;

use App\Domain\Notification\Models\Notification;
use Illuminate\Support\Facades\Http;

/**
 * Firebase Cloud Messaging провайдер
 */
class FcmProvider implements PushProviderInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(string $token, array $payload, Notification $notification): array
    {
        $data = [
            'to' => $token,
            'notification' => [
                'title' => $payload['title'],
                'body' => $payload['body'],
                'icon' => $payload['icon'] ?? asset('images/notification-icon.png'),
                'click_action' => $payload['click_action'] ?? config('app.url'),
            ],
            'data' => $payload['data'] ?? [],
        ];

        // Добавить collapse_key для группировки похожих уведомлений
        if ($notification->group_key) {
            $data['collapse_key'] = $this->config['collapse_key_prefix'] . $notification->group_key;
        }

        // Добавить priority
        $data['priority'] = $this->getPriority($notification->priority);
        
        // Добавить TTL
        $data['time_to_live'] = $this->getTtl($notification);

        $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->config['server_key'],
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post($this->config['url'], $data);

        if (!$response->successful()) {
            throw new \Exception("FCM API error: " . $response->status());
        }

        $result = $response->json();
        
        if ($result['failure'] > 0) {
            $error = $result['results'][0]['error'] ?? 'Unknown FCM error';
            throw new \Exception("FCM error: {$error}");
        }

        return [
            'success' => true,
            'external_id' => $result['results'][0]['message_id'] ?? null,
            'provider' => 'fcm',
            'token' => $token,
        ];
    }

    public function handleCallback(array $data): bool
    {
        // FCM обычно не отправляет callback о доставке
        // Но может отправлять информацию о неактивных токенах
        
        if (isset($data['results'])) {
            foreach ($data['results'] as $result) {
                if (isset($result['error']) && $result['error'] === 'NotRegistered') {
                    // Деактивировать токен
                    $this->deactivateToken($result['registration_id'] ?? null);
                }
            }
        }
        
        return true;
    }

    protected function getPriority(string $priority): string
    {
        return match($priority) {
            'urgent', 'high' => 'high',
            'medium', 'low' => 'normal',
            default => 'normal',
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

    protected function deactivateToken(?string $token): void
    {
        if (!$token) {
            return;
        }

        \App\Domain\User\Models\PushDevice::where('token', $token)
            ->update(['is_active' => false, 'deactivated_at' => now()]);
    }
}