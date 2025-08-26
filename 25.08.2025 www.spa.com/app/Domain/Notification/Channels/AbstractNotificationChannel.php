<?php

namespace App\Domain\Notification\Channels;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Models\NotificationDelivery;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Базовый класс для каналов уведомлений
 */
abstract class AbstractNotificationChannel implements NotificationChannelInterface
{
    /**
     * Конфигурация канала
     */
    protected array $config;

    /**
     * Канал уведомлений
     */
    protected NotificationChannel $channel;

    /**
     * Максимальное количество попыток отправки
     */
    protected int $maxRetries = 3;

    /**
     * Таймаут для отправки (секунды)
     */
    protected int $timeout = 30;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->channel = $this->getChannel();
    }

    /**
     * Получить канал уведомлений
     */
    abstract protected function getChannel(): NotificationChannel;

    /**
     * Получить конфигурацию по умолчанию
     */
    abstract protected function getDefaultConfig(): array;

    /**
     * Выполнить отправку уведомления
     */
    abstract protected function doSend(Notification $notification, array $content): array;

    /**
     * {@inheritdoc}
     */
    public function send(Notification $notification): NotificationDelivery
    {
        // Создать запись о доставке
        $delivery = $this->createDelivery($notification);

        try {
            // Проверить лимиты
            if (!$this->checkRateLimits($notification)) {
                throw new \Exception('Rate limit exceeded');
            }

            // Проверить возможность отправки
            if (!$this->canSend($notification)) {
                throw new \Exception('Cannot send notification via this channel');
            }

            // Подготовить контент
            $content = $this->prepareContent($notification);

            // Отправить уведомление
            $result = $this->doSend($notification, $content);

            // Обновить статус доставки
            $delivery->markAsSent($result['external_id'] ?? null);
            $delivery->update([
                'content' => $content,
                'metadata' => array_merge($delivery->metadata ?? [], [
                    'send_result' => $result,
                    'sent_via' => $this->getChannelId(),
                ])
            ]);

            Log::info("Notification sent via {$this->getChannelName()}", [
                'notification_id' => $notification->id,
                'delivery_id' => $delivery->id,
                'channel' => $this->getChannelId(),
            ]);

        } catch (\Exception $e) {
            // Пометить как неудачную
            $delivery->markAsFailed($e->getMessage());
            $delivery->update([
                'metadata' => array_merge($delivery->metadata ?? [], [
                    'failure_details' => [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ],
                ])
            ]);

            Log::error("Failed to send notification via {$this->getChannelName()}", [
                'notification_id' => $notification->id,
                'delivery_id' => $delivery->id,
                'channel' => $this->getChannelId(),
                'error' => $e->getMessage(),
            ]);

            // Перебросить исключение, если это не последняя попытка
            if ($delivery->canRetry()) {
                throw $e;
            }
        }

        return $delivery;
    }

    /**
     * {@inheritdoc}
     */
    public function canSend(Notification $notification): bool
    {
        // Проверить, поддерживает ли уведомление этот канал
        if (!$notification->shouldSendViaChannel($this->channel)) {
            return false;
        }

        // Проверить доступность канала
        if (!$this->isAvailable()) {
            return false;
        }

        // Проверить, есть ли получатель для этого канала
        return $this->hasRecipient($notification);
    }

    /**
     * {@inheritdoc}
     */
    public function getChannelId(): string
    {
        return $this->channel->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getChannelName(): string
    {
        return $this->channel->getLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return $this->config['enabled'] ?? true;
    }

    /**
     * {@inheritdoc}
     */
    public function getRateLimits(): array
    {
        return [
            'per_minute' => $this->config['rate_limit_per_minute'] ?? 60,
            'per_hour' => $this->config['rate_limit_per_hour'] ?? 1000,
            'per_day' => $this->config['rate_limit_per_day'] ?? 10000,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function prepareContent(Notification $notification): array
    {
        $content = [
            'title' => $notification->getDisplayTitle(),
            'message' => $notification->getDisplayMessage(),
            'data' => $notification->data ?? [],
        ];

        // Применить шаблон, если указан
        if ($notification->template) {
            $template = \App\Domain\Notification\Models\NotificationTemplate::where('name', $notification->template)
                ->where('locale', $notification->locale)
                ->active()
                ->first();

            if ($template && $template->supportsChannel($this->channel)) {
                $rendered = $template->render($notification->data ?? []);
                $content = array_merge($content, $rendered);
            }
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function handleCallback(array $data): bool
    {
        // Базовая реализация - просто возвращаем true
        // Конкретные каналы должны переопределить этот метод
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryStatus(NotificationDelivery $delivery): array
    {
        // Базовая реализация - возвращаем текущий статус
        return [
            'status' => $delivery->status->value,
            'delivered_at' => $delivery->delivered_at?->toISOString(),
            'failed_at' => $delivery->failed_at?->toISOString(),
            'failure_reason' => $delivery->failure_reason,
        ];
    }

    /**
     * Создать запись о доставке
     */
    protected function createDelivery(Notification $notification): NotificationDelivery
    {
        return NotificationDelivery::create([
            'notification_id' => $notification->id,
            'channel' => $this->channel,
            'status' => NotificationStatus::PENDING,
            'recipient' => $this->getRecipient($notification),
            'max_retries' => $this->maxRetries,
        ]);
    }

    /**
     * Проверить лимиты скорости
     */
    protected function checkRateLimits(Notification $notification): bool
    {
        $limits = $this->getRateLimits();
        $key = "notification_channel:{$this->getChannelId()}";

        foreach ($limits as $period => $limit) {
            $periodKey = "{$key}:{$period}";
            
            if (RateLimiter::tooManyAttempts($periodKey, $limit)) {
                return false;
            }
            
            RateLimiter::increment($periodKey);
        }

        return true;
    }

    /**
     * Проверить, есть ли получатель для этого канала
     */
    protected function hasRecipient(Notification $notification): bool
    {
        $recipient = $this->getRecipient($notification);
        return !empty($recipient);
    }

    /**
     * Получить получателя для канала
     */
    protected function getRecipient(Notification $notification): ?string
    {
        // Базовая реализация - должна быть переопределена в конкретных каналах
        return $notification->user->email ?? null;
    }

    /**
     * Логировать отправку
     */
    protected function logSend(Notification $notification, array $content, array $result): void
    {
        if ($this->config['log_sends'] ?? false) {
            Log::channel('notifications')->info("Notification sent", [
                'notification_id' => $notification->id,
                'channel' => $this->getChannelId(),
                'recipient' => $this->getRecipient($notification),
                'content_length' => strlen(json_encode($content)),
                'result' => $result,
            ]);
        }
    }

    /**
     * Логировать ошибку
     */
    protected function logError(Notification $notification, \Exception $e): void
    {
        Log::channel('notifications')->error("Notification send failed", [
            'notification_id' => $notification->id,
            'channel' => $this->getChannelId(),
            'recipient' => $this->getRecipient($notification),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Получить HTTP клиент с настройками
     */
    protected function getHttpClient(): \Illuminate\Http\Client\PendingRequest
    {
        return \Illuminate\Support\Facades\Http::timeout($this->timeout)
            ->retry(3, 100)
            ->withHeaders([
                'User-Agent' => 'SPA-Platform-Notifications/1.0',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);
    }

    /**
     * Замаскировать чувствительные данные для логов
     */
    protected function maskSensitiveData(array $data): array
    {
        $sensitive = ['password', 'token', 'api_key', 'secret', 'authorization'];
        
        foreach ($sensitive as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***masked***';
            }
        }
        
        return $data;
    }
}