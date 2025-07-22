<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdPlan;

class AdPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AdPlan::create([
    'name' => '7 дней',
    'days' => 7,
    'price' => 1235,
    'description' => 'Если нужно быстро привлечь внимание к своему объявлению',
    'is_popular' => false,
    'is_active' => true,
    'sort_order' => 1,
]);

AdPlan::create([
    'name' => '14 дней',
    'days' => 14,
    'price' => 253,
    'description' => 'Когда нужно чуть больше времени',
    'is_popular' => false,
    'is_active' => true,
    'sort_order' => 2,
]);

AdPlan::create([
    'name' => '30 дней',
    'days' => 30,
    'price' => 389,
    'description' => 'Самый популярный вариант',
    'is_popular' => true,
    'is_active' => true,
    'sort_order' => 3,
]);
    }
}