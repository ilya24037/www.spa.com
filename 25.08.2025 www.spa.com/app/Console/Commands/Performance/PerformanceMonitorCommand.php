<?php

namespace App\Console\Commands\Performance;

use Illuminate\Console\Command;
use App\Infrastructure\Monitoring\MetricsCollectorService;
use App\Infrastructure\Monitoring\MetricsDisplayService;
use App\Infrastructure\Monitoring\PerformanceReportService;

/**
 * Команда мониторинга производительности
 * Использует общие сервисы мониторинга для избежания дублирования
 * 
 * @deprecated Используйте app:monitor-performance вместо этой команды
 */
class PerformanceMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'performance:monitor 
                          {--url=/ : URL для проверки}
                          {--runs=5 : Количество прогонов}
                          {--detailed : Подробный отчет}
                          {--report : Генерировать JSON отчет}
                          {--realtime : Мониторинг в реальном времени}';

    /**
     * The console command description.
     */
    protected $description = '[DEPRECATED] Мониторинг производительности приложения. Используйте app:monitor-performance';

    private MetricsCollectorService $metricsCollector;
    private MetricsDisplayService $metricsDisplay;
    private PerformanceReportService $reportService;

    public function __construct()
    {
        parent::__construct();
        
        $this->metricsCollector = new MetricsCollectorService();
        $this->metricsDisplay = new MetricsDisplayService($this);
        $this->reportService = new PerformanceReportService($this->metricsCollector, $this);
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Предупреждение об устаревшей команде
        $this->warn('⚠️  Эта команда устарела и будет удалена в следующей версии.');
        $this->warn('Используйте вместо неё: php artisan app:monitor-performance');
        $this->newLine();

        // Спрашиваем, хочет ли пользователь продолжить
        if (!$this->confirm('Хотите продолжить с устаревшей командой?')) {
            $this->info('Используйте: php artisan app:monitor-performance');
            return Command::SUCCESS;
        }

        // Делегируем работу общим сервисам
        if ($this->option('realtime')) {
            return $this->runRealtimeMonitoring();
        }

        if ($this->option('report')) {
            return $this->generateReport();
        }

        if ($this->option('detailed')) {
            return $this->showDetailedMetrics();
        }

        // Обычный режим - показываем текущие метрики
        return $this->showCurrentMetrics();
    }

    /**
     * Показать текущие метрики
     */
    private function showCurrentMetrics(): int
    {
        $this->info('🚀 Мониторинг производительности SPA Platform');
        $this->newLine();

        // Собираем метрики через общий сервис
        $metrics = $this->metricsCollector->collectAllMetrics();
        
        // Отображаем через общий сервис
        $this->metricsDisplay->displayMetrics($metrics);
        
        // Проверяем пороговые значения
        $warnings = $this->reportService->checkThresholds($metrics);
        if (!empty($warnings)) {
            $this->newLine();
            $this->error('Обнаружены проблемы производительности:');
            foreach ($warnings as $warning) {
                $this->warn($warning['message']);
            }
        }

        // Показываем рекомендации
        $recommendations = $this->reportService->generateRecommendations();
        if (!empty($recommendations)) {
            $this->newLine();
            $this->comment('Рекомендации по оптимизации:');
            foreach ($recommendations as $rec) {
                $this->line("  • {$rec['message']}");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Показать детальные метрики
     */
    private function showDetailedMetrics(): int
    {
        $this->info('🚀 Детальный мониторинг производительности');
        $this->newLine();

        // Собираем детальные метрики
        $metrics = $this->metricsCollector->collectDetailedMetrics();
        
        // Отображаем детальные метрики
        $this->metricsDisplay->displayDetailedMetrics($metrics);
        
        // Показываем топ медленных запросов
        $slowQueries = $this->reportService->getTopSlowQueries();
        if (!empty($slowQueries)) {
            $this->newLine();
            $this->comment('Топ медленных запросов:');
            foreach ($slowQueries as $query) {
                $this->line("  • {$query['query']} ({$query['time']}ms, {$query['count']} раз)");
            }
        }

        // Показываем топ потребителей памяти
        $memoryConsumers = $this->reportService->getTopMemoryConsumers();
        if (!empty($memoryConsumers)) {
            $this->newLine();
            $this->comment('Топ потребителей памяти:');
            foreach ($memoryConsumers as $consumer) {
                $this->line("  • {$consumer['component']}: {$consumer['memory_mb']} MB");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Мониторинг в реальном времени
     */
    private function runRealtimeMonitoring(): int
    {
        $this->metricsDisplay->showRealtimeHeader();
        
        while (true) {
            $this->metricsDisplay->clearScreen();
            
            // Собираем и отображаем метрики
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
            
            // Ждем 5 секунд перед следующим обновлением
            sleep(5);
        }

        return Command::SUCCESS;
    }

    /**
     * Генерировать отчет
     */
    private function generateReport(): int
    {
        $filename = $this->reportService->generateReport();
        $this->info("✅ Отчет сохранен: {$filename}");
        
        return Command::SUCCESS;
    }

    /**
     * Получить имя команды для миграции
     */
    public function getMigrationCommand(): string
    {
        return 'app:monitor-performance';
    }
}