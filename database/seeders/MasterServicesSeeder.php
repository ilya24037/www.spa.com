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
            // Создаём тестового мастера если его нет
            $masterId = DB::table('master_profiles')->insertGetId([
                'display_name' => 'Тестовый мастер массажа',
                'description' => 'Профессиональный массажист с опытом работы',
                'price_from' => 2000,
                'rating' => 4.8,
                'reviews_count' => 15,
                'district' => 'Центральный',
                'metro' => 'Пушкинская',
                'experience_years' => 5,
                'is_verified' => true,
                'is_premium' => false,
                'is_online' => true,
                'folder_name' => 'test_master',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $masters = collect([['id' => $masterId]]);
        }
        
        // Базовые услуги массажа для каждого мастера
        $services = [
            ['name' => 'Классический массаж', 'price' => 2000, 'duration' => 60],
            ['name' => 'Расслабляющий массаж', 'price' => 2500, 'duration' => 90],
            ['name' => 'Лечебный массаж', 'price' => 3000, 'duration' => 60],
            ['name' => 'Антицеллюлитный массаж', 'price' => 3500, 'duration' => 90],
            ['name' => 'Лимфодренажный массаж', 'price' => 4000, 'duration' => 120],
        ];
        
        foreach ($masters as $master) {
            foreach ($services as $index => $service) {
                // Проверим, нет ли уже такой услуги у мастера
                $exists = DB::table('master_services')
                    ->where('master_profile_id', $master->id)
                    ->where('service_name', $service['name'])
                    ->exists();
                    
                if (!$exists) {
                    DB::table('master_services')->insert([
                        'master_profile_id' => $master->id,
                        'service_name' => $service['name'],
                        'service_description' => "Профессиональный {$service['name']} от опытного мастера",
                        'price' => $service['price'],
                        'duration_minutes' => $service['duration'],
                        'is_active' => true,
                        'is_featured' => $index < 2, // Первые 2 услуги - рекомендуемые
                        'sort_order' => $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
        
        $this->command->info('Master services seeded successfully!');
    }
}