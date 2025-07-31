<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MonitorPerformance extends Command
{
    protected $signature = 'app:monitor-performance 
                            {--realtime : Monitor in real-time}
                            {--report : Generate performance report}';

    protected $description = 'Мониторинг производительности приложения';

    protected array $metrics = [];

    public function handle()
    {
        if ($this->option('realtime')) {
            $this->monitorRealtime();
        } elseif ($this->option('report')) {
            $this->generateReport();
        } else {
            $this->collectMetrics();
            $this->displayMetrics();
        }
    }

    protected function monitorRealtime()
    {
        $this->info('Запуск мониторинга в реальном времени... (Ctrl+C для выхода)');
        
        while (true) {
            $this->collectMetrics();
            $this->displayMetrics();
            sleep(5);
        }
    }

    protected function collectMetrics()
    {
        $this->metrics = [
            'timestamp' => now()->toDateTimeString(),
            'database' => $this->collectDatabaseMetrics(),
            'cache' => $this->collectCacheMetrics(),
            'memory' => $this->collectMemoryMetrics(),
            'requests' => $this->collectRequestMetrics(),
            'queue' => $this->collectQueueMetrics(),
        ];
    }

    protected function collectDatabaseMetrics(): array
    {
        $metrics = [
            'connections' => 0,
            'slow_queries' => 0,
            'queries_per_second' => 0,
            'avg_query_time' => 0,
        ];

        try {
            // Количество активных соединений
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            $metrics['connections'] = $connections[0]->Value ?? 0;

            // Медленные запросы
            $slowQueries = DB::select("SHOW STATUS LIKE 'Slow_queries'");
            $metrics['slow_queries'] = $slowQueries[0]->Value ?? 0;

            // Запросов в секунду
            $questions = DB::select("SHOW STATUS LIKE 'Questions'");
            $uptime = DB::select("SHOW STATUS LIKE 'Uptime'");
            if ($questions && $uptime) {
                $metrics['queries_per_second'] = round(
                    $questions[0]->Value / $uptime[0]->Value, 
                    2
                );
            }

            // Среднее время запроса (из логов)
            $recentQueries = DB::getQueryLog();
            if (count($recentQueries) > 0) {
                $totalTime = array_sum(array_column($recentQueries, 'time'));
                $metrics['avg_query_time'] = round($totalTime / count($recentQueries), 2);
            }
        } catch (\Exception $e) {
            Log::error('Error collecting database metrics: ' . $e->getMessage());
        }

        return $metrics;
    }

    protected function collectCacheMetrics(): array
    {
        $metrics = [
            'hit_rate' => 0,
            'memory_usage' => '0B',
            'keys_count' => 0,
            'evictions' => 0,
        ];

        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::getStore()->getRedis()->connection();
                $info = $redis->info();

                $hits = $info['keyspace_hits'] ?? 0;
                $misses = $info['keyspace_misses'] ?? 0;
                $total = $hits + $misses;

                if ($total > 0) {
                    $metrics['hit_rate'] = round(($hits / $total) * 100, 2);
                }

                $metrics['memory_usage'] = $info['used_memory_human'] ?? '0B';
                $metrics['keys_count'] = $redis->dbsize();
                $metrics['evictions'] = $info['evicted_keys'] ?? 0;
            }
        } catch (\Exception $e) {
            Log::error('Error collecting cache metrics: ' . $e->getMessage());
        }

        return $metrics;
    }

    protected function collectMemoryMetrics(): array
    {
        return [
            'current' => $this->formatBytes(memory_get_usage(true)),
            'peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit'),
            'usage_percent' => round(
                (memory_get_usage(true) / $this->getMemoryLimitBytes()) * 100, 
                2
            ),
        ];
    }

    protected function collectRequestMetrics(): array
    {
        $metrics = [
            'total_today' => 0,
            'avg_response_time' => 0,
            'error_rate' => 0,
            'active_users' => 0,
        ];

        try {
            // Получаем метрики из логов или APM
            $todayLogs = DB::table('request_logs')
                ->whereDate('created_at', today())
                ->selectRaw('
                    COUNT(*) as total,
                    AVG(response_time) as avg_time,
                    SUM(CASE WHEN status >= 400 THEN 1 ELSE 0 END) as errors,
                    COUNT(DISTINCT user_id) as unique_users
                ')
                ->first();

            if ($todayLogs) {
                $metrics['total_today'] = $todayLogs->total;
                $metrics['avg_response_time'] = round($todayLogs->avg_time, 2);
                $metrics['error_rate'] = $todayLogs->total > 0 
                    ? round(($todayLogs->errors / $todayLogs->total) * 100, 2)
                    : 0;
                $metrics['active_users'] = $todayLogs->unique_users;
            }
        } catch (\Exception $e) {
            // Таблица request_logs может не существовать
        }

        return $metrics;
    }

    protected function collectQueueMetrics(): array
    {
        $metrics = [
            'pending' => 0,
            'failed' => 0,
            'processed_today' => 0,
            'avg_wait_time' => 0,
        ];

        try {
            $metrics['pending'] = DB::table('jobs')->count();
            $metrics['failed'] = DB::table('failed_jobs')->count();
            
            $todayJobs = DB::table('job_logs')
                ->whereDate('processed_at', today())
                ->selectRaw('
                    COUNT(*) as total,
                    AVG(wait_time) as avg_wait
                ')
                ->first();

            if ($todayJobs) {
                $metrics['processed_today'] = $todayJobs->total;
                $metrics['avg_wait_time'] = round($todayJobs->avg_wait, 2);
            }
        } catch (\Exception $e) {
            // Таблицы могут не существовать
        }

        return $metrics;
    }

    protected function displayMetrics()
    {
        $this->newLine();
        $this->info('=== Метрики производительности ===');
        $this->info('Время: ' . $this->metrics['timestamp']);
        $this->newLine();

        // База данных
        $this->comment('📊 База данных:');
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Активные соединения', $this->metrics['database']['connections']],
                ['Медленные запросы', $this->metrics['database']['slow_queries']],
                ['Запросов/сек', $this->metrics['database']['queries_per_second']],
                ['Среднее время запроса', $this->metrics['database']['avg_query_time'] . ' мс'],
            ]
        );

        // Кеш
        $this->comment('💾 Кеш:');
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Hit Rate', $this->metrics['cache']['hit_rate'] . '%'],
                ['Использование памяти', $this->metrics['cache']['memory_usage']],
                ['Количество ключей', $this->metrics['cache']['keys_count']],
                ['Вытеснено ключей', $this->metrics['cache']['evictions']],
            ]
        );

        // Память
        $this->comment('🧠 Память:');
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Текущее использование', $this->metrics['memory']['current']],
                ['Пиковое использование', $this->metrics['memory']['peak']],
                ['Лимит', $this->metrics['memory']['limit']],
                ['Использование %', $this->metrics['memory']['usage_percent'] . '%'],
            ]
        );

        // Запросы
        if ($this->metrics['requests']['total_today'] > 0) {
            $this->comment('🌐 Запросы:');
            $this->table(
                ['Метрика', 'Значение'],
                [
                    ['Всего сегодня', $this->metrics['requests']['total_today']],
                    ['Среднее время ответа', $this->metrics['requests']['avg_response_time'] . ' мс'],
                    ['Процент ошибок', $this->metrics['requests']['error_rate'] . '%'],
                    ['Активные пользователи', $this->metrics['requests']['active_users']],
                ]
            );
        }

        // Очереди
        $this->comment('📬 Очереди:');
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['В ожидании', $this->metrics['queue']['pending']],
                ['Провалено', $this->metrics['queue']['failed']],
                ['Обработано сегодня', $this->metrics['queue']['processed_today']],
                ['Среднее время ожидания', $this->metrics['queue']['avg_wait_time'] . ' сек'],
            ]
        );

        // Предупреждения
        $this->checkThresholds();
    }

    protected function checkThresholds()
    {
        $warnings = [];

        // Проверка памяти
        if ($this->metrics['memory']['usage_percent'] > 80) {
            $warnings[] = '⚠️  Высокое использование памяти: ' . 
                         $this->metrics['memory']['usage_percent'] . '%';
        }

        // Проверка кеша
        if ($this->metrics['cache']['hit_rate'] < 80) {
            $warnings[] = '⚠️  Низкий hit rate кеша: ' . 
                         $this->metrics['cache']['hit_rate'] . '%';
        }

        // Проверка медленных запросов
        if ($this->metrics['database']['slow_queries'] > 10) {
            $warnings[] = '⚠️  Много медленных запросов: ' . 
                         $this->metrics['database']['slow_queries'];
        }

        // Проверка очередей
        if ($this->metrics['queue']['failed'] > 100) {
            $warnings[] = '⚠️  Много проваленных задач: ' . 
                         $this->metrics['queue']['failed'];
        }

        if (!empty($warnings)) {
            $this->newLine();
            $this->error('Предупреждения:');
            foreach ($warnings as $warning) {
                $this->warn($warning);
            }
        }
    }

    protected function generateReport()
    {
        $this->info('Генерация отчета о производительности...');

        $report = [
            'generated_at' => now()->toDateTimeString(),
            'period' => 'last_24_hours',
            'metrics' => $this->collectDetailedMetrics(),
            'recommendations' => $this->generateRecommendations(),
        ];

        $filename = storage_path('logs/performance-report-' . now()->format('Y-m-d-H-i-s') . '.json');
        file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));

        $this->info('Отчет сохранен: ' . $filename);
    }

    protected function collectDetailedMetrics(): array
    {
        return [
            'database' => $this->collectDatabaseMetrics(),
            'cache' => $this->collectCacheMetrics(),
            'memory' => $this->collectMemoryMetrics(),
            'requests' => $this->collectRequestMetrics(),
            'queue' => $this->collectQueueMetrics(),
            'top_slow_queries' => $this->getTopSlowQueries(),
            'top_memory_consumers' => $this->getTopMemoryConsumers(),
        ];
    }

    protected function getTopSlowQueries(): array
    {
        // Здесь должна быть логика получения медленных запросов
        return [];
    }

    protected function getTopMemoryConsumers(): array
    {
        // Здесь должна быть логика получения потребителей памяти
        return [];
    }

    protected function generateRecommendations(): array
    {
        $recommendations = [];

        if ($this->metrics['cache']['hit_rate'] < 80) {
            $recommendations[] = 'Увеличьте TTL кеша или проверьте логику инвалидации';
        }

        if ($this->metrics['database']['slow_queries'] > 10) {
            $recommendations[] = 'Оптимизируйте медленные запросы, добавьте индексы';
        }

        if ($this->metrics['memory']['usage_percent'] > 80) {
            $recommendations[] = 'Рассмотрите увеличение лимита памяти или оптимизацию кода';
        }

        return $recommendations;
    }

    protected function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }

    protected function getMemoryLimitBytes(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit == -1) {
            return PHP_INT_MAX;
        }

        $limit = strtolower($limit);
        $max = (int) $limit;
        
        switch (substr($limit, -1)) {
            case 'g': $max *= 1024;
            case 'm': $max *= 1024;
            case 'k': $max *= 1024;
        }

        return $max;
    }
}