<?php

namespace App\Domain\User\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: Регистрация нового пользователя
 * Для создания связанных записей и отправки уведомлений
 */
class UserRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int $userId ID зарегистрированного пользователя
     * @param string $email Email пользователя
     * @param string $role Роль пользователя
     * @param array $registrationData Дополнительные данные регистрации
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly string $role,
        public readonly array $registrationData = []
    ) {}

    /**
     * Проверить, регистрируется ли мастер
     */
    public function isMasterRegistration(): bool
    {
        return $this->role === 'master';
    }

    /**
     * Проверить, регистрируется ли клиент
     */
    public function isClientRegistration(): bool
    {
        return $this->role === 'client';
    }

    /**
     * Получить данные для создания профиля
     */
    public function getProfileData(): array
    {
        return [
            'name' => $this->registrationData['name'] ?? 'Пользователь',
            'phone' => $this->registrationData['phone'] ?? null,
            'city' => $this->registrationData['city'] ?? null,
            'notifications_enabled' => true,
            'newsletter_enabled' => true,
        ];
    }

    /**
     * Получить данные для создания настроек
     */
    public function getSettingsData(): array
    {
        return [
            'locale' => $this->registrationData['locale'] ?? 'ru',
            'timezone' => $this->registrationData['timezone'] ?? 'Europe/Moscow',
            'currency' => 'RUB',
            'email_notifications' => true,
            'sms_notifications' => true,
            'push_notifications' => true,
            'marketing_emails' => false,
            'two_factor_enabled' => false,
            'privacy_settings' => [],
        ];
    }

    /**
     * Получить данные для уведомлений
     */
    public function getNotificationData(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'role' => $this->role,
            'registration_source' => $this->registrationData['source'] ?? 'web',
        ];
    }
}