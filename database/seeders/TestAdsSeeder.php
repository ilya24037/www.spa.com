<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use App\Domain\Ad\Enums\AdStatus;

/**
 * Сидер для создания тестовых объявлений
 * Используется только в development окружении
 */
class TestAdsSeeder extends Seeder
{
    /**
     * Запуск сидера
     *
     * @return void
     */
    public function run()
    {
        // Проверяем, что мы в dev окружении
        if (!app()->environment('local', 'development', 'testing')) {
            $this->command->warn('TestAdsSeeder skipped: not in development environment');
            return;
        }
        
        $this->command->info('Creating test ads...');
        
        // Создаем тестовых пользователей если их нет
        $users = $this->createTestUsers();
        
        // Создаем объявления для каждого пользователя
        foreach ($users as $user) {
            $this->createAdsForUser($user);
        }
        
        $this->command->info('Test ads created successfully!');
    }
    
    /**
     * Создать тестовых пользователей
     *
     * @return array
     */
    private function createTestUsers(): array
    {
        $testUsers = [
            [
                'name' => 'Анна Петрова',
                'email' => 'anna@test.com',
                'password' => bcrypt('password'),
                'is_verified' => true,
            ],
            [
                'name' => 'Ирина Сидорова',
                'email' => 'irina@test.com',
                'password' => bcrypt('password'),
                'is_verified' => true,
            ],
            [
                'name' => 'Елена Иванова',
                'email' => 'elena@test.com',
                'password' => bcrypt('password'),
                'is_verified' => false,
            ],
            [
                'name' => 'Мария Козлова',
                'email' => 'maria@test.com',
                'password' => bcrypt('password'),
                'is_verified' => false,
            ],
            [
                'name' => 'Ольга Новикова',
                'email' => 'olga@test.com',
                'password' => bcrypt('password'),
                'is_verified' => true,
            ],
            [
                'name' => 'Светлана Морозова',
                'email' => 'svetlana@test.com',
                'password' => bcrypt('password'),
                'is_verified' => false,
            ],
        ];
        
        $users = [];
        foreach ($testUsers as $userData) {
            $users[] = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
        
        return $users;
    }
    
    /**
     * Создать объявления для пользователя
     *
     * @param User $user
     * @return void
     */
    private function createAdsForUser(User $user)
    {
        $adsData = $this->getAdsDataByUserEmail($user->email);
        
        foreach ($adsData as $adData) {
            Ad::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'title' => $adData['title']
                ],
                array_merge($adData, ['user_id' => $user->id])
            );
        }
    }
    
    /**
     * Получить данные объявлений по email пользователя
     *
     * @param string $email
     * @return array
     */
    private function getAdsDataByUserEmail(string $email): array
    {
        $adsMapping = [
            'anna@test.com' => [
                [
                    'title' => 'Профессиональный классический массаж',
                    'description' => 'Опытный массажист с медицинским образованием. Провожу классический, релакс и антицеллюлитный массаж.',
                    'category' => 'massage',
                    'specialty' => 'classic',
                    'status' => AdStatus::ACTIVE->value,
                    'price' => 2500,
                    'price_unit' => 'за час',
                    'prices' => json_encode([
                        'Классический массаж' => 2500,
                        'Релакс массаж' => 3000,
                        'Антицеллюлитный' => 3500
                    ]),
                    'services' => json_encode(['Классический массаж', 'Релакс массаж', 'Антицеллюлитный']),
                    'address' => 'ул. Арбат, 10',
                    'geo' => json_encode([
                        'lat' => 55.7522,
                        'lng' => 37.5856,
                        'district' => 'Центральный',
                        'metro' => 'Арбатская'
                    ]),
                    'phone' => '+7 (999) 123-45-67',
                    'photos' => json_encode([
                        ['url' => '/images/masters/загруженное.png']
                    ]),
                    'experience' => 5,
                    'published_at' => now(),
                    'moderated_at' => now(),
                ]
            ],
            'irina@test.com' => [
                [
                    'title' => 'Тайский массаж и СПА процедуры',
                    'description' => 'Сертифицированный специалист по тайскому массажу. Обучалась в Таиланде.',
                    'category' => 'massage',
                    'specialty' => 'thai',
                    'status' => AdStatus::ACTIVE->value,
                    'price' => 3000,
                    'price_unit' => 'за сеанс',
                    'prices' => json_encode([
                        'Тайский массаж' => 3000,
                        'Стоун-терапия' => 4000,
                        'Ароматерапия' => 3500
                    ]),
                    'services' => json_encode(['Тайский массаж', 'Стоун-терапия', 'Ароматерапия']),
                    'address' => 'ул. Баррикадная, 5',
                    'geo' => json_encode([
                        'lat' => 55.7609,
                        'lng' => 37.5812,
                        'district' => 'Пресненский',
                        'metro' => 'Баррикадная'
                    ]),
                    'phone' => '+7 (999) 234-56-78',
                    'photos' => json_encode([
                        ['url' => '/images/masters/загруженное (4).png']
                    ]),
                    'experience' => 8,
                    'published_at' => now(),
                    'moderated_at' => now(),
                ]
            ],
            'elena@test.com' => [
                [
                    'title' => 'Спортивный и лечебный массаж',
                    'description' => 'Специализируюсь на спортивном и лечебном массаже. Работаю со спортсменами.',
                    'category' => 'massage',
                    'specialty' => 'medical',
                    'status' => AdStatus::ACTIVE->value,
                    'price' => 2000,
                    'price_unit' => 'за час',
                    'prices' => json_encode([
                        'Спортивный массаж' => 2000,
                        'Лечебный массаж' => 2500,
                        'Мануальная терапия' => 3000
                    ]),
                    'services' => json_encode(['Спортивный массаж', 'Лечебный массаж', 'Мануальная терапия']),
                    'address' => 'Тверская ул., 15',
                    'geo' => json_encode([
                        'lat' => 55.7649,
                        'lng' => 37.6066,
                        'district' => 'Тверской',
                        'metro' => 'Тверская'
                    ]),
                    'phone' => '+7 (999) 345-67-89',
                    'photos' => json_encode([
                        ['url' => '/images/masters/загруженное (5).png']
                    ]),
                    'experience' => 3,
                    'published_at' => now(),
                    'moderated_at' => now(),
                ]
            ],
            'maria@test.com' => [
                [
                    'title' => 'Лимфодренажный массаж и обертывания',
                    'description' => 'Предлагаю комплексные программы для похудения и улучшения состояния кожи.',
                    'category' => 'massage',
                    'specialty' => 'spa',
                    'status' => AdStatus::ACTIVE->value,
                    'price' => 3500,
                    'price_unit' => 'за услугу',
                    'prices' => json_encode([
                        'Лимфодренажный' => 3500,
                        'Антицеллюлитный' => 3000,
                        'Обертывания' => 2500
                    ]),
                    'services' => json_encode(['Лимфодренажный', 'Антицеллюлитный', 'Обертывания']),
                    'address' => 'Смоленская площадь, 3',
                    'geo' => json_encode([
                        'lat' => 55.7491,
                        'lng' => 37.5816,
                        'district' => 'Арбат',
                        'metro' => 'Смоленская'
                    ]),
                    'phone' => '+7 (999) 456-78-90',
                    'photos' => json_encode([
                        ['url' => '/images/masters/загруженное (6).png']
                    ]),
                    'experience' => 10,
                    'published_at' => now(),
                    'moderated_at' => now(),
                ]
            ],
            'olga@test.com' => [
                [
                    'title' => 'Расслабляющий массаж и СПА',
                    'description' => 'Создаю атмосферу полного расслабления и восстановления.',
                    'category' => 'massage',
                    'specialty' => 'spa',
                    'status' => AdStatus::ACTIVE->value,
                    'price' => 2200,
                    'price_unit' => 'за час',
                    'prices' => json_encode([
                        'Расслабляющий массаж' => 2200,
                        'СПА программы' => 4000,
                        'Пилинг' => 1500
                    ]),
                    'services' => json_encode(['Расслабляющий массаж', 'СПА программы', 'Пилинг']),
                    'address' => 'ул. Остоженка, 25',
                    'geo' => json_encode([
                        'lat' => 55.7361,
                        'lng' => 37.5950,
                        'district' => 'Хамовники',
                        'metro' => 'Парк Культуры'
                    ]),
                    'phone' => '+7 (999) 567-89-01',
                    'photos' => json_encode([
                        ['url' => '/images/masters/загруженное (7).png']
                    ]),
                    'experience' => 4,
                    'published_at' => now(),
                    'moderated_at' => now(),
                ]
            ],
            'svetlana@test.com' => [
                [
                    'title' => 'Медовый и баночный массаж',
                    'description' => 'Использую традиционные методики для оздоровления организма.',
                    'category' => 'massage',
                    'specialty' => 'medical',
                    'status' => AdStatus::ACTIVE->value,
                    'price' => 2800,
                    'price_unit' => 'за сеанс',
                    'prices' => json_encode([
                        'Медовый массаж' => 2800,
                        'Баночный массаж' => 2500,
                        'Рефлексотерапия' => 3000
                    ]),
                    'services' => json_encode(['Медовый массаж', 'Баночный массаж', 'Рефлексотерапия']),
                    'address' => 'Пятницкая ул., 40',
                    'geo' => json_encode([
                        'lat' => 55.7313,
                        'lng' => 37.6277,
                        'district' => 'Замоскворечье',
                        'metro' => 'Новокузнецкая'
                    ]),
                    'phone' => '+7 (999) 678-90-12',
                    'photos' => json_encode([
                        ['url' => '/images/masters/загруженное (8).png']
                    ]),
                    'experience' => 6,
                    'published_at' => now(),
                    'moderated_at' => now(),
                ]
            ],
        ];
        
        return $adsMapping[$email] ?? [];
    }
}