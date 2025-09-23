<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsersSeeder::class,
            // AdsSeeder::class, // Закомментировано - использует старую структуру
            // MassageCategorySeeder::class, // Закомментировано - таблица не существует
            // MasterSeeder::class, // Закомментировано - не нужен для тестирования модерации
            GenerateAdsSeeder::class, // Новый сидер для генерации объявлений
        ]);
    }
}