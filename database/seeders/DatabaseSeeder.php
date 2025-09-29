<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class, // Создание администраторов и модераторов
            UsersSeeder::class, // Создание тестовых пользователей (мастеров)
            GenerateAdsSeeder::class, // Новый сидер для генерации объявлений
        ]);
    }
}