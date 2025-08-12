<?php

namespace App\Console\Commands;

use App\Models\MasterProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Команда для регенерации slug у существующих профилей мастеров
 * 
 * Использование:
 * php artisan masters:regenerate-slugs
 * php artisan masters:regenerate-slugs --dry-run
 * php artisan masters:regenerate-slugs --force
 */
class RegenerateMasterSlugs extends Command
{
    /**
     * Имя и сигнатура консольной команды
     */
    protected $signature = 'masters:regenerate-slugs 
                            {--dry-run : Показать что будет изменено без сохранения}
                            {--force : Принудительно обновить все slug}';

    /**
     * Описание команды
     */
    protected $description = 'Регенерирует slug для профилей мастеров (убирает случайные символы)';

    /**
     * Выполнение команды
     */
    public function handle()
    {
        $this->info('🔄 Начинаем регенерацию slug для профилей мастеров...');
        
        // Получаем все профили
        $query = MasterProfile::query();
        
        // Если не указан --force, обрабатываем только slug со случайными символами
        if (!$this->option('force')) {
            // Ищем slug с паттерном типа "anna-x7k9m2" (имя + дефис + 6 случайных символов)
            $query->where('slug', 'REGEXP', '-[a-z0-9]{6}$');
        }
        
        $profiles = $query->get();
        
        if ($profiles->isEmpty()) {
            $this->info('✅ Нет профилей для обновления.');
            return Command::SUCCESS;
        }
        
        $this->info("📊 Найдено профилей для обработки: {$profiles->count()}");
        $this->newLine();
        
        // Таблица для отображения изменений
        $changes = [];
        
        // Начинаем транзакцию если не dry-run
        if (!$this->option('dry-run')) {
            DB::beginTransaction();
        }
        
        try {
            foreach ($profiles as $profile) {
                $oldSlug = $profile->slug;
                
                // Генерируем новый slug
                $newSlug = $profile->generateUniqueSlug($profile->display_name);
                
                // Добавляем в таблицу изменений
                $changes[] = [
                    'ID' => $profile->id,
                    'Имя' => $profile->display_name,
                    'Старый slug' => $oldSlug,
                    'Новый slug' => $newSlug,
                    'Статус' => $oldSlug === $newSlug ? '⏭️ Без изменений' : '✅ Обновлён'
                ];
                
                // Если не dry-run и slug изменился - сохраняем
                if (!$this->option('dry-run') && $oldSlug !== $newSlug) {
                    $profile->slug = $newSlug;
                    $profile->saveQuietly(); // Сохраняем без вызова событий
                }
            }
            
            // Показываем таблицу изменений
            $this->table(
                ['ID', 'Имя', 'Старый slug', 'Новый slug', 'Статус'],
                $changes
            );
            
            // Подсчитываем статистику
            $updated = collect($changes)->where('Статус', '✅ Обновлён')->count();
            $skipped = collect($changes)->where('Статус', '⏭️ Без изменений')->count();
            
            $this->newLine();
            $this->info("📈 Статистика:");
            $this->info("   - Обновлено: {$updated}");
            $this->info("   - Пропущено: {$skipped}");
            
            if ($this->option('dry-run')) {
                $this->newLine();
                $this->warn('⚠️  Это был тестовый прогон (--dry-run).');
                $this->warn('   Никакие изменения не были сохранены в базу данных.');
                $this->info('   Для реального обновления запустите команду без --dry-run');
            } else {
                // Коммитим транзакцию
                DB::commit();
                $this->newLine();
                $this->info('✅ Все изменения успешно сохранены в базу данных!');
            }
            
            // Проверяем на дубликаты
            $this->checkForDuplicates();
            
        } catch (\Exception $e) {
            // Откатываем транзакцию при ошибке
            if (!$this->option('dry-run')) {
                DB::rollBack();
            }
            
            $this->error('❌ Произошла ошибка: ' . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Проверка на дубликаты slug после регенерации
     */
    private function checkForDuplicates()
    {
        $duplicates = MasterProfile::select('slug', DB::raw('COUNT(*) as count'))
            ->groupBy('slug')
            ->having('count', '>', 1)
            ->get();
        
        if ($duplicates->isNotEmpty()) {
            $this->newLine();
            $this->warn('⚠️  Обнаружены дубликаты slug:');
            foreach ($duplicates as $duplicate) {
                $this->warn("   - {$duplicate->slug} ({$duplicate->count} записей)");
            }
            $this->info('   Система автоматически добавит числовые суффиксы при следующем обращении.');
        }
    }
}