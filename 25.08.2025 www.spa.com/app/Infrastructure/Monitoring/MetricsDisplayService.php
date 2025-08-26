<?php

namespace App\Infrastructure\Monitoring;

use Illuminate\Console\Command;

/**
 * Сервис отображения метрик производительности
 */
class MetricsDisplayService
{
    private Command $command;

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Отобразить все метрики
     */
    public function displayMetrics(array $metrics): void
    {
        $this->command->line('');
        $this->command->line('📊 <fg=blue>Метрики производительности</fg=blue> - ' . $metrics['timestamp']);
        $this->command->line(str_repeat('=', 60));

        $this->displayDatabaseMetrics($metrics['database'] ?? []);
        $this->displayCacheMetrics($metrics['cache'] ?? []);
        $this->displayMemoryMetrics($metrics['memory'] ?? []);
        $this->displayRequestMetrics($metrics['requests'] ?? []);
        $this->displayQueueMetrics($metrics['queues'] ?? []);

        $this->command->line('');
    }

    /**
     * Отобразить детальные метрики для отчета
     */
    public function displayDetailedMetrics(array $metrics): void
    {
        $this->displayMetrics($metrics);
        
        if (isset($metrics['system'])) {
            $this->displaySystemMetrics($metrics['system']);
        }
        
        if (isset($metrics['application'])) {
            $this->displayApplicationMetrics($metrics['application']);
        }
        
        if (isset($metrics['storage'])) {
            $this->displayStorageMetrics($metrics['storage']);
        }
    }

    /**
     * Отобразить метрики базы данных
     */
    private function displayDatabaseMetrics(array $metrics): void
    {
        $this->command->line('🗄️  <fg=yellow>База данных:</fg=yellow>');
        
        if ($metrics['status'] === 'connected') {
            $statusColor = 'green';
            $statusText = '✅ Подключена';
        } else {
            $statusColor = 'red';
            $statusText = '❌ Ошибка';
        }
        
        $this->command->line("   Статус: <fg=$statusColor>$statusText</fg=$statusColor>");
        
        if (isset($metrics['response_time_ms'])) {
            $responseTime = $metrics['response_time_ms'];
            $timeColor = $responseTime < 50 ? 'green' : ($responseTime < 200 ? 'yellow' : 'red');
            $this->command->line("   Время ответа: <fg=$timeColor>{$responseTime}ms</fg=$timeColor>");
        }
        
        if (isset($metrics['connections'])) {
            $this->command->line("   Соединения: {$metrics['connections']}");
        }
        
        if (isset($metrics['queries'])) {
            $this->command->line("   Запросы: {$metrics['queries']}");
        }
        
        if (isset($metrics['slow_queries']) && $metrics['slow_queries'] > 0) {
            $this->command->line("   <fg=red>Медленные запросы: {$metrics['slow_queries']}</fg=red>");
        }
        
        $this->command->line('');
    }

    /**
     * Отобразить метрики кеша
     */
    private function displayCacheMetrics(array $metrics): void
    {
        $this->command->line('🗃️  <fg=yellow>Кеш:</fg=yellow>');
        
        if ($metrics['status'] === 'working') {
            $statusColor = 'green';
            $statusText = '✅ Работает';
        } else {
            $statusColor = 'red';
            $statusText = '❌ Ошибка';
        }
        
        $this->command->line("   Статус: <fg=$statusColor>$statusText</fg=$statusColor>");
        
        if (isset($metrics['response_time_ms'])) {
            $responseTime = $metrics['response_time_ms'];
            $timeColor = $responseTime < 10 ? 'green' : ($responseTime < 50 ? 'yellow' : 'red');
            $this->command->line("   Время ответа: <fg=$timeColor>{$responseTime}ms</fg=$timeColor>");
        }
        
        if (isset($metrics['driver'])) {
            $this->command->line("   Драйвер: {$metrics['driver']}");
        }
        
        $this->command->line('');
    }

    /**
     * Отобразить метрики памяти
     */
    private function displayMemoryMetrics(array $metrics): void
    {
        $this->command->line('💾 <fg=yellow>Память:</fg=yellow>');
        
        if (isset($metrics['current_mb'])) {
            $this->command->line("   Текущее использование: {$metrics['current_mb']} MB");
        }
        
        if (isset($metrics['peak_mb'])) {
            $this->command->line("   Пиковое использование: {$metrics['peak_mb']} MB");
        }
        
        if (isset($metrics['limit_mb']) && $metrics['limit_mb'] > 0) {
            $this->command->line("   Лимит: {$metrics['limit_mb']} MB");
            
            if (isset($metrics['usage_percent'])) {
                $usage = $metrics['usage_percent'];
                $usageColor = $usage < 70 ? 'green' : ($usage < 90 ? 'yellow' : 'red');
                $this->command->line("   Использование: <fg=$usageColor>{$usage}%</fg=$usageColor>");
            }
        }
        
        $this->command->line('');
    }

