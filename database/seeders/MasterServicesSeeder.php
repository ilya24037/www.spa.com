<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Проверим есть ли мастера в таблице master_profiles
        $masters = DB::table('master_profiles')->limit(10)->get();
        
        if ($masters->isEmpty()) {
            $this->command->info('Мастера не найдены. Сначала запустите MasterSeeder.');
            return;
        }
        
        $this->command->info("Найдено мастеров: {$masters->count()}");
        
        // Базовые услуги массажа для каждого мастера
        $services = [
            [
                'name' => 'Классический массаж',
                'description' => 'Классический массаж всего тела для расслабления и восстановления',
                'price' => 2000,
                'duration_minutes' => 60,
                'category' => 'Классический',
                'features' => ['Расслабление', 'Восстановление', 'Снятие напряжения']
            ],
            [
                'name' => 'Расслабляющий массаж',
                'description' => 'Мягкий расслабляющий массаж для снятия стресса и усталости',
                'price' => 2500,
                'duration_minutes' => 90,
                'category' => 'Релакс',
                'features' => ['Снятие стресса', 'Расслабление', 'Улучшение сна']
            ],
            [
                'name' => 'Лечебный массаж',
                'description' => 'Лечебный массаж для устранения болей и восстановления мышц',
                'price' => 3000,
                'duration_minutes' => 60,
                'category' => 'Лечебный',
                'features' => ['Устранение болей', 'Восстановление мышц', 'Улучшение кровообращения']
            ],
            [
                'name' => 'Антицеллюлитный массаж',
                'description' => 'Специальный массаж для борьбы с целлюлитом и улучшения кожи',
                'price' => 3500,
                'duration_minutes' => 90,
                'category' => 'Косметический',
                'features' => ['Борьба с целлюлитом', 'Улучшение кожи', 'Лимфодренаж']
            ],
            [
                'name' => 'Лимфодренажный массаж',
                'description' => 'Мягкий массаж для улучшения лимфотока и вывода токсинов',
                'price' => 4000,
                'duration_minutes' => 120,
                'category' => 'Лимфодренаж',
                'features' => ['Улучшение лимфотока', 'Вывод токсинов', 'Снятие отеков']
            ],
        ];
        
        foreach ($masters as $master) {
            $this->command->info("Обрабатываем мастера: {$master->display_name}");
            
            foreach ($services as $index => $service) {
                // Проверим, нет ли уже такой услуги у мастера
                $exists = DB::table('master_services')
                    ->where('master_profile_id', $master->id)
                    ->where('name', $service['name'])
                    ->exists();
                    
                if (!$exists) {
                    DB::table('master_services')->insert([
                        'master_profile_id' => $master->id,
                        'name' => $service['name'],
                        'description' => $service['description'],
                        'price' => $service['price'],
                        'duration_minutes' => $service['duration_minutes'],
                        'category' => $service['category'],
                        'features' => json_encode($service['features']),
                        'is_active' => true,
                        'is_popular' => $index < 2, // Первые 2 услуги - популярные
                        'sort_order' => $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    $this->command->info("  ✅ Добавлена услуга: {$service['name']}");
                } else {
                    $this->command->info("  ⏭️ Услуга уже существует: {$service['name']}");
                }
            }
        }
        
        $this->command->info('✅ Все услуги мастеров успешно добавлены!');
    }
}