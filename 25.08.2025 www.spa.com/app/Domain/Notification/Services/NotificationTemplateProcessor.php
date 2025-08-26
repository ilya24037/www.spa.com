<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\NotificationTemplate;
use App\Domain\User\Models\User;
use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Models\Payment;
use App\Enums\NotificationType;
use Illuminate\Support\Facades\Cache;

/**
 * Процессор шаблонов уведомлений
 */
class NotificationTemplateProcessor
{
    /**
     * Обработать шаблон уведомления
     */
    public function processTemplate(
        NotificationType $type,
        array $data = [],
        string $locale = 'ru'
    ): array {
        $template = $this->getTemplate($type, $locale);
        
        if (!$template) {
            return $this->getDefaultTemplate($type, $data);
        }
        
        return [
            'title' => $this->processPlaceholders($template->title, $data),
            'body' => $this->processPlaceholders($template->body, $data),
            'action_url' => $this->processPlaceholders($template->action_url ?? '', $data),
            'template_id' => $template->id,
            'variables' => $data
        ];
    }

    /**
     * Получить шаблон уведомления
     */
    protected function getTemplate(NotificationType $type, string $locale): ?NotificationTemplate
    {
        $cacheKey = "notification_template_{$type->value}_{$locale}";
        
        return Cache::remember($cacheKey, 3600, function () use ($type, $locale) {
            return NotificationTemplate::where('type', $type)
                ->where('locale', $locale)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Обработать плейсхолдеры в тексте
     */
    protected function processPlaceholders(string $text, array $data): string
    {
        // Простые переменные {variable_name}
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $text = str_replace("{{$key}}", (string) $value, $text);
            }
        }

        // Специальные обработчики
        $text = $this->processDatePlaceholders($text, $data);
        $text = $this->processUrlPlaceholders($text, $data);
        $text = $this->processUserPlaceholders($text, $data);
        $text = $this->processBookingPlaceholders($text, $data);
        $text = $this->processPaymentPlaceholders($text, $data);

        return $text;
    }

    /**
     * Обработать плейсхолдеры дат
     */
    protected function processDatePlaceholders(string $text, array $data): string
    {
        $patterns = [
            '/\{date:([^}]+)\}/' => function ($matches) {
                return now()->format($matches[1]);
            },
            '/\{booking_date:([^}]+)\}/' => function ($matches) use ($data) {
                if (isset($data['booking']) && $data['booking'] instanceof Booking) {
                    return $data['booking']->booking_date->format($matches[1]);
                }
                return '';
            }
        ];

        foreach ($patterns as $pattern => $callback) {
            $text = preg_replace_callback($pattern, $callback, $text);
        }

        return $text;
    }

    /**
     * Обработать URL плейсхолдеры
     */
    protected function processUrlPlaceholders(string $text, array $data): string
    {
        $urls = [
            '{app_url}' => config('app.url'),
            '{booking_url}' => function () use ($data) {
                if (isset($data['booking_id'])) {
                    return config('app.url') . "/bookings/{$data['booking_id']}";
                }
                return config('app.url');
            },
            '{profile_url}' => function () use ($data) {
                if (isset($data['user']) && $data['user'] instanceof User) {
                    return config('app.url') . "/profile/{$data['user']->id}";
                }
                return config('app.url') . "/profile";
            },
            '{payment_url}' => function () use ($data) {
                if (isset($data['payment_id'])) {
                    return config('app.url') . "/payments/{$data['payment_id']}";
                }
                return config('app.url');
            }
        ];

        foreach ($urls as $placeholder => $replacement) {
            if (is_callable($replacement)) {
                $replacement = $replacement();
            }
            $text = str_replace($placeholder, $replacement, $text);
        }

        return $text;
    }

    /**
     * Обработать пользовательские плейсхолдеры
     */
    protected function processUserPlaceholders(string $text, array $data): string
    {
        if (!isset($data['user']) || !($data['user'] instanceof User)) {
            return $text;
        }

        $user = $data['user'];
        $replacements = [
            '{user_name}' => $user->name,
            '{user_email}' => $user->email,
            '{user_phone}' => $user->phone ?? '',
            '{user_first_name}' => explode(' ', $user->name)[0] ?? ''
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * Обработать плейсхолдеры бронирования
     */
    protected function processBookingPlaceholders(string $text, array $data): string
    {
        if (!isset($data['booking']) || !($data['booking'] instanceof Booking)) {
            return $text;
        }

        $booking = $data['booking'];
        $replacements = [
            '{booking_id}' => $booking->id,
            '{booking_service}' => $booking->service_name ?? '',
            '{booking_date}' => $booking->booking_date->format('d.m.Y'),
            '{booking_time}' => $booking->booking_date->format('H:i'),
            '{booking_duration}' => $booking->duration ?? '',
            '{booking_price}' => number_format($booking->price, 0, '.', ' ') . ' ₽',
            '{master_name}' => $booking->master->user->name ?? '',
            '{client_name}' => $booking->user->name ?? ''
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * Обработать плейсхолдеры платежа
     */
    protected function processPaymentPlaceholders(string $text, array $data): string
    {
        if (!isset($data['payment']) || !($data['payment'] instanceof Payment)) {
            return $text;
        }

        $payment = $data['payment'];
        $replacements = [
            '{payment_id}' => $payment->id,
            '{payment_amount}' => number_format($payment->amount, 2, '.', ' ') . ' ' . $payment->currency,
            '{payment_method}' => $payment->payment_method ?? '',
            '{payment_status}' => $payment->status->getLabel() ?? ''
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * Получить дефолтный шаблон
     */
    protected function getDefaultTemplate(NotificationType $type, array $data): array
    {
        return match($type) {
            NotificationType::BOOKING_CREATED => [
                'title' => 'Новое бронирование',
                'body' => 'У вас новое бронирование от {client_name} на {booking_date} {booking_time}',
                'action_url' => '{booking_url}',
                'variables' => $data
            ],
            NotificationType::BOOKING_CONFIRMED => [
                'title' => 'Бронирование подтверждено',
                'body' => 'Ваше бронирование на {booking_date} {booking_time} подтверждено',
                'action_url' => '{booking_url}',
                'variables' => $data
            ],
            NotificationType::BOOKING_CANCELLED => [
                'title' => 'Бронирование отменено',
                'body' => 'Ваше бронирование на {booking_date} {booking_time} отменено',
                'action_url' => '{booking_url}',
                'variables' => $data
            ],
            NotificationType::PAYMENT_SUCCESSFUL => [
                'title' => 'Платеж успешен',
                'body' => 'Ваш платеж на сумму {payment_amount} успешно обработан',
                'action_url' => '{payment_url}',
                'variables' => $data
            ],
            NotificationType::PAYMENT_FAILED => [
                'title' => 'Ошибка платежа',
                'body' => 'Не удалось обработать ваш платеж на сумму {payment_amount}',
                'action_url' => '{payment_url}',
                'variables' => $data
            ],
            default => [
                'title' => 'Уведомление',
                'body' => 'У вас новое уведомление',
                'action_url' => '{app_url}',
                'variables' => $data
            ]
        };
    }

    /**
     * Валидировать шаблон
     */
    public function validateTemplate(string $title, string $body): array
    {
        $errors = [];

        if (empty($title)) {
            $errors['title'] = 'Заголовок обязателен';
        } elseif (strlen($title) > 100) {
            $errors['title'] = 'Заголовок не должен превышать 100 символов';
        }

        if (empty($body)) {
            $errors['body'] = 'Текст уведомления обязателен';
        } elseif (strlen($body) > 500) {
            $errors['body'] = 'Текст не должен превышать 500 символов';
        }

        return $errors;
    }

    /**
     * Получить доступные переменные для типа уведомления
     */
    public function getAvailableVariables(NotificationType $type): array
    {
        $common = ['user_name', 'user_email', 'user_phone', 'date', 'app_url', 'profile_url'];

        return match($type) {
            NotificationType::BOOKING_CREATED,
            NotificationType::BOOKING_CONFIRMED,
            NotificationType::BOOKING_CANCELLED => array_merge($common, [
                'booking_id', 'booking_service', 'booking_date', 'booking_time',
                'booking_duration', 'booking_price', 'master_name', 'client_name', 'booking_url'
            ]),
            NotificationType::PAYMENT_SUCCESSFUL,
            NotificationType::PAYMENT_FAILED => array_merge($common, [
                'payment_id', 'payment_amount', 'payment_method', 'payment_status', 'payment_url'
            ]),
            default => $common
        };
    }
}