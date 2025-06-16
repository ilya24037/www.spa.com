<?php

namespace Database\Seeders;

use App\Models\MassageCategory;
use App\Models\MasterProfile;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MasterSeeder extends Seeder
{
    public function run()
    {
        $masters = [
            [
                'name' => 'Анна Петрова',
                'email' => 'anna@spa.test',
                'phone' => '+7 (999) 123-45-67',
                'bio' => 'Сертифицированный массажист с 8-летним опытом. Специализируюсь на классическом и лечебном массаже.',
                'experience_years' => 8,
                'district' => 'Центральный',
                'metro_station' => 'Чистые пруды',
                'price_from' => 2500,
                'rating' => 4.8,
                'reviews_count' => 127,
                'is_verified' => true,
                'is_premium' => true,
                'avatar' => 'https://i.pravatar.cc/300?img=1',
                'certificates' => ['cert1.jpg', 'cert2.jpg'],
                'latitude' => 55.7659,
                'longitude' => 37.6444,
            ],
            [
                'name' => 'Михаил Иванов',
                'email' => 'mikhail@spa.test',
                'phone' => '+7 (999) 234-56-78',
                'bio' => 'Мастер спортивного и восстановительного массажа. Работаю с профессиональными спортсменами.',
                'experience_years' => 10,
                'district' => 'Северный',
                'metro_station' => 'Водный стадион',
                'price_from' => 3000,
                'rating' => 4.9,
                'reviews_count' => 89,
                'is_verified' => true,
                'is_premium' => false,
                'avatar' => 'https://i.pravatar.cc/300?img=3',
                'certificates' => ['cert3.jpg', 'cert4.jpg', 'cert5.jpg'],
                'latitude' => 55.8396,
                'longitude' => 37.4878,
            ],
            [
                'name' => 'Елена Сидорова',
                'email' => 'elena@spa.test',
                'phone' => '+7 (999) 345-67-89',
                'bio' => 'Специалист по тайскому и релакс-массажу. Обучалась в Таиланде.',
                'experience_years' => 5,
                'district' => 'Южный',
                'metro_station' => 'Автозаводская',
                'price_from' => 2000,
                'rating' => 4.7,
                'reviews_count' => 156,
                'is_verified' => true,
                'is_premium' => true,
                'avatar' => 'https://i.pravatar.cc/300?img=5',
                'certificates' => ['cert6.jpg'],
                'latitude' => 55.7082,
                'longitude' => 37.6574,
            ],
            [
                'name' => 'Дмитрий Козлов',
                'email' => 'dmitry@spa.test',
                'phone' => '+7 (999) 456-78-90',
                'bio' => 'Профессиональный массажист-реабилитолог. Помогаю восстановиться после травм.',
                'experience_years' => 15,
                'district' => 'Западный',
                'metro_station' => 'Кунцевская',
                'price_from' => 3500,
                'rating' => 4.95,
                'reviews_count' => 234,
                'is_verified' => true,
                'is_premium' => true,
                'avatar' => 'https://i.pravatar.cc/300?img=8',
                'certificates' => ['cert7.jpg', 'cert8.jpg', 'cert9.jpg', 'cert10.jpg'],
                'latitude' => 55.7307,
                'longitude' => 37.4461,
            ],
            [
                'name' => 'Ольга Николаева',
                'email' => 'olga@spa.test',
                'phone' => '+7 (999) 567-89-01',
                'bio' => 'Мастер антицеллюлитного и лимфодренажного массажа. Индивидуальный подход к каждому клиенту.',
                'experience_years' => 12,
                'district' => 'Восточный',
                'metro_station' => 'Партизанская',
                'price_from' => 2200,
                'rating' => 4.6,
                'reviews_count' => 203,
                'is_verified' => true,
                'is_premium' => false,
                'avatar' => 'https://i.pravatar.cc/300?img=9',
                'certificates' => ['cert11.jpg', 'cert12.jpg'],
                'latitude' => 55.7944,
                'longitude' => 37.7495,
            ],
        ];

        foreach ($masters as $masterData) {
            // Создаем или находим пользователя
            $user = User::firstOrCreate(
                ['email' => $masterData['email']],
                [
                    'name' => $masterData['name'],
                    'password' => Hash::make('password'),
                    'role' => 'master',
                    'email_verified_at' => now(),
                ]
            );
            
            $this->command->info("✅ Обработан пользователь: {$masterData['name']}");

            // Создаем или обновляем профиль мастера
            $masterProfile = MasterProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $masterData['name'],
                    'slug' => Str::slug($masterData['name']) . '-' . Str::random(6),
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
                ]
            );

            $this->command->info("✅ Профиль мастера готов: {$masterData['name']}");

            // Удаляем старые услуги мастера
            Service::where('master_profile_id', $masterProfile->id)->delete();

            // Создаем новые услуги для мастера
$categories = MassageCategory::inRandomOrder()->take(3)->get();

foreach ($categories as $index => $category) {
    $serviceName = $category->name;
    $slug = Str::slug($serviceName . ' ' . $masterData['name']) . '-' . Str::random(6);
    
    Service::create([
        'master_profile_id' => $masterProfile->id,
        'massage_category_id' => $category->id,
        'name' => $serviceName,
        'slug' => $slug,
        'description' => 'Профессиональный ' . mb_strtolower($category->name) . ' с использованием современных техник и натуральных масел.',
        'price' => $masterData['price_from'] + ($index * 500),
        'price_home' => $masterData['price_from'] + ($index * 500) + 500, // +500р за выезд
        'duration_minutes' => rand(2, 4) * 30, // 60, 90 или 120 минут
        'status' => 'active',
        'is_featured' => $index === 0, // Первая услуга - рекомендуемая
        'is_new' => true,
        'bookings_count' => rand(10, 100),
        'rating' => rand(45, 50) / 10, // От 4.5 до 5.0
        'views_count' => rand(100, 1000),
        'instant_booking' => true,
        'advance_booking_hours' => 2,
        'cancellation_hours' => 24,
    ]);
}

            $this->command->info("✅ Создано 3 услуги для мастера: {$masterData['name']}");
        }

        $this->command->info('✅ Все тестовые мастера и их услуги созданы!');
    }
}