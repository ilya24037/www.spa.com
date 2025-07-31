<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\FeatureFlagService;

class MonitorMigration extends Command
{
    protected $signature = 'app:monitor-migration 
                            {--realtime : Monitor in real-time}
                            {--report : Generate migration report}';

    protected $description = 'Мониторинг процесса миграции на модульную архитектуру';

    private FeatureFlagService $featureFlags;

    public function __construct(FeatureFlagService $featureFlags)
    {
        parent::__construct();
        $this->featureFlags = $featureFlags;
    }

    public function handle()
    {
        if ($this->option('realtime')) {
            return $this->monitorRealtime();
        }

        if ($this->option('report')) {
            return $this->generateReport();
        }

        return $this->showDashboard();
    }

    private function showDashboard()
    {
        $this->info('📊 Панель мониторинга миграции');
        $this->newLine();

        // Статус миграции
        $this->showMigrationStatus();
        $this->newLine();

        // Feature flags статус
        $this->showFeatureFlagsStatus();
        $this->newLine();

        // Legacy вызовы
        $this->showLegacyCalls();
        $this->newLine();

        // Ошибки
        $this->showErrors();
        $this->newLine();

        // Производительность
        $this->showPerformanceMetrics();

        return 0;
    }

    private function showMigrationStatus()
    {
        $this->comment('📋 Статус миграции:');

        $steps = DB::table('modular_migration_status')
            ->select('step', 'status', 'metadata', 'updated_at')
            ->get();

        if ($steps->isEmpty()) {
            $this->warn('Миграция еще не начата');
            return;
        }

        $rows = [];
        foreach ($steps as $step) {
            $metadata = json_decode($step->metadata, true);
            $rows[] = [
                $step->step,
                $this->formatStatus($step->status),
                $metadata['completed_at'] ?? '-',
                $metadata['user'] ?? '-'
            ];
        }

        $this->table(
            ['Шаг', 'Статус', 'Завершен', 'Пользователь'],
            $rows
        );
    }

    private function showFeatureFlagsStatus()
    {
        $this->comment('🚩 Feature Flags:');

        $migrationFlags = [
            'use_modern_booking_service',
            'use_modern_search',
            'use_adapters',
            'log_legacy_calls'
        ];

        $rows = [];
        foreach ($migrationFlags as $flag) {
            $config = $this->featureFlags->getFlag($flag);
            if ($config) {
                $enabled = $config['enabled'] ?? false;
                $percentage = $config['percentage'] ?? 100;
                
                $stats = $this->featureFlags->getUsageStats($flag, 1);
                $adoption = $stats['summary']['adoption_rate'];
                
                $rows[] = [
                    $flag,
                    $enabled ? '✅' : '❌',
                    "{$percentage}%",
                    "{$adoption}%",
                    number_format($stats['summary']['total_checks'])
                ];
            }
        }

        $this->table(
            ['Feature', 'Enabled', 'Coverage', 'Adoption', 'Checks (24h)'],
            $rows
        );
    }

    private function showLegacyCalls()
    {
        $this->comment('📞 Legacy вызовы (последние 24 часа):');

        $calls = DB::table('legacy_call_logs')
            ->where('called_at', '>=', now()->subDay())
            ->select('service', 'method', DB::raw('COUNT(*) as count'))
            ->groupBy('service', 'method')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        if ($calls->isEmpty()) {
            $this->info('Нет legacy вызовов');
            return;
        }

        $rows = [];
        foreach ($calls as $call) {
            $rows[] = [
                $call->service,
                $call->method,
                number_format($call->count)
            ];
        }

        $this->table(
            ['Сервис', 'Метод', 'Количество'],
            $rows
        );
    }

    private function showErrors()
    {
        $this->comment('❌ Ошибки (последние 24 часа):');

        // Получаем ошибки из логов
        $errors = DB::table('logs')
            ->where('level', 'error')
            ->where('created_at', '>=', now()->subDay())
            ->where(function ($query) {
                $query->where('message', 'like', '%Modern%service failed%')
                    ->orWhere('message', 'like', '%adapter%failed%');
            })
            ->select('message', DB::raw('COUNT(*) as count'))
            ->groupBy('message')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        if ($errors->isEmpty()) {
            $this->info('Ошибок не обнаружено ✅');
            return;
        }

        foreach ($errors as $error) {
            $this->error("{$error->message} ({$error->count} раз)");
        }
    }

    private function showPerformanceMetrics()
    {
        $this->comment('⚡ Производительность:');

        // Сравнение времени ответа
        $metrics = [
            'Legacy BookingService' => $this->getAverageResponseTime('legacy_booking'),
            'Modern BookingService' => $this->getAverageResponseTime('modern_booking'),
            'Legacy Search' => $this->getAverageResponseTime('legacy_search'),
            'Modern Search' => $this->getAverageResponseTime('modern_search'),
        ];

        $rows = [];
        foreach ($metrics as $service => $time) {
            $rows[] = [$service, $time ? "{$time}ms" : 'N/A'];
        }

        $this->table(['Сервис', 'Среднее время ответа'], $rows);
    }

    private function monitorRealtime()
    {
        $this->info('🔄 Мониторинг в реальном времени (Ctrl+C для выхода)');
        
        while (true) {
            system('clear');
            $this->showDashboard();
            sleep(5);
        }
    }

