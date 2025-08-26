<?php

namespace App\Domain\User\Traits;

use App\Application\Services\Integration\UserFavoritesIntegrationService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Трейт для интеграции с избранным через сервисы
 * Заменяет HasFavorites трейт с прямыми связями
 * СОБЛЮДАЕТ DDD ПРИНЦИПЫ - нет прямых импортов моделей других доменов
 */
trait HasFavoritesIntegration
{
    /**
     * Получить все избранные объявления пользователя
     * Заменяет: $this->favorites()
     */
    public function getFavorites(): Collection
    {
        return app(UserFavoritesIntegrationService::class)->getUserFavorites($this->id);
    }

    /**
     * Получить количество избранных объявлений
     * Заменяет: $this->favorites()->count()
     */
    public function getFavoritesCount(): int
    {
        return app(UserFavoritesIntegrationService::class)->getUserFavoritesCount($this->id);
    }

    /**
     * Проверить находится ли объявление в избранном
     * Заменяет: $this->favorites()->where('ad_id', $adId)->exists()
     */
    public function hasFavorite(int $adId): bool
    {
        return app(UserFavoritesIntegrationService::class)->userHasFavorite($this->id, $adId);
    }

    /**
     * Добавить объявление в избранное через событие
     * Заменяет: $this->favorites()->attach($adId)
     * 
     * ВАЖНО: Не создает напрямую, а отправляет событие!
     */
    public function addToFavorites(int $adId): bool
    {
        return app(UserFavoritesIntegrationService::class)->addToUserFavorites($this->id, $adId);
    }

    /**
     * Удалить объявление из избранного через событие
     * Заменяет: $this->favorites()->detach($adId)
     * 
     * ВАЖНО: Не удаляет напрямую, а отправляет событие!
     */
    public function removeFromFavorites(int $adId): bool
    {
        return app(UserFavoritesIntegrationService::class)->removeFromUserFavorites($this->id, $adId);
    }

    /**
     * Переключить состояние избранного
     * Новый метод для удобства UI
     */
    public function toggleFavorite(int $adId): bool
    {
        return app(UserFavoritesIntegrationService::class)->toggleUserFavorite($this->id, $adId);
    }

    /**
     * Очистить все избранное пользователя
     * Заменяет: $this->favorites()->detach()
     */
    public function clearFavorites(): int
    {
        return app(UserFavoritesIntegrationService::class)->clearUserFavorites($this->id);
    }

    /**
     * Получить недавно добавленные в избранное
     * Новый метод для аналитики
     */
    public function getRecentFavorites(int $limit = 10): Collection
    {
        return app(UserFavoritesIntegrationService::class)->getRecentUserFavorites($this->id, $limit);
    }

    /**
     * Получить статистику избранного
     * Новый метод для аналитики
     */
    public function getFavoritesStatistics(): array
    {
        return app(UserFavoritesIntegrationService::class)->getUserFavoritesStatistics($this->id);
    }

    /**
     * Получить популярные категории в избранном
     * Новый метод для рекомендаций
     */
    public function getFavoriteCategories(): array
    {
        return app(UserFavoritesIntegrationService::class)->getUserFavoriteCategories($this->id);
    }

    /**
     * Проверить может ли пользователь добавить в избранное
     * Новый метод с бизнес-логикой (лимиты, правила)
     */
    public function canAddToFavorites(int $adId): bool
    {
        return app(UserFavoritesIntegrationService::class)->canUserAddToFavorites($this->id, $adId);
    }

    /**
     * DEPRECATED методы для обратной совместимости
     * Постепенно удалим после рефакторинга всех вызовов
     */

    /**
     * @deprecated Используйте getFavorites()
     */
    public function favorites()
    {
        return $this->getFavorites();
    }

    /**
     * @deprecated Используйте getFavoritesCount()
     */
    public function getFavoritesCountAttribute(): int
    {
        return $this->getFavoritesCount();
    }

    /**
     * @deprecated Используйте hasFavorite($adId)
     */
    public function isFavorite(int $adId): bool
    {
        return $this->hasFavorite($adId);
    }

    /**
     * Алиас для clearFavorites() для совместимости
     * @deprecated Используйте clearFavorites()
     */
    public function clearAllFavorites(): int
    {
        return $this->clearFavorites();
    }
}