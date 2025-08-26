<?php

namespace Database\Seeders;

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;

class AdsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        
        if ($users->isEmpty()) {
            echo "❌ Пользователи не найдены. Сначала запустите UsersSeeder.\n";
            return;
        }

        $adsData = [
            [
                'title' => 'Классический массаж от Анны',
                'description' => 'Профессиональный массаж в уютной обстановке. Более 5 лет опыта.',
                'specialty' => 'Классический массаж',
                'age' => '28',
                'height' => '165',
                'weight' => '55',
                'hair_color' => 'blonde',
                'eye_color' => 'blue',
                'nationality' => 'russian',
                'appearance' => 'attractive',
                'clients' => json_encode(['men', 'women']),
                'service_provider' => json_encode(['individual']),
                'service_location' => json_encode(['apartments', 'outcall']),
                'services' => json_encode([
                    'massage' => ['classic', 'relaxing'],
                    'additional' => ['aromatherapy']
                ]),
                'schedule' => json_encode([
                    'monday' => ['10:00-18:00'],
                    'tuesday' => ['10:00-18:00'],
                    'wednesday' => ['10:00-18:00'],
                    'thursday' => ['10:00-18:00'],
                    'friday' => ['10:00-18:00']
                ]),
                'prices' => json_encode([
                    'apartments_1h' => 5000,
                    'outcall_1h' => 6000,
                    'apartments_2h' => 9000
                ]),
                'geo' => json_encode(['lat' => 55.7558, 'lng' => 37.6173]), // Москва
                'address' => 'Москва, Тверская область',
                'phone' => '+7 (925) 123-45-67',
                'status' => 'active',
                'is_paid' => true,
                'views_count' => 120,
                'favorites_count' => 8,
            ],
            [
                'title' => 'Расслабляющий массаж от Марии',
                'description' => 'Антистресс массаж для восстановления энергии и душевного равновесия.',
                'specialty' => 'Релакс массаж',
                'age' => '25',
                'height' => '168',
                'weight' => '52',
                'hair_color' => 'brown',
                'eye_color' => 'green',
                'nationality' => 'russian',
                'appearance' => 'beautiful',
                'clients' => json_encode(['men', 'women']),
                'service_provider' => json_encode(['individual']),
                'service_location' => json_encode(['apartments']),
                'services' => json_encode([
                    'massage' => ['relaxing', 'anti_stress'],
                    'additional' => ['music_therapy']
                ]),
                'schedule' => json_encode([
                    'tuesday' => ['12:00-20:00'],
                    'wednesday' => ['12:00-20:00'],
                    'thursday' => ['12:00-20:00'],
                    'friday' => ['12:00-20:00'],
                    'saturday' => ['12:00-20:00']
                ]),
                'prices' => json_encode([
                    'apartments_1h' => 4500,
                    'apartments_2h' => 8000
                ]),
                'geo' => json_encode(['lat' => 55.7887, 'lng' => 37.6070]),
                'address' => 'Москва, Центральный округ',
                'phone' => '+7 (926) 234-56-78',
                'status' => 'active',
                'is_paid' => true,
                'views_count' => 95,
                'favorites_count' => 12,
            ],
            [
                'title' => 'Спортивный массаж от Елены',
                'description' => 'Массаж для спортсменов и активных людей. Восстановление после тренировок.',
                'specialty' => 'Спортивный массаж',
                'age' => '30',
                'height' => '170',
                'weight' => '58',
                'hair_color' => 'black',
                'eye_color' => 'brown',
                'nationality' => 'russian',
                'appearance' => 'sporty',
                'clients' => json_encode(['men', 'women']),
                'service_provider' => json_encode(['individual']),
                'service_location' => json_encode(['apartments', 'outcall']),
                'services' => json_encode([
                    'massage' => ['sport', 'therapeutic'],
                    'additional' => ['stretching']
                ]),
                'schedule' => json_encode([
                    'monday' => ['09:00-17:00'],
                    'wednesday' => ['09:00-17:00'],
                    'friday' => ['09:00-17:00'],
                    'saturday' => ['10:00-18:00'],
                    'sunday' => ['10:00-16:00']
                ]),
                'prices' => json_encode([
                    'apartments_1h' => 5500,
                    'outcall_1h' => 6500,
                    'apartments_2h' => 10000
                ]),
                'geo' => json_encode(['lat' => 55.7387, 'lng' => 37.6032]),
                'address' => 'Москва, Хамовники',
                'phone' => '+7 (927) 345-67-89',
                'status' => 'active',
                'is_paid' => false,
                'views_count' => 76,
                'favorites_count' => 5,
            ]
        ];

        foreach ($adsData as $index => $adData) {
            $user = $users->get($index);
            if ($user) {
                $adData['user_id'] = $user->id;
                Ad::create($adData);
            }
        }
        
        echo "✅ Создано объявлений: " . count($adsData) . "\n";
    }
}