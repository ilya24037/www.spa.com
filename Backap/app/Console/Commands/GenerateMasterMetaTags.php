<?php

namespace App\Console\Commands;

use App\Models\MasterProfile;
use Illuminate\Console\Command;

class GenerateMasterMetaTags extends Command
{
    /**
     * Имя и сигнатура команды
     */
    protected $signature = 'masters:generate-meta 
                            {--force : Перезаписать существующие meta-теги}
                            {--dry-run : Показать что будет сделано без сохранения}';

    /**
     * Описание команды
     */
    protected $description = 'Генерирует SEO meta-теги для профилей мастеров';

    /**
     * Выполнение команды
     */
    public function handle()
    {
        $this->info('🔍 Генерация SEO meta-тегов для профилей мастеров...');
        
        // Подготавливаем запрос
        $query = MasterProfile::query();
        
        // Если не указан --force, обрабатываем только профили без meta-тегов
        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('meta_title')
                  ->orWhereNull('meta_description')
                  ->orWhere('meta_title', '')
                  ->orWhere('meta_description', '');
            });
        }
        
        $profiles = $query->with(['services'])->get();
        
        if ($profiles->isEmpty()) {
            $this->info('✅ Все профили уже имеют meta-теги!');
            return Command::SUCCESS;
        }
        
        $this->info("📊 Найдено профилей для обработки: {$profiles->count()}");
        $this->newLine();
        
        // Прогресс-бар для красоты
        $bar = $this->output->createProgressBar($profiles->count());
        $bar->start();
        
        $updated = 0;
        $examples = []; // Для показа примеров
        
        foreach ($profiles as $profile) {
            $oldTitle = $profile->meta_title;
            $oldDescription = $profile->meta_description;
            
            // Генерируем meta-теги
            $profile->generateMetaTags();
            
            // Сохраняем первые 3 примера для показа
            if (count($examples) < 3) {
                $examples[] = [
                    'name' => $profile->display_name,
                    'title' => $profile->meta_title,
                    'description' => mb_substr($profile->meta_description, 0, 80) . '...',
                ];
            }
            
            // Если не dry-run - сохраняем
            if (!$this->option('dry-run')) {
                $profile->save();
                $updated++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // Показываем примеры сгенерированных тегов
        if (!empty($examples)) {
            $this->info('📝 Примеры сгенерированных meta-тегов:');
            $this->newLine();
            
            foreach ($examples as $i => $example) {
                $this->line("  <fg=cyan>Профиль:</> {$example['name']}");
                $this->line("  <fg=yellow>Title:</> {$example['title']}");
                $this->line("  <fg=yellow>Description:</> {$example['description']}");
                if ($i < count($examples) - 1) {
                    $this->line("  " . str_repeat('-', 70));
                }
            }
        }
        
        $this->newLine();
        
        if ($this->option('dry-run')) {
            $this->warn('⚠️  Это был тестовый прогон (--dry-run)');
            $this->warn('   Meta-теги НЕ были сохранены в базу данных');
            $this->info('   Запустите команду без --dry-run для сохранения');
        } else {
            $this->info("✅ Успешно обновлено профилей: {$updated}");
            
            // Проверяем заполненность
            $filled = MasterProfile::whereNotNull('meta_title')
                ->whereNotNull('meta_description')
                ->where('meta_title', '!=', '')
                ->where('meta_description', '!=', '')
                ->count();
                
            $total = MasterProfile::count();
            $percent = $total > 0 ? round(($filled / $total) * 100) : 0;
            
            $this->info("📈 Статистика: {$filled} из {$total} профилей имеют meta-теги ({$percent}%)");
        }
        
        return Command::SUCCESS;
    }
}