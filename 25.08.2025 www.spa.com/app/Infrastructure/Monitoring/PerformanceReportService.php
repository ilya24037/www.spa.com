<?php

namespace App\Infrastructure\Monitoring;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Сервис генерации отчетов о производительности
 */
class PerformanceReportService
{
    private MetricsCollectorService $metricsCollector;
    private Command $command;

    public function __construct(MetricsCollectorService $metricsCollector, Command $command)
    {
        $this->metricsCollector = $metricsCollector;
        $this->command = $command;
    }

    /**
     * Генерировать полный отчет о производительности
     */
    public function generateReport(): string
    {
        $this->command->info('Генерация отчета о производительности...');

        $report = [
            'generated_at' => now()->toDateTimeString(),
            'period' => 'last_24_hours',
            'metrics' => $this->metricsCollector->collectDetailedMetrics(),
            'recommendations' => $this->generateRecommendations(),
            'summary' => $this->generateSummary(),
            'analysis' => $this->generateAnalysis(),
        ];

        $filename = $this->saveReport($report);
        $this->command->info('Отчет сохранен: ' . $filename);

        return $filename;
    }

    /**
     * Сгенерировать рекомендации на основе текущих метрик
     */
    public function generateRecommendations(): array
    {
        $metrics = $this->metricsCollector->collectAllMetrics();
        $recommendations = [];

        // Рекомендации по кешу
        if (isset($metrics['cache']['response_time_ms']) && $metrics['cache']['response_time_ms'] > 50) {
            $recommendations[] = [
                'type' => 'cache',
                'priority' => 'medium',
                'message' => 'Медленное время ответа кеша. Проверьте подключение к Redis или используйте другой драйвер кеша',
                'metric' => 'response_time: ' . $metrics['cache']['response_time_ms'] . 'ms'
            ];
        }

        // Рекомендации по базе данных
        if (isset($metrics['database']['slow_queries']) && $metrics['database']['slow_queries'] > 10) {
            $recommendations[] = [
                'type' => 'database',
                'priority' => 'high',
                'message' => 'Большое количество медленных запросов. Добавьте индексы или оптимизируйте запросы',
                'metric' => 'slow_queries: ' . $metrics['database']['slow_queries']
            ];
        }

        if (isset($metrics['database']['response_time_ms']) && $metrics['database']['response_time_ms'] > 200) {
            $recommendations[] = [
                'type' => 'database',
                'priority' => 'medium',
                'message' => 'Медленное время ответа базы данных. Проверьте подключение и нагрузку на сервер БД',
                'metric' => 'response_time: ' . $metrics['database']['response_time_ms'] . 'ms'
            ];
        }

        // Рекомендации по памяти
        if (isset($metrics['memory']['usage_percent']) && $metrics['memory']['usage_percent'] > 80) {
            $recommendations[] = [
                'type' => 'memory',
                'priority' => $metrics['memory']['usage_percent'] > 95 ? 'critical' : 'high',
                'message' => 'Высокое использование памяти. Рассмотрите увеличение лимита или оптимизацию кода',
                'metric' => 'usage: ' . $metrics['memory']['usage_percent'] . '%'
            ];
        }

        // Рекомендации по очередям
        if (isset($metrics['queues']['pending']) && $metrics['queues']['pending'] > 100) {
            $recommendations[] = [
                'type' => 'queue',
                'priority' => $metrics['queues']['pending'] > 1000 ? 'high' : 'medium',
                'message' => 'Большое количество задач в очереди. Увеличьте количество воркеров или оптимизируйте обработку',
                'metric' => 'pending: ' . $metrics['queues']['pending']
            ];
        }

        if (isset($metrics['queues']['failed']) && $metrics['queues']['failed'] > 50) {
            $recommendations[] = [
                'type' => 'queue',
                'priority' => 'high',
                'message' => 'Много провалившихся задач в очереди. Проверьте логи и исправьте ошибки',
                'metric' => 'failed: ' . $metrics['queues']['failed']
            ];
        }

        // Рекомендации по HTTP запросам
        if (isset($metrics['requests']['error_rate_percent']) && $metrics['requests']['error_rate_percent'] > 5) {
            $recommendations[] = [
                'type' => 'requests',
                'priority' => 'high',
                'message' => 'Высокий процент HTTP ошибок. Проверьте логи приложения и исправьте критические ошибки',
                'metric' => 'error_rate: ' . $metrics['requests']['error_rate_percent'] . '%'
            ];
        }

        return $recommendations;
    }

