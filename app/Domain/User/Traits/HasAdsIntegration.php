<?php

namespace App\Domain\User\Traits;

use App\Application\Services\Integration\UserAdsIntegrationService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Трейт для интеграции с объявлениями через сервисы
 * Заменяет HasAds трейт с прямыми связями
 * СОБЛЮДАЕТ DDD ПРИНЦИПЫ - нет прямых импортов моделей других доменов
 */
trait HasAdsIntegration
{
    /**
     * Получить все объявления пользователя
     * Заменяет: $this->ads()
     */
    public function getAds(): Collection
    {
        return app(UserAdsIntegrationService::class)->getUserAds($this->id);
    }

    /**
     * Получить активные объявления пользователя
     * Заменяет: $this->ads()->where('status', 'active')
     */
    public function getActiveAds(): Collection
    {
        return app(UserAdsIntegrationService::class)->getUserActiveAds($this->id);
    }

    /**
     * Получить черновики пользователя
     * Заменяет: $this->ads()->where('status', 'draft')
     */
    public function getDraftAds(): Collection
    {
        return app(UserAdsIntegrationService::class)->getUserDraftAds($this->id);
    }

    /**
     * Получить архивированные объявления
     * Заменяет: $this->ads()->where('status', 'archived')
     */
    public function getArchivedAds(): Collection
    {
        return app(UserAdsIntegrationService::class)->getUserArchivedAds($this->id);
    }

    /**
     * Получить количество всех объявлений
     * Заменяет: $this->ads()->count()
     */
    public function getAdsCount(): int
    {
        return app(UserAdsIntegrationService::class)->getUserAdsCount($this->id);
    }

    /**
     * Получить количество активных объявлений
     * Заменяет: $this->ads()->where('status', 'active')->count()
     */
    public function getActiveAdsCount(): int
    {
        return app(UserAdsIntegrationService::class)->getUserActiveAdsCount($this->id);
    }

    /**
     * Проверить может ли пользователь создать новое объявление
     * Новый метод с бизнес-логикой (лимиты, права)
     */
    public function canCreateAd(): bool
    {
        return app(UserAdsIntegrationService::class)->canUserCreateAd($this->id);
    }

    /**
     * Проверить принадлежит ли объявление пользователю
     * Заменяет: $this->ads()->where('id', $adId)->exists()
     */
    public function ownsAd(int $adId): bool
    {
        return app(UserAdsIntegrationService::class)->userOwnsAd($this->id, $adId);
    }

    /**
     * Создать новое объявление через событие
     * ВАЖНО: Не создает напрямую, а отправляет событие!
     */
    public function createAd(array $adData): ?int
    {
        return app(UserAdsIntegrationService::class)->createUserAd($this->id, $adData);
    }

    /**
     * Обновить объявление через событие
     * ВАЖНО: Не обновляет напрямую, а отправляет событие!
     */
    public function updateAd(int $adId, array $adData): bool
    {
        return app(UserAdsIntegrationService::class)->updateUserAd($this->id, $adId, $adData);
    }

    /**
     * Удалить объявление через событие
     */
    public function deleteAd(int $adId): bool
    {
        return app(UserAdsIntegrationService::class)->deleteUserAd($this->id, $adId);
    }

    /**
     * Архивировать объявление
     */
    public function archiveAd(int $adId): bool
    {
        return app(UserAdsIntegrationService::class)->archiveUserAd($this->id, $adId);
    }

    /**
     * Восстановить объявление из архива
     */
    public function restoreAd(int $adId): bool
    {
        return app(UserAdsIntegrationService::class)->restoreUserAd($this->id, $adId);
    }

    /**
     * Опубликовать объявление
     */
    public function publishAd(int $adId): bool
    {
        return app(UserAdsIntegrationService::class)->publishUserAd($this->id, $adId);
    }

    /**
     * Получить недавние объявления
     * Новый метод для аналитики
     */
    public function getRecentAds(int $limit = 10): Collection
    {
        return app(UserAdsIntegrationService::class)->getRecentUserAds($this->id, $limit);
    }

    /**
     * Получить статистику объявлений
     * Новый метод для аналитики
     */
    public function getAdsStatistics(): array
    {
        return app(UserAdsIntegrationService::class)->getUserAdsStatistics($this->id);
    }

    /**
     * Получить популярные категории объявлений пользователя
     * Новый метод для аналитики
     */
    public function getAdsCategories(): array
    {
        return app(UserAdsIntegrationService::class)->getUserAdsCategories($this->id);
    }

    /**
     * Получить объявления с истекающим сроком
     * Новый метод для уведомлений
     */
    public function getExpiringAds(int $daysBeforeExpiry = 7): Collection
    {
        return app(UserAdsIntegrationService::class)->getUserExpiringAds($this->id, $daysBeforeExpiry);
    }

    /**
     * Получить неоплаченные объявления
     */
    public function getUnpaidAds(): Collection
    {
        return app(UserAdsIntegrationService::class)->getUserUnpaidAds($this->id);
    }

    /**
     * Получить доходы от объявлений
     * Новый метод для финансовой аналитики
     */
    public function getAdsRevenue(): array
    {
        return app(UserAdsIntegrationService::class)->getUserAdsRevenue($this->id);
    }

    /**
     * DEPRECATED методы для обратной совместимости
     * Постепенно удалим после рефакторинга всех вызовов
     */

    /**
     * @deprecated Используйте getAds()
     */
    public function ads()
    {
        return $this->getAds();
    }

    /**
     * @deprecated Используйте getActiveAds()
     */
    public function activeAds()
    {
        return $this->getActiveAds();
    }

    /**
     * @deprecated Используйте getDraftAds()
     */
    public function draftAds()
    {
        return $this->getDraftAds();
    }

    /**
     * @deprecated Используйте getAdsCount()
     */
    public function getAdsCountAttribute(): int
    {
        return $this->getAdsCount();
    }

    /**
     * @deprecated Используйте getActiveAdsCount()
     */
    public function getActiveAdsCountAttribute(): int
    {
        return $this->getActiveAdsCount();
    }

    /**
     * @deprecated Используйте ownsAd($adId)
     */
    public function hasAd(int $adId): bool
    {
        return $this->ownsAd($adId);
    }
}