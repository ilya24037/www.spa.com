<?php

namespace App\Services\AiContext;

use App\Services\AiContext\Analyzers\FileAnalyzer;
use App\Services\AiContext\Analyzers\GitAnalyzer;
use App\Services\AiContext\Analyzers\ProgressAnalyzer;
use App\Services\AiContext\Analyzers\CodeAnalyzer;
use App\Services\AiContext\Formatters\MarkdownFormatter;
use Illuminate\Support\Facades\File;

class AiContextService
{
    private FileAnalyzer $fileAnalyzer;
    private GitAnalyzer $gitAnalyzer;
    private ProgressAnalyzer $progressAnalyzer;
    private CodeAnalyzer $codeAnalyzer;
    private MarkdownFormatter $formatter;
    
    public function __construct()
    {
        $this->fileAnalyzer = new FileAnalyzer();
        $this->gitAnalyzer = new GitAnalyzer();
        $this->progressAnalyzer = new ProgressAnalyzer();
        $this->codeAnalyzer = new CodeAnalyzer();
        $this->formatter = new MarkdownFormatter();
    }
    
    /**
     * Генерировать контекст
     */
    public function generate(string $mode = 'normal'): array
    {
        $sections = [];
        
        // Получаем информацию о проекте
        $projectInfo = ContextConfig::getProjectInfo();
        
        // Заголовок
        $sections['header'] = $this->formatter->addHeader(
            $projectInfo['name'],
            $projectInfo['tech_stack']
        );
        
        // Git анализ и текущий фокус
        $sections['git'] = $this->gitAnalyzer->analyze();
        
        // Если есть недавние файлы, определяем фокус работы
        $recentFiles = $this->fileAnalyzer->getRecentlyModifiedFiles();
        if (!empty($recentFiles)) {
            $focus = $this->gitAnalyzer->detectWorkFocus($recentFiles);
            if ($focus) {
                $sections['git'][] = "🎯 **Скорее всего работали над:** " . $focus;
                $sections['git'][] = "";
            }
        }
        
        // Прогресс проекта
        $sections['progress'] = $this->progressAnalyzer->analyze();
        
        // Структура проекта
        if ($mode !== 'quick') {
            $sections['structure'] = $this->fileAnalyzer->analyze();
        }
        
        // Проблемы в коде
        $sections['issues'] = $this->codeAnalyzer->analyze();
        
        // Рекомендации
        $recommendations = $this->codeAnalyzer->generateNextSteps();
        $sections['recommendations'] = $this->formatter->formatRecommendations($recommendations);
        
        // Футер
        $sections['footer'] = $this->formatter->addFooter();
        
        // Форматируем в Markdown
        $content = $this->formatter->format($sections);
        
        return [
            'content' => $content,
            'stats' => $this->calculateStats($content),
            'mode' => $mode
        ];
    }
    
    /**
     * Сохранить контекст
     */
    public function save(string $content): array
    {
        $dir = storage_path('ai-sessions');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "context_{$timestamp}.md";
        
        // Сохраняем в архив
        File::put($dir . '/' . $filename, $content);
        File::put($dir . '/latest_context.md', $content);
        
        // Сохраняем в корень для удобства
        File::put(base_path('AI_CONTEXT.md'), $content);
        
        // Сохраняем метаданные
        $metadata = [
            'generated_at' => now()->toIso8601String(),
            'stats' => $this->calculateStats($content),
            'files' => [
                'archive' => "storage/ai-sessions/{$filename}",
                'latest' => 'storage/ai-sessions/latest_context.md',
                'main' => 'AI_CONTEXT.md'
            ]
        ];
        
        File::put($dir . '/latest_meta.json', json_encode($metadata, JSON_PRETTY_PRINT));
        
        return $metadata;
    }
    
    /**
     * Рассчитать статистику
     */
    private function calculateStats(string $content): array
    {
        $lines = explode("\n", $content);
        
        return [
            'lines' => count($lines),
            'size' => strlen($content),
            'size_formatted' => $this->formatFileSize(strlen($content)),
            'words' => str_word_count($content)
        ];
    }
    
    /**
     * Форматировать размер файла
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}