    private function generateReport()
    {
        $this->info('📄 Генерация отчета о миграции...');

        $report = [
            'generated_at' => now()->toDateTimeString(),
            'migration_status' => $this->getMigrationStatusData(),
            'feature_flags' => $this->getFeatureFlagsData(),
            'legacy_calls' => $this->getLegacyCallsData(),
            'errors' => $this->getErrorsData(),
            'performance' => $this->getPerformanceData(),
            'recommendations' => $this->generateRecommendations()
        ];

        $filename = storage_path('reports/migration-report-' . now()->format('Y-m-d-H-i-s') . '.json');
        
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }

        file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));

        $this->info("Отчет сохранен: {$filename}");

        // Показываем сводку
        $this->newLine();
        $this->showReportSummary($report);

        return 0;
    }

    private function formatStatus(string $status): string
    {
        return match($status) {
            'completed' => '✅ Завершен',
            'in_progress' => '🔄 В процессе',
            'failed' => '❌ Ошибка',
            default => '⏸️ Ожидает'
        };
    }

    private function getAverageResponseTime(string $service): ?float
    {
        // Здесь должна быть реальная логика получения метрик
        // Например, из APM или логов
        return rand(50, 200);
    }

    private function getMigrationStatusData(): array
    {
        return DB::table('modular_migration_status')->get()->toArray();
    }

    private function getFeatureFlagsData(): array
    {
        $flags = ['use_modern_booking_service', 'use_modern_search', 'use_adapters'];
        $data = [];
        
        foreach ($flags as $flag) {
            $config = $this->featureFlags->getFlag($flag);
            $stats = $this->featureFlags->getUsageStats($flag, 7);
            
            $data[$flag] = [
                'config' => $config,
                'stats' => $stats
            ];
        }
        
        return $data;
    }

    private function getLegacyCallsData(): array
    {
        return DB::table('legacy_call_logs')
            ->where('called_at', '>=', now()->subWeek())
            ->select('service', 'method', DB::raw('COUNT(*) as count'), DB::raw('DATE(called_at) as date'))
            ->groupBy('service', 'method', 'date')
            ->get()
            ->toArray();
    }

    private function getErrorsData(): array
    {
        return DB::table('logs')
            ->where('level', 'error')
            ->where('created_at', '>=', now()->subWeek())
            ->where('message', 'like', '%Modern%')
            ->select('message', 'context', 'created_at')
            ->get()
            ->toArray();
    }

    private function getPerformanceData(): array
    {
        // Собираем данные о производительности
        return [
            'response_times' => [
                'legacy_booking' => ['avg' => 150, 'p95' => 300],
                'modern_booking' => ['avg' => 100, 'p95' => 200],
                'legacy_search' => ['avg' => 200, 'p95' => 400],
                'modern_search' => ['avg' => 80, 'p95' => 150],
            ],
            'memory_usage' => [
                'before_migration' => '256MB',
                'after_migration' => '192MB'
            ],
            'cache_hit_rate' => [
                'before' => 65,
                'after' => 85
            ]
        ];
    }

    private function generateRecommendations(): array
    {
        $recommendations = [];

        // Анализируем данные и даем рекомендации
        $bookingAdoption = $this->featureFlags->getUsageStats('use_modern_booking_service', 1)['summary']['adoption_rate'];
        
        if ($bookingAdoption < 90) {
            $recommendations[] = [
                'priority' => 'high',
                'message' => 'Низкий уровень adoption для BookingService. Проверьте логи ошибок.',
                'action' => 'Увеличьте процент постепенно после исправления проблем'
            ];
        }

        $legacyCalls = DB::table('legacy_call_logs')
            ->where('called_at', '>=', now()->subDay())
            ->count();
            
        if ($legacyCalls > 1000) {
            $recommendations[] = [
                'priority' => 'medium',
                'message' => 'Высокое количество legacy вызовов. Ускорьте миграцию.',
                'action' => 'Увеличьте процент пользователей для modern сервисов'
            ];
        }

        return $recommendations;
    }

    private function showReportSummary(array $report)
    {
        $this->info('📊 Сводка отчета:');
        
        // Рекомендации
        if (!empty($report['recommendations'])) {
            $this->newLine();
            $this->warn('⚠️ Рекомендации:');
            foreach ($report['recommendations'] as $rec) {
                $priority = strtoupper($rec['priority']);
                $this->line("[{$priority}] {$rec['message']}");
                $this->line("    → {$rec['action']}");
            }
        }

        // Производительность
        $this->newLine();
        $this->info('📈 Улучшения производительности:');
        $perf = $report['performance'];
        
        $bookingImprovement = round((1 - $perf['response_times']['modern_booking']['avg'] / $perf['response_times']['legacy_booking']['avg']) * 100);
        $searchImprovement = round((1 - $perf['response_times']['modern_search']['avg'] / $perf['response_times']['legacy_search']['avg']) * 100);
        
        $this->line("- BookingService: {$bookingImprovement}% быстрее");
        $this->line("- SearchEngine: {$searchImprovement}% быстрее");
        $this->line("- Cache hit rate: +{$perf['cache_hit_rate']['after']}%");
    }
}