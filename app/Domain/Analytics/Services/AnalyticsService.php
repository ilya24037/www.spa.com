<?php

namespace App\Domain\Analytics\Services;

use App\Domain\Analytics\Models\PageView;
use App\Domain\Analytics\Models\UserAction;
use App\Domain\Analytics\Contracts\AnalyticsServiceInterface;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\Analytics\DTOs\TrackPageViewDTO;
use App\Domain\Analytics\DTOs\TrackUserActionDTO;
use App\Domain\Analytics\Handlers\AnalyticsTrackingHandler;
use App\Domain\Analytics\Handlers\AnalyticsStatsHandler;
use App\Domain\Analytics\Handlers\AnalyticsConversionHandler;
use App\Domain\Analytics\Handlers\AnalyticsMaintenanceHandler;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * Упрощенный сервис аналитики
 * Делегирует логику специализированным обработчикам
 */
class AnalyticsService implements AnalyticsServiceInterface
{
    protected AnalyticsTrackingHandler $trackingHandler;
    protected AnalyticsStatsHandler $statsHandler;
    protected AnalyticsConversionHandler $conversionHandler;
    protected AnalyticsMaintenanceHandler $maintenanceHandler;

    public function __construct(protected AnalyticsRepository $repository)
    {
        $this->trackingHandler = new AnalyticsTrackingHandler($repository);
        $this->statsHandler = new AnalyticsStatsHandler();
        $this->conversionHandler = new AnalyticsConversionHandler();
        $this->maintenanceHandler = new AnalyticsMaintenanceHandler();
    }

    // === ТРЕКИНГ ===

    /**
     * Записать просмотр страницы
     */
    public function trackPageView(TrackPageViewDTO $dto): PageView
    {
        return $this->trackingHandler->trackPageView($dto);
    }

    /**
     * Записать действие пользователя
     */
    public function trackUserAction(TrackUserActionDTO $dto): UserAction
    {
        return $this->trackingHandler->trackUserAction($dto);
    }

    /**
     * Обновить длительность просмотра страницы
     */
    public function updatePageViewDuration(int $pageViewId, int $durationSeconds): bool
    {
        return $this->trackingHandler->updatePageViewDuration($pageViewId, $durationSeconds);
    }

    /**
     * Массовая запись событий
     */
    public function batchTrackPageViews(array $pageViewDTOs): array
    {
        return $this->trackingHandler->batchTrackPageViews($pageViewDTOs);
    }

    public function batchTrackUserActions(array $userActionDTOs): array
    {
        return $this->trackingHandler->batchTrackUserActions($userActionDTOs);
    }

    /**
     * Записать кастомное событие
     */
    public function trackCustomEvent(array $eventData): bool
    {
        return $this->trackingHandler->trackCustomEvent($eventData);
    }

    // === СТАТИСТИКА ===

    /**
     * Получить статистику просмотров за период
     */
    public function getPageViewStats(Carbon $from, Carbon $to, ?string $viewableType = null): array
    {
        return $this->statsHandler->getPageViewStats($from, $to, $viewableType);
    }

    /**
     * Получить статистику действий за период
     */
    public function getUserActionStats(Carbon $from, Carbon $to, ?string $actionType = null): array
    {
        return $this->statsHandler->getUserActionStats($from, $to, $actionType);
    }

    /**
     * Получить популярные страницы
     */
    public function getTopPages(Carbon $from, Carbon $to, int $limit = 10): Collection
    {
        return $this->statsHandler->getTopPages($from, $to, $limit);
    }

    /**
     * Получить статистику по устройствам
     */
    public function getDeviceStats(Carbon $from, Carbon $to): array
    {
        return $this->statsHandler->getDeviceStats($from, $to);
    }

    /**
     * Получить активность пользователя
     */
    public function getUserActivity(int $userId, int $days = 30): array
    {
        return $this->statsHandler->getUserActivity($userId, $days);
    }

    /**
     * Получить статистику источников трафика
     */
    public function getTrafficSources(Carbon $from, Carbon $to): array
    {
        return $this->statsHandler->getTrafficSources($from, $to);
    }

    /**
     * Получить статистику по времени
     */
    public function getHourlyStats(Carbon $from, Carbon $to): array
    {
        return $this->statsHandler->getHourlyStats($from, $to);
    }

