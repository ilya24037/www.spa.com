<?php

namespace App\Domain\Notification\Channels;

use App\Domain\Notification\DTOs\NotificationDTO;
use App\Domain\Notification\Enums\NotificationStatus;
use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Events\NotificationSent;
use App\Domain\Notification\Events\NotificationFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

/**
 * SMS канал для отправки уведомлений (Domain слой)
 */
class SmsChannel extends AbstractNotificationChannel
{
    protected string $channelName = 'sms';
    protected NotificationChannel $channelType = NotificationChannel::SMS;

    public function send(NotificationDTO $notification): bool
    {
        try {
            $this->logSending($notification);

            // Проверяем что SMS канал включен
            if (!$this->isEnabled()) {
                $this->logInfo('SMS channel is disabled', $notification);
                return false;
            }

            // Валидация данных
            $validation = $this->validateNotificationData($notification);
            if (!$validation['valid']) {
                $this->logValidationErrors($validation['errors'], $notification);
                return false;
            }

            // Получаем данные получателя
            $recipientData = $notification->getRecipientData();
            $phone = $this->normalizePhone($recipientData['phone'] ?? '');
            
            if (empty($phone)) {
                $this->logError('No valid phone number provided', $notification);
                return false;
            }

            // Форматируем сообщение
            $message = $this->formatSmsMessage($notification);
            
            // Отправляем SMS
            $result = $this->sendSms($phone, $message, $notification);
            
            if ($result) {
                $this->logSuccess($notification);
                Event::dispatch(new NotificationSent($notification, $this->channelType));
                return true;
            } else {
                $this->logError('SMS sending failed', $notification);
                Event::dispatch(new NotificationFailed($notification, $this->channelType, 'SMS sending failed'));
                return false;
            }

        } catch (\Exception $e) {
            $this->logException($e, $notification);
            Event::dispatch(new NotificationFailed($notification, $this->channelType, $e->getMessage()));
            return false;
        }
    }

    /**
     * Отправка SMS сообщения
     */
    protected function sendSms(string $phone, string $message, NotificationDTO $notification): bool
    {
        $provider = config('services.sms.provider', 'test');
        
        switch ($provider) {
            case 'smsc':
                return $this->sendViaSmsc($phone, $message, $notification);
            case 'smsru':
                return $this->sendViaSmsRu($phone, $message, $notification);
            case 'twilio':
                return $this->sendViaTwilio($phone, $message, $notification);
            case 'test':
            default:
                return $this->sendTestSms($phone, $message, $notification);
        }
    }

    /**
     * Тестовая отправка SMS
     */
    protected function sendTestSms(string $phone, string $message, NotificationDTO $notification): bool
    {
        Log::info('📱 SMS Notification (TEST MODE)', [
            'notification_id' => $notification->getId(),
            'phone' => $phone,
            'message' => $message,
            'title' => $notification->getTitle(),
            'type' => $notification->getType()->value,
            'priority' => $notification->getPriority()->value
        ]);

        // Имитируем задержку отправки
        if (config('app.debug')) {
            usleep(100000); // 0.1 секунда
        }

        return true;
    }

