<?php

namespace App\Infrastructure\Analysis\AiContext\Analyzers;

use Illuminate\Support\Facades\File;

class CodeAnalyzer extends BaseAnalyzer
{
    /**
     * Анализировать проблемы в коде
     */
    public function analyze(): array
    {
        $this->clearOutput();
        
        $this->addSection('⚠️ НАЙДЕННЫЕ ПРОБЛЕМЫ И ЗАМЕТКИ');
        
        $issues = $this->findCodeIssues();
        
        // TODO комментарии
        if (!empty($issues['todo'])) {
            $this->addLine("### 📝 TODO ({$issues['todo_count']})");
            foreach (array_slice($issues['todo'], 0, 5) as $todo) {
                $this->addLine("- {$todo['text']} (`{$todo['file']}:{$todo['line']}`)");
            }
            if ($issues['todo_count'] > 5) {
                $this->addLine("_... и ещё " . ($issues['todo_count'] - 5) . "_");
            }
            $this->addLine();
        }
        
        // Debug вызовы
        if (!empty($issues['debug'])) {
            $this->addLine("### ⚠️ Debug ({$issues['debug_count']})");
            foreach (array_slice($issues['debug'], 0, 5) as $debug) {
                $this->addLine("- {$debug['text']} (`{$debug['file']}:{$debug['line']}`)");
            }
            if ($issues['debug_count'] > 5) {
                $this->addLine("_... и ещё " . ($issues['debug_count'] - 5) . "_");
            }
            $this->addLine();
        }
        
        // FIXME комментарии
        if (!empty($issues['fixme'])) {
            $this->addLine("### 🔧 FIXME ({$issues['fixme_count']})");
            foreach ($issues['fixme'] as $fixme) {
                $this->addLine("- {$fixme['text']} (`{$fixme['file']}:{$fixme['line']}`)");
            }
            $this->addLine();
        }
        
        return $this->getOutput();
    }
    
    /**
     * Найти проблемы в коде
     */
    private function findCodeIssues(): array
    {
        $issues = [
            'todo' => [],
            'todo_count' => 0,
            'debug' => [],
            'debug_count' => 0,
            'fixme' => [],
            'fixme_count' => 0
        ];
        
        $directories = [
            'app',
            'resources/js'
        ];
        
        foreach ($directories as $dir) {
            $path = base_path($dir);
            if (!File::exists($path)) continue;
            
            $this->scanDirectory($path, $issues);
        }
        
        return $issues;
    }
    
    /**
     * Сканировать директорию на проблемы
     */
    private function scanDirectory(string $path, array &$issues): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            
            $extension = $file->getExtension();
            if (!in_array($extension, ['php', 'js', 'vue'])) continue;
            
            $this->scanFile($file->getPathname(), $issues);
        }
    }
    
    /**
     * Сканировать файл на проблемы
     */
    private function scanFile(string $filepath, array &$issues): void
    {
        $content = File::get($filepath);
        $lines = explode("\n", $content);
        $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $filepath);
        $relativePath = str_replace('\\', '/', $relativePath);
        
        foreach ($lines as $lineNum => $line) {
            $lineNumber = $lineNum + 1;
            
            // TODO
            if (preg_match('/\/\/\s*TODO\s*:?\s*(.*)$/i', $line, $matches)) {
                $issues['todo'][] = [
                    'file' => $relativePath,
                    'line' => $lineNumber,
                    'text' => trim($matches[1]) ?: 'TODO'
                ];
                $issues['todo_count']++;
            }
            
            // FIXME
            if (preg_match('/\/\/\s*FIXME\s*:?\s*(.*)$/i', $line, $matches)) {
                $issues['fixme'][] = [
                    'file' => $relativePath,
                    'line' => $lineNumber,
                    'text' => trim($matches[1]) ?: 'FIXME'
                ];
                $issues['fixme_count']++;
            }
            
            // Debug (console.log, dd, dump, var_dump)
            if (preg_match('/(console\.(log|error|warn)|dd\(|dump\(|var_dump\(|debug\()/i', $line)) {
                $issues['debug'][] = [
                    'file' => $relativePath,
                    'line' => $lineNumber,
                    'text' => 'Debug'
                ];
                $issues['debug_count']++;
            }
        }
    }
    
    /**
     * Генерировать рекомендации следующих шагов
     */
    public function generateNextSteps(): array
    {
        $steps = [
            'critical' => [],
            'important' => [],
            'nice_to_have' => []
        ];
        
        // Анализируем что отсутствует
        $patterns = \App\Services\AiContext\ContextConfig::getAutoDetectPatterns();
        
        // Критичные задачи
        if (!$this->filePatternExists('BookingController')) {
            $steps['critical'][] = 'Завершить систему бронирования - ключевая функция платформы!';
        }
        
        // Важные задачи
        if (!$this->filePatternExists('SearchController')) {
            $steps['important'][] = 'Реализовать поиск мастеров (сейчас 14%)';
        }
        
        if (!$this->filePatternExists('Masters/Index.vue')) {
            $steps['important'][] = 'Создать страницу списка мастеров (Masters/Index.vue)';
        }
        
        // Желательные задачи
        if (!$this->filePatternExists('MasterCard.vue')) {
            $steps['nice_to_have'][] = 'Создать компонент карточки мастера (MasterCard.vue)';
        }
        
        return $steps;
    }
    
    /**
     * Проверить существование файла по паттерну
     */
    private function filePatternExists(string $pattern): bool
    {
        $paths = [
            'app/Http/Controllers/',
            'resources/js/Pages/',
            'resources/js/Components/'
        ];
        
        foreach ($paths as $path) {
            if (!empty(glob(base_path($path . '*' . $pattern . '*')))) {
                return true;
            }
        }
        
        return false;
    }
}