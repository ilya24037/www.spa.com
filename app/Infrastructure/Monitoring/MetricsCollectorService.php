<?php

namespace App\Infrastructure\Monitoring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

/**
 * Сервис сбора метрик производительности
 */
class MetricsCollectorService
{
    /**
     * Собрать все метрики
     */
    public function collectAllMetrics(): array
    {
        return [
            'timestamp' => now()->toDateTimeString(),
            'database' => $this->collectDatabaseMetrics(),
            'cache' => $this->collectCacheMetrics(),
            'memory' => $this->collectMemoryMetrics(),
            'requests' => $this->collectRequestMetrics(),
            'queues' => $this->collectQueueMetrics(),
        ];
    }

    /**
     * Собрать детальные метрики для отчета
     */
    public function collectDetailedMetrics(): array
    {
        $basicMetrics = $this->collectAllMetrics();
        
        return array_merge($basicMetrics, [
            'system' => $this->collectSystemMetrics(),
            'application' => $this->collectApplicationMetrics(),
            'storage' => $this->collectStorageMetrics(),
            'network' => $this->collectNetworkMetrics(),
        ]);
    }

    /**
     * Собрать метрики базы данных
     */
    public function collectDatabaseMetrics(): array
    {
        try {
            $start = microtime(true);
            
            // Тестовый запрос для проверки времени ответа
            DB::select('SELECT 1');
            $responseTime = (microtime(true) - $start) * 1000;

            // Информация о соединениях
            $processlist = DB::select('SHOW PROCESSLIST');
            $connections = count($processlist);

            // Статус базы данных
            $status = collect(DB::select('SHOW STATUS'))
                ->pluck('Value', 'Variable_name')
                ->toArray();

            return [
                'status' => 'connected',
                'response_time_ms' => round($responseTime, 2),
                'connections' => $connections,
                'queries' => (int) ($status['Queries'] ?? 0),
                'slow_queries' => (int) ($status['Slow_queries'] ?? 0),
                'threads_running' => (int) ($status['Threads_running'] ?? 0),
                'uptime' => (int) ($status['Uptime'] ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to collect database metrics', [
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Собрать метрики кеша
     */
    public function collectCacheMetrics(): array
    {
        try {
            $start = microtime(true);
            
            // Тестирование кеша
            $testKey = 'performance_test_' . time();
            Cache::put($testKey, 'test_value', 10);
            $value = Cache::get($testKey);
            Cache::forget($testKey);
            
            $responseTime = (microtime(true) - $start) * 1000;

            return [
                'status' => $value === 'test_value' ? 'working' : 'error',
                'response_time_ms' => round($responseTime, 2),
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Собрать метрики памяти
     */
    public function collectMemoryMetrics(): array
    {
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        return [
            'current_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak_mb' => round($peakMemory / 1024 / 1024, 2),
            'limit_mb' => $memoryLimit,
            'usage_percent' => $memoryLimit > 0 ? round(($memoryUsage / ($memoryLimit * 1024 * 1024)) * 100, 2) : 0,
        ];
    }

    /**
     * Собрать метрики запросов (из логов или кеша)
     */
    public function collectRequestMetrics(): array
    {
        // Получаем данные из кеша, где сохраняются счетчики
        $requests = Cache::get('app_metrics_requests', [
            'total' => 0,
            'success' => 0,
            'errors' => 0,
            'avg_response_time' => 0,
        ]);

        $errorRate = $requests['total'] > 0 ? 
            round(($requests['errors'] / $requests['total']) * 100, 2) : 0;

        return [
            'total' => $requests['total'],
            'success' => $requests['success'],
            'errors' => $requests['errors'],
            'error_rate_percent' => $errorRate,
            'avg_response_time_ms' => $requests['avg_response_time'],
        ];
    }

    /**
     * Собрать метрики очередей
     */
    public function collectQueueMetrics(): array
    {
        try {
            $defaultConnection = config('queue.default');
            
            // Для разных типов очередей нужна разная логика
            $metrics = [
                'connection' => $defaultConnection,
                'status' => 'unknown',
                'pending' => 0,
                'processed' => 0,
                'failed' => 0,
            ];

            if ($defaultConnection === 'redis') {
                $metrics = array_merge($metrics, $this->collectRedisQueueMetrics());
            } elseif ($defaultConnection === 'database') {
                $metrics = array_merge($metrics, $this->collectDatabaseQueueMetrics());
            }

            return $metrics;
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Собрать системные метрики
     */
    private function collectSystemMetrics(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'server_time' => now()->toDateTimeString(),
            'timezone' => config('app.timezone'),
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug'),
            'load_average' => $this->getLoadAverage(),
        ];
    }

    /**
     * Собрать метрики приложения
     */
    private function collectApplicationMetrics(): array
    {
        return [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_driver' => config('queue.default'),
            'mail_driver' => config('mail.default'),
        ];
    }

    /**
     * Собрать метрики хранилища
     */
    private function collectStorageMetrics(): array
    {
        $storagePath = storage_path();
        $freeBytes = disk_free_space($storagePath);
        $totalBytes = disk_total_space($storagePath);

        return [
            'free_gb' => round($freeBytes / 1024 / 1024 / 1024, 2),
            'total_gb' => round($totalBytes / 1024 / 1024 / 1024, 2),
            'used_percent' => round((($totalBytes - $freeBytes) / $totalBytes) * 100, 2),
        ];
    }

    /**
     * Собрать сетевые метрики (заглушка)
     */
    private function collectNetworkMetrics(): array
    {
        return [
            'external_apis' => $this->checkExternalApis(),
        ];
    }

    /**
     * Собрать метрики Redis очередей
     */
    private function collectRedisQueueMetrics(): array
    {
        // Реализация для Redis очередей
        return [
            'status' => 'active',
            'pending' => 0, // Количество ожидающих задач
            'processed' => 0, // Обработанные задачи
            'failed' => 0, // Провалившиеся задачи
        ];
    }

    /**
     * Собрать метрики database очередей
     */
    private function collectDatabaseQueueMetrics(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();

            return [
                'status' => 'active',
                'pending' => $pending,
                'failed' => $failed,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Парсинг лимита памяти
     */
    private function parseMemoryLimit(string $limit): float
    {
        if ($limit === '-1') return -1;
        
        $unit = strtolower(substr($limit, -1));
        $value = (float) substr($limit, 0, -1);
        
        return match($unit) {
            'g' => $value * 1024,
            'm' => $value,
            'k' => $value / 1024,
            default => $value / 1024 / 1024,
        };
    }

    /**
     * Получить загрузку системы
     */
    private function getLoadAverage(): ?array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => $load[0],
                '5min' => $load[1],
                '15min' => $load[2],
            ];
        }
        
        return null;
    }

    /**
     * Проверить внешние API
     */
    private function checkExternalApis(): array
    {
        // Заглушка для проверки внешних сервисов
        return [
            'payment_gateway' => 'ok',
            'email_service' => 'ok',
            'cdn' => 'ok',
        ];
    }
}