<?php

namespace App\Domain\Analytics\Handlers;

use App\Domain\Analytics\Models\PageView;
use App\Domain\Analytics\Models\UserAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Обработчик обслуживания данных аналитики
 */
class AnalyticsMaintenanceHandler
{
    /**
     * Очистка старых данных
     */
    public function cleanup(int $daysToKeep = 365): array
    {
        $cutoffDate = now()->subDays($daysToKeep);

        $deletedPageViews = PageView::where('viewed_at', '<', $cutoffDate)->count();
        PageView::where('viewed_at', '<', $cutoffDate)->delete();

        $deletedUserActions = UserAction::where('performed_at', '<', $cutoffDate)->count();
        UserAction::where('performed_at', '<', $cutoffDate)->delete();

        Log::info('Analytics cleanup completed', [
            'cutoff_date' => $cutoffDate->format('Y-m-d'),
            'deleted_page_views' => $deletedPageViews,
            'deleted_user_actions' => $deletedUserActions,
        ]);

        return [
            'deleted_page_views' => $deletedPageViews,
            'deleted_user_actions' => $deletedUserActions,
            'cutoff_date' => $cutoffDate->format('Y-m-d'),
        ];
    }

    /**
     * Очистка данных ботов
     */
    public function cleanupBotData(): array
    {
        $botUserAgents = [
            'bot', 'crawler', 'spider', 'scraper', 'googlebot', 
            'bingbot', 'yandexbot', 'facebookexternalhit'
        ];

        $query = PageView::query();
        
        foreach ($botUserAgents as $pattern) {
            $query->orWhere('user_agent', 'LIKE', "%{$pattern}%");
        }

        $deletedBotViews = $query->count();
        $query->delete();

        // Также очищаем действия от ботов (если есть user_agent в UserAction)
        $deletedBotActions = 0;
        if (DB::getSchemaBuilder()->hasColumn('user_actions', 'user_agent')) {
            $actionQuery = UserAction::query();
            
            foreach ($botUserAgents as $pattern) {
                $actionQuery->orWhere('user_agent', 'LIKE', "%{$pattern}%");
            }
            
            $deletedBotActions = $actionQuery->count();
            $actionQuery->delete();
        }

        Log::info('Bot data cleanup completed', [
            'deleted_bot_page_views' => $deletedBotViews,
            'deleted_bot_actions' => $deletedBotActions,
        ]);

        return [
            'deleted_bot_page_views' => $deletedBotViews,
            'deleted_bot_actions' => $deletedBotActions,
        ];
    }