    /**
     * Генерировать краткую сводку состояния системы
     */
    private function generateSummary(): array
    {
        $metrics = $this->metricsCollector->collectAllMetrics();
        
        return [
            'overall_status' => $this->calculateOverallStatus($metrics),
            'critical_issues' => $this->findCriticalIssues($metrics),
            'performance_score' => $this->calculatePerformanceScore($metrics),
            'key_metrics' => [
                'database_status' => $metrics['database']['status'] ?? 'unknown',
                'cache_status' => $metrics['cache']['status'] ?? 'unknown',
                'memory_usage' => ($metrics['memory']['usage_percent'] ?? 0) . '%',
                'queue_status' => $metrics['queues']['status'] ?? 'unknown',
            ]
        ];
    }

    /**
     * Генерировать детальный анализ производительности
     */
    private function generateAnalysis(): array
    {
        $metrics = $this->metricsCollector->collectDetailedMetrics();
        
        return [
            'trends' => [
                'memory_trend' => 'Стабильное использование памяти',
                'database_trend' => 'Время ответа в норме',
                'cache_trend' => 'Эффективное использование кеша',
            ],
            'bottlenecks' => $this->identifyBottlenecks($metrics),
            'optimization_opportunities' => $this->findOptimizationOpportunities($metrics),
            'resource_utilization' => [
                'memory_efficiency' => $this->calculateMemoryEfficiency($metrics),
                'cache_efficiency' => $this->calculateCacheEfficiency($metrics),
                'database_efficiency' => $this->calculateDatabaseEfficiency($metrics),
            ]
        ];
    }

    /**
     * Рассчитать общий статус системы
     */
    private function calculateOverallStatus(array $metrics): string
    {
        $issues = 0;
        
        // Проверяем критические компоненты
        if (($metrics['database']['status'] ?? '') !== 'connected') $issues++;
        if (($metrics['cache']['status'] ?? '') !== 'working') $issues++;
        if (($metrics['memory']['usage_percent'] ?? 0) > 90) $issues++;
        if (($metrics['queues']['status'] ?? '') === 'error') $issues++;
        
        return match(true) {
            $issues === 0 => 'healthy',
            $issues <= 2 => 'warning',
            default => 'critical'
        };
    }

    /**
     * Найти критические проблемы
     */
    private function findCriticalIssues(array $metrics): array
    {
        $issues = [];
        
        if (($metrics['database']['status'] ?? '') !== 'connected') {
            $issues[] = 'База данных недоступна';
        }
        
        if (($metrics['memory']['usage_percent'] ?? 0) > 95) {
            $issues[] = 'Критически высокое использование памяти';
        }
        
        if (($metrics['queues']['failed'] ?? 0) > 1000) {
            $issues[] = 'Критическое количество провалившихся задач в очереди';
        }
        
        return $issues;
    }

    /**
     * Рассчитать общий балл производительности (0-100)
     */
    private function calculatePerformanceScore(array $metrics): int
    {
        $score = 100;
        
        // Штрафы за проблемы
        if (($metrics['database']['response_time_ms'] ?? 0) > 100) $score -= 10;
        if (($metrics['cache']['response_time_ms'] ?? 0) > 20) $score -= 5;
        if (($metrics['memory']['usage_percent'] ?? 0) > 80) $score -= 15;
        if (($metrics['requests']['error_rate_percent'] ?? 0) > 1) $score -= 20;
        if (($metrics['queues']['pending'] ?? 0) > 50) $score -= 10;
        
        return max(0, $score);
    }

    /**
     * Определить узкие места в производительности
     */
    private function identifyBottlenecks(array $metrics): array
    {
        $bottlenecks = [];
        
        if (($metrics['database']['response_time_ms'] ?? 0) > 200) {
            $bottlenecks[] = 'Медленные запросы к базе данных';
        }
        
        if (($metrics['memory']['usage_percent'] ?? 0) > 85) {
            $bottlenecks[] = 'Высокое использование памяти';
        }
        
        if (($metrics['queues']['pending'] ?? 0) > 100) {
            $bottlenecks[] = 'Перегруженная очередь задач';
        }
        
        return $bottlenecks;
    }

