<?php

namespace App\Services\AiContext\Analyzers;

abstract class BaseAnalyzer
{
    protected array $outputLines = [];
    
    /**
     * Выполнить анализ
     */
    abstract public function analyze(): array;
    
    /**
     * Добавить линию в вывод
     */
    protected function addLine(string $line = ''): void
    {
        $this->outputLines[] = $line;
    }
    
    /**
     * Добавить заголовок секции
     */
    protected function addSection(string $title, int $level = 2): void
    {
        $prefix = str_repeat('#', $level);
        $this->addLine("{$prefix} {$title}");
        $this->addLine();
    }
    
    /**
     * Получить результаты анализа
     */
    public function getOutput(): array
    {
        return $this->outputLines;
    }
    
    /**
     * Очистить вывод
     */
    protected function clearOutput(): void
    {
        $this->outputLines = [];
    }
    
    /**
     * Выполнить команду shell
     */
    protected function executeCommand(string $command): ?string
    {
        $isWindows = PHP_OS_FAMILY === 'Windows';
        $nullDevice = $isWindows ? '2>nul' : '2>/dev/null';
        
        if (strpos($command, '2>') === false) {
            $command .= ' ' . $nullDevice;
        }
        
        return shell_exec($command);
    }
    
    /**
     * Проверить существование команды
     */
    protected function commandExists(string $command): bool
    {
        return PHP_OS_FAMILY === 'Windows' 
            ? $this->executeCommand("where {$command}") !== null
            : $this->executeCommand("which {$command}") !== null;
    }
    
    /**
     * Форматировать размер файла
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}