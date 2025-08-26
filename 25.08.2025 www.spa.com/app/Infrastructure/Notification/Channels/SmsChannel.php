<?php

namespace App\Infrastructure\Notification\Channels;

use App\Domain\Notification\Models\NotificationDelivery;
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

    /**
     * Отправить уведомление
     */
    public function send(NotificationDelivery $delivery): array
    {
        try {
            // Проверяем что SMS сервис включен
            if (!$this->isEnabled()) {
                return [
                    'success' => false,
                    'error' => 'SMS channel is disabled'
                ];
            }

            $content = $delivery->content;
            $recipient = $delivery->recipient;

            // Проверяем наличие номера телефона
            if (empty($recipient)) {
                return [
                    'success' => false,
                    'error' => 'No phone number provided'
                ];
            }

            $phone = $this->normalizePhone($recipient);
            $message = $this->formatMessage($content);

            // Отправляем SMS
            $result = $this->sendSms($phone, $message, $delivery);

            if ($result) {
                Log::info('SMS notification sent successfully', [
                    'delivery_id' => $delivery->id,
                    'phone' => $this->maskPhone($phone)
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'external_id' => 'sms_' . time(),
                    'delivery_time' => rand(1, 10),
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'SMS sending failed'
                ];
            }

        } catch (\Exception $e) {
            Log::error('SMS notification failed', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Отправка SMS сообщения
     */
    protected function sendSms(string $phone, string $message, NotificationDelivery $delivery): bool
    {
        $provider = config('services.sms.provider', 'test');
        
        switch ($provider) {
            case 'smsc':
                return $this->sendViaSmsc($phone, $message, $delivery);
            case 'smsru':
                return $this->sendViaSmsRu($phone, $message, $delivery);
            case 'twilio':
                return $this->sendViaTwilio($phone, $message, $delivery);
            case 'test':
            default:
                return $this->sendTestSms($phone, $message, $delivery);
        }
    }

    /**
     * Тестовая отправка SMS в development
     */
    protected function sendTestSms(string $phone, string $message, NotificationDelivery $delivery): bool
    {
        Log::info('📱 SMS notification (TEST MODE)', [
            'delivery_id' => $delivery->id,
            'phone' => $this->maskPhone($phone),
            'message' => $message,
        ]);

        if (config('app.debug')) {
            Log::channel('single')->info("📱 SMS TO: {$this->maskPhone($phone)}\nMESSAGE:\n{$message}");
        }

        return true;
    }

    /**
     * Отправка через SMSC.ru
     */
    protected function sendViaSmsc(string $phone, string $message, NotificationDelivery $delivery): bool
    {
        // TODO: Реализовать интеграцию с SMSC.ru
        Log::info('SMS via SMSC.ru', [
            'delivery_id' => $delivery->id,
            'phone' => $this->maskPhone($phone),
            'message' => mb_substr($message, 0, 50) . '...'
        ]);
        return true;
    }

    /**
     * Отправка через SMS.ru
     */
    protected function sendViaSmsRu(string $phone, string $message, NotificationDelivery $delivery): bool
    {
        // TODO: Реализовать интеграцию с SMS.ru
        Log::info('SMS via SMS.ru', [
            'delivery_id' => $delivery->id,
            'phone' => $this->maskPhone($phone),
            'message' => mb_substr($message, 0, 50) . '...'
        ]);
        return true;
    }

    /**
     * Отправка через Twilio
     */
    protected function sendViaTwilio(string $phone, string $message, NotificationDelivery $delivery): bool
    {
        // TODO: Реализовать интеграцию с Twilio
        Log::info('SMS via Twilio', [
            'delivery_id' => $delivery->id,
            'phone' => $this->maskPhone($phone),
            'message' => mb_substr($message, 0, 50) . '...'
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
    protected function formatMessage(array $content): string
    {
        $title = $content['title'] ?? '';
        $message = $content['message'] ?? '';
        
        // Объединяем заголовок и сообщение
        if (!empty($message) && $message !== $title) {
            $text = $title . "\n" . $message;
        } else {
            $text = $title ?: $message;
        }
        
        // Ограничиваем длину SMS (160 символов для стандартного SMS)
        $maxLength = config('services.sms.max_length', 160);
        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength - 3) . '...';
        }
        
        return $text;
    }

    /**
     * Маскировка номера телефона для логов
     */
    protected function maskPhone(string $phone): string
    {
        if (strlen($phone) <= 4) {
            return $phone;
        }
        
        return substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 6) . substr($phone, -3);
    }

    // === МЕТОДЫ ИНТЕРФЕЙСА ===

    public function isAvailable(): bool
    {
        return $this->isEnabled() && !empty(config('services.sms.provider'));
    }

    public function getMaxDeliveryTime(): int
    {
        return 30; // 30 секунд для SMS
    }

    public function supportsDeliveryConfirmation(): bool
    {
        return false; // SMS обычно не поддерживает подтверждение доставки в базовой версии
    }
}