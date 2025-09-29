<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use App\Services\AdminActionLogger;

class CheckFilamentHealth extends Command
{
    protected $signature = 'filament:health {--fix : Attempt to fix found issues}';
    protected $description = 'Check the health of Filament admin panel and report any issues';

    private $issues = [];
    private $warnings = [];
    private $successes = [];
    private $logger;

    public function __construct()
    {
        parent::__construct();
        $this->logger = new AdminActionLogger();
    }

    public function handle()
    {
        $this->info('🔍 Running Filament Admin Panel Health Check...');
        $this->newLine();

        // Run all checks
        $this->checkFilamentInstallation();
        $this->checkResources();
        $this->checkDatabaseTables();
        $this->checkMigrations();
        $this->checkPermissions();
        $this->checkLogFiles();
        $this->checkActions();
        $this->checkEnumValues();
        $this->checkAlpineJsConfiguration();
        $this->checkRoutesConfiguration();

        // Generate report
        $this->generateReport();

        // Fix issues if requested
        if ($this->option('fix')) {
            $this->fixIssues();
        }

        // Log the health check
        $this->logger->log('health_check', null, [
            'issues' => count($this->issues),
            'warnings' => count($this->warnings),
            'successes' => count($this->successes),
        ]);

        return count($this->issues) > 0 ? 1 : 0;
    }

    private function checkFilamentInstallation()
    {
        $this->info('  Checking Filament installation...');

        // Check if Filament is installed
        if (!class_exists(\Filament\FilamentManager::class)) {
            $this->issues[] = 'Filament is not installed';
            return false;
        }

        // Check version
        $composerJson = json_decode(File::get(base_path('composer.json')), true);
        $filamentVersion = $composerJson['require']['filament/filament'] ?? null;

        if (!$filamentVersion) {
            $this->issues[] = 'Filament version not found in composer.json';
            return false;
        }

        if (!str_starts_with($filamentVersion, '^3.')) {
            $this->warnings[] = "Filament version {$filamentVersion} - expected v3";
        }

        $this->successes[] = "Filament v3 installed ({$filamentVersion})";
        return true;
    }

    private function checkResources()
    {
        $this->info('  Checking Filament resources...');
            $resourceFiles = glob(app_path('Filament/Resources/*.php'));
            $enabledResources = [];
            $disabledResources = [];

            foreach ($resourceFiles as $file) {
                if (str_ends_with($file, '.disabled')) {
                    $disabledResources[] = basename($file);
                } else {
                    $className = 'App\\Filament\\Resources\\' . basename($file, '.php');
                    if (class_exists($className)) {
                        $enabledResources[] = basename($file, '.php');
                    }
                }
            }

            if (count($enabledResources) === 0) {
                $this->issues[] = 'No active Filament resources found';
                return false;
            }

            $this->successes[] = 'Active resources: ' . implode(', ', $enabledResources);

            if (count($disabledResources) > 0) {
                $this->warnings[] = 'Disabled resources: ' . implode(', ', $disabledResources);
            }

            return true;
    }

    private function checkDatabaseTables()
    {
        $this->info('  Checking database tables...');
            $requiredTables = [
                'users',
                'ads',
                'master_profiles',
                'reviews',
                'complaints',
                'notifications',
            ];

            $missingTables = [];

            foreach ($requiredTables as $table) {
                if (!DB::getSchemaBuilder()->hasTable($table)) {
                    $missingTables[] = $table;
                }
            }

            if (count($missingTables) > 0) {
                $this->issues[] = 'Missing tables: ' . implode(', ', $missingTables);
                return false;
            }

            // Check for status column in notifications
            if (DB::getSchemaBuilder()->hasTable('notifications')) {
                if (!DB::getSchemaBuilder()->hasColumn('notifications', 'status')) {
                    $this->warnings[] = 'Notifications table missing "status" column';
                }
            }

            $this->successes[] = 'All required tables exist';
            return true;
    }

    private function checkMigrations()
    {
        $this->info('  Checking pending migrations...');
            $pendingMigrations = [];

            exec('php artisan migrate:status --pending 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                foreach ($output as $line) {
                    if (str_contains($line, 'Pending')) {
                        $pendingMigrations[] = $line;
                    }
                }
            }

            if (count($pendingMigrations) > 0) {
                $this->warnings[] = 'Pending migrations: ' . count($pendingMigrations);
                return false;
            }

