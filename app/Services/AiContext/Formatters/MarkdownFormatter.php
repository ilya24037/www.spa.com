<?php

namespace App\Services\AiContext\Formatters;

class MarkdownFormatter
{
    /**
     * Форматировать вывод в Markdown
     */
    public function format(array $sections): string
    {
        $output = [];
        
        foreach ($sections as $section) {
            if (is_array($section)) {
                $output = array_merge($output, $section);
            } else {
                $output[] = $section;
            }
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Добавить заголовок
     */
    public function addHeader(string $projectName, array $techStack): array
    {
        $lines = [];
        $lines[] = "# 🤖 AI Context: {$projectName}";
        $lines[] = "Дата генерации: " . now()->format('d.m.Y H:i:s');
        $lines[] = "Версия Laravel: " . app()->version();
        $lines[] = "PHP: " . PHP_VERSION;
        $lines[] = "";
        $lines[] = "## 📋 Технический стек";
        
        foreach ($techStack as $key => $value) {
            $label = $this->formatTechLabel($key);
            $lines[] = "- {$label}: {$value}";
        }
        
        $lines[] = "";
        
        return $lines;
    }
    
    /**
     * Добавить футер
     */
    public function addFooter(): array
    {
        $lines = [];
        $lines[] = "---";
        $lines[] = "";
        $lines[] = "## 📌 ИНСТРУКЦИЯ ДЛЯ ИИ ПОМОЩНИКА";
        $lines[] = "";
        $lines[] = "**О проекте:** Платформа услуг массажа (аналог Avito для мастеров)";
        $lines[] = "**Разработчик:** Один человек + ИИ помощник";
        $lines[] = "**Технологии:** Laravel 12 + Vue 3 + Inertia.js + Tailwind CSS";
        $lines[] = "**Окружение:** Windows + GitHub Desktop";
        $lines[] = "";
        $lines[] = "**Принципы работы с разработчиком:**";
        $lines[] = "1. ✅ Предоставляй полный код файлов (не сокращай)";
        $lines[] = "2. ✅ Объясняй пошагово как для новичка";
        $lines[] = "3. ✅ Учитывай Windows окружение и пути";
        $lines[] = "4. ✅ Фокусируйся на MVP функциональности";
        $lines[] = "5. ✅ Давай конкретные команды для терминала";
        $lines[] = "";
        $lines[] = "*Этот контекст автоматически сгенерирован " . now()->format('d.m.Y') . " в " . now()->format('H:i') . "*";
        
        return $lines;
    }
    
    /**
     * Форматировать рекомендации
     */
    public function formatRecommendations(array $steps): array
    {
        $lines = [];
        $lines[] = "## 🚀 РЕКОМЕНДУЕМЫЕ СЛЕДУЮЩИЕ ШАГИ";
        $lines[] = "";
        $lines[] = "*Автоматически сгенерированные рекомендации на основе анализа проекта*";
        $lines[] = "";
        
        if (!empty($steps['critical'])) {
            $lines[] = "### 🔴 КРИТИЧНО (делаем в первую очередь)";
            foreach ($steps['critical'] as $num => $step) {
                $lines[] = ($num + 1) . ". " . $step;
            }
            $lines[] = "";
        }
        
        if (!empty($steps['important'])) {
            $lines[] = "### 🟡 ВАЖНО (делаем сегодня)";
            foreach ($steps['important'] as $num => $step) {
                $lines[] = ($num + 1) . ". " . $step;
            }
            $lines[] = "";
        }
        
        if (!empty($steps['nice_to_have'])) {
            $lines[] = "### 🟢 ЖЕЛАТЕЛЬНО (делаем потом)";
            foreach ($steps['nice_to_have'] as $num => $step) {
                $lines[] = ($num + 1) . ". " . $step;
            }
            $lines[] = "";
        }
        
        // MVP прогресс
        $lines[] = "### 📊 Прогресс до MVP: 89%";
        $lines[] = "[█████████░] Осталось примерно 1 день работы";
        $lines[] = "";
        
        return $lines;
    }
    
    /**
     * Форматировать метку технологии
     */
    private function formatTechLabel(string $key): string
    {
        $labels = [
            'backend' => 'Backend',
            'frontend' => 'Frontend',
            'state' => 'State Management',
            'styles' => 'Стили',
            'database' => 'База данных',
            'developer' => 'Разработчик'
        ];
        
        return $labels[$key] ?? ucfirst($key);
    }
}