    /**
     * Отобразить метрики запросов
     */
    private function displayRequestMetrics(array $metrics): void
    {
        $this->command->line('📡 <fg=yellow>HTTP Запросы:</fg=yellow>');
        
        if (isset($metrics['total'])) {
            $this->command->line("   Всего: {$metrics['total']}");
        }
        
        if (isset($metrics['success'])) {
            $this->command->line("   <fg=green>Успешные: {$metrics['success']}</fg=green>");
        }
        
        if (isset($metrics['errors'])) {
            $errorColor = $metrics['errors'] > 0 ? 'red' : 'green';
            $this->command->line("   <fg=$errorColor>Ошибки: {$metrics['errors']}</fg=$errorColor>");
        }
        
        if (isset($metrics['error_rate_percent'])) {
            $errorRate = $metrics['error_rate_percent'];
            $rateColor = $errorRate < 1 ? 'green' : ($errorRate < 5 ? 'yellow' : 'red');
            $this->command->line("   Процент ошибок: <fg=$rateColor>{$errorRate}%</fg=$rateColor>");
        }
        
        if (isset($metrics['avg_response_time_ms'])) {
            $avgTime = $metrics['avg_response_time_ms'];
            $timeColor = $avgTime < 200 ? 'green' : ($avgTime < 1000 ? 'yellow' : 'red');
            $this->command->line("   Среднее время ответа: <fg=$timeColor>{$avgTime}ms</fg=$timeColor>");
        }
        
        $this->command->line('');
    }

    /**
     * Отобразить метрики очередей
     */
    private function displayQueueMetrics(array $metrics): void
    {
        $this->command->line('⚡ <fg=yellow>Очереди:</fg=yellow>');
        
        if (isset($metrics['connection'])) {
            $this->command->line("   Подключение: {$metrics['connection']}");
        }
        
        if ($metrics['status'] === 'active') {
            $this->command->line("   Статус: <fg=green>✅ Активные</fg=green>");
        } elseif ($metrics['status'] === 'error') {
            $this->command->line("   Статус: <fg=red>❌ Ошибка</fg=red>");
            if (isset($metrics['error'])) {
                $this->command->line("   Ошибка: <fg=red>{$metrics['error']}</fg=red>");
            }
        } else {
            $this->command->line("   Статус: <fg=yellow>⚠️  Неизвестно</fg=yellow>");
        }
        
        if (isset($metrics['pending'])) {
            $pendingColor = $metrics['pending'] > 100 ? 'red' : ($metrics['pending'] > 10 ? 'yellow' : 'green');
            $this->command->line("   Ожидают: <fg=$pendingColor>{$metrics['pending']}</fg=$pendingColor>");
        }
        
        if (isset($metrics['failed']) && $metrics['failed'] > 0) {
            $this->command->line("   <fg=red>Провалились: {$metrics['failed']}</fg=red>");
        }
        
        $this->command->line('');
    }

    /**
     * Отобразить системные метрики
     */
    private function displaySystemMetrics(array $metrics): void
    {
        $this->command->line('🖥️  <fg=yellow>Система:</fg=yellow>');
        
        if (isset($metrics['php_version'])) {
            $this->command->line("   PHP версия: {$metrics['php_version']}");
        }
        
        if (isset($metrics['environment'])) {
            $envColor = $metrics['environment'] === 'production' ? 'red' : 'green';
            $this->command->line("   Окружение: <fg=$envColor>{$metrics['environment']}</fg=$envColor>");
        }
        
        if (isset($metrics['load_average'])) {
            $load = $metrics['load_average'];
            $this->command->line("   Загрузка: {$load['1min']} / {$load['5min']} / {$load['15min']}");
        }
        
        $this->command->line('');
    }

    /**
     * Отобразить метрики приложения
     */
    private function displayApplicationMetrics(array $metrics): void
    {
        $this->command->line('🚀 <fg=yellow>Приложение:</fg=yellow>');
        
        foreach ($metrics as $key => $value) {
            $displayKey = ucfirst(str_replace('_', ' ', $key));
            $this->command->line("   {$displayKey}: {$value}");
        }
        
        $this->command->line('');
    }

    /**
     * Отобразить метрики хранилища
     */
    private function displayStorageMetrics(array $metrics): void
    {
        $this->command->line('💽 <fg=yellow>Хранилище:</fg=yellow>');
        
        if (isset($metrics['free_gb'])) {
            $this->command->line("   Свободно: {$metrics['free_gb']} GB");
        }
        
        if (isset($metrics['total_gb'])) {
            $this->command->line("   Всего: {$metrics['total_gb']} GB");
        }
        
        if (isset($metrics['used_percent'])) {
            $usage = $metrics['used_percent'];
            $usageColor = $usage < 80 ? 'green' : ($usage < 95 ? 'yellow' : 'red');
            $this->command->line("   Использование: <fg=$usageColor>{$usage}%</fg=$usageColor>");
        }
        
        $this->command->line('');
    }

    /**
     * Показать заголовок для мониторинга в реальном времени
     */
    public function showRealtimeHeader(): void
    {
        $this->command->info('Запуск мониторинга в реальном времени... (Ctrl+C для выхода)');
        $this->command->line('Обновление каждые 5 секунд');
        $this->command->line('');
    }

    /**
     * Очистить экран для режима реального времени
     */
    public function clearScreen(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            system('cls');
        } else {
            system('clear');
        }
    }
}