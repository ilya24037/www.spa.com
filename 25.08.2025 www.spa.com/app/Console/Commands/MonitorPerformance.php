<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Infrastructure\Monitoring\MetricsCollectorService;
use App\Infrastructure\Monitoring\MetricsDisplayService;
use App\Infrastructure\Monitoring\PerformanceReportService;

class MonitorPerformance extends Command
{
    protected $signature = 'app:monitor-performance 
                            {--realtime : Monitor in real-time}
                            {--report : Generate performance report}';

    protected $description = 'Мониторинг производительности приложения';

    private MetricsCollectorService $metricsCollector;
    private MetricsDisplayService $metricsDisplay;
    private PerformanceReportService $reportService;
    private array $metrics = [];

    public function __construct()
    {
        parent::__construct();
        
        $this->metricsCollector = new MetricsCollectorService();
        $this->metricsDisplay = new MetricsDisplayService($this);
        $this->reportService = new PerformanceReportService($this->metricsCollector, $this);
    }

    public function handle()
    {
        if ($this->option('realtime')) {
            $this->monitorRealtime();
        } elseif ($this->option('report')) {
            $this->reportService->generateReport();
        } else {
            $this->showCurrentMetrics();
        }
    }

    protected function monitorRealtime()
    {
        $this->metricsDisplay->showRealtimeHeader();
        
        while (true) {
            $this->metricsDisplay->clearScreen();
            $metrics = $this->metricsCollector->collectAllMetrics();
            $this->metricsDisplay->displayMetrics($metrics);
            
            // Проверяем пороговые значения и показываем предупреждения
            $warnings = $this->reportService->checkThresholds($metrics);
            if (!empty($warnings)) {
                $this->newLine();
                $this->error('Предупреждения:');
                foreach ($warnings as $warning) {
                    $this->warn($warning['message']);
                }
            }
            
            sleep(5);
        }
    }

    /**
     * Показать текущие метрики
     */
    protected function showCurrentMetrics()
    {
        $metrics = $this->metricsCollector->collectAllMetrics();
        $this->metricsDisplay->displayMetrics($metrics);
        
        // Проверяем пороговые значения
        $warnings = $this->reportService->checkThresholds($metrics);
        if (!empty($warnings)) {
            $this->newLine();
            $this->error('Предупреждения:');
            foreach ($warnings as $warning) {
                $this->warn($warning['message']);
            }
        }
    }

    /**
     * @deprecated Используйте MetricsCollectorService::collectDatabaseMetrics()
     */
    protected function collectDatabaseMetrics(): array
    {
        return $this->metricsCollector->collectDatabaseMetrics();
    }

    /**
     * @deprecated Используйте MetricsCollectorService::collectCacheMetrics()
     */
    protected function collectCacheMetrics(): array
    {
        return $this->metricsCollector->collectCacheMetrics();
    }

    /**
     * @deprecated Используйте MetricsCollectorService::collectMemoryMetrics()
     */
    protected function collectMemoryMetrics(): array
    {
        return $this->metricsCollector->collectMemoryMetrics();
    }

    /**
     * @deprecated Используйте MetricsCollectorService::collectRequestMetrics()
     */
    protected function collectRequestMetrics(): array
    {
        return $this->metricsCollector->collectRequestMetrics();
    }

    /**
     * @deprecated Используйте MetricsCollectorService::collectQueueMetrics()
     */
    protected function collectQueueMetrics(): array
    {
        return $this->metricsCollector->collectQueueMetrics();
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

    /**
     * @deprecated Используйте PerformanceReportService::checkThresholds()
     */
    protected function checkThresholds()
    {
        $warnings = $this->reportService->checkThresholds($this->metrics);
        
        if (!empty($warnings)) {
            $this->newLine();
            $this->error('Предупреждения:');
            foreach ($warnings as $warning) {
                $this->warn($warning['message']);
            }
        }
    }

    /**
     * @deprecated Используйте PerformanceReportService::generateReport()
     */
    protected function generateReport()
    {
        return $this->reportService->generateReport();
    }

    /**
     * @deprecated Используйте MetricsCollectorService::collectDetailedMetrics()
     */
    protected function collectDetailedMetrics(): array
    {
        return $this->metricsCollector->collectDetailedMetrics();
    }

    /**
     * @deprecated Используйте PerformanceReportService::getTopSlowQueries()
     */
    protected function getTopSlowQueries(): array
    {
        return $this->reportService->getTopSlowQueries();
    }

    /**
     * @deprecated Используйте PerformanceReportService::getTopMemoryConsumers()
     */
    protected function getTopMemoryConsumers(): array
    {
        return $this->reportService->getTopMemoryConsumers();
    }

    /**
     * @deprecated Используйте PerformanceReportService::generateRecommendations()
     */
    protected function generateRecommendations(): array
    {
        return $this->reportService->generateRecommendations();
    }

    /**
     * @deprecated Эти методы перенесены в MetricsCollectorService
     */
}