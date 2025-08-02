<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Infrastructure\Feature\FeatureFlagService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class MigrateToModularArchitecture extends Command
{
    protected $signature = 'app:migrate-modular 
                            {--step= : Migration step to run}
                            {--rollback : Rollback the migration}
                            {--dry-run : Run without making changes}
                            {--force : Force migration without confirmation}';

    protected $description = 'Постепенная миграция на модульную архитектуру';

    private FeatureFlagService $featureFlags;
    private array $steps = [];
    private bool $dryRun = false;

    public function __construct(FeatureFlagService $featureFlags)
    {
        parent::__construct();
        $this->featureFlags = $featureFlags;
        $this->initializeSteps();
    }

    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        
        if ($this->option('rollback')) {
            return $this->rollback();
        }

        $step = $this->option('step');
        
        if ($step) {
            return $this->runStep($step);
        }

        return $this->runAllSteps();
    }

    private function initializeSteps()
    {
        $this->steps = [
            '1_prepare_environment' => [
                'name' => 'Подготовка окружения',
                'description' => 'Создание необходимых таблиц и настроек',
                'execute' => [$this, 'prepareEnvironment'],
                'rollback' => [$this, 'rollbackEnvironment'],
            ],
            '2_enable_adapters' => [
                'name' => 'Включение адаптеров',
                'description' => 'Активация адаптеров для постепенной миграции',
                'execute' => [$this, 'enableAdapters'],
                'rollback' => [$this, 'disableAdapters'],
            ],
            '3_migrate_booking_service' => [
                'name' => 'Миграция BookingService',
                'description' => 'Постепенный переход на новый BookingService',
                'execute' => [$this, 'migrateBookingService'],
                'rollback' => [$this, 'rollbackBookingService'],
            ],
            '4_migrate_master_service' => [
                'name' => 'Миграция MasterService',
                'description' => 'Переход на новый MasterService',
                'execute' => [$this, 'migrateMasterService'],
                'rollback' => [$this, 'rollbackMasterService'],
            ],
            '5_migrate_search_engine' => [
                'name' => 'Миграция SearchEngine',
                'description' => 'Переход на новый поисковый движок',
                'execute' => [$this, 'migrateSearchEngine'],
                'rollback' => [$this, 'rollbackSearchEngine'],
            ],
            '6_cleanup_legacy' => [
                'name' => 'Очистка legacy кода',
                'description' => 'Удаление старого кода после успешной миграции',
                'execute' => [$this, 'cleanupLegacy'],
                'rollback' => [$this, 'restoreLegacy'],
            ],
        ];
    }

    private function runAllSteps()
    {
        $this->info('Начинаем миграцию на модульную архитектуру...');
        
        if (!$this->option('force') && !$this->confirm('Это действие изменит архитектуру приложения. Продолжить?')) {
            return 1;
        }

        $completedSteps = $this->getCompletedSteps();
        
        foreach ($this->steps as $key => $step) {
            if (in_array($key, $completedSteps)) {
                $this->line("Шаг '{$step['name']}' уже выполнен. Пропускаем.");
                continue;
            }

            $this->info("\n📋 Выполняем: {$step['name']}");
            $this->line($step['description']);
            
            try {
                $result = call_user_func($step['execute']);
                
                if ($result === false) {
                    $this->error("Ошибка при выполнении шага '{$step['name']}'");
                    return 1;
                }
                
                if (!$this->dryRun) {
                    $this->markStepCompleted($key);
                }
                
                $this->info("✅ Шаг '{$step['name']}' выполнен успешно!");
                
            } catch (\Exception $e) {
                $this->error("❌ Ошибка: " . $e->getMessage());
                Log::error('Migration step failed', [
                    'step' => $key,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return 1;
            }
        }

        $this->newLine();
        $this->info('🎉 Миграция завершена успешно!');
        
        return 0;
    }

    private function runStep($stepKey)
    {
        if (!isset($this->steps[$stepKey])) {
            $this->error("Шаг '{$stepKey}' не найден");
            return 1;
        }

        $step = $this->steps[$stepKey];
        
        $this->info("Выполняем шаг: {$step['name']}");
        $this->line($step['description']);
        
        try {
            $result = call_user_func($step['execute']);
            
            if ($result === false) {
                $this->error("Ошибка при выполнении шага");
                return 1;
            }
            
            if (!$this->dryRun) {
                $this->markStepCompleted($stepKey);
            }
            
            $this->info("Шаг выполнен успешно!");
            
        } catch (\Exception $e) {
            $this->error("Ошибка: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    // Шаг 1: Подготовка окружения
    private function prepareEnvironment()
    {
        $this->line('Создаем таблицы для миграции...');
        
        if (!$this->dryRun) {
            // Создаем таблицу для отслеживания миграции
            if (!Schema::hasTable('modular_migration_status')) {
                Schema::create('modular_migration_status', function ($table) {
                    $table->id();
                    $table->string('step')->unique();
                    $table->string('status');
                    $table->json('metadata')->nullable();
                    $table->timestamps();
                });
            }

            // Создаем логи для отслеживания legacy вызовов
            if (!Schema::hasTable('legacy_call_logs')) {
                Schema::create('legacy_call_logs', function ($table) {
                    $table->id();
                    $table->string('service');
                    $table->string('method');
                    $table->json('parameters')->nullable();
                    $table->string('caller')->nullable();
                    $table->timestamp('called_at');
                    $table->index(['service', 'method']);
                    $table->index('called_at');
                });
            }
        }

        $this->line('Настраиваем feature flags...');
        
        if (!$this->dryRun) {
            // Инициализируем feature flags
            $this->featureFlags->setFlag('use_adapters', [
                'enabled' => true,
                'description' => 'Использовать адаптеры для миграции'
            ]);
            
            $this->featureFlags->setFlag('log_legacy_calls', [
                'enabled' => true,
                'description' => 'Логировать legacy вызовы'
            ]);
        }

        $this->info('Окружение подготовлено');
        return true;
    }

    // Шаг 2: Включение адаптеров
    private function enableAdapters()
    {
        $this->line('Регистрируем AdapterServiceProvider...');
        
        if (!$this->dryRun) {
            // Добавляем провайдер в config/app.php
            $this->updateServiceProviders();
        }

        $this->line('Публикуем конфигурацию...');
        
        if (!$this->dryRun) {
            $this->call('vendor:publish', [
                '--tag' => 'adapters-config',
                '--force' => true
            ]);
        }

        $this->info('Адаптеры включены');
        return true;
    }

    // Шаг 3: Миграция BookingService
    private function migrateBookingService()
    {
        $this->line('Начинаем миграцию BookingService...');
        
        // Фаза 1: 10% пользователей
        $this->line('Фаза 1: Включаем для 10% пользователей');
        if (!$this->dryRun) {
            $this->featureFlags->setFlag('use_modern_booking_service', [
                'enabled' => true,
                'percentage' => 10,
                'description' => 'Новый BookingService - фаза 1'
            ]);
        }
        
        $this->line('Ждем 24 часа для мониторинга...');
        if (!$this->dryRun && !$this->option('force')) {
            $this->warn('Проверьте метрики и логи ошибок перед продолжением');
            if (!$this->confirm('Продолжить с фазой 2?')) {
                return false;
            }
        }

        // Фаза 2: 50% пользователей
        $this->line('Фаза 2: Увеличиваем до 50% пользователей');
        if (!$this->dryRun) {
            $this->featureFlags->setPercentage('use_modern_booking_service', 50);
        }

        // Фаза 3: 100% пользователей
        $this->line('Фаза 3: Включаем для всех пользователей');
        if (!$this->dryRun) {
            $this->featureFlags->setPercentage('use_modern_booking_service', 100);
        }

        $this->info('BookingService мигрирован');
        return true;
    }

    // Шаг 4: Миграция MasterService
    private function migrateMasterService()
    {
        $this->line('Мигрируем MasterService...');
        
        if (!$this->dryRun) {
            // MasterService уже использует новую архитектуру
            // Просто обновляем импорты в контроллерах
            $this->updateControllerImports('MasterService');
        }

        $this->info('MasterService мигрирован');
        return true;
    }

    // Шаг 5: Миграция SearchEngine
    private function migrateSearchEngine()
    {
        $this->line('Начинаем миграцию SearchEngine...');
        
        if (!$this->dryRun) {
            // Переиндексируем данные
            $this->line('Переиндексация данных...');
            $this->call('search:reindex', ['--force' => true]);
            
            // Включаем новый поиск
            $this->featureFlags->setFlag('use_modern_search', [
                'enabled' => true,
                'percentage' => 100,
                'description' => 'Новый поисковый движок'
            ]);
        }

        $this->info('SearchEngine мигрирован');
        return true;
    }

    // Шаг 6: Очистка legacy кода
    private function cleanupLegacy()
    {
        $this->line('Очищаем legacy код...');
        
        if (!$this->confirm('Это удалит старый код. Убедитесь, что есть резервная копия. Продолжить?')) {
            return false;
        }

        if (!$this->dryRun) {
            // Отключаем адаптеры
            $this->featureFlags->disable('use_adapters');
            
            // Архивируем старые файлы
            $this->archiveLegacyFiles();
        }

        $this->info('Legacy код очищен');
        return true;
    }

    // Вспомогательные методы
    private function getCompletedSteps(): array
    {
        if ($this->dryRun) {
            return [];
        }

        return DB::table('modular_migration_status')
            ->where('status', 'completed')
            ->pluck('step')
            ->toArray();
    }

    private function markStepCompleted(string $step)
    {
        DB::table('modular_migration_status')->updateOrInsert(
            ['step' => $step],
            [
                'status' => 'completed',
                'metadata' => json_encode([
                    'completed_at' => now()->toDateTimeString(),
                    'user' => auth()->user()->name ?? 'console'
                ]),
                'updated_at' => now()
            ]
        );
    }

    private function updateServiceProviders()
    {
        $configPath = config_path('app.php');
        $config = file_get_contents($configPath);
        
        if (!str_contains($config, 'AdapterServiceProvider')) {
            $config = str_replace(
                "'providers' => ServiceProvider::defaultProviders()->merge([",
                "'providers' => ServiceProvider::defaultProviders()->merge([\n        App\Providers\AdapterServiceProvider::class,",
                $config
            );
            
            file_put_contents($configPath, $config);
        }
    }

    private function updateControllerImports(string $service)
    {
        // Здесь должна быть логика обновления импортов в контроллерах
        $this->line("Обновляем импорты для {$service}...");
    }

    private function archiveLegacyFiles()
    {
        $archiveDir = storage_path('archive/legacy_' . date('Y-m-d'));
        
        if (!is_dir($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }

        // Список файлов для архивации
        $legacyFiles = [
            app_path('Services/BookingService.php.old'),
            app_path('Services/SearchService.php.old'),
            // Добавьте другие файлы
        ];

        foreach ($legacyFiles as $file) {
            if (file_exists($file)) {
                $destination = $archiveDir . '/' . basename($file);
                rename($file, $destination);
                $this->line("Архивирован: " . basename($file));
            }
        }
    }

    private function rollback()
    {
        $this->error('Откат миграции пока не реализован');
        return 1;
    }

    private function rollbackEnvironment() { return true; }
    private function disableAdapters() { return true; }
    private function rollbackBookingService() { return true; }
    private function rollbackMasterService() { return true; }
    private function rollbackSearchEngine() { return true; }
    private function restoreLegacy() { return true; }
}