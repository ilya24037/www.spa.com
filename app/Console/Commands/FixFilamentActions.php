<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixFilamentActions extends Command
{
    protected $signature = 'filament:fix-actions {--dry-run : Only show what would be changed}';
    protected $description = 'Fix Filament v3 Actions imports and usage in all resources';

    private $errors = [];
    private $fixed = [];

    public function handle()
    {
        $this->info('🔍 Scanning for Filament Actions issues...');

        $dryRun = $this->option('dry-run');

        // Получаем все PHP файлы в ресурсах Filament
        $files = File::glob(app_path('Filament/Resources/**/*.php'));
        $files = array_merge($files, File::glob(app_path('Filament/Resources/*.php')));

        foreach ($files as $file) {
            $this->processFile($file, $dryRun);
        }

        // Выводим отчет
        $this->printReport();

        if (!$dryRun && count($this->fixed) > 0) {
            $this->info('✅ Fixed ' . count($this->fixed) . ' files');
        }

        if (count($this->errors) > 0) {
            $this->error('❌ Found ' . count($this->errors) . ' errors');
            return 1;
        }

        return 0;
    }

    private function processFile($file, $dryRun)
    {
        $content = File::get($file);
        $originalContent = $content;
        $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file);

        // Паттерны для поиска и замены
        $patterns = [
            // Неправильные импорты Actions
            '/use Filament\\\\Tables\\\\Actions\\\\ViewAction;/' => '',
            '/use Filament\\\\Tables\\\\Actions\\\\EditAction;/' => '',
            '/use Filament\\\\Tables\\\\Actions\\\\DeleteAction;/' => '',
            '/use Filament\\\\Tables\\\\Actions\\\\BulkActionGroup;/' => '',
            '/use Filament\\\\Tables\\\\Actions\\\\DeleteBulkAction;/' => '',

            // Неправильное использование Actions без namespace
            '/(\s+)ViewAction::make\(\)/' => '$1Tables\Actions\ViewAction::make()',
            '/(\s+)EditAction::make\(\)/' => '$1Tables\Actions\EditAction::make()',
            '/(\s+)DeleteAction::make\(\)/' => '$1Tables\Actions\DeleteAction::make()',
            '/(\s+)BulkActionGroup::make\(/' => '$1Tables\Actions\BulkActionGroup::make(',
            '/(\s+)DeleteBulkAction::make\(\)/' => '$1Tables\Actions\DeleteBulkAction::make()',
        ];

        $changed = false;

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
                $changed = true;
            }
        }

        // Проверяем специфичные ошибки
        if (preg_match('/ViewAction::make\(\)/', $content) &&
            !preg_match('/Tables\\\\Actions\\\\ViewAction::make\(\)/', $content)) {
            $this->errors[] = [
                'file' => $relativePath,
                'issue' => 'ViewAction used without proper namespace'
            ];
        }

        if (preg_match('/EditAction::make\(\)/', $content) &&
            !preg_match('/Tables\\\\Actions\\\\EditAction::make\(\)/', $content)) {
            $this->errors[] = [
                'file' => $relativePath,
                'issue' => 'EditAction used without proper namespace'
            ];
        }

        // Если были изменения, сохраняем файл
        if ($changed && $content !== $originalContent) {
            if (!$dryRun) {
                File::put($file, $content);
            }
            $this->fixed[] = $relativePath;
            $this->line("  ✓ Fixed: $relativePath");
        }

        // Логируем все найденные Actions
        if (preg_match_all('/Tables\\\\Actions\\\\(\w+)::make/', $content, $matches)) {
            foreach (array_unique($matches[1]) as $action) {
                $this->info("    Found action: $action in $relativePath");
            }
        }
    }

    private function printReport()
    {
        $this->newLine();
        $this->info('=== FILAMENT ACTIONS FIX REPORT ===');

        if (count($this->fixed) > 0) {
            $this->info('Fixed files:');
            foreach ($this->fixed as $file) {
                $this->line("  ✓ $file");
            }
        }

        if (count($this->errors) > 0) {
            $this->error('Errors found:');
            foreach ($this->errors as $error) {
                $this->error("  ✗ {$error['file']}: {$error['issue']}");
            }
        }

        if (count($this->fixed) === 0 && count($this->errors) === 0) {
            $this->info('✅ No issues found! All Actions are properly configured.');
        }
    }
}