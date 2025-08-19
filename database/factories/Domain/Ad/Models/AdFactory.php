<?php

namespace Database\Factories\Domain\Ad\Models;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Enums\AdStatus;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика для создания тестовых объявлений
 */
class AdFactory extends Factory
{
    protected $model = Ad::class;

    /**
     * Определение модели по умолчанию
     *
     * @return array
     */
    public function definition()
    {
        $services = [
            'Классический массаж' => $this->faker->numberBetween(2000, 3000),
            'Тайский массаж' => $this->faker->numberBetween(3000, 4000),
            'Спортивный массаж' => $this->faker->numberBetween(2500, 3500),
            'Лечебный массаж' => $this->faker->numberBetween(3000, 4000),
            'Антицеллюлитный' => $this->faker->numberBetween(2500, 3500),
            'СПА программы' => $this->faker->numberBetween(4000, 6000),
        ];
        
        $selectedServices = $this->faker->randomElements($services, $this->faker->numberBetween(2, 4), true);
        
        $districts = ['Центральный', 'Пресненский', 'Тверской', 'Арбат', 'Хамовники', 'Замоскворечье'];
        $metros = ['Арбатская', 'Баррикадная', 'Тверская', 'Смоленская', 'Парк Культуры', 'Новокузнецкая'];
        
        $lat = $this->faker->randomFloat(4, 55.7000, 55.8000);
        $lng = $this->faker->randomFloat(4, 37.5000, 37.7000);
        $district = $this->faker->randomElement($districts);
        
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->randomElement(['Профессиональный массаж', 'СПА услуги', 'Лечебный массаж']),
            'description' => $this->faker->paragraph(3),
            'category' => 'massage',
            'specialty' => $this->faker->randomElement(['classic', 'thai', 'medical', 'spa']),
            'status' => AdStatus::ACTIVE->value,
            'price' => min($selectedServices),
            'price_unit' => $this->faker->randomElement(['за час', 'за услугу', 'за сеанс']),
            'prices' => json_encode($selectedServices),
            'services' => json_encode(array_keys($selectedServices)),
            'address' => $this->faker->streetAddress(),
            'geo' => json_encode([
                'lat' => $lat,
                'lng' => $lng,
                'district' => $district
            ]),
            'phone' => $this->faker->phoneNumber(),
            'photos' => json_encode([
                ['url' => '/images/masters/загруженное.png'],
                ['url' => '/images/masters/загруженное (4).png'],
            ]),
            'schedule' => json_encode([
                'monday' => ['from' => '09:00', 'to' => '21:00'],
                'tuesday' => ['from' => '09:00', 'to' => '21:00'],
                'wednesday' => ['from' => '09:00', 'to' => '21:00'],
                'thursday' => ['from' => '09:00', 'to' => '21:00'],
                'friday' => ['from' => '09:00', 'to' => '21:00'],
                'saturday' => ['from' => '10:00', 'to' => '18:00'],
                'sunday' => ['from' => '10:00', 'to' => '18:00'],
            ]),
            'experience' => $this->faker->numberBetween(1, 15),
            'service_location' => json_encode(['salon', 'home']),
            'work_format' => 'individual',
            'published_at' => now(),
            'moderated_at' => now(),
        ];
    }

    /**
     * Указать, что объявление в статусе черновика
     *
     * @return Factory
     */
    public function draft()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => AdStatus::DRAFT->value,
                'published_at' => null,
                'moderated_at' => null,
            ];
        });
    }

    /**
     * Указать, что объявление архивировано
     *
     * @return Factory
     */
    public function archived()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => AdStatus::ARCHIVED->value,
                'archived_at' => now(),
            ];
        });
    }

    /**
     * Указать, что объявление на модерации
     *
     * @return Factory
     */
    public function moderation()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => AdStatus::MODERATION->value,
                'published_at' => now(),
                'moderated_at' => null,
            ];
        });
    }

    /**
     * Создать объявление с полными данными для главной страницы
     *
     * @return Factory
     */
    public function withFullData()
    {
        return $this->state(function (array $attributes) {
            return [
                'clients' => json_encode(['women', 'men']),
                'features' => json_encode(['parking', 'wifi', 'tea']),
                'height' => $this->faker->numberBetween(160, 180),
                'weight' => $this->faker->numberBetween(50, 70),
                'hair_color' => $this->faker->randomElement(['blonde', 'brunette', 'red']),
                'eye_color' => $this->faker->randomElement(['blue', 'green', 'brown']),
                'nationality' => 'Русская',
                'new_client_discount' => $this->faker->numberBetween(10, 30),
                'gift' => 'Бесплатная консультация',
                'travel_radius' => $this->faker->numberBetween(5, 20),
                'travel_price' => $this->faker->numberBetween(500, 2000),
            ];
        });
    }
}