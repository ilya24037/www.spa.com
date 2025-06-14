<?php
// app/Console/Commands/AiContextGenerator.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiContextGenerator extends Command
{
    protected $signature = 'ai:context 
                            {--quick : Быстрый режим - только основное}
                            {--full : Полный дамп проекта}
                            {--module= : Контекст конкретного модуля}
                            {--format=markdown : Формат вывода (markdown/json)}
                            {--last-session : Показать информацию о последней сессии работы}';
    
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

    // Расширенный список для поиска проблем
    private $problem_patterns = [
        '/\/\/\s*TODO:?\s*(.+)/i' => 'TODO',
        '/\/\/\s*FIXME:?\s*(.+)/i' => 'FIXME',
        '/\/\/\s*HACK:?\s*(.+)/i' => 'HACK',
        '/\/\/\s*BUG:?\s*(.+)/i' => 'BUG',
        '/\/\/\s*XXX:?\s*(.+)/i' => 'XXX',
        '/console\.(log|error|warn)\(/i' => 'DEBUG',
        '/dd\(|dump\(/i' => 'DEBUG'
    ];

    public function handle()
    {
        $this->info('🤖 Генерация контекста для ИИ помощника...');
        
        // Определяем режим работы
        $mode = $this->option('quick') ? 'quick' : ($this->option('full') ? 'full' : 'normal');
        
        // Специальный режим для последней сессии
        if ($this->option('last-session')) {
            $this->showLastSession();
            return;
        }
        
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
            $this->addInstalledPackages();
            $this->addKeyCode();
            $this->addEnvironmentInfo();
        }
        
        if ($mode === 'full') {
            $this->addDetailedFileAnalysis();
            $this->addPerformanceMetrics();
        }
        
        $this->addNextSteps();
        $this->addSessionHistory();
        $this->addFooter();
        
        // Сохраняем результат
        $this->saveContext();
        
        $this->info('✅ Контекст успешно сгенерирован!');
        
        // Показываем статистику
        $this->showStats();
    }
    
    private function addHeader()
    {
        $this->output_lines[] = "# 🤖 AI Context: SPA Platform - Платформа услуг массажа";
        $this->output_lines[] = "Дата генерации: " . now()->format('Y-m-d H:i:s');
        $this->output_lines[] = "Версия Laravel: " . app()->version();
        $this->output_lines[] = "PHP: " . PHP_VERSION;
        $this->output_lines[] = "OS: " . PHP_OS_FAMILY;
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
        
        // Проверяем наличие Git (кроссплатформенно)
        $gitExists = $this->checkGitExists();
        
        if ($gitExists) {
            // Читаем последний коммит
            $lastCommit = $this->executeCommand('git log -1 --pretty=format:"%s"') ?: 'Нет коммитов';
            $lastCommitDate = $this->executeCommand('git log -1 --pretty=format:"%ad" --date=relative') ?: '';
            $lastFiles = $this->executeCommand('git diff --name-only HEAD~1..HEAD') ?: '';
            
            $this->output_lines[] = "**Последняя работа:** " . trim($lastCommit);
            if ($lastCommitDate) {
                $this->output_lines[] = "**Когда:** " . trim($lastCommitDate);
            }
            
            if ($lastFiles) {
                $this->output_lines[] = "**Изменённые файлы:**";
                foreach (explode("\n", trim($lastFiles)) as $file) {
                    if ($file) {
                        $this->output_lines[] = "- " . $file;
                    }
                }
            }
            
            // Проверяем наличие незакоммиченных изменений
            $uncommitted = $this->executeCommand('git status --porcelain');
            if ($uncommitted) {
                $this->output_lines[] = "";
                $this->output_lines[] = "**⚠️ Есть незакоммиченные изменения:**";
                $changes = explode("\n", trim($uncommitted));
                foreach (array_slice($changes, 0, 10) as $change) {
                    if ($change) {
                        $this->output_lines[] = "- " . $change;
                    }
                }
                if (count($changes) > 10) {
                    $this->output_lines[] = "... и ещё " . (count($changes) - 10) . " файлов";
                }
            }
            
            // Текущая ветка
            $branch = $this->executeCommand('git branch --show-current');
            if ($branch) {
                $this->output_lines[] = "**Ветка:** " . trim($branch);
            }
        } else {
            $this->output_lines[] = "**⚠️ Git не найден** - установите Git для отслеживания изменений";
            $this->output_lines[] = "Скачать: https://git-scm.com/downloads";
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
            $inProgressTasks = 0;
            
            foreach ($status['modules'] as $module) {
                foreach ($module['tasks'] as $task) {
                    $totalTasks++;
                    if ($task['done']) {
                        $completedTasks++;
                    } elseif (isset($task['progress']) && $task['progress'] > 0) {
                        $inProgressTasks++;
                    }
                }
            }
            
            $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
            $this->output_lines[] = "**Общий прогресс: {$progress}% ({$completedTasks}/{$totalTasks} задач)**";
            $this->output_lines[] = "**В работе:** {$inProgressTasks} задач";
            $this->output_lines[] = "";
            
            // Модули с прогресс-барами
            foreach ($status['modules'] as $moduleKey => $module) {
                $moduleCompleted = 0;
                $moduleTasks = count($module['tasks']);
                
                foreach ($module['tasks'] as $task) {
                    if ($task['done']) {
                        $moduleCompleted++;
                    }
                }
                
                $moduleProgress = round(($moduleCompleted / $moduleTasks) * 100);
                $progressBar = $this->createProgressBar($moduleProgress);
                
                $this->output_lines[] = "### {$module['name']} {$progressBar} {$moduleProgress}%";
                
                foreach ($module['tasks'] as $task) {
                    $status = $task['done'] ? '✅' : '❌';
                    $progress = isset($task['progress']) ? " ({$task['progress']}%)" : '';
                    $this->output_lines[] = "- {$status} {$task['name']}{$progress}";
                }
                $this->output_lines[] = "";
            }
        } else {
            $this->output_lines[] = "⚠️ Файл статуса проекта не найден";
            $this->output_lines[] = "Создайте `config/project-status.json` для отслеживания прогресса";
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
                    $size = $this->getDirectorySize(base_path($dir));
                    $this->output_lines[] = "├── {$dir}/ ({$count} файлов, {$size})";
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
        
        // Сортируем: сначала папки, потом файлы
        $dirs = [];
        $files = [];
        
        foreach ($items as $item) {
            if (in_array($item, ['.', '..', 'node_modules', 'vendor', '.git', 'storage', 'bootstrap', '.idea', '.vscode'])) {
                continue;
            }
            
            $itemPath = $path ? $path . '/' . $item : $item;
            $fullPath = base_path($itemPath);
            
            if (is_dir($fullPath)) {
                $dirs[] = $item;
            } else {
                if ($this->isImportantFile($item)) {
                    $files[] = $item;
                }
            }
        }
        
        // Выводим папки
        foreach ($dirs as $dir) {
            $itemPath = $path ? $path . '/' . $dir : $dir;
            $this->output_lines[] = $prefix . "├── {$dir}/";
            if ($level < $maxLevel) {
                $this->addDirectoryTree($itemPath, $level + 1, $maxLevel);
            }
        }
        
        // Выводим файлы
        foreach ($files as $file) {
            $itemPath = $path ? $path . '/' . $file : $file;
            $fullPath = base_path($itemPath);
            $size = $this->formatFileSize(filesize($fullPath));
            $this->output_lines[] = $prefix . "├── {$file} ({$size})";
        }
    }
    
    private function isImportantFile($filename)
    {
        $extensions = ['php', 'vue', 'js', 'json', 'md', 'ts', 'jsx', 'tsx'];
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        return in_array($extension, $extensions);
    }
    
    private function formatFileSize($bytes)
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
    
    private function getDirectorySize($path)
    {
        $size = 0;
        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }
        return $this->formatFileSize($size);
    }
    
    private function addRecentChanges()
    {
        $this->output_lines[] = "## 💻 Последние изменения (10 коммитов)";
        $this->output_lines[] = "```";
        
        if ($this->checkGitExists()) {
            $commits = $this->executeCommand('git log --oneline -10');
            if ($commits) {
                $this->output_lines[] = trim($commits);
            } else {
                $this->output_lines[] = "Нет истории коммитов";
            }
            
            // Добавляем статистику изменений
            $stats = $this->executeCommand('git log --shortstat -1');
            if ($stats && strpos($stats, 'changed') !== false) {
                $this->output_lines[] = "";
                $this->output_lines[] = "Последний коммит: " . trim(substr($stats, strpos($stats, 'changed')));
            }
        } else {
            $this->output_lines[] = "Git не установлен";
        }
        
        $this->output_lines[] = "```";
        $this->output_lines[] = "";
    }
    
    private function addTodoAndIssues()
    {
        $this->output_lines[] = "## ⚠️ TODO и проблемы";
        
        $allIssues = [];
        
        foreach ($this->important_dirs as $dir) {
            if (File::exists(base_path($dir))) {
                $files = File::allFiles(base_path($dir));
                foreach ($files as $file) {
                    if (!in_array($file->getExtension(), ['php', 'vue', 'js', 'ts'])) {
                        continue;
                    }
                    
                    $content = File::get($file);
                    $lines = explode("\n", $content);
                    
                    foreach ($this->problem_patterns as $pattern => $type) {
                        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
                        
                        foreach ($matches[0] as $index => $match) {
                            $lineNumber = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                            $message = isset($matches[1][$index]) ? trim($matches[1][$index][0]) : $type;
                            
                            $allIssues[] = [
                                'type' => $type,
                                'message' => $message,
                                'file' => $file->getRelativePathname(),
                                'line' => $lineNumber
                            ];
                        }
                    }
                }
            }
        }
        
        // Группируем по типам
        $groupedIssues = [];
        foreach ($allIssues as $issue) {
            $groupedIssues[$issue['type']][] = $issue;
        }
        
        if (count($allIssues) > 0) {
            foreach ($groupedIssues as $type => $issues) {
                $this->output_lines[] = "### {$type} ({" . count($issues) . "})";
                foreach (array_slice($issues, 0, 5) as $issue) {
                    $this->output_lines[] = "- {$issue['message']} (`{$issue['file']}:{$issue['line']}`)";
                }
                if (count($issues) > 5) {
                    $this->output_lines[] = "... и ещё " . (count($issues) - 5) . " {$type}";
                }
                $this->output_lines[] = "";
            }
        } else {
            $this->output_lines[] = "✅ Проблемы не найдены в коде";
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
            $output = $this->executeCommand('php artisan migrate:status');
            if ($output) {
                // Считаем строки с "Pending" и "Ran"
                $pending = substr_count($output, 'Pending');
                $ran = substr_count($output, 'Ran');
                
                if ($pending > 0) {
                    $this->output_lines[] = "**⚠️ Невыполненных миграций:** " . $pending;
                } else {
                    $this->output_lines[] = "**✅ Все миграции выполнены**";
                }
                
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
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $this->output_lines[] = "**Таблиц в БД:** " . count($tables);
            
            // Проверяем количество записей в основных таблицах
            $mainTables = ['users', 'master_profiles', 'services', 'bookings', 'reviews'];
            $hasData = false;
            
            foreach ($mainTables as $table) {
                try {
                    $count = DB::table($table)->count();
                    if ($count > 0) {
                        $this->output_lines[] = "- `{$table}`: {$count} записей";
                        $hasData = true;
                    }
                } catch (\Exception $e) {
                    // Таблица не существует
                }
            }
            
            if (!$hasData) {
                $this->output_lines[] = "**📌 База данных пуста** - запустите seeders для тестовых данных";
            }
        } catch (\Exception $e) {
            $this->output_lines[] = "**БД недоступна**";
        }
        
        $this->output_lines[] = "";
    }
    
    private function addRoutes()
    {
        $this->output_lines[] = "## 🛣️ Основные маршруты";
        $this->output_lines[] = "```";
        
        // Читаем web.php
        if (File::exists(base_path('routes/web.php'))) {
            $webRoutes = File::get(base_path('routes/web.php'));
            preg_match_all('/Route::(get|post|put|patch|delete)\([\'"](.+?)[\'"]/', $webRoutes, $matches);
            
            $routes = [];
            for ($i = 0; $i < count($matches[0]); $i++) {
                $method = strtoupper($matches[1][$i]);
                $path = $matches[2][$i];
                $routes[] = str_pad($method, 7) . " " . $path;
            }
            
            // Группируем по префиксам
            $groupedRoutes = [];
            foreach ($routes as $route) {
                $prefix = explode('/', trim(explode(' ', $route)[1], '/'))[0] ?: '/';
                $groupedRoutes[$prefix][] = $route;
            }
            
            $shown = 0;
            foreach ($groupedRoutes as $prefix => $prefixRoutes) {
                if ($shown >= 20) break;
                foreach ($prefixRoutes as $route) {
                    if ($shown >= 20) break;
                    $this->output_lines[] = $route;
                    $shown++;
                }
            }
            
            if (count($routes) > 20) {
                $this->output_lines[] = "... и ещё " . (count($routes) - 20) . " маршрутов";
            }
        }
        
        $this->output_lines[] = "```";
        $this->output_lines[] = "";
    }
    
    private function addInstalledPackages()
    {
        $this->output_lines[] = "## 📦 Установленные пакеты";
        
        // Composer пакеты
        if (File::exists(base_path('composer.json'))) {
            $composer = json_decode(File::get(base_path('composer.json')), true);
            
            $this->output_lines[] = "### Composer (основные)";
            $mainPackages = array_merge(
                $composer['require'] ?? [],
                ['laravel/framework', 'inertiajs/inertia-laravel', 'laravel/sanctum']
            );
            
            foreach ($composer['require'] ?? [] as $package => $version) {
                if (!Str::startsWith($package, 'php') && !Str::startsWith($package, 'ext-')) {
                    $this->output_lines[] = "- {$package}: {$version}";
                }
            }
        }
        
        // NPM пакеты
        if (File::exists(base_path('package.json'))) {
            $package = json_decode(File::get(base_path('package.json')), true);
            
            $this->output_lines[] = "";
            $this->output_lines[] = "### NPM (основные)";
            $mainNpmPackages = ['vue', '@inertiajs/vue3', 'pinia', 'tailwindcss'];
            
            foreach ($mainNpmPackages as $pkg) {
                if (isset($package['dependencies'][$pkg])) {
                    $this->output_lines[] = "- {$pkg}: {$package['dependencies'][$pkg]}";
                } elseif (isset($package['devDependencies'][$pkg])) {
                    $this->output_lines[] = "- {$pkg}: {$package['devDependencies'][$pkg]}";
                }
            }
        }
        
        $this->output_lines[] = "";
    }
    
    private function addKeyCode()
    {
        $this->output_lines[] = "## 🔧 Ключевые участки кода";
        
        // Показываем важные файлы с кратким содержимым
        $keyFiles = [
            'app/Models/User.php' => 'Модель пользователя',
            'app/Models/MasterProfile.php' => 'Профиль мастера',
            'app/Models/Service.php' => 'Модель услуги',
            'app/Http/Controllers/HomeController.php' => 'Главный контроллер',
            'app/Http/Controllers/MasterController.php' => 'Контроллер мастеров',
            'resources/js/Pages/Home.vue' => 'Главная страница',
            'resources/js/Components/Masters/MasterCard.vue' => 'Карточка мастера'
        ];
        
        foreach ($keyFiles as $file => $description) {
            if (File::exists(base_path($file))) {
                $this->output_lines[] = "### {$description} (`{$file}`)";
                $this->output_lines[] = "";
                
                // Показываем структуру файла
                $content = File::get(base_path($file));
                $lines = count(explode("\n", $content));
                $size = $this->formatFileSize(strlen($content));
                
                $this->output_lines[] = "**Размер:** {$size} ({$lines} строк)";
                
                if (Str::endsWith($file, '.php')) {
                    // Для PHP показываем методы и связи
                    preg_match_all('/public function (\w+)/', $content, $methods);
                    if ($methods[1]) {
                        $this->output_lines[] = "**Методы:** " . implode(', ', array_unique($methods[1]));
                    }
                    
                    // Проверяем связи Eloquent
                    preg_match_all('/function (\w+)\(\).*?(?:hasMany|hasOne|belongsTo|belongsToMany)/s', $content, $relations);
                    if ($relations[1]) {
                        $this->output_lines[] = "**Связи:** " . implode(', ', array_unique($relations[1]));
                    }
                } elseif (Str::endsWith($file, '.vue')) {
                    // Для Vue показываем структуру компонента
                    $hasTemplate = strpos($content, '<template>') !== false;
                    $hasScript = strpos($content, '<script') !== false;
                    $hasStyle = strpos($content, '<style') !== false;
                    
                    $blocks = [];
                    if ($hasTemplate) $blocks[] = 'template';
                    if ($hasScript) $blocks[] = 'script';
                    if ($hasStyle) $blocks[] = 'style';
                    
                    $this->output_lines[] = "**Блоки:** " . implode(', ', $blocks);
                    
                    // Проверяем используемые компоненты
                    preg_match_all('/import\s+(\w+)\s+from/', $content, $imports);
                    if ($imports[1]) {
                        $this->output_lines[] = "**Импорты:** " . implode(', ', array_slice($imports[1], 0, 5));
                    }
                }
                
                $this->output_lines[] = "";
            }
        }
    }
    
    private function addEnvironmentInfo()
    {
        $this->output_lines[] = "## 🔧 Окружение разработки";
        
        // PHP расширения
        $requiredExtensions = ['pdo', 'sqlite3', 'gd', 'mbstring', 'xml', 'curl', 'json'];
        $installedExtensions = [];
        $missingExtensions = [];
        
        foreach ($requiredExtensions as $ext) {
            if (extension_loaded($ext)) {
                $installedExtensions[] = $ext;
            } else {
                $missingExtensions[] = $ext;
            }
        }
        
        $this->output_lines[] = "**PHP расширения:** " . implode(', ', $installedExtensions);
        if (!empty($missingExtensions)) {
            $this->output_lines[] = "**⚠️ Отсутствуют:** " . implode(', ', $missingExtensions);
        }
        
        // Проверка Node.js
        $nodeVersion = $this->executeCommand('node -v');
        if ($nodeVersion) {
            $this->output_lines[] = "**Node.js:** " . trim($nodeVersion);
        }
        
        // Проверка NPM
        $npmVersion = $this->executeCommand('npm -v');
        if ($npmVersion) {
            $this->output_lines[] = "**NPM:** " . trim($npmVersion);
        }
        
        $this->output_lines[] = "";
    }
    
    private function addDetailedFileAnalysis()
    {
        $this->output_lines[] = "## 📊 Детальный анализ файлов";
        
        $totalLines = 0;
        $filesByType = [];
        
        foreach ($this->important_dirs as $dir) {
            if (File::exists(base_path($dir))) {
                $files = File::allFiles(base_path($dir));
                foreach ($files as $file) {
                    $ext = $file->getExtension();
                    if (!isset($filesByType[$ext])) {
                        $filesByType[$ext] = ['count' => 0, 'lines' => 0, 'size' => 0];
                    }
                    
                    $content = File::get($file);
                    $lines = count(explode("\n", $content));
                    
                    $filesByType[$ext]['count']++;
                    $filesByType[$ext]['lines'] += $lines;
                    $filesByType[$ext]['size'] += $file->getSize();
                    $totalLines += $lines;
                }
            }
        }
        
        $this->output_lines[] = "**Общее количество строк кода:** " . number_format($totalLines);
        $this->output_lines[] = "";
        $this->output_lines[] = "### Статистика по типам файлов";
        
        arsort($filesByType);
        foreach ($filesByType as $ext => $stats) {
            $avgLines = round($stats['lines'] / $stats['count']);
            $totalSize = $this->formatFileSize($stats['size']);
            $this->output_lines[] = "- **.{$ext}**: {$stats['count']} файлов, {$stats['lines']} строк (среднее: {$avgLines}), {$totalSize}";
        }
        
        $this->output_lines[] = "";
    }
    
    private function addPerformanceMetrics()
    {
        $this->output_lines[] = "## ⚡ Метрики производительности";
        
        // Размер проекта
        $projectSize = $this->getDirectorySizeRecursive(base_path());
        $this->output_lines[] = "**Размер проекта:** " . $this->formatFileSize($projectSize['size']);
        $this->output_lines[] = "**Всего файлов:** " . number_format($projectSize['files']);
        
        // Проверка оптимизации
        $optimizations = [];
        
        // Composer
        if (File::exists(base_path('vendor/composer/autoload_classmap.php'))) {
            $optimizations[] = "✅ Composer autoload оптимизирован";
        } else {
            $optimizations[] = "❌ Запустите `composer dump-autoload -o`";
        }
        
        // NPM production build
        if (File::exists(public_path('build/manifest.json'))) {
            $manifest = json_decode(File::get(public_path('build/manifest.json')), true);
            $optimizations[] = "✅ Production build создан (" . count($manifest) . " файлов)";
        } else {
            $optimizations[] = "❌ Запустите `npm run build`";
        }
        
        // Кэширование конфигов
        if (File::exists(base_path('bootstrap/cache/config.php'))) {
            $optimizations[] = "✅ Конфигурация закэширована";
        } else {
            $optimizations[] = "❌ Запустите `php artisan config:cache`";
        }
        
        $this->output_lines[] = "";
        $this->output_lines[] = "### Оптимизация";
        foreach ($optimizations as $opt) {
            $this->output_lines[] = $opt;
        }
        
        $this->output_lines[] = "";
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
            // Автоматические рекомендации на основе анализа
            $recommendations = [];
            
            // Проверяем что не завершено
            if (!File::exists(app_path('Models/Schedule.php'))) {
                $recommendations[] = "Создать модель Schedule для расписания мастеров";
            }
            
            if (!File::exists(app_path('Http/Controllers/ReviewController.php'))) {
                $recommendations[] = "Создать ReviewController для управления отзывами";
            }
            
            // Проверяем пустую БД
            try {
                if (DB::table('users')->count() == 0) {
                    $recommendations[] = "Создать Seeders для тестовых данных";
                }
            } catch (\Exception $e) {
                // БД недоступна
            }
            
            if (empty($recommendations)) {
                $recommendations = [
                    "Завершить основные компоненты",
                    "Реализовать систему поиска",
                    "Добавить систему бронирования"
                ];
            }
            
            foreach ($recommendations as $i => $rec) {
                $this->output_lines[] = ($i + 1) . ". " . $rec;
            }
        }
        
        $this->output_lines[] = "";
    }
    
    private function addSessionHistory()
    {
        $this->output_lines[] = "## 📅 История сессий";
        
        $sessionDir = storage_path('ai-sessions');
        if (File::exists($sessionDir)) {
            $files = File::files($sessionDir);
            $sessions = [];
            
            foreach ($files as $file) {
                if (Str::startsWith($file->getFilename(), 'context_') && Str::endsWith($file->getFilename(), '.md')) {
                    $sessions[] = [
                        'name' => $file->getFilename(),
                        'time' => $file->getMTime(),
                        'size' => $file->getSize()
                    ];
                }
            }
            
            // Сортируем по времени
            usort($sessions, function($a, $b) {
                return $b['time'] - $a['time'];
            });
            
            $this->output_lines[] = "**Последние сессии:**";
            foreach (array_slice($sessions, 0, 5) as $session) {
                $date = date('Y-m-d H:i', $session['time']);
                $size = $this->formatFileSize($session['size']);
                $this->output_lines[] = "- {$date} ({$size})";
            }
            
            $this->output_lines[] = "**Всего сессий:** " . count($sessions);
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
        $this->output_lines[] = "";
        $this->output_lines[] = "**Важно:** Учитывай операционную систему " . PHP_OS_FAMILY . " при командах терминала.";
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
        $content = implode("\n", $this->output_lines);
        File::put($filepath, $content);
        
        // Также сохраняем как latest для быстрого доступа
        File::put($dir . '/latest_context.md', $content);
        
        // Сохраняем метаданные
        $metadata = [
            'generated_at' => now()->toIso8601String(),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'os' => PHP_OS_FAMILY,
            'size' => strlen($content),
            'lines' => count($this->output_lines)
        ];
        
        File::put($dir . '/latest_meta.json', json_encode($metadata, JSON_PRETTY_PRINT));
        
        $this->info("📄 Контекст сохранён в: storage/ai-sessions/{$filename}");
        $this->info("📋 Быстрый доступ: storage/ai-sessions/latest_context.md");
    }
    
    private function showStats()
    {
        $this->info("");
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Строк в отчёте', count($this->output_lines)],
                ['Размер файла', $this->formatFileSize(strlen(implode("\n", $this->output_lines)))],
                ['Время генерации', round(microtime(true) - LARAVEL_START, 2) . ' сек'],
            ]
        );
    }
    
    private function showLastSession()
    {
        $metaFile = storage_path('ai-sessions/latest_meta.json');
        
        if (!File::exists($metaFile)) {
            $this->error('Нет сохранённых сессий');
            return;
        }
        
        $meta = json_decode(File::get($metaFile), true);
        
        $this->info('📊 Информация о последней сессии:');
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Создана', $meta['generated_at']],
                ['Laravel', $meta['laravel_version']],
                ['PHP', $meta['php_version']],
                ['ОС', $meta['os']],
                ['Размер', $this->formatFileSize($meta['size'])],
                ['Строк', $meta['lines']],
            ]
        );
    }
    
    // Вспомогательные методы
    
    private function checkGitExists()
    {
        return PHP_OS_FAMILY === 'Windows' 
            ? shell_exec('where git 2>nul') !== null
            : shell_exec('which git 2>/dev/null') !== null;
    }
    
   private function executeCommand($command)
    {
        $isWindows = PHP_OS_FAMILY === 'Windows';
        $nullDevice = $isWindows ? '2>nul' : '2>/dev/null';
        
        // Добавляем перенаправление ошибок если его нет
        if (strpos($command, '2>') === false) {
            $command .= ' ' . $nullDevice;
        }
        
        return shell_exec($command);
    }
    
    private function createProgressBar($percentage)
    {
        $filled = round($percentage / 10);
        $empty = 10 - $filled;
        
        return '[' . str_repeat('█', $filled) . str_repeat('░', $empty) . ']';
    }
    
    private function getDirectorySizeRecursive($path)
    {
        $size = 0;
        $files = 0;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            // Пропускаем большие директории
            $pathname = $file->getPathname();
            if (Str::contains($pathname, ['vendor', 'node_modules', '.git', 'storage/framework'])) {
                continue;
            }
            
            if ($file->isFile()) {
                $size += $file->getSize();
                $files++;
            }
        }
        
        return ['size' => $size, 'files' => $files];
    }
}