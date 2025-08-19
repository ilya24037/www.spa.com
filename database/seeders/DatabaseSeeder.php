<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsersSeeder::class,
            AdsSeeder::class,
            MassageCategorySeeder::class, // Добавьте эту строку ПЕРЕД MasterSeeder
            MasterSeeder::class,
        ]);
    }
}