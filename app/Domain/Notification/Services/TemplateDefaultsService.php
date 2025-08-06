<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Handlers\TemplateCrudHandler;
use App\Enums\NotificationType;
use App\Enums\NotificationChannel;
use Illuminate\Support\Facades\Log;

/**
 * Сервис создания шаблонов по умолчанию
 */
class TemplateDefaultsService
{
    public function __construct(
        private TemplateCrudHandler $crudHandler
    ) {}

    /**
     * Создать шаблоны по умолчанию
     */
    public function createDefaultTemplates(): array
    {
        $templates = [];
        $defaultTemplates = $this->getDefaultTemplatesData();

        foreach ($defaultTemplates as $templateData) {
            try {
                $templates[] = $this->crudHandler->create($templateData);
            } catch (\Exception $e) {
                Log::error('Failed to create default template', [
                    'template_name' => $templateData['name'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $templates;
    }

    /**
     * Получить данные шаблонов по умолчанию
     */
    private function getDefaultTemplatesData(): array
    {
        return [
            [
                'name' => 'default_welcome',
                'type' => NotificationType::USER_WELCOME,
                'title' => 'Добро пожаловать, {{user_name}}!',
                'subject' => 'Добро пожаловать на SPA Platform',
                'content' => 'Здравствуйте, {{user_name}}! Рады видеть вас на нашей платформе.',
                'variables' => [
                    'user_name' => ['required' => true, 'description' => 'Имя пользователя'],
                ],
                'channels' => [NotificationChannel::EMAIL->value],
                'category' => 'auth',
            ],
            [
                'name' => 'default_booking_confirmed',
                'type' => NotificationType::BOOKING_CONFIRMED,
                'title' => 'Бронирование подтверждено',
                'subject' => 'Ваше бронирование №{{booking_number}} подтверждено',
                'content' => 'Ваше бронирование на {{service_name}} у мастера {{master_name}} подтверждено на {{booking_date}}.',
                'variables' => [
                    'booking_number' => ['required' => true],
                    'service_name' => ['required' => true],
                    'master_name' => ['required' => true],
                    'booking_date' => ['required' => true],
                ],
                'channels' => [NotificationChannel::EMAIL->value, NotificationChannel::PUSH->value],
                'category' => 'booking',
            ],
            [
                'name' => 'default_payment_completed',
                'type' => NotificationType::PAYMENT_COMPLETED,
                'title' => 'Платеж выполнен',
                'subject' => 'Платеж на сумму {{amount}} выполнен успешно',
                'content' => 'Ваш платеж на сумму {{amount}} выполнен успешно. Номер операции: {{transaction_id}}.',
                'variables' => [
                    'amount' => ['required' => true],
                    'transaction_id' => ['required' => true],
                ],
                'channels' => [NotificationChannel::EMAIL->value, NotificationChannel::SMS->value],
                'category' => 'payment',
            ],
            [
                'name' => 'default_booking_reminder',
                'type' => NotificationType::BOOKING_REMINDER,
                'title' => 'Напоминание о бронировании',
                'subject' => 'Напоминание: через час ваш сеанс {{service_name}}',
                'content' => 'Напоминаем, что через час у вас сеанс {{service_name}} у мастера {{master_name}}.',
                'variables' => [
                    'service_name' => ['required' => true],
                    'master_name' => ['required' => true],
                    'booking_time' => ['required' => false],
                ],
                'channels' => [NotificationChannel::PUSH->value, NotificationChannel::SMS->value],
                'category' => 'booking',
            ],
            [
                'name' => 'default_review_request',
                'type' => NotificationType::REVIEW_REQUEST,
                'title' => 'Оцените услугу',
                'subject' => 'Поделитесь впечатлениями о сеансе {{service_name}}',
                'content' => 'Как прошел ваш сеанс {{service_name}} у мастера {{master_name}}? Поделитесь впечатлениями!',
                'variables' => [
                    'service_name' => ['required' => true],
                    'master_name' => ['required' => true],
                    'review_url' => ['required' => false],
                ],
                'channels' => [NotificationChannel::EMAIL->value],
                'category' => 'review',
            ],
        ];
    }
}