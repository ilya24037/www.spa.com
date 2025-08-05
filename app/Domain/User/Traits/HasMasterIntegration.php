<?php

namespace App\Domain\User\Traits;

use App\Application\Services\Integration\UserMasterIntegrationService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Трейт для интеграции с мастерами через сервисы
 * Заменяет HasMasterProfile трейт с прямыми связями
 * СОБЛЮДАЕТ DDD ПРИНЦИПЫ - нет прямых импортов моделей других доменов
 */
trait HasMasterIntegration
{
    /**
     * Получить профиль мастера пользователя
     * Заменяет: $this->masterProfile()
     */
    public function getMasterProfile()
    {
        return app(UserMasterIntegrationService::class)->getUserMasterProfile($this->id);
    }

    /**
     * Получить все профили мастера пользователя
     * Заменяет: $this->masterProfiles()
     */
    public function getMasterProfiles(): Collection
    {
        return app(UserMasterIntegrationService::class)->getUserMasterProfiles($this->id);
    }

    /**
     * Получить основной профиль мастера
     * Заменяет: $this->getMainMasterProfile()
     */
    public function getMainMasterProfile()
    {
        return app(UserMasterIntegrationService::class)->getMainMasterProfile($this->id);
    }

    /**
     * Проверить есть ли активный профиль мастера
     * Заменяет: $this->hasActiveMasterProfile()
     */
    public function hasActiveMasterProfile(): bool
    {
        return app(UserMasterIntegrationService::class)->hasActiveMasterProfile($this->id);
    }

    /**
     * Создать профиль мастера через событие
     * Заменяет: $this->masterProfile()->create($data)
     * 
     * ВАЖНО: Не создает напрямую, а отправляет событие!
     */
    public function createMasterProfile(array $profileData): void
    {
        app(UserMasterIntegrationService::class)->createMasterProfileForUser(
            $this->id, 
            $profileData
        );
    }

    /**
     * Обновить профиль мастера
     * Новый метод с валидацией и событиями
     */
    public function updateMasterProfile(int $profileId, array $data): bool
    {
        return app(UserMasterIntegrationService::class)->updateUserMasterProfile(
            $this->id, 
            $profileId, 
            $data
        );
    }

    /**
     * Активировать профиль мастера
     * Интеграция с процессом модерации
     */
    public function activateMasterProfile(int $profileId): bool
    {
        return app(UserMasterIntegrationService::class)->activateUserMasterProfile(
            $this->id, 
            $profileId
        );
    }

    /**
     * Деактивировать профиль мастера
     * С указанием причины
     */
    public function deactivateMasterProfile(int $profileId, ?string $reason = null): bool
    {
        return app(UserMasterIntegrationService::class)->deactivateUserMasterProfile(
            $this->id, 
            $profileId, 
            $reason
        );
    }

    /**
     * Получить статистики мастера
     * Новый метод для аналитики
     */
    public function getMasterStatistics(): array
    {
        return app(UserMasterIntegrationService::class)->getUserMasterStatistics($this->id);
    }

    /**
     * Получить рейтинг мастера пользователя
     * Интеграция с системой отзывов
     */
    public function getMasterRating(): array
    {
        return app(UserMasterIntegrationService::class)->getUserMasterRating($this->id);
    }

    /**
     * Проверить может ли пользователь создать профиль мастера
     * Новый метод с бизнес-логикой
     */
    public function canCreateMasterProfile(): bool
    {
        return app(UserMasterIntegrationService::class)->canUserCreateMasterProfile($this->id);
    }

    /**
     * Получить краткую информацию о мастере для UI
     * Новый метод для интерфейса
     */
    public function getMasterSummary(): ?array
    {
        $profile = $this->getMasterProfile();
        if (!$profile) {
            return null;
        }

        $rating = $this->getMasterRating();
        
        return [
            'name' => $profile->name,
            'city' => $profile->city,
            'services' => $profile->services,
            'rating' => $rating['rating'] ?? 0,
            'reviews_count' => $rating['reviews_count'] ?? 0,
            'status' => $profile->status,
            'is_active' => $profile->status === 'active',
        ];
    }

    /**
     * DEPRECATED методы для обратной совместимости
     * Постепенно удалим после рефакторинга всех вызовов
     */

    /**
     * @deprecated Используйте getMasterProfile()
     */
    public function masterProfile()
    {
        return $this->getMasterProfile();
    }

    /**
     * @deprecated Используйте getMasterProfiles()
     */
    public function masterProfiles()
    {
        return $this->getMasterProfiles();
    }

    /**
     * @deprecated Используйте getMainMasterProfile()
     */
    public function getMainMasterProfile_old()
    {
        return $this->getMainMasterProfile();
    }
}