<?php

namespace App\Infrastructure\Notification\Channels;

use App\Domain\Notification\DTOs\NotificationData;
use Illuminate\Support\Facades\Log;

/**
 * SMS канал для отправки уведомлений
 */
class SmsChannel implements ChannelInterface
{
    public function getName(): string
    {
        return 'sms';
    }

    public function getDescription(): string
    {
        return 'SMS уведомления через мобильную связь';
    }

    public function isEnabled(): bool
    {
        return config('services.sms.enabled', false);
    }

    public function send(NotificationData $notification): bool
    {
        try {
            // Проверяем что SMS сервис включен
            if (!$this->isEnabled()) {
                Log::info('SMS channel is disabled, skipping notification', [
                    'notification_id' => $notification->getId()
                ]);
                return false;
            }

            // Проверяем наличие номера телефона
            if (empty($notification->getRecipient()['phone'])) {
                Log::warning('SMS notification failed: no phone number', [
                    'notification_id' => $notification->getId(),
                    'recipient' => $notification->getRecipient()
                ]);
                return false;
            }

            $phone = $this->normalizePhone($notification->getRecipient()['phone']);
            $message = $this->formatMessage($notification);

            // В production здесь будет реальная отправка SMS
            // Пока логируем для разработки
            if (app()->environment('production')) {
                return $this->sendRealSms($phone, $message, $notification);
            } else {
                return $this->sendTestSms($phone, $message, $notification);
            }

        } catch (\Exception $e) {
            Log::error('SMS notification failed', [
                'notification_id' => $notification->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Отправка реального SMS в production
     */
    protected function sendRealSms(string $phone, string $message, NotificationData $notification): bool
    {
        // Здесь интеграция с реальным SMS провайдером
        // Например: SMSC.ru, SMS.ru, Twilio и т.д.
        
        $provider = config('services.sms.provider', 'smsc');
        
        switch ($provider) {
            case 'smsc':
                return $this->sendViaSmsc($phone, $message, $notification);
            case 'smsru':
                return $this->sendViaSmsRu($phone, $message, $notification);
            case 'twilio':
                return $this->sendViaTwilio($phone, $message, $notification);
            default:
                Log::error('Unknown SMS provider', ['provider' => $provider]);
                return false;
        }
    }

    /**
     * Тестовая отправка SMS в development
     */
    protected function sendTestSms(string $phone, string $message, NotificationData $notification): bool
    {
        Log::info('SMS notification (TEST MODE)', [
            'notification_id' => $notification->getId(),
            'phone' => $phone,
            'message' => $message,
            'title' => $notification->getTitle(),
            'type' => $notification->getType()
        ]);

        return true;
    }

    /**
     * Отправка через SMSC.ru
     */
    protected function sendViaSmsc(string $phone, string $message, NotificationData $notification): bool
    {
        // TODO: Реализовать интеграцию с SMSC.ru
        Log::info('SMS via SMSC.ru', [
            'phone' => $phone,
            'message' => $message
        ]);
        return true;
    }

    /**
     * Отправка через SMS.ru
     */
    protected function sendViaSmsRu(string $phone, string $message, NotificationData $notification): bool
    {
        // TODO: Реализовать интеграцию с SMS.ru
        Log::info('SMS via SMS.ru', [
            'phone' => $phone,
            'message' => $message
        ]);
        return true;
    }

    /**
     * Отправка через Twilio
     */
    protected function sendViaTwilio(string $phone, string $message, NotificationData $notification): bool
    {
        // TODO: Реализовать интеграцию с Twilio
        Log::info('SMS via Twilio', [
            'phone' => $phone,
            'message' => $message
        ]);
        return true;
    }

    /**
     * Нормализация номера телефона
     */
    protected function normalizePhone(string $phone): string
    {
        // Убираем все символы кроме цифр
        $phone = preg_replace('/[^\d]/', '', $phone);
        
        // Добавляем код России если номер начинается с 8
        if (str_starts_with($phone, '8') && strlen($phone) === 11) {
            $phone = '7' . substr($phone, 1);
        }
        
        // Добавляем + для международного формата
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }
        
        return $phone;
    }

    /**
     * Форматирование сообщения для SMS
     */
    protected function formatMessage(NotificationData $notification): string
    {
        $message = $notification->getTitle();
        
        // Добавляем тело сообщения если есть
        if (!empty($notification->getBody())) {
            $message .= "\n" . $notification->getBody();
        }
        
        // Ограничиваем длину SMS (160 символов для стандартного SMS)
        if (mb_strlen($message) > 160) {
            $message = mb_substr($message, 0, 157) . '...';
        }
        
        return $message;
    }

    public function getRequiredData(): array
    {
        return [
            'phone' => 'Номер телефона получателя'
        ];
    }

    public function validateNotification(NotificationData $notification): array
    {
        $errors = [];
        
        $recipient = $notification->getRecipient();
        
        if (empty($recipient['phone'])) {
            $errors[] = 'Отсутствует номер телефона получателя';
        } else {
            $phone = $this->normalizePhone($recipient['phone']);
            if (!preg_match('/^\+\d{10,15}$/', $phone)) {
                $errors[] = 'Неверный формат номера телефона';
            }
        }
        
        if (empty($notification->getTitle())) {
            $errors[] = 'Отсутствует текст сообщения';
        }
        
        return $errors;
    }
}