<?php

namespace App\Domain\Notification\Channels;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Push\FcmProvider;
use App\Domain\Notification\Push\ApnsProvider;
use App\Domain\Notification\Push\PushPayloadBuilder;
use App\Domain\Notification\Push\PushProviderInterface;
use App\Enums\NotificationChannel;
use Illuminate\Support\Facades\Log;

/**
 * Упрощенный канал Push уведомлений - делегирует работу специализированным провайдерам
 */
class PushChannel extends AbstractNotificationChannel
{
    protected array $providers = [];
    protected PushPayloadBuilder $payloadBuilder;

    public function __construct()
    {
        parent::__construct();
        
        $this->payloadBuilder = new PushPayloadBuilder();
        $this->initializeProviders();
    }

    /**
     * {@inheritdoc}
     */
    protected function getChannel(): NotificationChannel
    {
        return NotificationChannel::PUSH;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => config('push.enabled', true),
            'provider' => config('push.default', 'fcm'),
            'rate_limit_per_minute' => 300,
            'rate_limit_per_hour' => 5000,
            'rate_limit_per_day' => 50000,
            'timeout' => 30,
            'log_sends' => true,
            'batch_size' => 100,
            'providers' => [
                'fcm' => [
                    'url' => 'https://fcm.googleapis.com/fcm/send',
                    'server_key' => config('push.providers.fcm.server_key'),
                    'project_id' => config('push.providers.fcm.project_id'),
                    'collapse_key_prefix' => 'spa_notification_',
                ],
                'apns' => [
                    'url' => 'https://api.push.apple.com/3/device/',
                    'sandbox_url' => 'https://api.sandbox.push.apple.com/3/device/',
                    'key_id' => config('push.providers.apns.key_id'),
                    'team_id' => config('push.providers.apns.team_id'),
                    'bundle_id' => config('push.providers.apns.bundle_id'),
                    'private_key' => config('push.providers.apns.private_key'),
                    'production' => config('push.providers.apns.production', false),
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doSend(Notification $notification, array $content): array
    {
        $tokens = $this->getDeviceTokens($notification);
        
        if (empty($tokens)) {
            throw new \Exception('No device tokens found for push notification');
        }

        // Подготовить payload
        $payload = $this->payloadBuilder->build($notification, $content);
        
        // Отправить через выбранного провайдера
        $provider = $this->getProvider($this->config['provider']);
        $results = [];

        foreach ($tokens as $token) {
            try {
                $result = $provider->send($token, $payload, $notification);
                $results[] = $result;
            } catch (\Exception $e) {
                Log::error("Push notification failed for token", [
                    'token' => substr($token, 0, 10) . '...',
                    'error' => $e->getMessage(),
                ]);
                
                $results[] = [
                    'success' => false,
                    'token' => $token,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Вернуть сводный результат
        $successful = array_filter($results, fn($r) => $r['success']);
        
        return [
            'success' => !empty($successful),
            'total_sent' => count($successful),
            'total_failed' => count($results) - count($successful),
            'external_ids' => array_column($successful, 'external_id'),
            'results' => $results,
        ];
    }

    /**
     * Получить токены устройств пользователя
     */
    protected function getDeviceTokens(Notification $notification): array
    {
        if (!$notification->user) {
            return [];
        }

        // Получить активные токены из базы данных
        $devices = $notification->user->pushDevices()
            ->where('is_active', true)
            ->get();

        return $devices->pluck('token')->toArray();
    }

    /**
     * Инициализировать провайдеров
     */
    protected function initializeProviders(): void
    {
        $this->providers['fcm'] = new FcmProvider($this->config['providers']['fcm']);
        $this->providers['apns'] = new ApnsProvider($this->config['providers']['apns']);
    }

    /**
     * Получить провайдера
     */
    protected function getProvider(string $name): PushProviderInterface
    {
        if (!isset($this->providers[$name])) {
            throw new \Exception("Unsupported push provider: {$name}");
        }

        return $this->providers[$name];
    }

    /**
     * {@inheritdoc}
     */
    protected function getRecipient(Notification $notification): ?string
    {
        return $notification->user?->name ?? "User {$notification->user_id}";
    }

    /**
     * {@inheritdoc}
     */
    public function handleCallback(array $data): bool
    {
        $provider = $this->getProvider($this->config['provider']);
        return $provider->handleCallback($data);
    }

    /**
     * Проверить, может ли пользователь получать push уведомления
     */
    protected function canSendToUser($user): bool
    {
        if (!$user) {
            return false;
        }

        // Проверить настройки уведомлений пользователя
        $preferences = $user->notification_preferences ?? [];
        
        return $preferences['push'] ?? true;
    }

    /**
     * {@inheritdoc}
     */
    public function canSend(Notification $notification): bool
    {
        if (!parent::canSend($notification)) {
            return false;
        }

        // Проверить, может ли пользователь получать push
        return $this->canSendToUser($notification->user);
    }
}