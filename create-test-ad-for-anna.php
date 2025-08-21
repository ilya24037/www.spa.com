<?php

require_once 'vendor/autoload.php';

// Загружаем Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;

try {
    echo "🔍 Поиск пользователя anna@spa.test...\n";
    
    // Ищем пользователя
    $user = User::where('email', 'anna@spa.test')->first();
    
    if (!$user) {
        echo "❌ Пользователь anna@spa.test не найден!\n";
        echo "Создаём пользователя...\n";
        
        $user = User::create([
            'name' => 'Анна',
            'email' => 'anna@spa.test',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);
        
        echo "✅ Пользователь создан с ID: {$user->id}\n";
    } else {
        echo "✅ Пользователь найден с ID: {$user->id}\n";
    }
    
    // Проверяем есть ли уже активные объявления
    $existingAds = Ad::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->count();
    
    echo "📊 Активных объявлений у пользователя: {$existingAds}\n";
    
    if ($existingAds > 0) {
        echo "ℹ️  У пользователя уже есть активные объявления\n";
        
        // Показываем существующие объявления
        $ads = Ad::where('user_id', $user->id)->get();
        foreach ($ads as $ad) {
            echo "   - ID: {$ad->id}, Статус: {$ad->status->value}, Заголовок: {$ad->title}\n";
        }
        
        echo "\n❓ Создать ещё одно объявление? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $input = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($input) !== 'y') {
            echo "❌ Отменено пользователем\n";
            exit;
        }
    }
    
    echo "\n📝 Создаём тестовое активное объявление...\n";
    
    // Создаём тестовое объявление
    $ad = Ad::create([
        'user_id' => $user->id,
        'category' => 'massage',
        'title' => 'Профессиональный массаж от Анны',
        'description' => 'Предлагаю качественный расслабляющий и лечебный массаж. Большой опыт работы, индивидуальный подход к каждому клиенту. Работаю в комфортной обстановке.',
        'specialty' => 'Классический массаж',
        
        // Параметры мастера
        'age' => '28',
        'height' => '165',
        'weight' => '55',
        'breast_size' => '2',
        'hair_color' => 'Русые',
        'eye_color' => 'Карие',
        'nationality' => 'Русская',
        
        // JSON поля
        'clients' => json_encode(['men', 'women']),
        'service_provider' => json_encode(['women']),
        'service_location' => json_encode(['incall', 'outcall']),
        'services' => json_encode([
            'massage' => ['classic', 'relaxing', 'therapeutic'],
            'additional' => ['aromatherapy']
        ]),
        'features' => json_encode(['experienced', 'professional', 'comfortable']),
        'schedule' => json_encode([
            'monday' => ['enabled' => true, 'from' => '10:00', 'to' => '20:00'],
            'tuesday' => ['enabled' => true, 'from' => '10:00', 'to' => '20:00'],
            'wednesday' => ['enabled' => true, 'from' => '10:00', 'to' => '20:00'],
            'thursday' => ['enabled' => true, 'from' => '10:00', 'to' => '20:00'],
            'friday' => ['enabled' => true, 'from' => '10:00', 'to' => '20:00'],
            'saturday' => ['enabled' => true, 'from' => '12:00', 'to' => '18:00'],
            'sunday' => ['enabled' => false, 'from' => '', 'to' => '']
        ]),
        'prices' => json_encode([
            'apartments_1h' => 3000,
            'apartments_2h' => 5000,
            'outcall_1h' => 4000,
            'outcall_2h' => 6500,
            'taxi_included' => false
        ]),
        'geo' => json_encode([
            'lat' => 55.7558,
            'lng' => 37.6176,
            'city' => 'Москва',
            'address' => 'Москва, центр'
        ]),
        'photos' => json_encode([]),
        'video' => json_encode([]),
        
        // Дополнительные поля
        'services_additional_info' => 'Использую только качественные масла и кремы. Возможен выезд на дом.',
        'additional_features' => 'Уютная обстановка, расслабляющая музыка, ароматические свечи',
        'schedule_notes' => 'Возможна запись на вечернее время по договорённости',
        
        // Работа и опыт
        'work_format' => 'individual',
        'experience' => '5+ лет',
        
        // Цены
        'price' => 3000,
        'price_unit' => 'час',
        'is_starting_price' => true,
        
        // Контакты
        'phone' => '+7 (999) 123-45-67',
        'contact_method' => 'any',
        'whatsapp' => '+7 (999) 123-45-67',
        'telegram' => '@anna_massage',
        
        // Локация
        'address' => 'Москва, Центральный район',
        'travel_area' => 'Центр, СВАО, САО',
        'travel_radius' => '15',
        'travel_price' => 500,
        'travel_price_type' => 'fixed',
        
        // Промо
        'new_client_discount' => '10%',
        'gift' => 'Бесплатная консультация по уходу',
        
        // Настройки
        'online_booking' => true,
        
        // СТАТУС - АКТИВНОЕ ОБЪЯВЛЕНИЕ
        'status' => 'active',
        'is_paid' => true,
        'paid_at' => now(),
        'expires_at' => now()->addDays(30),
        
        // Статистика
        'views_count' => rand(50, 200),
        'contacts_shown' => rand(10, 50),
        'favorites_count' => rand(5, 25),
        
        'created_at' => now()->subDays(rand(1, 7)),
        'updated_at' => now()
    ]);
    
    echo "✅ Тестовое объявление создано!\n";
    echo "   - ID объявления: {$ad->id}\n";
    echo "   - Заголовок: {$ad->title}\n";
    echo "   - Статус: {$ad->status->value}\n";
    echo "   - Пользователь: {$user->name} ({$user->email})\n";
    echo "   - Создано: {$ad->created_at}\n";
    
    echo "\n🔗 Ссылки для проверки:\n";
    echo "   - Просмотр объявления: /ads/{$ad->id}\n";
    echo "   - Редактирование: /ads/{$ad->id}/edit\n";
    echo "   - Личный кабинет: /profile/items/active\n";
    
    echo "\n✅ Готово! Теперь у пользователя anna@spa.test есть активное объявление.\n";

} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . "\n";
    echo "Строка: " . $e->getLine() . "\n";
}
