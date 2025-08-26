<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\NotificationTemplate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;

/**
 * Сервис рендеринга шаблонов уведомлений
 */
class TemplateRenderingService
{
    /**
     * Рендерить шаблон с данными
     */
    public function render(NotificationTemplate $template, array $data = []): array
    {
        // Валидация обязательных переменных
        $this->validateRequiredVariables($template, $data);
        
        // Подготовка переменных с дефолтными значениями
        $variables = $this->prepareVariables($template, $data);
        
        // Рендеринг каждого поля
        return [
            'title' => $this->renderString($template->title, $variables),
            'subject' => $this->renderString($template->subject, $variables),
            'content' => $this->renderContent($template->content, $variables),
            'metadata' => [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'rendered_at' => now()->toDateTimeString(),
                'variables_used' => array_keys($variables),
            ],
        ];
    }

    /**
     * Проверить обязательные переменные
     */
    public function validateRequiredVariables(NotificationTemplate $template, array $data): void
    {
        $required = $this->getRequiredVariables($template);
        $missing = array_diff($required, array_keys($data));
        
        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                "Отсутствуют обязательные переменные: " . implode(', ', $missing)
            );
        }
    }

    /**
     * Получить список обязательных переменных
     */
    public function getRequiredVariables(NotificationTemplate $template): array
    {
        $variables = $template->variables ?? [];
        
        return array_keys(array_filter($variables, function($var) {
            return $var['required'] ?? false;
        }));
    }

    /**
     * Получить список опциональных переменных
     */
    public function getOptionalVariables(NotificationTemplate $template): array
    {
        $variables = $template->variables ?? [];
        
        return array_keys(array_filter($variables, function($var) {
            return !($var['required'] ?? false);
        }));
    }

    /**
     * Подготовить переменные с дефолтными значениями
     */
    private function prepareVariables(NotificationTemplate $template, array $data): array
    {
        $variables = [];
        
        foreach ($template->variables ?? [] as $key => $config) {
            // Используем переданное значение или дефолтное из конфига
            $variables[$key] = $data[$key] ?? $config['default'] ?? '';
            
            // Применяем форматирование если указано
            if (isset($config['format'])) {
                $variables[$key] = $this->formatVariable($variables[$key], $config['format']);
            }
        }
        
        // Добавляем системные переменные
        $variables = array_merge($variables, $this->getSystemVariables());
        
        return $variables;
    }

    /**
     * Рендеринг строки с переменными
     */
    private function renderString(?string $template, array $variables): ?string
    {
        if (!$template) {
            return null;
        }

        // Поддержка разных форматов переменных: {{var}}, {var}, %var%
        $rendered = $template;
        
        // Формат {{variable}}
        $rendered = preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) use ($variables) {
            $key = $matches[1];
            return $variables[$key] ?? $matches[0];
        }, $rendered);
        
        // Формат {variable}
        $rendered = preg_replace_callback('/\{(\w+)\}/', function($matches) use ($variables) {
            $key = $matches[1];
            return $variables[$key] ?? $matches[0];
        }, $rendered);
        
        // Формат %variable%
        $rendered = preg_replace_callback('/%(\w+)%/', function($matches) use ($variables) {
            $key = $matches[1];
            return $variables[$key] ?? $matches[0];
        }, $rendered);
        
        return $rendered;
    }

    /**
     * Рендеринг контента (может быть HTML или Markdown)
     */
    private function renderContent(?string $content, array $variables): ?string
    {
        if (!$content) {
            return null;
        }
        
        // Сначала заменяем переменные
        $rendered = $this->renderString($content, $variables);
        
        // Если контент содержит Blade-синтаксис, компилируем его
        if ($this->containsBladeDirectives($rendered)) {
            $rendered = $this->renderBlade($rendered, $variables);
        }
        
        // Если это Markdown, конвертируем в HTML
        if ($this->isMarkdown($rendered)) {
            $rendered = $this->renderMarkdown($rendered);
        }
        
        return $rendered;
    }

    /**
     * Проверить наличие Blade директив
     */
    private function containsBladeDirectives(string $content): bool
    {
        return preg_match('/@(if|foreach|for|while|include|extends)/', $content) === 1;
    }

    /**
     * Рендеринг Blade шаблона
     */
    private function renderBlade(string $template, array $variables): string
    {
        try {
            return Blade::render($template, $variables);
        } catch (\Exception $e) {
            // Если не удалось скомпилировать, возвращаем как есть
            return $template;
        }
    }

    /**
     * Проверить, является ли контент Markdown
     */
    private function isMarkdown(string $content): bool
    {
        // Простая проверка на наличие Markdown синтаксиса
        return preg_match('/^#{1,6}\s|\*\*.*\*\*|\[.*\]\(.*\)/', $content) === 1;
    }

    /**
     * Конвертировать Markdown в HTML
     */
    private function renderMarkdown(string $markdown): string
    {
        // Здесь можно использовать библиотеку для парсинга Markdown
        // Для примера - простая замена
        $html = $markdown;
        
        // Заголовки
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        
        // Жирный текст
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        
        // Ссылки
        $html = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $html);
        
        // Переносы строк
        $html = nl2br($html);
        
        return $html;
    }

    /**
     * Форматировать переменную
     */
    private function formatVariable($value, string $format): string
    {
        switch ($format) {
            case 'currency':
                return number_format((float)$value, 2, ',', ' ') . ' ₽';
            
            case 'date':
                return $value instanceof \DateTime 
                    ? $value->format('d.m.Y')
                    : (string)$value;
            
            case 'datetime':
                return $value instanceof \DateTime 
                    ? $value->format('d.m.Y H:i')
                    : (string)$value;
            
            case 'phone':
                return $this->formatPhone((string)$value);
            
            case 'uppercase':
                return mb_strtoupper((string)$value);
            
            case 'lowercase':
                return mb_strtolower((string)$value);
            
            case 'capitalize':
                return mb_convert_case((string)$value, MB_CASE_TITLE);
            
            default:
                return (string)$value;
        }
    }

    /**
     * Форматировать телефон
     */
    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) === 11) {
            return sprintf('+%s (%s) %s-%s-%s',
                substr($phone, 0, 1),
                substr($phone, 1, 3),
                substr($phone, 4, 3),
                substr($phone, 7, 2),
                substr($phone, 9, 2)
            );
        }
        
        return $phone;
    }

    /**
     * Получить системные переменные
     */
    private function getSystemVariables(): array
    {
        return [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'current_date' => now()->format('d.m.Y'),
            'current_time' => now()->format('H:i'),
            'current_year' => now()->year,
        ];
    }

    /**
     * Извлечь переменные из шаблона
     */
    public function extractVariables(string $content): array
    {
        $variables = [];
        
        // Ищем все форматы переменных
        if (preg_match_all('/\{\{(\w+)\}\}/', $content, $matches)) {
            $variables = array_merge($variables, $matches[1]);
        }
        
        if (preg_match_all('/\{(\w+)\}/', $content, $matches)) {
            $variables = array_merge($variables, $matches[1]);
        }
        
        if (preg_match_all('/%(\w+)%/', $content, $matches)) {
            $variables = array_merge($variables, $matches[1]);
        }
        
        return array_unique($variables);
    }
}