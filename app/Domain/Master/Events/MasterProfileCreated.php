<?php

namespace App\Domain\Master\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: Создание профиля мастера
 * Заменяет прямое обращение к $user->masterProfile()->create()
 */
class MasterProfileCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int $userId ID пользователя-мастера
     * @param int $masterProfileId ID созданного профиля мастера
     * @param array $profileData Данные профиля
     */
    public function __construct(
        public readonly int $userId,
        public readonly int $masterProfileId,
        public readonly array $profileData
    ) {}

    /**
     * Получить основные данные профиля
     */
    public function getProfileSummary(): array
    {
        return [
            'name' => $this->profileData['name'] ?? 'Не указано',
            'services' => $this->profileData['services'] ?? [],
            'city' => $this->profileData['city'] ?? 'Не указан',
            'experience' => $this->profileData['experience'] ?? 0,
        ];
    }

    /**
     * Проверить, является ли профиль основным
     */
    public function isMainProfile(): bool
    {
        return $this->profileData['is_main'] ?? false;
    }

    /**
     * Получить данные для уведомлений
     */
    public function getNotificationData(): array
    {
        return [
            'user_id' => $this->userId,
            'profile_id' => $this->masterProfileId,
            'profile_name' => $this->profileData['name'] ?? 'Новый профиль',
            'status' => $this->profileData['status'] ?? 'pending',
        ];
    }
}