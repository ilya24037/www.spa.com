<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ProjectStatus extends Command
{
    protected $signature = 'project:status 
                            {--update= : Обновить статус задачи}
                            {--check : Автоматически проверить файлы}
                            {--export : Экспортировать в файл}';
    
    protected $description = 'Показать статус проекта';

    private $configPath;
    private $config;
    
    // Правила для автоматической проверки
    private $autoCheckRules = [
        'models' => [
            'user' => 'app/Models/User.php',
            'master_profile' => 'app/Models/MasterProfile.php',
            'massage_category' => 'app/Models/MassageCategory.php',
            'service' => 'app/Models/Service.php',
            'booking' => 'app/Models/Booking.php',
            'review' => 'app/Models/Review.php',
        ],
        'controllers' => [
            'home' => 'app/Http/Controllers/HomeController.php',
            'master' => 'app/Http/Controllers/MasterController.php',
            'booking' => 'app/Http/Controllers/BookingController.php',
            'search' => 'app/Http/Controllers/SearchController.php',
        ],
        'database' => [
            'master_profiles' => 'database/migrations/*_create_master_profiles_table.php',
            'services' => 'database/migrations/*_create_services_table.php',
            'bookings' => 'database/migrations/*_create_bookings_table.php',
        ],
        'frontend' => [
            'home_page' => 'resources/js/Pages/Home.vue',
            'master_card' => 'resources/js/Components/Masters/MasterCard.vue',
            'booking_form' => 'resources/js/Components/Booking/BookingForm.vue',
            'calendar' => 'resources/js/Components/Booking/Calendar.vue',
        ]
    ];

    public function __construct()
    {
        parent::__construct();
        $this->configPath = config_path('project-status.json');
    }

    public function handle()
    {
        $this->loadConfig();

        // Автоматическая проверка
        if ($this->option('check')) {
            $this->autoCheckProgress();
            $this->info("✅ Прогресс обновлен автоматически!");
        }

        // Обновление статуса
        if ($task = $this->option('update')) {
            $this->updateTaskStatus($task);
            return;
        }

        // Генерация отчета
        $report = $this->generateReport();
        $this->info($report);

        // Экспорт
        if ($this->option('export')) {
            $filename = 'project-report-' . date('Y-m-d') . '.txt';
            File::put(storage_path('app/' . $filename), $report);
            $this->info("\n📄 Отчет сохранен: storage/app/{$filename}");
        }
    }

    private function autoCheckProgress()
    {
        foreach ($this->autoCheckRules as $module => $tasks) {
            foreach ($tasks as $task => $path) {
                if (!isset($this->config['modules'][$module]['tasks'][$task])) {
                    continue;
                }

                // Проверяем существование файла
                $exists = false;
                if (strpos($path, '*') !== false) {
                    // Для паттернов (миграции)
                    $files = glob(base_path($path));
                    $exists = !empty($files);
                } else {
                    // Для обычных файлов
                    $exists = File::exists(base_path($path));
                }

                // Обновляем статус
                $this->config['modules'][$module]['tasks'][$task]['done'] = $exists;
                
                // Автоматический прогресс для некоторых файлов
                if ($exists && $module === 'frontend') {
                    $progress = $this->calculateFileProgress(base_path($path));
                    $this->config['modules'][$module]['tasks'][$task]['progress'] = $progress;
                }
            }
        }

        // Сохраняем
        $this->saveConfig();
    }

    private function calculateFileProgress($filePath)
    {
        if (!File::exists($filePath)) {
            return 0;
        }

        $content = File::get($filePath);
        $progress = 0;

        // Анализируем Vue компоненты
        if (strpos($filePath, '.vue') !== false) {
            // Проверяем наличие основных блоков
            if (strpos($content, '<template>') !== false) $progress += 25;
            if (strpos($content, '<script') !== false) $progress += 25;
            if (strpos($content, 'defineProps') !== false) $progress += 15;
            if (strpos($content, '@click') !== false || strpos($content, 'v-model') !== false) $progress += 20;
            if (strpos($content, '<style') !== false) $progress += 15;
        }

        // Анализируем PHP контроллеры
        if (strpos($filePath, 'Controller.php') !== false) {
            if (strpos($content, 'public function index') !== false) $progress += 30;
            if (strpos($content, 'public function store') !== false) $progress += 25;
            if (strpos($content, 'public function show') !== false) $progress += 20;
            if (strpos($content, 'return Inertia::render') !== false) $progress += 25;
        }

        return min($progress, 100);
    }

    private function loadConfig()
    {
        if (!File::exists($this->configPath)) {
            $this->error('Файл конфигурации не найден! Создайте config/project-status.json');
            exit(1);
        }

        $this->config = json_decode(File::get($this->configPath), true);
    }

    private function saveConfig()
    {
        File::put($this->configPath, json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function updateTaskStatus($taskPath)
    {
        [$module, $task] = explode('.', $taskPath);
        
        if (!isset($this->config['modules'][$module]['tasks'][$task])) {
            $this->error("Задача {$taskPath} не найдена!");
            return;
        }

        // Переключаем статус
        $currentStatus = $this->config['modules'][$module]['tasks'][$task]['done'] ?? false;
        $this->config['modules'][$module]['tasks'][$task]['done'] = !$currentStatus;
        
        $this->saveConfig();
        
        $status = !$currentStatus ? '✅ выполнено' : '❌ не выполнено';
        $this->info("Статус обновлен: {$taskPath} - {$status}");
    }

    private function generateReport()
    {
        $output = [];
        
        // Заголовок
        $output[] = "📋 АВТОМАТИЧЕСКИЙ ОТЧЕТ О СОСТОЯНИИ ПРОЕКТА";
        $output[] = "Дата: " . date('Y-m-d H:i:s');
        $output[] = str_repeat('═', 50);
        
        // Технический стек
        $output[] = "\n🔧 Технический стек проекта:";
        foreach ($this->config['project']['stack'] as $key => $value) {
            $output[] = "• " . ucfirst($key) . ": {$value}";
        }
        
        // Прогресс по модулям
        $output[] = "\n📊 ПРОГРЕСС ПО МОДУЛЯМ:";
        $totalTasks = 0;
        $completedTasks = 0;
        
        foreach ($this->config['modules'] as $moduleKey => $module) {
            $moduleCompleted = 0;
            $moduleTasks = count($module['tasks']);
            
            foreach ($module['tasks'] as $task) {
                $totalTasks++;
                if ($task['done']) {
                    $moduleCompleted++;
                    $completedTasks++;
                }
            }
            
            $percentage = round(($moduleCompleted / $moduleTasks) * 100);
            $bar = $this->generateProgressBar($percentage);
            
            $output[] = "\n{$module['name']}: {$bar} {$percentage}%";
            
            // Детали
            foreach ($module['tasks'] as $taskKey => $task) {
                $status = $task['done'] ? '✅' : '❌';
                $progress = isset($task['progress']) ? " ({$task['progress']}%)" : '';
                $output[] = "  {$status} {$task['name']}{$progress}";
            }
        }
        
        // Общая статистика
        $overallProgress = round(($completedTasks / $totalTasks) * 100);
        $output[] = "\n" . str_repeat('─', 50);
        $output[] = "📈 ОБЩИЙ ПРОГРЕСС: {$overallProgress}% ({$completedTasks}/{$totalTasks} задач)";
        $output[] = $this->generateProgressBar($overallProgress);
        
        // Статистика файлов
        $output[] = "\n📁 СТАТИСТИКА ФАЙЛОВ:";
        $output[] = "• Миграций: " . count(glob(database_path('migrations/*.php')));
        $output[] = "• Моделей: " . count(glob(app_path('Models/*.php')));
        $output[] = "• Контроллеров: " . count(glob(app_path('Http/Controllers/*.php')));
        $output[] = "• Vue компонентов: " . count(glob(resource_path('js/Components/**/*.vue')));
        $output[] = "• Страниц: " . count(glob(resource_path('js/Pages/**/*.vue')));
        
        // Следующие шаги
        $output[] = "\n💡 СЛЕДУЮЩИЕ ШАГИ:";
        foreach ($this->config['next_steps'] as $i => $step) {
            $output[] = ($i + 1) . ". {$step}";
        }
        
        // Команды
        $output[] = "\n🚀 КОМАНДЫ:";
        $output[] = "php artisan project:status --check     # Автопроверка файлов";
        $output[] = "php artisan project:status --export    # Экспорт отчета";
        $output[] = "php artisan project:status --update=module.task  # Вручную";
        
        return implode("\n", $output);
    }

    private function generateProgressBar($percentage)
    {
        $filled = round($percentage / 10);
        $empty = 10 - $filled;
        
        return '[' . str_repeat('█', $filled) . str_repeat('░', $empty) . ']';
    }
}