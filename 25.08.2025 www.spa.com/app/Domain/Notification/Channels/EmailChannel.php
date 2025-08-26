<?php

namespace App\Domain\Notification\Channels;

use App\Domain\Notification\Models\Notification;
use App\Enums\NotificationChannel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Канал email уведомлений
 */
class EmailChannel extends AbstractNotificationChannel
{
    /**
     * {@inheritdoc}
     */
    protected function getChannel(): NotificationChannel
    {
        return NotificationChannel::EMAIL;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => config('mail.default') !== null,
            'from_address' => config('mail.from.address', 'noreply@spa-platform.ru'),
            'from_name' => config('mail.from.name', 'SPA Platform'),
            'rate_limit_per_minute' => 60,
            'rate_limit_per_hour' => 1000,
            'rate_limit_per_day' => 10000,
            'timeout' => 30,
            'log_sends' => true,
            'track_opens' => true,
            'track_clicks' => true,
            'unsubscribe_url' => config('app.url') . '/unsubscribe',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doSend(Notification $notification, array $content): array
    {
        $recipient = $this->getRecipient($notification);
        
        if (!$recipient) {
            throw new \Exception('No email address found for notification');
        }

        try {
            // Подготовить данные для письма
            $mailData = $this->prepareMailData($notification, $content);
            
            // Отправить письмо
            Mail::send('emails.notification', $mailData, function($message) use ($recipient, $content, $notification) {
                $message->to($recipient)
                       ->subject($content['subject'] ?? $content['title'] ?? 'Уведомление')
                       ->from($this->config['from_address'], $this->config['from_name']);
                
                // Добавить заголовки для отслеживания
                if ($this->config['track_opens']) {
                    $message->getHeaders()->addTextHeader('X-Track-Opens', 'true');
                }
                
                if ($this->config['track_clicks']) {
                    $message->getHeaders()->addTextHeader('X-Track-Clicks', 'true');
                }
                
                // Добавить заголовок для отписки
                if ($this->config['unsubscribe_url']) {
                    $unsubscribeUrl = $this->config['unsubscribe_url'] . '?token=' . $this->generateUnsubscribeToken($notification);
                    $message->getHeaders()->addTextHeader('List-Unsubscribe', "<{$unsubscribeUrl}>");
                }
                
                // Добавить приоритет
                $priority = $this->getEmailPriority($notification->priority);
                if ($priority) {
                    $message->getHeaders()->addTextHeader('X-Priority', $priority);
                }
            });

            return [
                'success' => true,
                'external_id' => $this->generateMessageId($notification),
                'recipient' => $recipient,
                'subject' => $content['subject'] ?? $content['title'],
            ];

        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'notification_id' => $notification->id,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);
            
            throw new \Exception("Failed to send email: {$e->getMessage()}");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getRecipient(Notification $notification): ?string
    {
        // Проверяем в данных уведомления
        if (!empty($notification->data['email'])) {
            return $notification->data['email'];
        }
        
        // Проверяем email пользователя
        if ($notification->user && !empty($notification->user->email)) {
            return $notification->user->email;
        }
        
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareContent(Notification $notification): array
    {
        $content = parent::prepareContent($notification);
        
        // Добавляем специфичные для email поля
        $content['subject'] = $content['subject'] ?? $content['title'];
        $content['preheader'] = $this->generatePreheader($content['message']);
        $content['unsubscribe_url'] = $this->config['unsubscribe_url'] . '?token=' . $this->generateUnsubscribeToken($notification);
        $content['view_online_url'] = route('notifications.view-online', $notification->id);
        
        return $content;
    }

    /**
     * Подготовить данные для шаблона письма
     */
    private function prepareMailData(Notification $notification, array $content): array
    {
        return [
            'notification' => $notification,
            'title' => $content['title'],
            'subject' => $content['subject'],
            'message' => $content['message'],
            'content' => $content['content'] ?? $content['message'],
            'preheader' => $content['preheader'],
            'user' => $notification->user,
            'data' => $notification->data ?? [],
            'action_url' => $notification->getActionUrl(),
            'action_text' => $notification->getActionText(),
            'unsubscribe_url' => $content['unsubscribe_url'],
            'view_online_url' => $content['view_online_url'],
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'support_email' => config('mail.support_address', 'support@spa-platform.ru'),
            'logo_url' => asset('images/logo-email.png'),
            'social_links' => [
                'vk' => 'https://vk.com/spa_platform',
                'telegram' => 'https://t.me/spa_platform',
                'instagram' => 'https://instagram.com/spa_platform',
            ],
        ];
    }

    /**
     * Генерировать preheader для письма
     */
    private function generatePreheader(string $message): string
    {
        // Берем первые 100 символов сообщения, убираем HTML теги
        $preheader = strip_tags($message);
        $preheader = mb_substr($preheader, 0, 100);
        
        // Добавляем точки, если обрезали
        if (mb_strlen($message) > 100) {
            $preheader .= '...';
        }
        
        return $preheader;
    }

    /**
     * Генерировать токен для отписки
     */
    private function generateUnsubscribeToken(Notification $notification): string
    {
        return hash_hmac('sha256', 
            $notification->user_id . ':' . $notification->type->value,
            config('app.key')
        );
    }

    /**
     * Генерировать ID сообщения
     */
    private function generateMessageId(Notification $notification): string
    {
        return 'notification-' . $notification->id . '-' . time() . '@' . parse_url(config('app.url'), PHP_URL_HOST);
    }

    /**
     * Получить приоритет для email заголовка
     */
    private function getEmailPriority(string $priority): ?string
    {
        return match($priority) {
            'urgent', 'high' => '1',
            'medium' => '3',
            'low' => '5',
            default => null,
        };
    }

    /**
     * {@inheritdoc}
     */
    public function handleCallback(array $data): bool
    {
        // Обработка webhook от email провайдера (например, Mailgun, SendGrid)
        $eventType = $data['event'] ?? $data['event-type'] ?? null;
        
        if (!$eventType) {
            return false;
        }

        $messageId = $data['message-id'] ?? $data['sg_message_id'] ?? null;
        
        if (!$messageId) {
            return false;
        }

        // Найти доставку по external_id
        $delivery = \App\Domain\Notification\Models\NotificationDelivery::where('external_id', $messageId)
            ->where('channel', $this->channel)
            ->first();

        if (!$delivery) {
            Log::warning('Email callback: delivery not found', [
                'message_id' => $messageId,
                'event' => $eventType,
            ]);
            return false;
        }

        // Обработать событие
        switch ($eventType) {
            case 'delivered':
                $delivery->markAsDelivered();
                Log::info('Email delivered', ['delivery_id' => $delivery->id]);
                break;
                
            case 'opened':
                // Сохранить информацию об открытии
                $delivery->update([
                    'metadata' => array_merge($delivery->metadata ?? [], [
                        'opened_at' => now()->toISOString(),
                        'user_agent' => $data['user-agent'] ?? null,
                        'client_info' => $data['client-info'] ?? null,
                    ])
                ]);
                Log::info('Email opened', ['delivery_id' => $delivery->id]);
                break;
                
            case 'clicked':
                // Сохранить информацию о клике
                $delivery->update([
                    'metadata' => array_merge($delivery->metadata ?? [], [
                        'clicked_at' => now()->toISOString(),
                        'clicked_url' => $data['url'] ?? null,
                        'user_agent' => $data['user-agent'] ?? null,
                    ])
                ]);
                Log::info('Email clicked', ['delivery_id' => $delivery->id]);
                break;
                
            case 'bounced':
            case 'dropped':
            case 'failed':
                $reason = $data['reason'] ?? $data['description'] ?? 'Email delivery failed';
                $delivery->markAsFailed($reason);
                Log::warning('Email failed', [
                    'delivery_id' => $delivery->id,
                    'reason' => $reason,
                ]);
                break;
                
            case 'complained':
            case 'unsubscribed':
                // Обработать жалобу или отписку
                $this->handleUnsubscribe($delivery->notification->user, $eventType);
                Log::info('Email complaint/unsubscribe', [
                    'delivery_id' => $delivery->id,
                    'event' => $eventType,
                ]);
                break;
        }

        return true;
    }

    /**
     * Обработать отписку пользователя
     */
    private function handleUnsubscribe($user, string $eventType): void
    {
        if (!$user) {
            return;
        }

        // Отключить email уведомления для пользователя
        $user->notification_preferences = array_merge(
            $user->notification_preferences ?? [],
            ['email' => false]
        );
        $user->save();

        // Создать запись об отписке
        \App\Domain\Notification\Models\NotificationUnsubscribe::create([
            'user_id' => $user->id,
            'channel' => $this->channel,
            'reason' => $eventType,
            'unsubscribed_at' => now(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryStatus(NotificationDelivery $delivery): array
    {
        $status = parent::getDeliveryStatus($delivery);
        
        // Добавить email-специфичную информацию
        $metadata = $delivery->metadata ?? [];
        
        $status['opened_at'] = $metadata['opened_at'] ?? null;
        $status['clicked_at'] = $metadata['clicked_at'] ?? null;
        $status['clicked_url'] = $metadata['clicked_url'] ?? null;
        $status['bounce_reason'] = $delivery->failure_reason;
        
        return $status;
    }

    /**
     * Проверить, может ли пользователь получать email уведомления
     */
    protected function canSendToUser($user): bool
    {
        if (!$user) {
            return false;
        }

        // Проверить, не отписался ли пользователь
        $unsubscribed = \App\Domain\Notification\Models\NotificationUnsubscribe::where('user_id', $user->id)
            ->where('channel', $this->channel)
            ->exists();

        if ($unsubscribed) {
            return false;
        }

        // Проверить настройки уведомлений пользователя
        $preferences = $user->notification_preferences ?? [];
        
        return $preferences['email'] ?? true;
    }

    /**
     * {@inheritdoc}
     */
    public function canSend(Notification $notification): bool
    {
        if (!parent::canSend($notification)) {
            return false;
        }

        // Проверить, может ли пользователь получать email
        return $this->canSendToUser($notification->user);
    }
}