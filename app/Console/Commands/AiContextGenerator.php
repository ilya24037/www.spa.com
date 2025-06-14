<?php
// app/Console/Commands/AiContextGenerator.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AiContextGenerator extends Command
{
    protected $signature = 'ai:context 
                            {--quick : Быстрый режим - только основное}
                            {--full : Полный дамп проекта}
                            {--module= : Контекст конкретного модуля}
                            {--format=markdown : Формат вывода (markdown/json)}';
    
    protected $description = 'Генерирует контекст проекта для ИИ помощника';

    private $output_lines = [];
    private $important_dirs = [
        'app/Models',
        'app/Http/Controllers', 
        'database/migrations',
        'resources/js/Pages',
        'resources/js/Components',
        'routes'
    ];
    
    private $config_files = [
        'composer.json',
        'package.json',
        'vite.config.js',
        'tailwind.config.js'
    ];

    public function handle()
    {
        $this->info('🤖 Генерация контекста для ИИ помощника...');
        
        // Определяем режим работы
        $mode = $this->option('quick') ? 'quick' : ($this->option('full') ? 'full' : 'normal');
        
        // Начинаем сборку контекста
        $this->addHeader();
        $this->addCurrentFocus();
        $this->addProjectStatus();
        $this->addProjectStructure($mode);
        $this->addRecentChanges();
        $this->addTodoAndIssues();
        
        if ($mode !== 'quick') {
            $this->addDatabaseState();
            $this->addRoutes();
            $this->addKeyCode();
        }
        
        $this->addNextSteps();
        $this->addFooter();
        
        // Сохраняем результат
        $this->saveContext();
        
        $this->info('✅ Контекст успешно сгенерирован!');
    }
    
    private function addHeader()
    {
        $this->output_lines[] = "# 🤖 AI Context: SPA Platform - Платформа услуг массажа";
        $this->output_lines[] = "Дата генерации: " . now()->format('Y-m-d H:i:s');
        $this->output_lines[] = "";
        $this->output_lines[] = "## 📋 Технический стек";
        $this->output_lines[] = "- Backend: Laravel 12 (PHP 8.2+)";
        $this->output_lines[] = "- Frontend: Vue.js 3 + Inertia.js";
        $this->output_lines[] = "- State: Pinia";
        $this->output_lines[] = "- Стили: Tailwind CSS";
        $this->output_lines[] = "- БД: SQLite";
        $this->output_lines[] = "";
    }
    
    private function addCurrentFocus()
    {
        $this->output_lines[] = "## 🎯 Текущий фокус работы";
        
        // Читаем последний коммит
        $lastCommit = shell_exec('git log -1 --pretty=format:"%s"') ?: 'Нет коммитов';
        $lastFiles = shell_exec('git diff --name-only HEAD~1..HEAD 2>/dev/null') ?: '';
        
        $this->output_lines[] = "**Последняя работа:** " . $lastCommit;
        
        if ($lastFiles) {
            $this->output_lines[] = "**Изменённые файлы:**";
            foreach (explode("\n", trim($lastFiles)) as $file) {
                if ($file) {
                    $this->output_lines[] = "- " . $file;
                }
            }
        }
        
        // Проверяем наличие незакоммиченных изменений
        $uncommitted = shell_exec('git status --porcelain');
        if ($uncommitted) {
            $this->output_lines[] = "";
            $this->output_lines[] = "**⚠️ Есть незакоммиченные изменения!**";
        }
        
        $this->output_lines[] = "";
    }
    
    private function addProjectStatus()
    {
        $this->output_lines[] = "## 📊 Состояние проекта";
        
        // Загружаем статус из конфига если есть
        $statusFile = config_path('project-status.json');
        if (File::exists($statusFile)) {
            $status = json_decode(File::get($statusFile), true);
            
            // Считаем общий прогресс
            $totalTasks = 0;
            $completedTasks = 0;
            
            foreach ($status['modules'] as $module) {
                foreach ($module['tasks'] as $task) {
                    $totalTasks++;
                    if ($task['done']) {
                        $completedTasks++;
                    }
                }
            }
            
            $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
            $this->output_lines[] = "**Общий прогресс: {$progress}% ({$completedTasks}/{$totalTasks} задач)**";
            $this->output_lines[] = "";
            
            // Модули
            foreach ($status['modules'] as $moduleKey => $module) {
                $moduleCompleted = 0;
                $moduleTasks = count($module['tasks']);
                
                foreach ($module['tasks'] as $task) {
                    if ($task['done']) {
                        $moduleCompleted++;
                    }
                }
                
                $moduleProgress = round(($moduleCompleted / $moduleTasks) * 100);
                $this->output_lines[] = "### {$module['name']} ({$moduleProgress}%)";
                
                foreach ($module['tasks'] as $task) {
                    $status = $task['done'] ? '✅' : '❌';
                    $progress = isset($task['progress']) ? " ({$task['progress']}%)" : '';
                    $this->output_lines[] = "- {$status} {$task['name']}{$progress}";
                }
                $this->output_lines[] = "";
            }
        } else {
            $this->output_lines[] = "⚠️ Файл статуса проекта не найден";
            $this->output_lines[] = "";
        }
    }
    
    private function addProjectStructure($mode)
    {
        $this->output_lines[] = "## 📁 Структура проекта";
        
        if ($mode === 'quick') {
            // В быстром режиме показываем только основные папки
            $this->output_lines[] = "```";
            $this->output_lines[] = "spa-platform/";
            foreach ($this->important_dirs as $dir) {
                if (File::exists(base_path($dir))) {
                    $count = count(File::allFiles(base_path($dir)));
                    $this->output_lines[] = "├── {$dir}/ ({$count} файлов)";
                }
            }
            $this->output_lines[] = "```";
        } else {
            // Полная структура с файлами
            $this->output_lines[] = "```";
            $this->addDirectoryTree('', 0, $mode === 'full' ? 3 : 2);
            $this->output_lines[] = "```";
        }
        
        $this->output_lines[] = "";
    }
    
    private function addDirectoryTree($path, $level, $maxLevel)
    {
        if ($level > $maxLevel) return;
        
        $basePath = base_path($path);
        $items = File::exists($basePath) ? scandir($basePath) : [];
        
        $prefix = str_repeat('│   ', $level);
        
        foreach ($items as $item) {
            if (in_array($item, ['.', '..', 'node_modules', 'vendor', '.git', 'storage', 'bootstrap'])) {
                continue;
            }
            
            $itemPath = $path ? $path . '/' . $item : $item;
            $fullPath = base_path($itemPath);
            
            if (is_dir($fullPath)) {
                $this->output_lines[] = $prefix . "├── {$item}/";
                if ($level < $maxLevel) {
                    $this->addDirectoryTree($itemPath, $level + 1, $maxLevel);
                }
            } else {
                // Показываем только важные файлы
                if ($this->isImportantFile($item)) {
                    $size = $this->formatFileSize(filesize($fullPath));
                    $this->output_lines[] = $prefix . "├── {$item} ({$size})";
                }
            }
        }
    }
    
    private function isImportantFile($filename)
    {
        $extensions = ['php', 'vue', 'js', 'json', 'md'];
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        return in_array($extension, $extensions);
    }
    
    private function formatFileSize($bytes)
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
    
    private function addRecentChanges()
    {
        $this->output_lines[] = "## 💻 Последние изменения (10 коммитов)";
        $this->output_lines[] = "```";
        
        $commits = shell_exec('git log --oneline -10');
        if ($commits) {
            $this->output_lines[] = trim($commits);
        } else {
            $this->output_lines[] = "Нет истории коммитов";
        }
        
        $this->output_lines[] = "```";
        $this->output_lines[] = "";
    }
    
    private function addTodoAndIssues()
    {
        $this->output_lines[] = "## ⚠️ TODO и проблемы";
        
        // Ищем TODO в коде
        $todos = [];
        foreach ($this->important_dirs as $dir) {
            if (File::exists(base_path($dir))) {
                $files = File::allFiles(base_path($dir));
                foreach ($files as $file) {
                    $content = File::get($file);
                    preg_match_all('/\/\/\s*TODO:?\s*(.+)/i', $content, $matches);
                    foreach ($matches[1] as $todo) {
                        $todos[] = "- " . trim($todo) . " (`{$file->getRelativePathname()}`)";
                    }
                }
            }
        }
        
        if (count($todos) > 0) {
            $this->output_lines[] = "### Найдено TODO в коде:";
            foreach (array_slice($todos, 0, 10) as $todo) {
                $this->output_lines[] = $todo;
            }
            if (count($todos) > 10) {
                $this->output_lines[] = "... и ещё " . (count($todos) - 10) . " TODO";
            }
        } else {
            $this->output_lines[] = "TODO не найдено в коде";
        }
        
        $this->output_lines[] = "";
    }
    
    private function addDatabaseState()
{
    $this->output_lines[] = "## 🗄️ Состояние базы данных";
    
    // Список миграций
    $migrations = File::files(database_path('migrations'));
    $this->output_lines[] = "**Миграций:** " . count($migrations);
    
    // Проверяем выполненные миграции (кроссплатформенно)
    try {
        $output = shell_exec('php artisan migrate:status');
        if ($output) {
            // Считаем строки с "Pending" 
            $pending = substr_count($output, 'Pending');
            
            if ($pending > 0) {
                $this->output_lines[] = "**⚠️ Невыполненных миграций:** " . $pending;
            } else {
                $this->output_lines[] = "**✅ Все миграции выполнены**";
            }
            
            // Считаем выполненные миграции
            $ran = substr_count($output, 'Ran');
            if ($ran > 0) {
                $this->output_lines[] = "**Выполнено миграций:** " . $ran;
            }
        } else {
            $this->output_lines[] = "**Не удалось проверить статус миграций**";
        }
    } catch (\Exception $e) {
        $this->output_lines[] = "**Ошибка при проверке миграций:** " . $e->getMessage();
    }
    
    // Проверяем таблицы в БД
    try {
        $tables = \DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        $this->output_lines[] = "**Таблиц в БД:** " . count($tables);
    } catch (\Exception $e) {
        // Игнорируем если БД недоступна
    }
    
    $this->output_lines[] = "";
}
    
    private function addRoutes()
    {
        $this->output_lines[] = "## 🛣️ Основные маршруты";
        $this->output_lines[] = "```";
        
        // Читаем web.php
        $webRoutes = File::get(base_path('routes/web.php'));
        preg_match_all('/Route::(get|post|put|delete)\([\'"](.+?)[\'"]/', $webRoutes, $matches);
        
        $routes = [];
        for ($i = 0; $i < count($matches[0]); $i++) {
            $routes[] = strtoupper($matches[1][$i]) . " " . $matches[2][$i];
        }
        
        foreach (array_slice($routes, 0, 15) as $route) {
            $this->output_lines[] = $route;
        }
        
        if (count($routes) > 15) {
            $this->output_lines[] = "... и ещё " . (count($routes) - 15) . " маршрутов";
        }
        
        $this->output_lines[] = "```";
        $this->output_lines[] = "";
    }
    
    private function addKeyCode()
    {
        $this->output_lines[] = "## 🔧 Ключевые участки кода";
        
        // Показываем важные файлы с кратким содержимым
        $keyFiles = [
            'app/Models/User.php' => 'Модель пользователя',
            'app/Models/MasterProfile.php' => 'Профиль мастера',
            'app/Http/Controllers/HomeController.php' => 'Главный контроллер',
            'resources/js/Pages/Home.vue' => 'Главная страница'
        ];
        
        foreach ($keyFiles as $file => $description) {
            if (File::exists(base_path($file))) {
                $this->output_lines[] = "### {$description} (`{$file}`)";
                $this->output_lines[] = "";
                
                // Показываем структуру файла
                $content = File::get(base_path($file));
                
                if (Str::endsWith($file, '.php')) {
                    // Для PHP показываем методы
                    preg_match_all('/public function (\w+)/', $content, $methods);
                    if ($methods[1]) {
                        $this->output_lines[] = "**Методы:** " . implode(', ', $methods[1]);
                    }
                } elseif (Str::endsWith($file, '.vue')) {
                    // Для Vue показываем основные блоки
                    $hasTemplate = strpos($content, '<template>') !== false;
                    $hasScript = strpos($content, '<script') !== false;
                    $hasStyle = strpos($content, '<style') !== false;
                    
                    $blocks = [];
                    if ($hasTemplate) $blocks[] = 'template';
                    if ($hasScript) $blocks[] = 'script';
                    if ($hasStyle) $blocks[] = 'style';
                    
                    $this->output_lines[] = "**Блоки:** " . implode(', ', $blocks);
                }
                
                $this->output_lines[] = "";
            }
        }
    }
    
    private function addNextSteps()
    {
        $this->output_lines[] = "## 🚀 Следующие шаги";
        
        $statusFile = config_path('project-status.json');
        if (File::exists($statusFile)) {
            $status = json_decode(File::get($statusFile), true);
            if (isset($status['next_steps'])) {
                foreach ($status['next_steps'] as $i => $step) {
                    $this->output_lines[] = ($i + 1) . ". " . $step;
                }
            }
        } else {
            $this->output_lines[] = "1. Завершить основные компоненты";
            $this->output_lines[] = "2. Реализовать систему поиска";
            $this->output_lines[] = "3. Добавить систему бронирования";
        }
        
        $this->output_lines[] = "";
    }
    
    private function addFooter()
    {
        $this->output_lines[] = "---";
        $this->output_lines[] = "";
        $this->output_lines[] = "## 📌 Инструкция для ИИ помощника";
        $this->output_lines[] = "";
        $this->output_lines[] = "Это контекст проекта платформы услуг массажа. Проект разрабатывается одним человеком с помощью ИИ.";
        $this->output_lines[] = "";
        $this->output_lines[] = "**Основные принципы работы:**";
        $this->output_lines[] = "1. Всегда предоставляй полный код файлов";
        $this->output_lines[] = "2. Объясняй каждый шаг для новичка";
        $this->output_lines[] = "3. Проверяй совместимость с текущим стеком";
        $this->output_lines[] = "4. Предлагай простые, но эффективные решения";
        $this->output_lines[] = "5. Учитывай, что это коммерческий проект";
        $this->output_lines[] = "";
        $this->output_lines[] = "**Формат работы:** После каждого файла жди подтверждения выполнения.";
    }
    
    private function saveContext()
    {
        // Создаём директорию если нет
        $dir = storage_path('ai-sessions');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        
        // Генерируем имя файла
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "context_{$timestamp}.md";
        $filepath = $dir . '/' . $filename;
        
        // Сохраняем
        File::put($filepath, implode("\n", $this->output_lines));
        
        // Также сохраняем как latest для быстрого доступа
        File::put($dir . '/latest_context.md', implode("\n", $this->output_lines));
        
        $this->info("📄 Контекст сохранён в: storage/ai-sessions/{$filename}");
        $this->info("📋 Быстрый доступ: storage/ai-sessions/latest_context.md");
    }
}