            $this->successes[] = 'All migrations are up to date';
            return true;
    }

    private function checkPermissions()
    {
        $this->info('  Checking file permissions...');
            $directories = [
                storage_path('logs'),
                storage_path('app'),
                storage_path('framework/cache'),
                storage_path('framework/sessions'),
                storage_path('framework/views'),
            ];

            $permissionIssues = [];

            foreach ($directories as $dir) {
                if (!is_writable($dir)) {
                    $permissionIssues[] = $dir;
                }
            }

            if (count($permissionIssues) > 0) {
                $this->issues[] = 'Permission issues in: ' . implode(', ', $permissionIssues);
                return false;
            }

            $this->successes[] = 'All required directories are writable';
            return true;
    }

    private function checkLogFiles()
    {
        $this->info('  Checking log files...');
            $logFile = storage_path('logs/admin_actions.log');

            if (!File::exists($logFile)) {
                $this->warnings[] = 'Admin actions log file does not exist yet';
            } else {
                $size = File::size($logFile);
                if ($size > 50 * 1024 * 1024) { // 50MB
                    $this->warnings[] = 'Admin actions log file is large (' . round($size / 1024 / 1024, 2) . ' MB)';
                }
            }

            $this->successes[] = 'Logging configuration is set up';
            return true;
    }

    private function checkActions()
    {
        $this->info('  Checking Filament Actions configuration...');
            $resourceFiles = glob(app_path('Filament/Resources/**/*.php'));
            $actionIssues = [];

            foreach ($resourceFiles as $file) {
                if (str_ends_with($file, '.disabled')) {
                    continue;
                }

                $content = File::get($file);

                // Check for incorrect Action imports
                if (preg_match('/use Filament\\\\Tables\\\\Actions\\\\(ViewAction|EditAction|DeleteAction);/', $content)) {
                    $actionIssues[] = basename($file) . ' has incorrect Action imports';
                }

                // Check for Actions used without namespace
                if (preg_match('/(?<!Tables\\\\Actions\\\\)(ViewAction|EditAction|DeleteAction)::make\(\)/', $content)) {
                    $actionIssues[] = basename($file) . ' uses Actions without proper namespace';
                }
            }

            if (count($actionIssues) > 0) {
                $this->issues[] = 'Action configuration issues in: ' . implode(', ', $actionIssues);
                return false;
            }

            $this->successes[] = 'All Actions are properly configured';
            return true;
    }

    private function checkEnumValues()
    {
        $this->info('  Checking Enum values...');
            $enumIssues = [];

            // Check AdStatus enum
            if (class_exists(\App\Domain\Ad\Enums\AdStatus::class)) {
                $requiredStatuses = [
                    'DRAFT',
                    'PENDING_MODERATION',
                    'ACTIVE',
                    'REJECTED',
                    'ARCHIVED',
                    'BLOCKED',
                ];

                foreach ($requiredStatuses as $status) {
                    if (!defined(\App\Domain\Ad\Enums\AdStatus::class . '::' . $status)) {
                        $enumIssues[] = "AdStatus missing {$status}";
                    }
                }
            } else {
                $this->issues[] = 'AdStatus enum not found';
            }

            if (count($enumIssues) > 0) {
                $this->issues[] = 'Enum issues: ' . implode(', ', $enumIssues);
                return false;
            }

            $this->successes[] = 'All enum values are properly defined';
            return true;
    }

    private function checkAlpineJsConfiguration()
    {
        $this->info('  Checking Alpine.js configuration...');
            $providerFile = app_path('Providers/Filament/AdminPanelProvider.php');

            if (!File::exists($providerFile)) {
                $this->issues[] = 'AdminPanelProvider not found';
                return false;
            }

            $content = File::get($providerFile);

            // Check for SPA mode (should be disabled to avoid Alpine conflicts)
            if (str_contains($content, '->spa()') && !str_contains($content, '// ->spa()')) {
                $this->warnings[] = 'SPA mode is enabled - may cause Alpine.js conflicts';
            }

            $this->successes[] = 'Alpine.js configuration checked';
            return true;
    }

    private function checkRoutesConfiguration()
    {
        $this->info('  Checking routes configuration...');
            // Check if admin routes are registered
            $routes = app('router')->getRoutes();
            $adminRoutes = [];

            foreach ($routes as $route) {
                if (str_starts_with($route->uri(), 'admin')) {
                    $adminRoutes[] = $route->uri();
                }
            }

            if (count($adminRoutes) === 0) {
                $this->issues[] = 'No admin routes found';
                return false;
            }

            $this->successes[] = 'Admin routes configured (' . count($adminRoutes) . ' routes)';
            return true;
    }

    private function generateReport()
    {
        $this->newLine();
        $this->info('=== FILAMENT HEALTH CHECK REPORT ===');
        $this->newLine();

        if (count($this->successes) > 0) {
            $this->info('✅ Successes (' . count($this->successes) . '):');
            foreach ($this->successes as $success) {
                $this->line('  ✓ ' . $success);
            }
            $this->newLine();
        }

        if (count($this->warnings) > 0) {
            $this->warn('⚠️  Warnings (' . count($this->warnings) . '):');
            foreach ($this->warnings as $warning) {
                $this->line('  ⚠ ' . $warning);
            }
            $this->newLine();
        }

        if (count($this->issues) > 0) {
            $this->error('❌ Issues (' . count($this->issues) . '):');
            foreach ($this->issues as $issue) {
                $this->line('  ✗ ' . $issue);
            }
            $this->newLine();
        }

        // Summary
        $this->info('Summary:');
        $this->line('  Total checks: ' . (count($this->successes) + count($this->warnings) + count($this->issues)));
        $this->line('  Passed: ' . count($this->successes));
        $this->line('  Warnings: ' . count($this->warnings));
        $this->line('  Failed: ' . count($this->issues));

        if (count($this->issues) === 0 && count($this->warnings) === 0) {
            $this->newLine();
            $this->info('🎉 All checks passed! Filament admin panel is healthy.');
        } elseif (count($this->issues) === 0) {
            $this->newLine();
            $this->warn('⚠️  Some warnings found, but no critical issues.');
        } else {
            $this->newLine();
            $this->error('❌ Critical issues found. Run with --fix to attempt automatic fixes.');
        }
    }

    private function fixIssues()
    {
        if (count($this->issues) === 0) {
            return;
        }

        $this->newLine();
        $this->info('Attempting to fix issues...');

        foreach ($this->issues as $issue) {
            if (str_contains($issue, 'Missing tables')) {
                $this->warn('Run: php artisan migrate');
            }

            if (str_contains($issue, 'Action configuration issues')) {
                $this->warn('Run: php artisan filament:fix-actions');
            }

            if (str_contains($issue, 'Permission issues')) {
                $this->warn('Fix permissions on storage directories');
            }
        }
    }
}