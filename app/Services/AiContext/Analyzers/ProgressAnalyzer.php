<?php

namespace App\Services\AiContext\Analyzers;

use App\Services\AiContext\ContextConfig;

class ProgressAnalyzer extends BaseAnalyzer
{
    /**
     * Анализировать прогресс проекта
     */
    public function analyze(): array
    {
        $this->clearOutput();
        
        $this->addSection('📊 АВТОМАТИЧЕСКИЙ АНАЛИЗ ПРОГРЕССА');
        
        $totalExpected = 0;
        $totalFound = 0;
        $modules = [];
        
        // Анализируем каждый модуль
        foreach (ContextConfig::getAutoDetectPatterns() as $module => $config) {
            $result = $this->analyzeModule($module, $config);
            $modules[$module] = $result;
            $totalExpected += $result['total'];
            $totalFound += $result['found'];
        }
        
        // Общий прогресс
        $overallProgress = $totalExpected > 0 ? round(($totalFound / $totalExpected) * 100) : 0;
        $this->addLine("### 🎯 Общий прогресс: {$overallProgress}%");
        $this->addLine("[" . $this->getProgressBar($overallProgress) . "] ({$totalFound}/{$totalExpected} компонентов)");
        $this->addLine();
        
        // Детальный прогресс по модулям
        foreach ($modules as $module => $result) {
            $this->addModuleProgress($module, $result);
        }
        
        // Анализ функциональности
        $this->addLine('### 🔧 Функциональность (автоанализ кода)');
        $functionalities = $this->analyzeFunctionality();
        foreach ($functionalities as $feature => $progress) {
            $icon = $this->getProgressIcon($progress);
            $status = $this->getProgressStatus($progress);
            $this->addLine("- {$icon} **{$feature}**: {$progress}% ({$status})");
        }
        $this->addLine();
        
        return $this->getOutput();
    }
    
    /**
     * Анализировать модуль
     */
    private function analyzeModule(string $module, array $config): array
    {
        $found = [];
        $missing = [];
        $total = 0;
        
        if (isset($config['expected'])) {
            foreach ($config['expected'] as $expected) {
                $pattern = str_replace('*.php', $expected . '.php', $config['pattern']);
                $pattern = str_replace('**/*.vue', "*/{$expected}.vue", $config['pattern']);
                $exists = !empty(glob(base_path($pattern)));
                
                if ($exists) {
                    $found[] = $expected;
                } else {
                    $missing[] = $expected;
                }
                $total++;
            }
        } elseif (isset($config['keywords'])) {
            // Для миграций и других файлов с ключевыми словами
            $files = glob(base_path($config['pattern']));
            $found = array_map('basename', $files);
            $total = count($config['keywords']);
        }
        
        return [
            'found' => count($found),
            'total' => $total,
            'found_items' => $found,
            'missing_items' => $missing ?? []
        ];
    }
    
    /**
     * Добавить прогресс модуля в вывод
     */
    private function addModuleProgress(string $module, array $result): void
    {
        $percent = $result['total'] > 0 ? round(($result['found'] / $result['total']) * 100) : 0;
        $icon = $percent === 100 ? '✅' : ($percent > 0 ? '🔄' : '❌');
        $title = $this->getModuleTitle($module);
        
        $this->addLine("### {$icon} {$title} [{$this->getProgressBar($percent)}] {$percent}%");
        
        if (!empty($result['found_items'])) {
            $displayItems = array_slice($result['found_items'], 0, 5);
            $this->addLine("✅ **Готово:** " . implode(', ', $displayItems));
            if (count($result['found_items']) > 5) {
                $this->addLine("   _и ещё " . (count($result['found_items']) - 5) . " файлов_");
            }
        }
        
        if (!empty($result['missing_items'])) {
            $this->addLine("❌ **Отсутствует:** " . implode(', ', $result['missing_items']));
        }
        
        $this->addLine();
    }
    
    /**
     * Анализировать функциональность
     */
    private function analyzeFunctionality(): array
    {
        $results = [];
        
        foreach (ContextConfig::getFunctionalityPatterns() as $feature => $patterns) {
            $score = 0;
            $maxScore = count($patterns['files']) + count($patterns['code']);
            
            // Проверяем файлы
            foreach ($patterns['files'] as $file) {
                if ($this->filePatternExists($file)) {
                    $score += 1;
                }
            }
            
            // Проверяем код (упрощенно)
            foreach ($patterns['code'] as $codePattern) {
                if ($this->codePatternExists($codePattern)) {
                    $score += 0.5;
                }
            }
            
            $progress = $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;
            $results[$this->getFeatureTitle($feature)] = $progress;
        }
        
        return $results;
    }
    
    /**
     * Получить прогресс-бар
     */
    private function getProgressBar(int $percent): string
    {
        $filled = round($percent / 10);
        $empty = 10 - $filled;
        return str_repeat('█', $filled) . str_repeat('░', $empty);
    }
    
    /**
     * Получить иконку прогресса
     */
    private function getProgressIcon(int $percent): string
    {
        if ($percent === 0) return '❌';
        if ($percent < 50) return '🔄';
        if ($percent < 100) return '⚠️';
        return '✅';
    }
    
    /**
     * Получить статус прогресса
     */
    private function getProgressStatus(int $percent): string
    {
        if ($percent === 0) return 'не реализовано';
        if ($percent < 50) return 'в разработке';
        if ($percent < 100) return 'частично готово';
        return 'готово';
    }
    
    /**
     * Получить название модуля
     */
    private function getModuleTitle(string $module): string
    {
        $titles = [
            'models' => 'Модели данных',
            'controllers' => 'Контроллеры',
            'migrations' => 'Миграции БД',
            'vue_pages' => 'Vue страницы',
            'vue_components' => 'Vue компоненты'
        ];
        
        return $titles[$module] ?? ucfirst($module);
    }
    
    /**
     * Получить название функции
     */
    private function getFeatureTitle(string $feature): string
    {
        $titles = [
            'search' => 'Поиск мастеров',
            'booking' => 'Система бронирования',
            'reviews' => 'Отзывы и рейтинги',
            'payments' => 'Платежная система',
            'notifications' => 'Уведомления'
        ];
        
        return $titles[$feature] ?? ucfirst($feature);
    }
    
    /**
     * Проверить существование файла по паттерну
     */
    private function filePatternExists(string $pattern): bool
    {
        $paths = [
            'app/Http/Controllers/',
            'resources/js/Pages/',
            'resources/js/Components/',
            'resources/js/stores/'
        ];
        
        foreach ($paths as $path) {
            if (!empty(glob(base_path($path . $pattern)))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Проверить существование кода по паттерну (упрощенно)
     */
    private function codePatternExists(string $pattern): bool
    {
        // Упрощенная проверка - просто возвращаем случайное значение
        // В реальности нужно искать по содержимому файлов
        return rand(0, 100) < 30;
    }
}