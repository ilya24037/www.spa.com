<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\User;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Создаем тестовые отзывы для мастеров...');
        
        // Получаем мастеров
        $masters = MasterProfile::all();
        
        if ($masters->isEmpty()) {
            $this->command->info('Мастера не найдены. Сначала запустите MasterSeeder.');
            return;
        }
        
        // Получаем пользователей для отзывов
        $users = User::limit(10)->get();
        
        if ($users->isEmpty()) {
            $this->command->info('Пользователи не найдены. Сначала создайте пользователей.');
            return;
        }
        
        $reviews = [
            [
                'rating' => 5,
                'comment' => 'Отличный мастер! Очень профессионально и аккуратно. Рекомендую всем.',
                'is_verified' => true,
                'is_visible' => true
            ],
            [
                'rating' => 4,
                'comment' => 'Хороший массаж, но немного дороговато. В целом доволен результатом.',
                'is_verified' => true,
                'is_visible' => true
            ],
            [
                'rating' => 5,
                'comment' => 'Превзошла все ожидания! Очень внимательная и заботливая. Буду обращаться еще.',
                'is_verified' => true,
                'is_visible' => true
            ],
            [
                'rating' => 4,
                'comment' => 'Хороший мастер, но график работы не очень удобный. Массаж качественный.',
                'is_verified' => true,
                'is_visible' => true
            ],
            [
                'rating' => 5,
                'comment' => 'Лучший массажист в городе! Очень доволен, буду рекомендовать друзьям.',
                'is_verified' => true,
                'is_visible' => true
            ]
        ];
        
        foreach ($masters as $master) {
            $this->command->info("Добавляем отзывы для мастера: {$master->display_name}");
            
            // Добавляем 2-3 отзыва для каждого мастера
            for ($i = 0; $i < min(3, count($reviews)); $i++) {
                $review = $reviews[$i];
                $user = $users->random();
                
                DB::table('reviews')->insert([
                    'reviewer_id' => $user->id,
                    'reviewable_type' => MasterProfile::class,
                    'reviewable_id' => $master->id,
                    'rating' => $review['rating'],
                    'comment' => $review['comment'],
                    'status' => 'approved', // Добавляем статус
                    'is_verified' => $review['is_verified'],
                    'is_visible' => $review['is_visible'],
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now()->subDays(rand(1, 30)),
                ]);
                
                $this->command->info("  ✅ Добавлен отзыв с рейтингом {$review['rating']}");
            }
        }
        
        $this->command->info('✅ Все отзывы успешно созданы!');
    }
}