    /**
     * Получить сравнительную статистику
     */
    public function getComparativeStats(Carbon $currentFrom, Carbon $currentTo, Carbon $previousFrom, Carbon $previousTo): array
    {
        return $this->statsHandler->getComparativeStats($currentFrom, $currentTo, $previousFrom, $previousTo);
    }

    // === КОНВЕРСИИ И ПРОИЗВОДИТЕЛЬНОСТЬ ===

    /**
     * Получить воронку конверсий
     */
    public function getConversionFunnel(Carbon $from, Carbon $to): array
    {
        return $this->conversionHandler->getConversionFunnel($from, $to);
    }

    /**
     * Получить метрики производительности
     */
    public function getPerformanceMetrics(Carbon $from, Carbon $to): array
    {
        return $this->conversionHandler->getPerformanceMetrics($from, $to);
    }

    /**
     * Получить детальные метрики конверсий
     */
    public function getDetailedConversionMetrics(Carbon $from, Carbon $to): array
    {
        return $this->conversionHandler->getDetailedConversionMetrics($from, $to);
    }

    /**
     * Получить когортный анализ
     */
    public function getCohortAnalysis(Carbon $from, Carbon $to, string $period = 'week'): array
    {
        return $this->conversionHandler->getCohortAnalysis($from, $to, $period);
    }

    /**
     * Получить анализ пути пользователя
     */
    public function getUserJourneyAnalysis(Carbon $from, Carbon $to): array
    {
        return $this->conversionHandler->getUserJourneyAnalysis($from, $to);
    }

    /**
     * Рассчитать показатель отказов
     */
    public function calculateBounceRate(Carbon $from, Carbon $to): float
    {
        return $this->conversionHandler->calculateBounceRate($from, $to);
    }

    // === ОБСЛУЖИВАНИЕ ===

    /**
     * Очистка старых данных
     */
    public function cleanup(int $daysToKeep = 365): array
    {
        return $this->maintenanceHandler->cleanup($daysToKeep);
    }

    /**
     * Очистка данных ботов
     */
    public function cleanupBotData(): array
    {
        return $this->maintenanceHandler->cleanupBotData();
    }

    /**
     * Архивирование старых данных
     */
    public function archiveOldData(int $daysToKeepActive = 90): array
    {
        return $this->maintenanceHandler->archiveOldData($daysToKeepActive);
    }

    /**
     * Оптимизация таблиц аналитики
     */
    public function optimizeTables(): array
    {
        return $this->maintenanceHandler->optimizeTables();
    }

    /**
     * Проверка целостности данных
     */
    public function validateDataIntegrity(): array
    {
        return $this->maintenanceHandler->validateDataIntegrity();
    }

    /**
     * Получить размеры таблиц
     */
    public function getTableSizes(): array
    {
        return $this->maintenanceHandler->getTableSizes();
    }

    /**
     * Сжать данные аналитики
     */
    public function compressOldData(int $daysToKeepDetailed = 30): array
    {
        return $this->maintenanceHandler->compressOldData($daysToKeepDetailed);
    }

    // === ДОПОЛНИТЕЛЬНЫЕ МЕТОДЫ ===

    /**
     * Проверить, является ли действие конверсией
     */
    public function isConversionAction(string $actionType): bool
    {
        return $this->trackingHandler->isConversionAction($actionType);
    }

    /**
     * Получить список типов конверсий
     */
    public function getConversionTypes(): array
    {
        return $this->trackingHandler->getConversionTypes();
    }

    /**
     * Валидировать данные перед трекингом
     */
    public function validateTrackingData(array $data): array
    {
        return $this->trackingHandler->validateTrackingData($data);
    }

    /**
     * Получить комплексный отчет
     */
    public function getComprehensiveReport(Carbon $from, Carbon $to): array
    {
        return [
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'days' => $from->diffInDays($to) + 1,
            ],
            'overview' => $this->getPerformanceMetrics($from, $to),
            'page_views' => $this->getPageViewStats($from, $to),
            'user_actions' => $this->getUserActionStats($from, $to),
            'conversions' => $this->getDetailedConversionMetrics($from, $to),
            'funnel' => $this->getConversionFunnel($from, $to),
            'devices' => $this->getDeviceStats($from, $to),
            'top_pages' => $this->getTopPages($from, $to)->toArray(),
            'traffic_sources' => $this->getTrafficSources($from, $to),
            'hourly_patterns' => $this->getHourlyStats($from, $to),
        ];
    }
}