<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckMigrationReadiness extends Command
{
    protected $signature = 'app:check-readiness';
    protected $description = 'Проверить готовность к миграции на модульную архитектуру';

    private array $checks = [];
    private int $passed = 0;
    private int $failed = 0;

    public function handle()
    {
        $this->info('🔍 Проверка готовности к миграции...');
        $this->newLine();

        // Выполняем проверки
        $this->checkEnvironment();
        $this->checkDatabase();
        $this->checkFiles();
        $this->checkDependencies();
        $this->checkTests();
        $this->checkBackups();

        // Показываем результаты
        $this->showResults();

        return $this->failed > 0 ? 1 : 0;
    }

    private function checkEnvironment()
    {
        $this->comment('Проверка окружения...');

        // PHP версия
        $phpVersion = PHP_VERSION;
        $this->check('PHP >= 8.4', version_compare($phpVersion, '8.4.0', '>='));

        // Laravel версия
        $laravelVersion = app()->version();
        $this->check('Laravel >= 12.0', version_compare($laravelVersion, '12.0.0', '>='));

        // Расширения
        $this->check('Redis расширение', extension_loaded('redis'));
        $this->check('OPcache включен', extension_loaded('opcache') && ini_get('opcache.enable'));
    }

    private function checkDatabase()
    {
        $this->comment('Проверка базы данных...');

        try {
            DB::connection()->getPdo();
            $this->check('Подключение к БД', true);
        } catch (\Exception $e) {
            $this->check('Подключение к БД', false);
        }

        // Проверка таблиц
        $this->check('Таблица feature_flags', Schema::hasTable('feature_flags'));
        $this->check('Таблица migrations', Schema::hasTable('migrations'));

        // Проверка размера БД
        $dbSize = DB::select("SELECT SUM(data_length + index_length) / 1024 / 1024 AS size 
                              FROM information_schema.TABLES 
                              WHERE table_schema = ?", [env('DB_DATABASE')])[0]->size ?? 0;
        $this->check('Размер БД < 10GB', $dbSize < 10240);
    }

    private function checkFiles()
    {
        $this->comment('Проверка файлов...');

        // Новые модули
        $modules = [
            'app/Domain/Booking' => 'Модуль Booking',
            'app/Domain/User' => 'Модуль User',
            'app/Domain/Search' => 'Модуль Search',
            'app/Enums' => 'Enums',
            'app/DTOs' => 'DTOs',
            'app/Repositories' => 'Repositories',
            'app/Services/Adapters' => 'Адаптеры',
        ];

        foreach ($modules as $path => $name) {
            $this->check($name, is_dir(base_path($path)));
        }

        // Конфигурация
        $this->check('config/features.php', file_exists(config_path('features.php')));
        $this->check('config/performance.php', file_exists(config_path('performance.php')));
    }

    private function checkDependencies()
    {
        $this->comment('Проверка зависимостей...');

        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        $required = [
            'laravel/framework' => '^12.0',
            'predis/predis' => '*',
            'elasticsearch/elasticsearch' => '*',
        ];

        foreach ($required as $package => $version) {
            $installed = isset($composer['require'][$package]);
            $this->check("Пакет {$package}", $installed);
        }
    }

    private function checkTests()
    {
        $this->comment('Проверка тестов...');

        // Проверяем наличие тестов
        $testFiles = glob(base_path('tests/Unit/**/*Test.php'));
        $this->check('Unit тесты (10+)', count($testFiles) >= 10);

        // Проверяем покрытие (если есть отчет)
        $coverageFile = base_path('coverage/clover.xml');
        if (file_exists($coverageFile)) {
            $coverage = simplexml_load_file($coverageFile);
            $percent = (float) $coverage->project->metrics['@attributes']['coveredstatements'] / 
                      (float) $coverage->project->metrics['@attributes']['statements'] * 100;
            $this->check('Покрытие тестами > 70%', $percent > 70);
        }
    }

    private function checkBackups()
    {
        $this->comment('Проверка резервных копий...');

        // Проверяем последнюю резервную копию
        $backupDir = storage_path('backups');
        $lastBackup = null;
        
        if (is_dir($backupDir)) {
            $files = glob($backupDir . '/db_*.sql');
            if (!empty($files)) {
                $lastBackup = max(array_map('filemtime', $files));
            }
        }

        $hasRecentBackup = $lastBackup && (time() - $lastBackup) < 86400; // 24 часа
        $this->check('Свежая резервная копия БД', $hasRecentBackup);
    }

    private function check(string $name, bool $passed)
    {
        $this->checks[] = ['name' => $name, 'passed' => $passed];
        
        if ($passed) {
            $this->passed++;
            $this->info("✅ {$name}");
        } else {
            $this->failed++;
            $this->error("❌ {$name}");
        }
    }

    private function showResults()
    {
        $this->newLine();
        $total = $this->passed + $this->failed;
        $percentage = $total > 0 ? round(($this->passed / $total) * 100, 1) : 0;

        $this->info("📊 Результаты проверки:");
        $this->line("Пройдено: {$this->passed}/{$total} ({$percentage}%)");

        if ($this->failed === 0) {
            $this->newLine();
            $this->info("✅ Система готова к миграции!");
            $this->line("Запустите: php artisan app:migrate-modular");
        } else {
            $this->newLine();
            $this->warn("⚠️  Обнаружены проблемы:");
            
            foreach ($this->checks as $check) {
                if (!$check['passed']) {
                    $this->line("   - {$check['name']}");
                }
            }
            
            $this->newLine();
            $this->line("Исправьте проблемы перед началом миграции.");
        }

        // Рекомендации
        if ($this->failed > 0) {
            $this->newLine();
            $this->comment("💡 Рекомендации:");
            
            if (!$this->hasCheck('Свежая резервная копия БД')) {
                $this->line("   - Создайте резервную копию: make backup");
            }
            
            if (!$this->hasCheck('Unit тесты')) {
                $this->line("   - Добавьте тесты для критичного функционала");
            }
            
            if (!$this->hasCheck('Таблица feature_flags')) {
                $this->line("   - Выполните миграции: php artisan migrate");
            }
        }
    }

    private function hasCheck(string $name): bool
    {
        foreach ($this->checks as $check) {
            if ($check['name'] === $name && $check['passed']) {
                return true;
            }
        }
        return false;
    }
}