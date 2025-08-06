<?php

namespace App\Console\Commands\Performance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Services\Performance\CacheService;

/**
 * Команда мониторинга производительности
 * Проверяет скорость работы как у Wildberries
 */
class PerformanceMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'performance:monitor 
                          {--url=/ : URL для проверки}
                          {--runs=5 : Количество прогонов}
                          {--detailed : Подробный отчет}';

    /**
     * The console command description.
     */
    protected $description = 'Мониторинг производительности приложения';

    /**
     * Сервис кеширования
     */
    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Запуск мониторинга производительности SPA Platform');
        $this->newLine();

        // Параметры
        $url = $this->option('url');
        $runs = (int) $this->option('runs');
        $detailed = $this->option('detailed');

        // Проверки
        $results = [
            'database' => $this->checkDatabasePerformance(),
            'cache' => $this->checkCachePerformance(),
            'http' => $this->checkHttpPerformance($url, $runs),
            'memory' => $this->checkMemoryUsage(),
            'disk' => $this->checkDiskUsage(),
        ];

        // Отображение результатов
        $this->displayResults($results, $detailed);

        // Рекомендации по оптимизации
        $this->showOptimizationRecommendations($results);

        return Command::SUCCESS;
    }

    /**
     * Проверка производительности базы данных
     */
    private function checkDatabasePerformance(): array
    {
        $this->info('📊 Проверка производительности БД...');

        $startTime = microtime(true);
        
        // Тестовые запросы
        $queries = [
            'users_count' => fn() => DB::table('users')->count(),
            'ads_count' => fn() => DB::table('ads')->count(),
            'masters_active' => fn() => DB::table('master_profiles')
                ->where('status', 'active')
                ->count(),
            'complex_join' => fn() => DB::table('ads')
                ->join('master_profiles', 'ads.user_id', '=', 'master_profiles.user_id')
                ->select('ads.id', 'master_profiles.display_name')
                ->limit(10)
                ->get(),
        ];

        $results = [];
        $totalQueries = 0;

        foreach ($queries as $name => $query) {
            $queryStart = microtime(true);
            
            try {
                $result = $query();
                $duration = (microtime(true) - $queryStart) * 1000;
                
                $results[$name] = [
                    'duration_ms' => round($duration, 2),
                    'status' => 'success',
                    'result_count' => is_countable($result) ? count($result) : (is_numeric($result) ? $result : 1)
                ];
                
                $totalQueries++;
                
            } catch (\Exception $e) {
                $results[$name] = [
                    'duration_ms' => 0,
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }

        $totalTime = (microtime(true) - $startTime) * 1000;

        return [
            'total_time_ms' => round($totalTime, 2),
            'queries_count' => $totalQueries,
            'avg_query_time_ms' => $totalQueries > 0 ? round($totalTime / $totalQueries, 2) : 0,
            'queries' => $results,
            'status' => $totalTime < 100 ? 'excellent' : ($totalTime < 500 ? 'good' : 'needs_optimization')
        ];
    }

    /**
     * Проверка производительности кеша
     */
    private function checkCachePerformance(): array
    {
        $this->info('💾 Проверка производительности кеша...');

        $startTime = microtime(true);
        
        // Тестовые операции с кешем
        $testKey = 'performance_test_' . time();
        $testData = ['test' => 'data', 'timestamp' => time()];

        $operations = [];

        // Запись в кеш
        $writeStart = microtime(true);
        Cache::put($testKey, $testData, 300);
        $operations['write'] = (microtime(true) - $writeStart) * 1000;

        // Чтение из кеша
        $readStart = microtime(true);
        $cached = Cache::get($testKey);
        $operations['read'] = (microtime(true) - $readStart) * 1000;

        // Удаление из кеша
        $deleteStart = microtime(true);
        Cache::forget($testKey);
        $operations['delete'] = (microtime(true) - $deleteStart) * 1000;

        $totalTime = (microtime(true) - $startTime) * 1000;

        // Статистика кеша
        $cacheStats = $this->cacheService->getCacheStats();

        return [
            'total_time_ms' => round($totalTime, 2),
            'operations' => array_map(fn($time) => round($time, 2), $operations),
            'cache_stats' => $cacheStats,
            'status' => $totalTime < 10 ? 'excellent' : ($totalTime < 50 ? 'good' : 'needs_optimization')
        ];
    }

    /**
     * Проверка HTTP производительности
     */
    private function checkHttpPerformance(string $url, int $runs): array
    {
        $this->info("🌐 Проверка HTTP производительности ({$runs} запросов)...");

        $baseUrl = config('app.url');
        $fullUrl = $baseUrl . $url;
        
        $times = [];
        $errors = 0;

        for ($i = 0; $i < $runs; $i++) {
            $startTime = microtime(true);
            
            try {
                $response = Http::timeout(10)->get($fullUrl);
                $duration = (microtime(true) - $startTime) * 1000;
                
                if ($response->successful()) {
                    $times[] = $duration;
                } else {
                    $errors++;
                }
                
            } catch (\Exception $e) {
                $errors++;
            }
        }

        if (empty($times)) {
            return [
                'status' => 'error',
                'error' => 'Все запросы завершились ошибкой',
                'errors_count' => $errors
            ];
        }

        $avgTime = array_sum($times) / count($times);
        $minTime = min($times);
        $maxTime = max($times);

        return [
            'url' => $url,
            'runs' => $runs,
            'avg_time_ms' => round($avgTime, 2),
            'min_time_ms' => round($minTime, 2),
            'max_time_ms' => round($maxTime, 2),
            'errors_count' => $errors,
            'success_rate' => round((count($times) / $runs) * 100, 1),
            'status' => $avgTime < 100 ? 'excellent' : ($avgTime < 500 ? 'good' : 'needs_optimization')
        ];
    }

    /**
     * Проверка использования памяти
     */
    private function checkMemoryUsage(): array
    {
        $this->info('🧠 Проверка использования памяти...');

        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        return [
            'current_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak_mb' => round($memoryPeak / 1024 / 1024, 2),
            'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
            'usage_percent' => round(($memoryPeak / $memoryLimit) * 100, 1),
            'status' => ($memoryPeak / $memoryLimit) < 0.7 ? 'good' : 'high'
        ];
    }

    /**
     * Проверка использования диска
     */
    private function checkDiskUsage(): array
    {
        $this->info('💽 Проверка использования диска...');

        $storagePath = storage_path();
        $totalBytes = disk_total_space($storagePath);
        $freeBytes = disk_free_space($storagePath);
        $usedBytes = $totalBytes - $freeBytes;

        return [
            'total_gb' => round($totalBytes / 1024 / 1024 / 1024, 2),
            'used_gb' => round($usedBytes / 1024 / 1024 / 1024, 2),
            'free_gb' => round($freeBytes / 1024 / 1024 / 1024, 2),
            'usage_percent' => round(($usedBytes / $totalBytes) * 100, 1),
            'status' => ($usedBytes / $totalBytes) < 0.8 ? 'good' : 'warning'
        ];
    }

    /**
     * Отображение результатов
     */
    private function displayResults(array $results, bool $detailed): void
    {
        $this->newLine();
        $this->info('📈 РЕЗУЛЬТАТЫ МОНИТОРИНГА ПРОИЗВОДИТЕЛЬНОСТИ');
        $this->line('===============================================');

        // База данных
        $db = $results['database'];
        $this->line("📊 База данных: {$this->getStatusEmoji($db['status'])} {$db['total_time_ms']}мс");
        
        if ($detailed) {
            $this->line("   └── Запросов: {$db['queries_count']}");
            $this->line("   └── Среднее время: {$db['avg_query_time_ms']}мс");
        }

        // Кеш
        $cache = $results['cache'];
        $this->line("💾 Кеш: {$this->getStatusEmoji($cache['status'])} {$cache['total_time_ms']}мс");
        
        if ($detailed) {
            $this->line("   └── Драйвер: {$cache['cache_stats']['driver']}");
            if (isset($cache['cache_stats']['memory_used'])) {
                $this->line("   └── Память: {$cache['cache_stats']['memory_used']}");
            }
        }

        // HTTP
        $http = $results['http'];
        if ($http['status'] !== 'error') {
            $this->line("🌐 HTTP: {$this->getStatusEmoji($http['status'])} {$http['avg_time_ms']}мс");
            
            if ($detailed) {
                $this->line("   └── Мин/Макс: {$http['min_time_ms']}/{$http['max_time_ms']}мс");
                $this->line("   └── Успешность: {$http['success_rate']}%");
            }
        } else {
            $this->line("🌐 HTTP: ❌ Ошибка соединения");
        }

        // Память
        $memory = $results['memory'];
        $this->line("🧠 Память: {$this->getStatusEmoji($memory['status'])} {$memory['current_mb']}МБ ({$memory['usage_percent']}%)");

        // Диск
        $disk = $results['disk'];
        $this->line("💽 Диск: {$this->getStatusEmoji($disk['status'])} {$disk['used_gb']}ГБ использовано ({$disk['usage_percent']}%)");

        $this->newLine();
        $this->line('===============================================');
    }

    /**
     * Рекомендации по оптимизации
     */
    private function showOptimizationRecommendations(array $results): void
    {
        $this->info('💡 РЕКОМЕНДАЦИИ ПО ОПТИМИЗАЦИИ');
        $this->line('===============================');

        $recommendations = [];

        // БД рекомендации
        if ($results['database']['status'] === 'needs_optimization') {
            $recommendations[] = '📊 База данных медленная - добавьте индексы, оптимизируйте запросы';
        }

        // Кеш рекомендации
        if ($results['cache']['status'] === 'needs_optimization') {
            $recommendations[] = '💾 Кеш медленный - рассмотрите Redis вместо файлового кеша';
        }

        // HTTP рекомендации
        if (isset($results['http']['status']) && $results['http']['status'] === 'needs_optimization') {
            $recommendations[] = '🌐 HTTP медленный - включите сжатие, оптимизируйте изображения';
            $recommendations[] = '⚡ Рассмотрите использование CDN для статичных ресурсов';
        }

        // Память рекомендации
        if ($results['memory']['status'] === 'high') {
            $recommendations[] = '🧠 Высокое потребление памяти - оптимизируйте запросы, используйте пагинацию';
        }

        // Диск рекомендации
        if ($results['disk']['status'] === 'warning') {
            $recommendations[] = '💽 Мало места на диске - очистите логи, настройте ротацию файлов';
        }

        // Общие рекомендации для достижения стандартов Wildberries
        $recommendations[] = '🚀 Для достижения <100мс как у Wildberries:';
        $recommendations[] = '   • Используйте Redis для кеширования';
        $recommendations[] = '   • Настройте CDN для статики';
        $recommendations[] = '   • Оптимизируйте изображения (WebP, адаптивность)';
        $recommendations[] = '   • Включите HTTP/2 и сжатие';
        $recommendations[] = '   • Используйте ленивую загрузку компонентов';

        foreach ($recommendations as $recommendation) {
            $this->line($recommendation);
        }

        $this->newLine();
    }

    /**
     * Получение эмодзи статуса
     */
    private function getStatusEmoji(string $status): string
    {
        return match ($status) {
            'excellent' => '🟢',
            'good' => '🟡',
            'needs_optimization', 'high', 'warning' => '🔴',
            'error' => '❌',
            default => '⚪'
        };
    }

    /**
     * Парсинг лимита памяти
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $limit = (int) $limit;

        switch ($last) {
            case 'g':
                $limit *= 1024;
            case 'm':
                $limit *= 1024;
            case 'k':
                $limit *= 1024;
        }

        return $limit;
    }
}