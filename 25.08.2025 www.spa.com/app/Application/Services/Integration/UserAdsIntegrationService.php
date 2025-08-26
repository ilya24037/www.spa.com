<?php

namespace App\Application\Services\Integration;

use App\Application\Services\Integration\UserAds\AdQueryHandler;
use App\Application\Services\Integration\UserAds\AdOperationsHandler;
use App\Application\Services\Integration\UserAds\AdStatisticsHandler;
use App\Application\Services\Integration\UserAds\AdValidationHandler;
use Illuminate\Database\Eloquent\Collection;

/**
 * Упрощенный сервис интеграции User ↔ Ads доменов
 * Заменяет прямые связи через трейт HasAds
 * Делегирует работу специализированным обработчикам
 */
class UserAdsIntegrationService
{
    private AdQueryHandler $queryHandler;
    private AdOperationsHandler $operationsHandler;
    private AdStatisticsHandler $statisticsHandler;
    private AdValidationHandler $validationHandler;

    public function __construct()
    {
        $this->queryHandler = new AdQueryHandler();
        $this->operationsHandler = new AdOperationsHandler();
        $this->statisticsHandler = new AdStatisticsHandler();
        $this->validationHandler = new AdValidationHandler();
    }

    // === ЗАПРОСЫ К ОБЪЯВЛЕНИЯМ ===

    /**
     * Получить все объявления пользователя
     * Заменяет: $user->ads()
     */
    public function getUserAds(int $userId): Collection
    {
        return $this->queryHandler->getUserAds($userId);
    }

    /**
     * Получить активные объявления пользователя
     * Заменяет: $user->ads()->where('status', 'active')
     */
    public function getUserActiveAds(int $userId): Collection
    {
        return $this->queryHandler->getUserActiveAds($userId);
    }

    /**
     * Получить черновики пользователя
     * Заменяет: $user->ads()->where('status', 'draft')
     */
    public function getUserDraftAds(int $userId): Collection
    {
        return $this->queryHandler->getUserDraftAds($userId);
    }

    /**
     * Получить архивированные объявления
     * Заменяет: $user->ads()->where('status', 'archived')
     */
    public function getUserArchivedAds(int $userId): Collection
    {
        return $this->queryHandler->getUserArchivedAds($userId);
    }

    /**
     * Получить недавние объявления пользователя
     */
    public function getRecentUserAds(int $userId, int $limit = 10): Collection
    {
        return $this->queryHandler->getRecentUserAds($userId, $limit);
    }

    /**
     * Получить объявления с истекающим сроком
     */
    public function getUserExpiringAds(int $userId, int $daysBeforeExpiry = 7): Collection
    {
        return $this->queryHandler->getUserExpiringAds($userId, $daysBeforeExpiry);
    }

    /**
     * Получить неоплаченные объявления
     */
    public function getUserUnpaidAds(int $userId): Collection
    {
        return $this->queryHandler->getUserUnpaidAds($userId);
    }

    /**
     * Получить количество всех объявлений
     * Заменяет: $user->ads()->count()
     */
    public function getUserAdsCount(int $userId): int
    {
        return $this->queryHandler->getUserAdsCount($userId);
    }

    /**
     * Получить количество активных объявлений
     * Заменяет: $user->ads()->where('status', 'active')->count()
     */
    public function getUserActiveAdsCount(int $userId): int
    {
        return $this->queryHandler->getUserActiveAdsCount($userId);
    }

    /**
     * Получить популярные категории объявлений пользователя
     */
    public function getUserAdsCategories(int $userId): array
    {
        return $this->queryHandler->getUserAdsCategories($userId);
    }

    // === ОПЕРАЦИИ С ОБЪЯВЛЕНИЯМИ ===

    /**
     * Создать новое объявление через событие
     * ВАЖНО: Не создает напрямую, а отправляет событие!
     */
    public function createUserAd(int $userId, array $adData): ?int
    {
        // Проверяем права и лимиты
        if (!$this->canUserCreateAd($userId)) {
            return null;
        }

        // Валидируем данные объявления
        $validatedData = $this->validateAdData($adData);
        if (!$validatedData) {
            return null;
        }

        return $this->operationsHandler->createUserAd($userId, $validatedData);
    }

    /**
     * Обновить объявление через событие
     * ВАЖНО: Не обновляет напрямую, а отправляет событие!
     */
    public function updateUserAd(int $userId, int $adId, array $adData): bool
    {
        // Проверяем права
        if (!$this->userOwnsAd($userId, $adId)) {
            return false;
        }

        // Валидируем данные
        $validatedData = $this->validateAdData($adData);
        if (!$validatedData) {
            return false;
        }

        return $this->operationsHandler->updateUserAd($userId, $adId, $validatedData);
    }

    /**
     * Удалить объявление (мягкое удаление)
     */
    public function deleteUserAd(int $userId, int $adId): bool
    {
        if (!$this->userOwnsAd($userId, $adId)) {
            return false;
        }

        return $this->operationsHandler->deleteUserAd($userId, $adId);
    }

    /**
     * Архивировать объявление
     */
    public function archiveUserAd(int $userId, int $adId): bool
    {
        if (!$this->userOwnsAd($userId, $adId)) {
            return false;
        }

        return $this->operationsHandler->archiveUserAd($userId, $adId);
    }

    /**
     * Восстановить объявление из архива
     */
    public function restoreUserAd(int $userId, int $adId): bool
    {
        if (!$this->userOwnsAd($userId, $adId)) {
            return false;
        }

        return $this->operationsHandler->restoreUserAd($userId, $adId);
    }

    /**
     * Опубликовать объявление
     */
    public function publishUserAd(int $userId, int $adId): bool
    {
        if (!$this->userOwnsAd($userId, $adId)) {
            return false;
        }

        return $this->operationsHandler->publishUserAd($userId, $adId);
    }

    // === СТАТИСТИКА И АНАЛИТИКА ===

    /**
     * Получить статистику объявлений пользователя
     */
    public function getUserAdsStatistics(int $userId): array
    {
        return $this->statisticsHandler->getUserAdsStatistics($userId);
    }

    /**
     * Получить доходы от объявлений
     */
    public function getUserAdsRevenue(int $userId): array
    {
        return $this->statisticsHandler->getUserAdsRevenue($userId);
    }

    // === ВАЛИДАЦИЯ И ПРОВЕРКИ ===

    /**
     * Проверить может ли пользователь создать новое объявление
     */
    public function canUserCreateAd(int $userId): bool
    {
        return $this->validationHandler->canUserCreateAd($userId);
    }

    /**
     * Проверить принадлежит ли объявление пользователю
     * Заменяет: $user->ads()->where('id', $adId)->exists()
     */
    public function userOwnsAd(int $userId, int $adId): bool
    {
        return $this->validationHandler->userOwnsAd($userId, $adId);
    }

    /**
     * Валидировать данные объявления
     */
    public function validateAdData(array $data): ?array
    {
        return $this->validationHandler->validateAdData($data);
    }

    /**
     * Проверить готовность объявления к публикации
     */
    public function isAdReadyForPublication(int $adId): array
    {
        return $this->validationHandler->isAdReadyForPublication($adId);
    }

    /**
     * Проверить лимиты пользователя по объявлениям
     */
    public function checkUserLimits(int $userId): array
    {
        return $this->validationHandler->checkUserLimits($userId);
    }
}