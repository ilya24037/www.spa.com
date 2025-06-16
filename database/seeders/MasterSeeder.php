<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\MasterProfile;
use App\Models\MassageCategory;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        // Создаем категории массажа
        $categories = [
            ['name' => 'Классический массаж', 'slug' => 'classic', 'is_active' => true],
            ['name' => 'Лечебный массаж', 'slug' => 'medical', 'is_active' => true],
            ['name' => 'Расслабляющий массаж', 'slug' => 'relax', 'is_active' => true],
            ['name' => 'Спортивный массаж', 'slug' => 'sport', 'is_active' => true],
            ['name' => 'Антицеллюлитный массаж', 'slug' => 'anti-cellulite', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            MassageCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        // Создаем тестовых мастеров
        $masters = [
            [
                'name' => 'Анна Петрова',
                'email' => 'anna@test.com',
                'phone' => '+7 (999) 111-11-11',
                'bio' => 'Профессиональный массажист с опытом работы более 10 лет. Специализируюсь на классическом и лечебном массаже.',
                'experience_years' => 10,
                'district' => 'Центральный',
                'metro_station' => 'Арбатская',
                'price_from' => 2000,
                'rating' => 4.8,
                'reviews_count' => 127,
                'is_verified' => true,
                'is_premium' => true,
                'avatar' => 'https://i.pravatar.cc/300?img=1',
                'certificates' => ['cert1.jpg', 'cert2.jpg'],
                'latitude' => 55.7522,
                'longitude' => 37.6156,
            ],
            [
                'name' => 'Михаил Иванов',
                'email' => 'mikhail@test.com',
                'phone' => '+7 (999) 222-22-22',
                'bio' => 'Специалист по лечебному и спортивному массажу. Работаю с профессиональными спортсменами.',
                'experience_years' => 7,
                'district' => 'Северный',
                'metro_station' => 'Сокольники',
                'price_from' => 2500,
                'rating' => 4.9,
                'reviews_count' => 89,
                'is_verified' => true,
                'is_premium' => false,
                'avatar' => 'https://i.pravatar.cc/300?img=2',
                'certificates' => ['cert3.jpg'],
                'latitude' => 55.7894,
                'longitude' => 37.6791,
            ],
            [
                'name' => 'Елена Сидорова',
                'email' => 'elena@test.com',
                'phone' => '+7 (999) 333-33-33',
                'bio' => 'Мастер расслабляющего и антицеллюлитного массажа. Использую натуральные масла и современные техники.',
                'experience_years' => 5,
                'district' => 'Южный',
                'metro_station' => 'Павелецкая',
                'price_from' => 1800,
                'rating' => 4.7,
                'reviews_count' => 65,
                'is_verified' => false,
                'is_premium' => false,
                'avatar' => 'https://i.pravatar.cc/300?img=3',
                'certificates' => [],
                'latitude' => 55.7303,
                'longitude' => 37.6388,
            ],
            [
                'name' => 'Дмитрий Козлов',
                'email' => 'dmitry@test.com',
                'phone' => '+7 (999) 444-44-44',
                'bio' => 'Профессиональный массажист, специализация - классический и спортивный массаж. Выезжаю на дом.',
                'experience_years' => 8,
                'district' => 'Западный',
                'metro_station' => 'Кутузовская',
                'price_from' => 3000,
                'rating' => 5.0,
                'reviews_count' => 156,
                'is_verified' => true,
                'is_premium' => true,
                'avatar' => 'https://i.pravatar.cc/300?img=4',
                'certificates' => ['cert4.jpg', 'cert5.jpg', 'cert6.jpg'],
                'latitude' => 55.7407,
                'longitude' => 37.5568,
            ],
            [
                'name' => 'Ольга Николаева',
                'email' => 'olga@test.com',
                'phone' => '+7 (999) 555-55-55',
                'bio' => 'Сертифицированный специалист по всем видам массажа. Индивидуальный подход к каждому клиенту.',
                'experience_years' => 12,
                'district' => 'Восточный',
                'metro_station' => 'Партизанская',
                'price_from' => 2200,
                'rating' => 4.6,
                'reviews_count' => 203,
                'is_verified' => true,
                'is_premium' => false,
                'avatar' => 'https://i.pravatar.cc/300?img=5',
                'certificates' => ['cert7.jpg', 'cert8.jpg'],
                'latitude' => 55.7944,
                'longitude' => 37.7495,
            ],
        ];

        foreach ($masters as $masterData) {
            // Проверяем, существует ли пользователь
            $user = User::where('email', $masterData['email'])->first();
            
            if (!$user) {
                // Создаем нового пользователя
                $user = User::create([
                    'name' => $masterData['name'],
                    'email' => $masterData['email'],
                    'password' => Hash::make('password'),
                    'role' => 'master',
                    'email_verified_at' => now(),
                ]);
                
                $this->command->info("✅ Создан пользователь: {$masterData['name']}");
            } else {
                $this->command->info("⚠️ Пользователь уже существует: {$masterData['name']}");
            }

            // Проверяем, есть ли у пользователя профиль мастера
            if (!$user->masterProfile) {
                // Создаем профиль мастера
                $slug = Str::slug($masterData['name']) . '-' . Str::random(6);
                
                $masterProfile = MasterProfile::create([
                    'user_id' => $user->id,
                    'display_name' => $masterData['name'],
                    'slug' => $slug,
                    'phone' => $masterData['phone'],
                    'bio' => $masterData['bio'],
                    'avatar' => $masterData['avatar'],
                    'experience_years' => $masterData['experience_years'],
                    'district' => $masterData['district'],
                    'metro_station' => $masterData['metro_station'],
                    'city' => 'Москва',
                    'home_service' => true,
                    'salon_service' => rand(0, 1) ? true : false,
                    'salon_address' => rand(0, 1) ? 'ул. Примерная, д. ' . rand(1, 100) : null,
                    'rating' => $masterData['rating'],
                    'reviews_count' => $masterData['reviews_count'],
                    'is_verified' => $masterData['is_verified'],
                    'is_premium' => $masterData['is_premium'],
                    'is_active' => true,
                    'status' => 'active',
                    'certificates' => $masterData['certificates'],
                    'latitude' => $masterData['latitude'],
                    'longitude' => $masterData['longitude'],
                    'completed_bookings' => rand(50, 500),
                    'views_count' => rand(100, 5000),
                    'show_contacts' => true,
                    'whatsapp' => $masterData['phone'],
                    'telegram' => '@' . Str::slug($masterData['name']),
                ]);

                // Создаем услуги для мастера
                $categories = MassageCategory::inRandomOrder()->take(3)->get();
                
                foreach ($categories as $index => $category) {
                    Service::create([
                        'master_profile_id' => $masterProfile->id,
                        'massage_category_id' => $category->id,
                        'name' => $category->name,
                        'description' => 'Профессиональный ' . strtolower($category->name) . ' с использованием современных техник и натуральных масел.',
                        'price' => $masterData['price_from'] + ($index * 500),
                        'duration' => rand(2, 4) * 30, // 60, 90 или 120 минут
                        'status' => 'active',
                        'is_active' => true,
                        'for_men' => true,
                        'for_women' => true,
                        'for_children' => rand(0, 1) ? true : false,
                        'for_pregnant' => false,
                    ]);
                }

                $this->command->info("✅ Создан профиль мастера: {$masterData['name']}");
            } else {
                $this->command->info("⚠️ Профиль мастера уже существует: {$masterData['name']}");
            }
        }

        $this->command->info('✅ Все тестовые мастера обработаны!');
    }
}