<?php

namespace Database\Seeders;

use App\Models\MassageCategory;
use Illuminate\Database\Seeder;

class MassageCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Классический массаж',
                'slug' => 'klassicheskiy',
                'description' => 'Традиционный расслабляющий массаж всего тела',
                'icon' => 'classic',
                'is_active' => true,
            ],
            [
                'name' => 'Спортивный массаж',
                'slug' => 'sportivnyy',
                'description' => 'Массаж для спортсменов и активных людей',
                'icon' => 'sport',
                'is_active' => true,
            ],
            [
                'name' => 'Тайский массаж',
                'slug' => 'tayskiy',
                'description' => 'Традиционный тайский массаж с элементами растяжки',
                'icon' => 'thai',
                'is_active' => true,
            ],
            [
                'name' => 'Лечебный массаж',
                'slug' => 'lechebnyy',
                'description' => 'Медицинский массаж для лечения и профилактики',
                'icon' => 'medical',
                'is_active' => true,
            ],
            [
                'name' => 'Антицеллюлитный',
                'slug' => 'antitsellyulitnyy',
                'description' => 'Массаж для коррекции фигуры',
                'icon' => 'anticellulite',
                'is_active' => true,
            ],
            [
                'name' => 'Расслабляющий',
                'slug' => 'rasslablyayushchiy',
                'description' => 'Нежный массаж для снятия стресса',
                'icon' => 'relax',
                'is_active' => true,
            ],
            [
                'name' => 'Массаж лица',
                'slug' => 'massazh-litsa',
                'description' => 'Косметический массаж лица и шеи',
                'icon' => 'face',
                'is_active' => true,
            ],
            [
                'name' => 'Лимфодренажный',
                'slug' => 'limfodrenazhnyy',
                'description' => 'Массаж для улучшения лимфотока',
                'icon' => 'lymph',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            MassageCategory::create($category);
        }

        $this->command->info('✅ Категории массажа созданы!');
    }
}