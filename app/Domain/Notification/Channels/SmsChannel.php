<?php

namespace App\Domain\Notification\Channels;

use App\Domain\Notification\Models\Notification;
use App\Enums\NotificationChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Канал SMS уведомлений
 */
class SmsChannel extends AbstractNotificationChannel
{
    /**
     * {@inheritdoc}
     */
    protected function getChannel(): NotificationChannel
    {
        return NotificationChannel::SMS;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => config('sms.default') !== null,
            'provider' => config('sms.default', 'smsru'),
            'api_key' => config('sms.providers.smsru.api_key'),
            'sender' => config('sms.sender', 'SPA-PLATFORM'),
            'rate_limit_per_minute' => 10,
            'rate_limit_per_hour' => 100,
            'rate_limit_per_day' => 500,
            'timeout' => 30,
            'log_sends' => true,
            'test_mode' => config('sms.test_mode', false),
            'max_length' => 160, // максимальная длина SMS
            'providers' => [
                'smsru' => [
                    'url' => 'https://sms.ru/sms/send',
                    'api_key_param' => 'api_id',
                ],
                'smsc' => [
                    'url' => 'https://smsc.ru/sys/send.php',
                    'login' => config('sms.providers.smsc.login'),
                    'password' => config('sms.providers.smsc.password'),
                ],
                'twilio' => [
                    'url' => 'https://api.twilio.com/2010-04-01/Accounts/{account_sid}/Messages.json',
                    'account_sid' => config('sms.providers.twilio.account_sid'),
                    'auth_token' => config('sms.providers.twilio.auth_token'),
                    'from' => config('sms.providers.twilio.from'),
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doSend(Notification $notification, array $content): array
    {
        $recipient = $this->getRecipient($notification);
        
        if (!$recipient) {
            throw new \Exception('No phone number found for notification');
        }

        // Нормализовать номер телефона
        $phone = $this->normalizePhone($recipient);
        
        if (!$this->isValidPhone($phone)) {
            throw new \Exception("Invalid phone number: {$recipient}");
        }

        // Подготовить текст SMS
        $message = $this->prepareSmsText($content);
        
        if (mb_strlen($message) > $this->config['max_length']) {
            $message = mb_substr($message, 0, $this->config['max_length'] - 3) . '...';
        }

        // Отправить через выбранного провайдера
        $provider = $this->config['provider'];
        
        return match($provider) {
            'smsru' => $this->sendViaSmsRu($phone, $message, $notification),
            'smsc' => $this->sendViaSmsc($phone, $message, $notification),
            'twilio' => $this->sendViaTwilio($phone, $message, $notification),
            default => throw new \Exception("Unsupported SMS provider: {$provider}"),
        };
    }

    /**
     * Отправка через SMS.RU
     */
    private function sendViaSmsRu(string $phone, string $message, Notification $notification): array
    {
        $config = $this->config['providers']['smsru'];
        
        $response = $this->getHttpClient()->post($config['url'], [
            'api_id' => $this->config['api_key'],
            'to' => $phone,
            'msg' => $message,
            'json' => 1,
            'from' => $this->config['sender'],
            'test' => $this->config['test_mode'] ? 1 : 0,
        ]);

        if (!$response->successful()) {
            throw new \Exception("SMS.RU API error: " . $response->status());
        }

        $data = $response->json();
        
        if ($data['status_code'] != 100) {
            $error = $this->getSmsRuError($data['status_code']);
            throw new \Exception("SMS.RU error: {$error}");
        }

        return [
            'success' => true,
            'external_id' => $data['sms'][$phone]['sms_id'] ?? null,
            'provider' => 'smsru',
            'cost' => $data['sms'][$phone]['cost'] ?? 0,
            'recipient' => $phone,
        ];
    }

    /**
     * Отправка через SMSC.RU
     */
    private function sendViaSmsc(string $phone, string $message, Notification $notification): array
    {
        $config = $this->config['providers']['smsc'];
        
        $response = $this->getHttpClient()->post($config['url'], [
            'login' => $config['login'],
            'psw' => $config['password'],
            'phones' => $phone,
            'mes' => $message,
            'fmt' => 3, // JSON формат
            'sender' => $this->config['sender'],
            'test' => $this->config['test_mode'] ? 1 : 0,
        ]);

        if (!$response->successful()) {
            throw new \Exception("SMSC API error: " . $response->status());
        }

        $data = $response->json();
        
        if (isset($data['error'])) {
            throw new \Exception("SMSC error: {$data['error']}");
        }

        return [
            'success' => true,
            'external_id' => $data['id'] ?? null,
            'provider' => 'smsc',
            'cost' => $data['cost'] ?? 0,
            'recipient' => $phone,
        ];
    }

    /**
     * Отправка через Twilio
     */
    private function sendViaTwilio(string $phone, string $message, Notification $notification): array
    {
        $config = $this->config['providers']['twilio'];
        $url = str_replace('{account_sid}', $config['account_sid'], $config['url']);
        
        $response = $this->getHttpClient()
            ->withBasicAuth($config['account_sid'], $config['auth_token'])
            ->asForm()
            ->post($url, [
                'To' => $phone,
                'From' => $config['from'],
                'Body' => $message,
            ]);

        if (!$response->successful()) {
            throw new \Exception("Twilio API error: " . $response->status());
        }

        $data = $response->json();
        
        if (isset($data['error_code'])) {
            throw new \Exception("Twilio error: {$data['error_message']}");
        }

        return [
            'success' => true,
            'external_id' => $data['sid'] ?? null,
            'provider' => 'twilio',
            'cost' => 0, // Twilio не возвращает стоимость в ответе
            'recipient' => $phone,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getRecipient(Notification $notification): ?string
    {
        // Проверяем в данных уведомления
        if (!empty($notification->data['phone'])) {
            return $notification->data['phone'];
        }
        
        // Проверяем телефон пользователя
        if ($notification->user && !empty($notification->user->phone)) {
            return $notification->user->phone;
        }
        
        return null;
    }

    /**
     * Нормализовать номер телефона
     */
    private function normalizePhone(string $phone): string
    {
        // Убираем все кроме цифр
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Добавляем +7 для российских номеров
        if (strlen($phone) == 10 && $phone[0] == '9') {
            $phone = '7' . $phone;
        } elseif (strlen($phone) == 11 && $phone[0] == '8') {
            $phone = '7' . substr($phone, 1);
        } elseif (strlen($phone) == 11 && $phone[0] == '7') {
            // Уже нормализован
        } else {
            // Для других стран - оставляем как есть
        }
        
        return '+' . $phone;
    }

    /**
     * Проверить валидность номера телефона
     */
    private function isValidPhone(string $phone): bool
    {
        // Простая проверка: начинается с + и содержит 10-15 цифр
        return preg_match('/^\+[1-9]\d{9,14}$/', $phone);
    }

    /**
     * Подготовить текст SMS
     */
    private function prepareSmsText(array $content): string
    {
        $text = $content['message'];
        
        // Убираем HTML теги
        $text = strip_tags($text);
        
        // Заменяем HTML сущности
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        // Убираем лишние пробелы и переносы
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Добавляем ссылку, если есть
        if (!empty($content['action_url'])) {
            $text .= ' ' . $content['action_url'];
        }
        
        return $text;
    }

    /**
     * Получить описание ошибки SMS.RU
     */
    private function getSmsRuError(int $code): string
    {
        return match($code) {
            100 => 'Запрос выполнен успешно',
            200 => 'Неправильный api_id',
            201 => 'Не хватает средств на лицевом счету',
            202 => 'Неправильно указан получатель',
            203 => 'Нет текста сообщения',
            204 => 'Имя отправителя не согласовано с администрацией',
            205 => 'Сообщение слишком длинное',
            206 => 'Будет превышен или уже превышен дневной лимит на отправку сообщений',
            207 => 'На этот номер нельзя отправлять сообщения',
            208 => 'Параметр time указан неправильно',
            209 => 'Вы добавили этот номер в стоп-лист',
            210 => 'Используется GET, где необходимо использовать POST',
            211 => 'Метод не найден',
            220 => 'Сервис временно недоступен',
            300 => 'Неправильный token',
            301 => 'Неправильный пароль, либо пользователь не найден',
            302 => 'Пользователь авторизован, но аккаунт не подтвержден',
            default => "Неизвестная ошибка (код: {$code})",
        };
    }

    /**
     * {@inheritdoc}
     */
    public function handleCallback(array $data): bool
    {
        // Обработка статусов доставки SMS
        $provider = $this->config['provider'];
        
        return match($provider) {
            'smsru' => $this->handleSmsRuCallback($data),
            'smsc' => $this->handleSmscCallback($data),
            'twilio' => $this->handleTwilioCallback($data),
            default => false,
        };
    }

    /**
     * Обработка callback от SMS.RU
     */
    private function handleSmsRuCallback(array $data): bool
    {
        $smsId = $data['sms_id'] ?? null;
        $status = $data['status'] ?? null;
        
        if (!$smsId || !$status) {
            return false;
        }
        
        $delivery = \App\Domain\Notification\Models\NotificationDelivery::where('external_id', $smsId)
            ->where('channel', $this->channel)
            ->first();

        if (!$delivery) {
            return false;
        }

        switch ($status) {
            case '1': // Доставлено
                $delivery->markAsDelivered();
                break;
            case '-1': // Ошибка
            case '0': // В очереди (пока не обрабатываем)
            case '2': // Отправлено (пока не обрабатываем)
                break;
            default:
                $delivery->markAsFailed("SMS status: {$status}");
        }

        return true;
    }

    /**
     * Обработка callback от SMSC
     */
    private function handleSmscCallback(array $data): bool
    {
        $id = $data['id'] ?? null;
        $status = $data['status'] ?? null;
        
        if (!$id || !$status) {
            return false;
        }
        
        $delivery = \App\Domain\Notification\Models\NotificationDelivery::where('external_id', $id)
            ->where('channel', $this->channel)
            ->first();

        if (!$delivery) {
            return false;
        }

        if ($status == 1) { // Доставлено
            $delivery->markAsDelivered();
        } elseif ($status < 0) { // Ошибка
            $delivery->markAsFailed("SMSC status: {$status}");
        }

        return true;
    }

    /**
     * Обработка callback от Twilio
     */
    private function handleTwilioCallback(array $data): bool
    {
        $messageSid = $data['MessageSid'] ?? null;
        $messageStatus = $data['MessageStatus'] ?? null;
        
        if (!$messageSid || !$messageStatus) {
            return false;
        }
        
        $delivery = \App\Domain\Notification\Models\NotificationDelivery::where('external_id', $messageSid)
            ->where('channel', $this->channel)
            ->first();

        if (!$delivery) {
            return false;
        }

        switch ($messageStatus) {
            case 'delivered':
                $delivery->markAsDelivered();
                break;
            case 'failed':
            case 'undelivered':
                $errorCode = $data['ErrorCode'] ?? 'unknown';
                $delivery->markAsFailed("Twilio error: {$errorCode}");
                break;
        }

        return true;
    }

    /**
     * Проверить, может ли пользователь получать SMS
     */
    protected function canSendToUser($user): bool
    {
        if (!$user) {
            return false;
        }

        // Проверить настройки уведомлений пользователя
        $preferences = $user->notification_preferences ?? [];
        
        return $preferences['sms'] ?? true;
    }

    /**
     * {@inheritdoc}
     */
    public function canSend(Notification $notification): bool
    {
        if (!parent::canSend($notification)) {
            return false;
        }

        // Проверить, может ли пользователь получать SMS
        return $this->canSendToUser($notification->user);
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryStatus(\App\Domain\Notification\Models\NotificationDelivery $delivery): array
    {
        $status = parent::getDeliveryStatus($delivery);
        
        // Добавить SMS-специфичную информацию
        $metadata = $delivery->metadata ?? [];
        
        $status['provider'] = $this->config['provider'];
        $status['cost'] = $metadata['send_result']['cost'] ?? null;
        $status['phone'] = $delivery->recipient;
        
        return $status;
    }
}