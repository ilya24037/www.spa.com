<?php

namespace App\Domain\Notification\Handlers;

use App\Domain\Notification\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик рендеринга и тестирования шаблонов
 */
class TemplateRenderHandler
{
    /**
     * Рендерить шаблон с данными
     */
    public function render(NotificationTemplate $template, array $data = []): array
    {
        try {
            return $template->render($data);
        } catch (\Exception $e) {
            Log::error('Template rendering failed', [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            
            throw $e;
        }
    }

    /**
     * Проверить шаблон с тестовыми данными
     */
    public function test(NotificationTemplate $template, array $testData = []): array
    {
        // Подготовить тестовые данные
        $testData = array_merge($this->getDefaultTestData(), $testData);
        
        try {
            // Рендерить шаблон
            $rendered = $this->render($template, $testData);
            
            return [
                'success' => true,
                'rendered' => $rendered,
                'test_data' => $testData,
                'validation_errors' => $template->validate(),
                'performance' => $this->measureRenderingPerformance($template, $testData),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'test_data' => $testData,
                'validation_errors' => $template->validate(),
            ];
        }
    }

    /**
     * Массовое тестирование шаблонов
     */
    public function batchTest(array $templates, array $testData = []): array
    {
        $results = [];
        
        foreach ($templates as $template) {
            if ($template instanceof NotificationTemplate) {
                $results[$template->id] = $this->test($template, $testData);
            }
        }
        
        return $results;
    }

    /**
     * Предварительный просмотр шаблона
     */
    public function preview(NotificationTemplate $template, array $data = []): array
    {
        // Используем безопасные тестовые данные для предварительного просмотра
        $previewData = array_merge($this->getSafePreviewData(), $data);
        
        try {
            $rendered = $this->render($template, $previewData);
            
            return [
                'success' => true,
                'preview' => $rendered,
                'data_used' => $previewData,
                'missing_variables' => $this->findMissingVariables($template, $previewData),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data_used' => $previewData,
            ];
        }
    }

    /**
     * Валидировать переменные шаблона
     */
    public function validateVariables(NotificationTemplate $template, array $data): array
    {
        $templateVariables = $template->variables ?? [];
        $errors = [];
        $warnings = [];

        foreach ($templateVariables as $key => $config) {
            $isRequired = $config['required'] ?? false;
            $hasValue = isset($data[$key]) && !empty($data[$key]);

            if ($isRequired && !$hasValue) {
                $errors[] = "Required variable '{$key}' is missing or empty";
            }

            if (!$isRequired && !$hasValue) {
                $warnings[] = "Optional variable '{$key}' is not provided";
            }

            // Проверка типа данных если указан
            if (isset($config['type']) && $hasValue) {
                $expectedType = $config['type'];
                $actualType = gettype($data[$key]);
                
                if (!$this->isCompatibleType($actualType, $expectedType)) {
                    $errors[] = "Variable '{$key}' expected {$expectedType}, got {$actualType}";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Найти отсутствующие переменные
     */
    public function findMissingVariables(NotificationTemplate $template, array $data): array
    {
        $templateVariables = array_keys($template->variables ?? []);
        $providedVariables = array_keys($data);
        
        return array_diff($templateVariables, $providedVariables);
    }

    /**
     * Найти неиспользуемые переменные
     */
    public function findUnusedVariables(NotificationTemplate $template, array $data): array
    {
        $templateVariables = array_keys($template->variables ?? []);
        $providedVariables = array_keys($data);
        
        return array_diff($providedVariables, $templateVariables);
    }

    /**
     * Оптимизировать шаблон для рендеринга
     */
    public function optimize(NotificationTemplate $template): array
    {
        $optimizations = [];
        
        // Проверяем длину контента
        $contentLength = strlen($template->content ?? '');
        if ($contentLength > 10000) {
            $optimizations[] = 'Content is very long, consider splitting';
        }

        // Проверяем количество переменных
        $variableCount = count($template->variables ?? []);
        if ($variableCount > 20) {
            $optimizations[] = 'Too many variables, consider simplifying';
        }

        // Проверяем сложность рендеринга
        $complexity = $this->calculateRenderingComplexity($template);
        if ($complexity > 100) {
            $optimizations[] = 'High rendering complexity detected';
        }

        return [
            'optimizations' => $optimizations,
            'complexity_score' => $complexity,
            'estimated_render_time' => $this->estimateRenderTime($complexity),
        ];
    }

    /**
     * Измерить производительность рендеринга
     */
    protected function measureRenderingPerformance(NotificationTemplate $template, array $data): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        try {
            $this->render($template, $data);
            $success = true;
        } catch (\Exception $e) {
            $success = false;
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        return [
            'render_time' => round(($endTime - $startTime) * 1000, 2), // в миллисекундах
            'memory_used' => $endMemory - $startMemory, // в байтах
            'success' => $success,
        ];
    }

    /**
     * Рассчитать сложность рендеринга
     */
    protected function calculateRenderingComplexity(NotificationTemplate $template): int
    {
        $complexity = 0;
        
        // Базовая сложность по длине контента
        $complexity += strlen($template->content ?? '') / 100;
        
        // Сложность по количеству переменных
        $complexity += count($template->variables ?? []) * 2;
        
        // Сложность по количеству условных конструкций
        $content = $template->content ?? '';
        $complexity += substr_count($content, '{{#if') * 3;
        $complexity += substr_count($content, '{{#each') * 5;
        $complexity += substr_count($content, '{{#unless') * 3;
        
        return (int) $complexity;
    }

    /**
     * Оценить время рендеринга
     */
    protected function estimateRenderTime(int $complexity): string
    {
        if ($complexity < 20) {
            return '< 1ms';
        } elseif ($complexity < 50) {
            return '1-5ms';
        } elseif ($complexity < 100) {
            return '5-20ms';
        } else {
            return '> 20ms';
        }
    }

    /**
     * Проверить совместимость типов
     */
    protected function isCompatibleType(string $actual, string $expected): bool
    {
        $typeMap = [
            'string' => ['string'],
            'number' => ['integer', 'double'],
            'boolean' => ['boolean'],
            'array' => ['array'],
            'object' => ['object', 'array'],
        ];
        
        $compatibleTypes = $typeMap[$expected] ?? [$expected];
        
        return in_array($actual, $compatibleTypes);
    }

    /**
     * Получить тестовые данные по умолчанию
     */
    public function getDefaultTestData(): array
    {
        return [
            'user_name' => 'Иван Иванов',
            'user_email' => 'ivan@example.com',
            'service_name' => 'Расслабляющий массаж',
            'master_name' => 'Анна Мастерова',
            'booking_number' => 'BK-12345',
            'booking_date' => '15 декабря 2024, 14:00',
            'amount' => '2 500 ₽',
            'transaction_id' => 'TXN-67890',
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'current_date' => now()->format('d.m.Y'),
            'current_time' => now()->format('H:i'),
        ];
    }

    /**
     * Получить безопасные данные для предварительного просмотра
     */
    protected function getSafePreviewData(): array
    {
        return [
            'user_name' => '[Имя пользователя]',
            'user_email' => '[email@example.com]',
            'service_name' => '[Название услуги]',
            'master_name' => '[Имя мастера]',
            'booking_number' => '[Номер бронирования]',
            'booking_date' => '[Дата и время]',
            'amount' => '[Сумма]',
            'transaction_id' => '[ID транзакции]',
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
        ];
    }

    /**
     * Создать отчет о рендеринге
     */
    public function createRenderingReport(NotificationTemplate $template, array $testCases = []): array
    {
        if (empty($testCases)) {
            $testCases = [
                'default' => $this->getDefaultTestData(),
                'minimal' => ['user_name' => 'Test User'],
                'full' => array_merge($this->getDefaultTestData(), [
                    'additional_data' => 'Extra information',
                    'custom_field' => 'Custom value',
                ]),
            ];
        }

        $report = [
            'template_info' => [
                'id' => $template->id,
                'name' => $template->name,
                'type' => $template->type->value,
                'variables_count' => count($template->variables ?? []),
            ],
            'test_results' => [],
            'performance' => [],
            'recommendations' => [],
        ];

        foreach ($testCases as $caseName => $data) {
            $result = $this->test($template, $data);
            $report['test_results'][$caseName] = $result;
            
            if (isset($result['performance'])) {
                $report['performance'][$caseName] = $result['performance'];
            }
        }

        // Добавляем рекомендации по оптимизации
        $optimization = $this->optimize($template);
        $report['recommendations'] = $optimization['optimizations'];
        
        return $report;
    }
}