    /**
     * Отправка через SMSC.ru
     */
    protected function sendViaSmsc(string $phone, string $message, NotificationDTO $notification): bool
    {
        $login = config('services.sms.smsc.login');
        $password = config('services.sms.smsc.password');
        
        if (empty($login) || empty($password)) {
            Log::error('SMSC credentials not configured');
            return false;
        }

        // API SMSC.ru
        $url = 'https://smsc.ru/sys/send.php';
        $data = [
            'login' => $login,
            'psw' => $password,
            'phones' => $phone,
            'mes' => $message,
            'fmt' => 3, // JSON ответ
            'charset' => 'utf-8'
        ];

        try {
            $response = $this->makeHttpRequest($url, $data);
            $result = json_decode($response, true);
            
            if (isset($result['error'])) {
                Log::error('SMSC API error', ['error' => $result['error']]);
                return false;
            }
            
            if (isset($result['id'])) {
                Log::info('SMS sent via SMSC', ['sms_id' => $result['id'], 'phone' => $phone]);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('SMSC request failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Отправка через SMS.ru
     */
    protected function sendViaSmsRu(string $phone, string $message, NotificationDTO $notification): bool
    {
        $apiId = config('services.sms.smsru.api_id');
        
        if (empty($apiId)) {
            Log::error('SMS.ru API ID not configured');
            return false;
        }

        $url = 'https://sms.ru/sms/send';
        $data = [
            'api_id' => $apiId,
            'to' => $phone,
            'msg' => $message,
            'json' => 1
        ];

        try {
            $response = $this->makeHttpRequest($url, $data);
            $result = json_decode($response, true);
            
            if ($result['status_code'] === 100) {
                Log::info('SMS sent via SMS.ru', ['phone' => $phone]);
                return true;
            } else {
                Log::error('SMS.ru API error', ['status_code' => $result['status_code']]);
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error('SMS.ru request failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Отправка через Twilio
     */
    protected function sendViaTwilio(string $phone, string $message, NotificationDTO $notification): bool
    {
        // TODO: Реализовать интеграцию с Twilio
        Log::info('SMS via Twilio (not implemented yet)', [
            'phone' => $phone,
            'message' => $message
        ]);
        return true;
    }

    /**
     * HTTP запрос к SMS API
     */
    protected function makeHttpRequest(string $url, array $data): string
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false || !empty($error)) {
            throw new \Exception("HTTP request failed: $error");
        }
        
        if ($httpCode !== 200) {
            throw new \Exception("HTTP error: $httpCode");
        }
        
        return $response;
    }

    /**
     * Нормализация номера телефона
     */
    protected function normalizePhone(string $phone): string
    {
        // Убираем все символы кроме цифр и +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Удаляем ведущий + если есть
        $phone = ltrim($phone, '+');
        
        // Заменяем ведущую 8 на 7 для российских номеров
        if (str_starts_with($phone, '8') && strlen($phone) === 11) {
            $phone = '7' . substr($phone, 1);
        }
        
        // Добавляем + для международного формата
        return '+' . $phone;
    }

    /**
     * Форматирование SMS сообщения
     */
    protected function formatSmsMessage(NotificationDTO $notification): string
    {
        $title = $notification->getTitle();
        $body = $notification->getBody();
        
        // Объединяем заголовок и тело
        if (!empty($body) && $body !== $title) {
            $message = $title . "\n" . $body;
        } else {
            $message = $title;
        }
        
        // Ограничиваем длину SMS
        $maxLength = config('services.sms.max_length', 160);
        if (mb_strlen($message) > $maxLength) {
            $message = mb_substr($message, 0, $maxLength - 3) . '...';
        }
        
        return $message;
    }

    /**
     * Валидация данных уведомления
     */
    protected function validateNotificationData(NotificationDTO $notification): array
    {
        $errors = [];
        
        // Проверяем наличие номера телефона
        $recipientData = $notification->getRecipientData();
        if (empty($recipientData['phone'])) {
            $errors[] = 'Phone number is required';
        } else {
            $phone = $this->normalizePhone($recipientData['phone']);
            if (!preg_match('/^\+\d{10,15}$/', $phone)) {
                $errors[] = 'Invalid phone number format';
            }
        }
        
        // Проверяем наличие текста
        if (empty($notification->getTitle()) && empty($notification->getBody())) {
            $errors[] = 'Message text is required';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Проверка включенности канала
     */
    public function isEnabled(): bool
    {
        return config('services.sms.enabled', false);
    }

    /**
     * Получение необходимых данных для канала
     */
    public function getRequiredFields(): array
    {
        return ['phone'];
    }

    /**
     * Получение конфигурации канала
     */
    public function getChannelConfig(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'provider' => config('services.sms.provider', 'test'),
            'max_length' => config('services.sms.max_length', 160),
            'supports_unicode' => config('services.sms.supports_unicode', true)
        ];
    }
}