    /**
     * Найти возможности для оптимизации
     */
    private function findOptimizationOpportunities(array $metrics): array
    {
        $opportunities = [];
        
        if (($metrics['requests']['avg_response_time_ms'] ?? 0) > 500) {
            $opportunities[] = 'Оптимизация времени ответа API';
        }
        
        if (($metrics['cache']['status'] ?? '') !== 'working') {
            $opportunities[] = 'Настройка и использование кеширования';
        }
        
        return $opportunities;
    }

    /**
     * Рассчитать эффективность использования памяти
     */
    private function calculateMemoryEfficiency(array $metrics): string
    {
        $usage = $metrics['memory']['usage_percent'] ?? 0;
        
        return match(true) {
            $usage < 50 => 'excellent',
            $usage < 70 => 'good',
            $usage < 85 => 'average',
            default => 'poor'
        };
    }

    /**
     * Рассчитать эффективность кеша
     */
    private function calculateCacheEfficiency(array $metrics): string
    {
        if (($metrics['cache']['status'] ?? '') !== 'working') {
            return 'poor';
        }
        
        $responseTime = $metrics['cache']['response_time_ms'] ?? 0;
        
        return match(true) {
            $responseTime < 10 => 'excellent',
            $responseTime < 25 => 'good',
            $responseTime < 50 => 'average',
            default => 'poor'
        };
    }

    /**
     * Рассчитать эффективность базы данных
     */
    private function calculateDatabaseEfficiency(array $metrics): string
    {
        if (($metrics['database']['status'] ?? '') !== 'connected') {
            return 'poor';
        }
        
        $responseTime = $metrics['database']['response_time_ms'] ?? 0;
        $slowQueries = $metrics['database']['slow_queries'] ?? 0;
        
        return match(true) {
            $responseTime < 50 && $slowQueries === 0 => 'excellent',
            $responseTime < 100 && $slowQueries < 5 => 'good',
            $responseTime < 200 && $slowQueries < 20 => 'average',
            default => 'poor'
        };
    }

    /**
     * Сохранить отчет в файл
     */
    private function saveReport(array $report): string
    {
        $filename = storage_path('logs/performance-report-' . now()->format('Y-m-d-H-i-s') . '.json');
        
        try {
            file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $filename;
        } catch (\Exception $e) {
            Log::error('Failed to save performance report', [
                'error' => $e->getMessage(),
                'filename' => $filename
            ]);
            throw $e;
        }
    }

    /**
     * Получить список топ медленных запросов (заглушка)
     */
    public function getTopSlowQueries(): array
    {
        // В реальной системе здесь была бы логика получения медленных запросов из логов
        return [
            [
                'query' => 'SELECT * FROM users WHERE...',
                'time' => 1500,
                'count' => 25
            ],
            [
                'query' => 'SELECT * FROM ads WHERE...',
                'time' => 800,
                'count' => 12
            ]
        ];
    }

    /**
     * Получить список топ потребителей памяти (заглушка)
     */
    public function getTopMemoryConsumers(): array
    {
        // В реальной системе здесь была бы логика анализа использования памяти
        return [
            [
                'component' => 'Image Processing',
                'memory_mb' => 45.2
            ],
            [
                'component' => 'Cache Loading',
                'memory_mb' => 32.1
            ]
        ];
    }

    /**
     * Проверить пороговые значения и показать предупреждения
     */
    public function checkThresholds(array $metrics): array
    {
        $warnings = [];

        // Проверка памяти
        if (($metrics['memory']['usage_percent'] ?? 0) > 80) {
            $warnings[] = [
                'type' => 'memory',
                'severity' => $metrics['memory']['usage_percent'] > 95 ? 'critical' : 'warning',
                'message' => '⚠️  Высокое использование памяти: ' . ($metrics['memory']['usage_percent'] ?? 0) . '%'
            ];
        }

        // Проверка базы данных
        if (($metrics['database']['slow_queries'] ?? 0) > 10) {
            $warnings[] = [
                'type' => 'database',
                'severity' => 'warning',
                'message' => '⚠️  Много медленных запросов: ' . ($metrics['database']['slow_queries'] ?? 0)
            ];
        }

        // Проверка очередей
        if (($metrics['queues']['failed'] ?? 0) > 100) {
            $warnings[] = [
                'type' => 'queue',
                'severity' => 'warning',
                'message' => '⚠️  Много проваленных задач: ' . ($metrics['queues']['failed'] ?? 0)
            ];
        }

        return $warnings;
    }
}