<?php

namespace Database\Factories\Domain\Ad\Models;

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Ad\Models\Ad>
 */
class AdFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Ad::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['individual', 'salon', 'escort'];
        // Статусы из базы данных: draft, active, paused, archived
        $statuses = ['draft', 'active', 'paused', 'archived'];
        $clients = ['men', 'women', 'couples'];
        $serviceProviders = ['woman', 'man', 'trans'];
        $workFormats = ['apartments', 'outcall', 'salon'];

        // Генерируем случайные цены
        $prices = [];
        foreach ($workFormats as $format) {
            if ($this->faker->boolean(70)) { // 70% шанс что формат доступен
                $basePrice = $this->faker->numberBetween(2000, 10000);
                $prices[$format . '_1h'] = $basePrice;
                $prices[$format . '_2h'] = $basePrice * 1.8;
                $prices[$format . '_night'] = $basePrice * 4;
            }
        }

        // Генерируем фотографии
        $photos = [];
        $photoCount = $this->faker->numberBetween(1, 6);
        for ($i = 0; $i < $photoCount; $i++) {
            $photos[] = [
                'url' => '/images/masters/demo-' . $this->faker->numberBetween(1, 4) . '.jpg',
                'preview' => '/images/masters/demo-' . $this->faker->numberBetween(1, 4) . '.jpg'
            ];
        }

        // Генерируем услуги
        $allServices = [
            'classic' => 'Классический массаж',
            'erotic' => 'Эротический массаж',
            'thai' => 'Тайский массаж',
            'relax' => 'Расслабляющий массаж',
            'tantric' => 'Тантрический массаж',
            'body' => 'Боди массаж',
            'striptease' => 'Стриптиз',
            'escort' => 'Эскорт'
        ];
        $services = $this->faker->randomElements(array_keys($allServices), $this->faker->numberBetween(2, 5));

        // Генерируем район и метро
        $districts = ['Центральный', 'Адмиралтейский', 'Василеостровский', 'Выборгский',
                     'Калининский', 'Кировский', 'Колпинский', 'Красногвардейский'];
        $metros = ['Невский проспект', 'Горьковская', 'Петроградская', 'Чернышевская',
                  'Площадь Восстания', 'Маяковская', 'Сенная площадь', 'Спортивная'];

        // Получаем случайного пользователя (не админа)
        $userId = User::where('role', '!=', 'admin')
            ->inRandomOrder()
            ->value('id') ?? 2; // Если нет обычных пользователей, используем ID 2

        return [
            'user_id' => $userId,
            'category' => $this->faker->randomElement($categories),
            'title' => $this->faker->name() . ' - ' . $this->faker->randomElement(['массажистка', 'мастер массажа']),
            'clients' => $this->faker->randomElement($clients),
            'client_age_from' => $this->faker->numberBetween(18, 30),
            'service_provider' => $this->faker->randomElement($serviceProviders),
            'services' => $services,
            'prices' => $prices,
            'work_format' => $this->faker->randomElements($workFormats, $this->faker->numberBetween(1, 3)),
            'schedule' => [
                'monday' => ['from' => '10:00', 'to' => '22:00'],
                'tuesday' => ['from' => '10:00', 'to' => '22:00'],
                'wednesday' => ['from' => '10:00', 'to' => '22:00'],
                'thursday' => ['from' => '10:00', 'to' => '22:00'],
                'friday' => ['from' => '10:00', 'to' => '22:00'],
                'saturday' => ['from' => '12:00', 'to' => '20:00'],
                'sunday' => ['from' => '12:00', 'to' => '20:00'],
            ],
            'photos' => $photos,
            'description' => $this->faker->paragraph(5),
            'address' => $this->faker->randomElement($districts) . ', м. ' . $this->faker->randomElement($metros),
            'phone' => $this->faker->phoneNumber(),
            'contact_method' => $this->faker->randomElement(['phone', 'whatsapp', 'telegram']),
            'geo' => [
                'lat' => $this->faker->latitude(59.8, 60.1),
                'lng' => $this->faker->longitude(30.1, 30.5),
                'district' => $this->faker->randomElement($districts),
                'metro' => $this->faker->randomElement($metros)
            ],
            'status' => $this->faker->randomElement($statuses), // Используем реальные статусы из базы
            'views_count' => $this->faker->numberBetween(0, 500),
            'created_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'updated_at' => now(),
        ];
    }


    /**
     * Indicate that the ad is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the ad is draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the ad is waiting payment.
     */
    public function waitingPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'waiting_payment',
        ]);
    }
}