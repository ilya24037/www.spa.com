<?php

namespace App\Domain\Analytics\Contracts;

use App\Domain\Analytics\Models\PageView;
use App\Domain\Analytics\Models\UserAction;
use App\Domain\Analytics\DTOs\TrackPageViewDTO;
use App\Domain\Analytics\DTOs\TrackUserActionDTO;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * Интерфейс сервиса аналитики
 */
interface AnalyticsServiceInterface
{
    /**
     * Записать просмотр страницы
     */
    public function trackPageView(TrackPageViewDTO $dto): PageView;

    /**
     * Записать действие пользователя
     */
    public function trackUserAction(TrackUserActionDTO $dto): UserAction;

    /**
     * Обновить длительность просмотра страницы
     */
    public function updatePageViewDuration(int $pageViewId, int $durationSeconds): bool;

    /**
     * Получить статистику просмотров за период
     */
    public function getPageViewStats(Carbon $from, Carbon $to, ?string $viewableType = null): array;

    /**
     * Получить статистику действий за период
     */
    public function getUserActionStats(Carbon $from, Carbon $to, ?string $actionType = null): array;

    /**
     * Получить воронку конверсий
     */
    public function getConversionFunnel(Carbon $from, Carbon $to): array;

    /**
     * Получить популярные страницы
     */
    public function getTopPages(Carbon $from, Carbon $to, int $limit = 10): Collection;

    /**
     * Получить активность пользователя
     */
    public function getUserActivity(int $userId, int $days = 30): array;

    /**
     * Получить статистику по устройствам
     */
    public function getDeviceStats(Carbon $from, Carbon $to): array;

    /**
     * Очистка старых данных
     */
    public function cleanup(int $daysToKeep = 365): array;

    /**
     * Получить метрики производительности
     */
    public function getPerformanceMetrics(Carbon $from, Carbon $to): array;
}