<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;
use App\Domain\Master\Models\MasterProfile;
use Illuminate\Support\Str;

class CreateAdsForAllUsersSeeder extends Seeder
{
    public function run()
    {
        // Получаем пользователей без объявлений через прямой SQL
        $usersWithAds = Ad::distinct()->pluck('user_id')->toArray();
        $usersWithoutAds = User::whereNotIn('id', $usersWithAds)->get();
        
        echo "Найдено пользователей без объявлений: " . $usersWithoutAds->count() . "\n";
        
        // Массив услуг для случайного выбора
        $services = [
            'Классический массаж',
            'Тайский массаж',
            'Спортивный массаж',
            'Релакс массаж',
            'Антицеллюлитный массаж',
            'Лимфодренажный массаж',
            'Массаж спины',
            'Массаж лица'
        ];
        
        // Районы Перми
        $districts = [
            'Ленинский',
            'Свердловский',
            'Мотовилихинский',
            'Индустриальный',
            'Дзержинский',
            'Кировский',
            'Орджоникидзевский'
        ];
        
        foreach ($usersWithoutAds as $user) {
            // Создаем объявление
            $ad = Ad::create([
                'user_id' => $user->id,
                'title' => $services[array_rand($services)] . ' от ' . $user->name,
                'description' => 'Приглашаю на профессиональный массаж. Опыт работы ' . rand(1, 10) . ' лет. ' .
                                'Индивидуальный подход, комфортная атмосфера, профессиональное оборудование. ' .
                                'Помогу расслабиться и восстановить силы.',
                'category' => 'Массаж',
                'specialty' => $services[array_rand($services)],
                'price' => rand(1500, 4000),
                'price_per_hour' => rand(1500, 4000),
                'price_unit' => 'час',
                'is_starting_price' => 0,
                'address' => 'Пермь, ' . $districts[array_rand($districts)] . ' район, ул. ' . 
                           ['Ленина', 'Комсомольский проспект', 'Сибирская', 'Куйбышева', 'Екатерининская'][array_rand(['Ленина', 'Комсомольский проспект', 'Сибирская', 'Куйбышева', 'Екатерининская'])] . 
                           ', ' . rand(1, 100),
                'phone' => '+7912' . rand(1000000, 9999999),
                'status' => 'active',
                'views_count' => rand(10, 500),
                'contacts_shown' => rand(5, 50),
                'services' => json_encode(array_slice($services, 0, rand(2, 5))),
                'schedule' => json_encode([
                    'monday' => ['09:00', '21:00'],
                    'tuesday' => ['09:00', '21:00'],
                    'wednesday' => ['09:00', '21:00'],
                    'thursday' => ['09:00', '21:00'],
                    'friday' => ['09:00', '21:00'],
                    'saturday' => ['10:00', '20:00'],
                    'sunday' => ['10:00', '18:00']
                ]),
                'amenities' => json_encode([
                    'wifi' => rand(0, 1) == 1,
                    'parking' => rand(0, 1) == 1,
                    'shower' => rand(0, 1) == 1,
                    'tea' => rand(0, 1) == 1,
                    'music' => rand(0, 1) == 1,
                    'air_conditioning' => rand(0, 1) == 1
                ]),
                'expires_at' => now()->addDays(30),
                'is_paid' => 1,
                'paid_at' => now()
            ]);
            
            echo "Создано объявление для пользователя: {$user->name} (ID: {$user->id})\n";
        }
        
        echo "Готово! Создано объявлений: " . $usersWithoutAds->count() . "\n";
    }
}