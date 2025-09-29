<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use ZipArchive;

class Backup extends Page
{
    // protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    public function getView(): string
    {
        return 'filament.pages.backup';
    }

    // protected static ?string $navigationGroup = 'Система';

    public static function getNavigationGroup(): ?string
    {
        return 'Система';
    }

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'Резервное копирование';
    }

    public function getHeading(): string
    {
        return 'Резервное копирование';
    }

    public function getSubheading(): ?string
    {
        return 'Создание и управление резервными копиями системы';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createBackup')
                ->label('Создать резервную копию')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->form([
                    Select::make('backup_type')
                        ->label('Тип резервной копии')
                        ->options([
                            'database' => 'Только база данных',
                            'files' => 'Только файлы',
                            'full' => 'Полная копия (БД + файлы)',
                        ])
                        ->default('database')
                        ->required(),

                    Toggle::make('include_media')
                        ->label('Включить медиа-файлы')
                        ->default(false)
                        ->visible(fn (callable $get) => in_array($get('backup_type'), ['files', 'full'])),

                    TextInput::make('backup_name')
                        ->label('Название копии (опционально)')
                        ->maxLength(255)
                        ->placeholder('Автоматическое название по дате'),
                ])
                ->action('createBackup'),

            Action::make('scheduleBackup')
                ->label('Настроить расписание')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->form([
                    Toggle::make('auto_backup')
                        ->label('Автоматическое резервное копирование')
                        ->default(false),

                    Select::make('frequency')
                        ->label('Частота')
                        ->options([
                            'daily' => 'Ежедневно',
                            'weekly' => 'Еженедельно',
                            'monthly' => 'Ежемесячно',
                        ])
                        ->default('weekly')
                        ->visible(fn (callable $get) => $get('auto_backup')),

                    Select::make('retention_days')
                        ->label('Хранить копии (дней)')
                        ->options([
                            7 => '7 дней',
                            14 => '14 дней',
                            30 => '30 дней',
                            60 => '60 дней',
                            90 => '90 дней',
                        ])
                        ->default(30)
                        ->visible(fn (callable $get) => $get('auto_backup')),
                ])
                ->action('scheduleBackup'),
        ];
    }

    public function createBackup(array $data): void
    {
        try {
            $backupName = $data['backup_name'] ?: 'backup_' . date('Y-m-d_H-i-s');
            $backupType = $data['backup_type'];
            $includeMedia = $data['include_media'] ?? false;

            switch ($backupType) {
                case 'database':
                    $this->createDatabaseBackup($backupName);
                    break;
                case 'files':
                    $this->createFilesBackup($backupName, $includeMedia);
                    break;
                case 'full':
                    $this->createDatabaseBackup($backupName);
                    $this->createFilesBackup($backupName, $includeMedia);
                    break;
            }

            Notification::make()
                ->title('Резервная копия создана')
                ->body("Резервная копия '{$backupName}' успешно создана.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Ошибка создания копии')
                ->body('Произошла ошибка: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function scheduleBackup(array $data): void
    {
        // Here you would save the backup schedule settings
        // For now, we'll just show a notification
        if ($data['auto_backup']) {
            Notification::make()
                ->title('Расписание настроено')
                ->body("Автоматическое резервное копирование настроено: {$data['frequency']}")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Автоматическое копирование отключено')
                ->success()
                ->send();
        }
    }

    public function downloadBackup(string $filename): void
    {
        $path = storage_path('app/backups/' . $filename);

        if (file_exists($path)) {
            response()->download($path)->send();
        } else {
            Notification::make()
                ->title('Файл не найден')
                ->body('Резервная копия не найдена.')
                ->danger()
                ->send();
        }
    }

    public function deleteBackup(string $filename): void
    {
        try {
            Storage::disk('local')->delete('backups/' . $filename);

            Notification::make()
                ->title('Резервная копия удалена')
                ->body("Файл '{$filename}' успешно удален.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Ошибка удаления')
                ->body('Произошла ошибка: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getBackupsList(): array
    {
        $backupsPath = storage_path('app/backups');

        if (!is_dir($backupsPath)) {
            mkdir($backupsPath, 0755, true);
            return [];
        }

        $files = glob($backupsPath . '/*');
        $backups = [];

        foreach ($files as $file) {
            if (is_file($file)) {
                $backups[] = [
                    'name' => basename($file),
                    'size' => $this->formatBytes(filesize($file)),
                    'created_at' => Carbon::createFromTimestamp(filemtime($file))->format('d.m.Y H:i:s'),
                    'type' => $this->getBackupType(basename($file)),
                ];
            }
        }

        // Sort by creation date (newest first)
        usort($backups, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $backups;
    }

    public function getBackupStats(): array
    {
        $backups = $this->getBackupsList();
        $totalSize = 0;

        foreach ($backups as $backup) {
            $filePath = storage_path('app/backups/' . $backup['name']);
            if (file_exists($filePath)) {
                $totalSize += filesize($filePath);
            }
        }

        return [
            'total_backups' => count($backups),
            'total_size' => $this->formatBytes($totalSize),
            'latest_backup' => $backups[0]['created_at'] ?? 'Нет резервных копий',
            'disk_usage' => $this->getDiskUsage(),
        ];
    }

    private function createDatabaseBackup(string $backupName): void
    {
        $databaseName = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');

        $backupPath = storage_path('app/backups');
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $filename = $backupPath . '/' . $backupName . '_database.sql';

        // Create database backup using mysqldump
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($databaseName),
            escapeshellarg($filename)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Ошибка создания резервной копии базы данных');
        }

        // Compress the SQL file
        $this->compressFile($filename);
        unlink($filename); // Remove uncompressed version
    }

    private function createFilesBackup(string $backupName, bool $includeMedia = false): void
    {
        $backupPath = storage_path('app/backups');
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $zipFilename = $backupPath . '/' . $backupName . '_files.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipFilename, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Не удается создать ZIP архив');
        }

        // Add essential files
        $this->addDirectoryToZip($zip, base_path('app'), 'app');
        $this->addDirectoryToZip($zip, base_path('config'), 'config');
        $this->addDirectoryToZip($zip, base_path('database'), 'database');
        $this->addDirectoryToZip($zip, base_path('resources'), 'resources');
        $this->addDirectoryToZip($zip, base_path('routes'), 'routes');

        // Add storage files (excluding logs and cache)
        $storageExcludes = ['logs', 'cache', 'sessions'];
        $this->addDirectoryToZip($zip, storage_path('app'), 'storage/app', $storageExcludes);

        if ($includeMedia) {
            $this->addDirectoryToZip($zip, public_path('storage'), 'public/storage');
        }

        $zip->close();
    }

    private function addDirectoryToZip(ZipArchive $zip, string $directory, string $zipPath, array $excludes = []): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $skip = false;
            foreach ($excludes as $exclude) {
                if (strpos($file->getPathname(), $exclude) !== false) {
                    $skip = true;
                    break;
                }
            }

            if ($skip) continue;

            if ($file->isFile()) {
                $relativePath = $zipPath . '/' . $iterator->getSubPathName();
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }
    }

    private function compressFile(string $filename): void
    {
        $gzFilename = $filename . '.gz';
        $file = fopen($filename, 'rb');
        $gzFile = gzopen($gzFilename, 'wb9');

        while (!feof($file)) {
            gzwrite($gzFile, fread($file, 8192));
        }

        fclose($file);
        gzclose($gzFile);
    }

    private function getBackupType(string $filename): string
    {
        if (strpos($filename, '_database') !== false) {
            return 'База данных';
        } elseif (strpos($filename, '_files') !== false) {
            return 'Файлы';
        }
        return 'Неизвестно';
    }

    private function getDiskUsage(): string
    {
        $backupsPath = storage_path('app/backups');
        if (!is_dir($backupsPath)) {
            return '0 B';
        }

        $totalSize = 0;
        $files = glob($backupsPath . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
            }
        }

        return $this->formatBytes($totalSize);
    }

    private function formatBytes(int $bytes, int $precision = 2): string
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