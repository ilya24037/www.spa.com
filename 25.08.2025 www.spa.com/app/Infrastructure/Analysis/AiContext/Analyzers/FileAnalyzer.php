<?php

namespace App\Infrastructure\Analysis\AiContext\Analyzers;

use Illuminate\Support\Facades\File;

class FileAnalyzer extends BaseAnalyzer
{
    /**
     * Анализировать файловую структуру проекта
     */
    public function analyze(): array
    {
        $this->clearOutput();
        
        $this->addSection('📁 СТРУКТУРА ПРОЕКТА');
        
        // Статистика файлов
        $stats = $this->calculateFileStats();
        $this->addLine('**Статистика файлов:**');
        $this->addLine("- PHP файлов: {$stats['php']}");
        $this->addLine("- Vue компонентов: {$stats['vue']}");
        $this->addLine("- JavaScript: {$stats['js']}");
        $this->addLine("- Всего строк кода: " . number_format($stats['total_lines']));
        $this->addLine();
        
        return $this->getOutput();
    }
    
    /**
     * Получить последние измененные файлы
     */
    public function getRecentlyModifiedFiles(int $limit = 10): array
    {
        $files = [];
        $directories = [
            'app',
            'resources/js',
            'resources/views',
            'database/migrations'
        ];
        
        foreach ($directories as $dir) {
            $path = base_path($dir);
            if (!File::exists($path)) continue;
            
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && !$this->shouldIgnoreFile($file->getPathname())) {
                    $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $files[] = [
                        'path' => str_replace('\\', '/', $relativePath),
                        'time' => $file->getMTime()
                    ];
                }
            }
        }
        
        // Сортируем по времени изменения
        usort($files, function($a, $b) {
            return $b['time'] - $a['time'];
        });
        
        return array_slice($files, 0, $limit);
    }
    
    /**
     * Определить тип файла по расширению
     */
    public function getFileType(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = basename($path);
        
        $types = [
            'vue' => '🧩 Компонент',
            'php' => '📋',
            'js' => '⚡ JavaScript',
            'blade.php' => '📄 Шаблон',
            'json' => '📦 Конфиг',
            'md' => '📝 Документация'
        ];
        
        // Специальные типы для PHP файлов
        if ($extension === 'php') {
            if (str_contains($path, 'Controller')) return '🎮 Контроллер';
            if (str_contains($path, 'Model')) return '📋 Модель';
            if (str_contains($path, 'Service')) return '🔧 Сервис';
            if (str_contains($path, 'migration')) return '🗄️ Миграция';
        }
        
        return $types[$extension] ?? '📄';
    }
    
    /**
     * Подсчитать статистику файлов
     */
    private function calculateFileStats(): array
    {
        $stats = [
            'php' => 0,
            'vue' => 0,
            'js' => 0,
            'total_lines' => 0
        ];
        
        // PHP файлы
        $phpFiles = glob(base_path('app/**/*.php'));
        $stats['php'] = count($phpFiles);
        
        // Vue компоненты
        $vueFiles = glob(base_path('resources/js/**/*.vue'));
        $stats['vue'] = count($vueFiles);
        
        // JavaScript файлы
        $jsFiles = glob(base_path('resources/js/**/*.js'));
        $stats['js'] = count($jsFiles);
        
        // Подсчет строк кода (упрощенно)
        $allFiles = array_merge($phpFiles, $vueFiles, $jsFiles);
        foreach ($allFiles as $file) {
            if (file_exists($file)) {
                $stats['total_lines'] += count(file($file));
            }
        }
        
        return $stats;
    }
    
    /**
     * Проверить, нужно ли игнорировать файл
     */
    private function shouldIgnoreFile(string $path): bool
    {
        $ignorePaths = [
            'node_modules',
            'vendor',
            'storage',
            '.git',
            'public/build',
            'bootstrap/cache'
        ];
        
        foreach ($ignorePaths as $ignore) {
            if (str_contains($path, $ignore)) {
                return true;
            }
        }
        
        return false;
    }
}