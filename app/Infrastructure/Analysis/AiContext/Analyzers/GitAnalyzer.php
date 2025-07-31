<?php

namespace App\Infrastructure\Analysis\AiContext\Analyzers;

class GitAnalyzer extends BaseAnalyzer
{
    /**
     * Анализировать Git статус и историю
     */
    public function analyze(): array
    {
        $this->clearOutput();
        
        if (!$this->commandExists('git')) {
            return $this->getOutput();
        }
        
        $this->addSection('🔥 НАД ЧЕМ РАБОТАЛИ ПОСЛЕДНИЙ РАЗ');
        
        // Последние коммиты
        $recentCommits = $this->getRecentCommits(5);
        if (!empty($recentCommits)) {
            $this->addLine('**Последние коммиты:**');
            foreach ($recentCommits as $commit) {
                $this->addLine("- {$commit}");
            }
            $this->addLine();
        }
        
        // Незакоммиченные изменения
        $uncommitted = $this->getUncommittedChanges();
        if (!empty($uncommitted)) {
            $this->addLine("**⚠️ Незакоммиченные изменения:** " . count($uncommitted) . " файлов");
            foreach (array_slice($uncommitted, 0, 5) as $file) {
                $this->addLine("- {$file}");
            }
            if (count($uncommitted) > 5) {
                $this->addLine("... и ещё " . (count($uncommitted) - 5) . " файлов");
            }
            $this->addLine();
        }
        
        // Текущая ветка
        $branch = $this->getCurrentBranch();
        if ($branch) {
            $this->addLine("**🌿 Текущая ветка:** {$branch}");
            $this->addLine();
        }
        
        return $this->getOutput();
    }
    
    /**
     * Получить последние коммиты
     */
    private function getRecentCommits(int $limit = 5): array
    {
        $output = $this->executeCommand("git log --oneline -n {$limit}");
        if (!$output) return [];
        
        $commits = [];
        foreach (explode("\n", trim($output)) as $line) {
            if (!empty($line)) {
                $commits[] = $line;
            }
        }
        
        return $commits;
    }
    
    /**
     * Получить незакоммиченные изменения
     */
    private function getUncommittedChanges(): array
    {
        $output = $this->executeCommand('git status --porcelain');
        if (!$output || !trim($output)) return [];
        
        $files = [];
        foreach (explode("\n", trim($output)) as $line) {
            if (!empty($line)) {
                // Извлекаем имя файла из строки статуса
                $parts = explode(' ', $line, 2);
                if (isset($parts[1])) {
                    $files[] = trim($parts[1]);
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Получить текущую ветку
     */
    private function getCurrentBranch(): ?string
    {
        $branch = $this->executeCommand('git branch --show-current');
        return $branch ? trim($branch) : null;
    }
    
    /**
     * Определить фокус работы по последним изменениям
     */
    public function detectWorkFocus(array $recentFiles): ?string
    {
        $patterns = [
            'Vue компонентами и UI' => ['*.vue', 'Components/', 'Pages/'],
            'Backend логикой' => ['Controller.php', 'Service.php', 'Model.php'],
            'Миграциями БД' => ['migration', 'database/'],
            'API endpoints' => ['api.php', 'routes/', 'ApiController'],
            'Тестированием' => ['Test.php', 'tests/'],
            'Конфигурацией' => ['config/', '.env', 'composer.json', 'package.json']
        ];
        
        $scores = [];
        foreach ($patterns as $focus => $keywords) {
            $score = 0;
            foreach ($recentFiles as $file) {
                foreach ($keywords as $keyword) {
                    if (str_contains($file['path'], $keyword)) {
                        $score++;
                    }
                }
            }
            if ($score > 0) {
                $scores[$focus] = $score;
            }
        }
        
        if (empty($scores)) return null;
        
        arsort($scores);
        return array_key_first($scores);
    }
}