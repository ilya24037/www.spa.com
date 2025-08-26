<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;
use Illuminate\Support\Facades\DB;

class PermAddressesSeeder extends Seeder
{
    /**
     * Реальные адреса в Перми с координатами
     */
    private array $permAddresses = [
        ['address' => 'Пермь, ул. Ленина, д. 45', 'lat' => 58.0092, 'lng' => 56.2270, 'district' => 'Ленинский район'],
        ['address' => 'Пермь, Комсомольский проспект, д. 32', 'lat' => 58.0105, 'lng' => 56.2502, 'district' => 'Свердловский район'],
        ['address' => 'Пермь, ул. Петропавловская, д. 68', 'lat' => 58.0150, 'lng' => 56.2465, 'district' => 'Ленинский район'],
        ['address' => 'Пермь, ул. Сибирская, д. 15', 'lat' => 58.0078, 'lng' => 56.2324, 'district' => 'Ленинский район'],
        ['address' => 'Пермь, ул. Пушкина, д. 78', 'lat' => 58.0112, 'lng' => 56.2589, 'district' => 'Свердловский район'],
        ['address' => 'Пермь, ул. Екатерининская, д. 120', 'lat' => 58.0140, 'lng' => 56.2520, 'district' => 'Ленинский район'],
        ['address' => 'Пермь, ул. Советская, д. 56', 'lat' => 58.0098, 'lng' => 56.2380, 'district' => 'Ленинский район'],
        ['address' => 'Пермь, ул. Мира, д. 23', 'lat' => 58.0180, 'lng' => 56.2610, 'district' => 'Мотовилихинский район'],
        ['address' => 'Пермь, ул. Куйбышева, д. 89', 'lat' => 58.0065, 'lng' => 56.2290, 'district' => 'Ленинский район'],
        ['address' => 'Пермь, ул. Горького, д. 14', 'lat' => 58.0125, 'lng' => 56.2450, 'district' => 'Ленинский район'],
        ['address' => 'Пермь, ул. Крисанова, д. 22', 'lat' => 58.0045, 'lng' => 56.2180, 'district' => 'Дзержинский район'],
        ['address' => 'Пермь, ул. Луначарского, д. 51', 'lat' => 58.0155, 'lng' => 56.2550, 'district' => 'Ленинский район'],
        ['address' => 'Пермь, ул. Максима Горького, д. 83', 'lat' => 58.0135, 'lng' => 56.2420, 'district' => 'Ленинский район'],
        ['address' => 'Пермь, ул. Революции, д. 21', 'lat' => 58.0088, 'lng' => 56.2340, 'district' => 'Ленинский район'],
        ['address' => 'Пермь, ул. Чернышевского, д. 15а', 'lat' => 58.0165, 'lng' => 56.2490, 'district' => 'Свердловский район'],
        ['address' => 'Пермь, ул. Газеты Звезда, д. 5', 'lat' => 58.0095, 'lng' => 56.2310, 'district' => 'Ленинский район'],
        ['address' => 'Пермь, ул. 25 Октября, д. 17', 'lat' => 58.0075, 'lng' => 56.2280, 'district' => 'Ленинский район'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Начинаем добавление адресов для пользователей и объявлений...');
        
        // Получаем всех пользователей
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('В базе нет пользователей!');
            return;
        }
        
        $this->command->info("Найдено пользователей: {$users->count()}");
        
        // Обновляем адреса для каждого пользователя
        foreach ($users as $index => $user) {
            // Берем адрес по индексу (циклично, если пользователей больше чем адресов)
            $addressData = $this->permAddresses[$index % count($this->permAddresses)];
            
            // Обновляем или создаем профиль пользователя с адресом
            DB::table('user_profiles')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'city' => 'Пермь',
                    'address' => $addressData['address'],
                    'lat' => $addressData['lat'],
                    'lng' => $addressData['lng'],
                    'district' => $addressData['district'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            
            $this->command->line("✓ Пользователь ID {$user->id}: {$addressData['address']}");
        }
        
        // Обновляем адреса для объявлений
        $ads = Ad::all();
        $this->command->info("\nНайдено объявлений: {$ads->count()}");
        
        foreach ($ads as $ad) {
            // Получаем адрес из профиля пользователя
            $userProfile = DB::table('user_profiles')
                ->where('user_id', $ad->user_id)
                ->first();
            
            if ($userProfile) {
                // Обновляем поля адреса и geo в объявлении
                $geoData = [
                    'address' => $userProfile->address,
                    'lat' => (float)$userProfile->lat,
                    'lng' => (float)$userProfile->lng,
                    'district' => $userProfile->district,
                    'city' => $userProfile->city
                ];
                
                $ad->update([
                    'address' => $userProfile->address,
                    'geo' => $geoData
                ]);
                
                $this->command->line("✓ Объявление ID {$ad->id}: адрес обновлен");
            }
        }
        
        $this->command->info("\n✅ Адреса успешно добавлены!");
        $this->command->info("Теперь карта сможет отображать маркеры с реальными координатами.");
    }
}