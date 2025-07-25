<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MassageCategorySeeder::class, // Добавьте эту строку ПЕРЕД MasterSeeder
            ExtendedServiceCategorySeeder::class, // Расширенные категории услуг
            MasterSeeder::class,
        ]);
    }
}