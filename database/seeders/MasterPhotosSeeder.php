<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MasterProfile;
use App\Models\MasterPhoto;

class MasterPhotosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Найдем мастера Елену Сидорову
        $master = MasterProfile::where('display_name', 'Елена Сидорова')->first();
        
        if (!$master) {
            $this->command->error('Мастер Елена Сидорова не найден!');
            return;
        }

        // Удалим существующие фотографии
        $master->photos()->delete();

        // Добавим тестовые фотографии
        $photos = [
            [
                'path' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=400&h=600&fit=crop&crop=face',
                'is_main' => true,
                'order' => 1
            ],
            [
                'path' => 'https://images.unsplash.com/photo-1580618672591-eb180b1a973f?w=400&h=600&fit=crop&crop=face',
                'is_main' => false,
                'order' => 2
            ],
            [
                'path' => 'https://images.unsplash.com/photo-1594824388853-0d0e4a8a1b4c?w=400&h=600&fit=crop&crop=face',
                'is_main' => false,
                'order' => 3
            ],
            [
                'path' => 'https://images.unsplash.com/photo-1607990281513-2c110a25bd8c?w=400&h=600&fit=crop&crop=face',
                'is_main' => false,
                'order' => 4
            ],
            [
                'path' => 'https://images.unsplash.com/photo-1588516903720-8ceb67f9ef84?w=400&h=600&fit=crop&crop=face',
                'is_main' => false,
                'order' => 5
            ],
            [
                'path' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=400&h=600&fit=crop&crop=face',
                'is_main' => false,
                'order' => 6
            ]
        ];

        foreach ($photos as $photo) {
            MasterPhoto::create([
                'master_profile_id' => $master->id,
                'path' => $photo['path'],
                'is_main' => $photo['is_main'],
                'order' => $photo['order']
            ]);
        }

        // Обновим аватар мастера
        $master->update([
            'avatar' => $photos[0]['path'],
            'is_verified' => true,
            'is_premium' => true,
            'premium_until' => now()->addMonths(3)
        ]);

        $this->command->info('✅ Добавлено ' . count($photos) . ' фотографий для мастера: ' . $master->display_name);
    }
} 