    /**
     * Архивирование старых данных
     */
    public function archiveOldData(int $daysToKeepActive = 90): array
    {
        $archiveDate = now()->subDays($daysToKeepActive);
        
        // Создаем архивные таблицы если не существуют
        $this->createArchiveTables();
        
        // Архивируем просмотры страниц
        $archivedViews = DB::table('page_views')
            ->where('viewed_at', '<', $archiveDate)
            ->count();

        if ($archivedViews > 0) {
            DB::statement("
                INSERT INTO page_views_archive 
                SELECT * FROM page_views 
                WHERE viewed_at < ?
            ", [$archiveDate]);
            
            PageView::where('viewed_at', '<', $archiveDate)->delete();
        }

        // Архивируем действия пользователей
        $archivedActions = DB::table('user_actions')
            ->where('performed_at', '<', $archiveDate)
            ->count();

        if ($archivedActions > 0) {
            DB::statement("
                INSERT INTO user_actions_archive 
                SELECT * FROM user_actions 
                WHERE performed_at < ?
            ", [$archiveDate]);
            
            UserAction::where('performed_at', '<', $archiveDate)->delete();
        }

        Log::info('Data archiving completed', [
            'archive_date' => $archiveDate->format('Y-m-d'),
            'archived_page_views' => $archivedViews,
            'archived_user_actions' => $archivedActions,
        ]);

        return [
            'archived_page_views' => $archivedViews,
            'archived_user_actions' => $archivedActions,
            'archive_date' => $archiveDate->format('Y-m-d'),
        ];
    }

    /**
     * Оптимизация таблиц аналитики
     */
    public function optimizeTables(): array
    {
        $results = [];

        // Оптимизируем таблицы
        $tables = ['page_views', 'user_actions'];
        
        foreach ($tables as $table) {
            try {
                DB::statement("OPTIMIZE TABLE {$table}");
                $results[$table] = 'optimized';
            } catch (\Exception $e) {
                Log::error("Failed to optimize table {$table}", [
                    'error' => $e->getMessage()
                ]);
                $results[$table] = 'failed';
            }
        }

        // Обновляем статистику таблиц
        foreach ($tables as $table) {
            try {
                DB::statement("ANALYZE TABLE {$table}");
            } catch (\Exception $e) {
                Log::warning("Failed to analyze table {$table}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Table optimization completed', $results);

        return $results;
    }

    /**
     * Проверка целостности данных
     */
    public function validateDataIntegrity(): array
    {
        $issues = [];

        // Проверяем orphaned записи в user_actions
        $orphanedActions = UserAction::whereNotNull('user_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('users')
                      ->whereRaw('users.id = user_actions.user_id');
            })
            ->count();

        if ($orphanedActions > 0) {
            $issues[] = "Found {$orphanedActions} user actions with missing users";
        }

        // Проверяем дубликаты
        $duplicatePageViews = DB::table('page_views')
            ->select('user_id', 'url', 'viewed_at', DB::raw('count(*) as count'))
            ->groupBy('user_id', 'url', 'viewed_at')
            ->having('count', '>', 1)
            ->count();

        if ($duplicatePageViews > 0) {
            $issues[] = "Found {$duplicatePageViews} potential duplicate page views";
        }

        // Проверяем аномальные значения
        $anomalousViews = PageView::where('duration_seconds', '>', 3600) // Больше часа
            ->count();

        if ($anomalousViews > 0) {
            $issues[] = "Found {$anomalousViews} page views with suspicious duration";
        }

        Log::info('Data integrity check completed', [
            'issues_found' => count($issues),
            'issues' => $issues,
        ]);

        return [
            'issues_count' => count($issues),
            'issues' => $issues,
            'status' => count($issues) === 0 ? 'clean' : 'issues_found',
        ];
    }

    /**
     * Создать архивные таблицы
     */
    protected function createArchiveTables(): void
    {
        // Создаем архивную таблицу для page_views
        if (!DB::getSchemaBuilder()->hasTable('page_views_archive')) {
            DB::statement("
                CREATE TABLE page_views_archive LIKE page_views
            ");
        }

        // Создаем архивную таблицу для user_actions
        if (!DB::getSchemaBuilder()->hasTable('user_actions_archive')) {
            DB::statement("
                CREATE TABLE user_actions_archive LIKE user_actions
            ");
        }
    }

    /**
     * Получить размеры таблиц
     */
    public function getTableSizes(): array
    {
        $tables = ['page_views', 'user_actions', 'page_views_archive', 'user_actions_archive'];
        $sizes = [];

        foreach ($tables as $table) {
            try {
                $result = DB::select("
                    SELECT 
                        table_name,
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                        table_rows
                    FROM information_schema.TABLES 
                    WHERE table_schema = ? AND table_name = ?
                ", [config('database.connections.mysql.database'), $table]);

                if (!empty($result)) {
                    $sizes[$table] = [
                        'size_mb' => $result[0]->size_mb ?? 0,
                        'rows' => $result[0]->table_rows ?? 0,
                    ];
                } else {
                    $sizes[$table] = ['size_mb' => 0, 'rows' => 0];
                }
            } catch (\Exception $e) {
                $sizes[$table] = ['error' => $e->getMessage()];
            }
        }

        return $sizes;
    }

    /**
     * Сжать данные аналитики (агрегация по дням)
     */
    public function compressOldData(int $daysToKeepDetailed = 30): array
    {
        $compressDate = now()->subDays($daysToKeepDetailed);
        
        // Создаем агрегированную таблицу если не существует
        $this->createAggregatedTables();
        
        // Агрегируем данные просмотров страниц по дням
        $compressedViews = DB::table('page_views')
            ->where('viewed_at', '<', $compressDate)
            ->select(
                DB::raw('DATE(viewed_at) as date'),
                'url',
                'device_type',
                'country',
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(DISTINCT ip_address) as unique_views'),
                DB::raw('AVG(duration_seconds) as avg_duration')
            )
            ->groupBy('date', 'url', 'device_type', 'country')
            ->get();

        // Вставляем агрегированные данные
        foreach ($compressedViews as $row) {
            DB::table('page_views_daily')->insertOrIgnore((array) $row);
        }

        // Удаляем детальные данные
        $deletedViews = PageView::where('viewed_at', '<', $compressDate)->count();
        PageView::where('viewed_at', '<', $compressDate)->delete();

        Log::info('Data compression completed', [
            'compress_date' => $compressDate->format('Y-m-d'),
            'compressed_views' => $compressedViews->count(),
            'deleted_detailed_views' => $deletedViews,
        ]);

        return [
            'compressed_views' => $compressedViews->count(),
            'deleted_detailed_views' => $deletedViews,
            'compress_date' => $compressDate->format('Y-m-d'),
        ];
    }

    /**
     * Создать агрегированные таблицы
     */
    protected function createAggregatedTables(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('page_views_daily')) {
            DB::statement("
                CREATE TABLE page_views_daily (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    date DATE NOT NULL,
                    url VARCHAR(2048) NOT NULL,
                    device_type VARCHAR(50),
                    country VARCHAR(2),
                    total_views INT UNSIGNED NOT NULL,
                    unique_views INT UNSIGNED NOT NULL,
                    avg_duration DECIMAL(8,2),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_daily_stats (date, url, device_type, country)
                )
            ");
        }
    }
}