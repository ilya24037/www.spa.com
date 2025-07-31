<?php

namespace App\Services\AiContext\Analyzers;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

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
     * Выполнить команду shell безопасно
     */
    protected function executeCommand(string $command): ?string
    {
        try {
            // Разбиваем команду на части для безопасности
            $commandParts = $this->parseCommand($command);
            
            // Создаем процесс с валидацией
            $process = new Process($commandParts);
            $process->setTimeout(30); // Таймаут 30 секунд
            $process->run();
            
            if (!$process->isSuccessful()) {
                return null;
            }
            
            return $process->getOutput();
            
        } catch (ProcessFailedException $exception) {
            // Логируем ошибку, но не выбрасываем исключение
            \Log::warning('Command execution failed', [
                'command' => $command,
                'error' => $exception->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Безопасный парсинг команды
     */
    private function parseCommand(string $command): array
    {
        // Удаляем перенаправления для безопасности
        $command = preg_replace('/\s*2>.*/', '', $command);
        
        // Список разрешенных команд
        $allowedCommands = [
            'git', 'find', 'wc', 'grep', 'ls', 'dir', 'where', 'which', 
            'php', 'composer', 'npm', 'node', 'cat', 'type'
        ];
        
        // Разбиваем команду
        $parts = explode(' ', trim($command));
        $baseCommand = $parts[0];
        
        // Проверяем разрешенные команды
        if (!in_array($baseCommand, $allowedCommands)) {
            throw new \InvalidArgumentException("Command not allowed: {$baseCommand}");
        }
        
        return $parts;
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