<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class SystemInfo extends Page
{
    // protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    public function getView(): string
    {
        return 'filament.pages.system-info';
    }

    // protected static ?string $navigationGroup = 'Система';

    public static function getNavigationGroup(): ?string
    {
        return 'Система';
    }

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'Системная информация';
    }

    public function getHeading(): string
    {
        return 'Системная информация';
    }

    public function getSubheading(): ?string
    {
        return 'Информация о системе, сервере и производительности';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearCache')
                ->label('Очистить кеш')
                ->icon('heroicon-o-trash')
                ->color('warning')
                ->action(function (): void {
                    Artisan::call('cache:clear');
                    Artisan::call('config:clear');
                    Artisan::call('route:clear');
                    Artisan::call('view:clear');

                    Notification::make()
                        ->title('Кеш очищен')
                        ->body('Кеш приложения успешно очищен.')
                        ->success()
                        ->send();
                }),

            Action::make('optimizeApp')
                ->label('Оптимизировать')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->action(function (): void {
                    Artisan::call('optimize');
                    Artisan::call('config:cache');
                    Artisan::call('route:cache');
                    Artisan::call('view:cache');

                    Notification::make()
                        ->title('Оптимизация выполнена')
                        ->body('Приложение оптимизировано для production.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'server_os' => php_uname('s') . ' ' . php_uname('r'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . ' секунд',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'timezone' => config('app.timezone'),
            'debug_mode' => config('app.debug') ? 'Включен' : 'Выключен',
            'environment' => config('app.env'),
        ];
    }

    public function getDatabaseInfo(): array
    {
        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();

            // Get database size
            $databaseName = $connection->getDatabaseName();
            $sizeQuery = "SELECT
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb'
                FROM information_schema.tables
                WHERE table_schema = '{$databaseName}'";

            $size = DB::select($sizeQuery)[0]->size_mb ?? 0;

            // Get table count
            $tableCount = DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '{$databaseName}'")[0]->count;

            return [
                'driver' => $connection->getDriverName(),
                'database_name' => $databaseName,
                'host' => $connection->getConfig('host'),
                'port' => $connection->getConfig('port'),
                'version' => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
                'size' => $size . ' MB',
                'tables_count' => $tableCount,
                'connection_status' => 'Подключено',
            ];
        } catch (\Exception $e) {
            return [
                'connection_status' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    public function getStorageInfo(): array
    {
        $disks = [];

        foreach (config('filesystems.disks') as $name => $config) {
            try {
                $disk = Storage::disk($name);

                if ($name === 'local') {
                    $path = storage_path('app');
                    $totalSpace = disk_total_space($path);
                    $freeSpace = disk_free_space($path);
                    $usedSpace = $totalSpace - $freeSpace;

                    $disks[$name] = [
                        'driver' => $config['driver'],
                        'total_space' => $this->formatBytes($totalSpace),
                        'used_space' => $this->formatBytes($usedSpace),
                        'free_space' => $this->formatBytes($freeSpace),
                        'usage_percent' => round(($usedSpace / $totalSpace) * 100, 2) . '%',
                    ];
                } else {
                    $disks[$name] = [
                        'driver' => $config['driver'],
                        'status' => 'Настроен',
                    ];
                }
            } catch (\Exception $e) {
                $disks[$name] = [
                    'driver' => $config['driver'] ?? 'Unknown',
                    'status' => 'Ошибка: ' . $e->getMessage(),
                ];
            }
        }

        return $disks;
    }

    public function getQueueInfo(): array
    {
        try {
            $defaultConnection = config('queue.default');
            $connections = config('queue.connections');

            $info = [
                'default_driver' => $defaultConnection,
                'drivers' => [],
            ];

            foreach ($connections as $name => $config) {
                $info['drivers'][$name] = [
                    'driver' => $config['driver'],
                    'status' => $name === $defaultConnection ? 'Активный' : 'Настроен',
                ];
            }

            return $info;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getCacheInfo(): array
    {
        try {
            $store = Cache::getDefaultDriver();
            $config = config("cache.stores.{$store}");

            $info = [
                'default_store' => $store,
                'driver' => $config['driver'] ?? 'Unknown',
            ];

            // Try to test cache
            $testKey = 'system_info_test_' . time();
            Cache::put($testKey, 'test', 5);
            $testValue = Cache::get($testKey);
            Cache::forget($testKey);

            $info['status'] = $testValue === 'test' ? 'Работает' : 'Не работает';

            return $info;
        } catch (\Exception $e) {
            return [
                'status' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    public function getInstalledPackages(): array
    {
        $composerPath = base_path('composer.lock');

        if (!file_exists($composerPath)) {
            return [];
        }

        $composerLock = json_decode(file_get_contents($composerPath), true);
        $packages = [];

        foreach ($composerLock['packages'] as $package) {
            $packages[] = [
                'name' => $package['name'],
                'version' => $package['version'],
                'description' => $package['description'] ?? '',
            ];
        }

        // Sort by name
        usort($packages, fn($a, $b) => strcmp($a['name'], $b['name']));

        return array_slice($packages, 0, 20); // Limit to 20 packages
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}