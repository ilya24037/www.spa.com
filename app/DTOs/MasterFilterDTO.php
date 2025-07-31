<?php

namespace App\DTOs;

use App\Enums\MasterStatus;
use App\Enums\MasterLevel;

/**
 * DTO для фильтрации мастеров
 */
class MasterFilterDTO
{
    public function __construct(
        public readonly ?MasterStatus $status = null,
        public readonly ?array $statuses = null,
        public readonly ?MasterLevel $level = null,
        public readonly ?array $levels = null,
        public readonly ?string $city = null,
        public readonly ?string $district = null,
        public readonly ?string $metro_station = null,
        public readonly ?string $service_type = null, // home, salon, both
        public readonly ?float $min_rating = null,
        public readonly ?int $min_experience = null,
        public readonly ?bool $is_verified = null,
        public readonly ?bool $is_premium = null,
        public readonly ?string $search = null,
        public readonly ?array $services = null,
        public readonly ?int $age_min = null,
        public readonly ?int $age_max = null,
        public readonly ?int $price_min = null,
        public readonly ?int $price_max = null,
        public readonly ?string $hair_color = null,
        public readonly ?string $eye_color = null,
        public readonly ?string $nationality = null,
        public readonly ?array $features = null,
        public readonly ?bool $medical_certificate = null,
        public readonly ?bool $works_during_period = null,
        public readonly ?string $sort_by = null,
        public readonly ?string $sort_order = null,
        public readonly ?int $per_page = null,
        public readonly ?int $page = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?int $radius = null,
    ) {}

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: isset($data['status']) ? MasterStatus::from($data['status']) : null,
            statuses: isset($data['statuses']) ? array_map(fn($s) => MasterStatus::from($s), $data['statuses']) : null,
            level: isset($data['level']) ? MasterLevel::from($data['level']) : null,
            levels: isset($data['levels']) ? array_map(fn($l) => MasterLevel::from($l), $data['levels']) : null,
            city: $data['city'] ?? null,
            district: $data['district'] ?? null,
            metro_station: $data['metro_station'] ?? null,
            service_type: $data['service_type'] ?? null,
            min_rating: isset($data['min_rating']) ? (float) $data['min_rating'] : null,
            min_experience: isset($data['min_experience']) ? (int) $data['min_experience'] : null,
            is_verified: isset($data['is_verified']) ? (bool) $data['is_verified'] : null,
            is_premium: isset($data['is_premium']) ? (bool) $data['is_premium'] : null,
            search: $data['search'] ?? null,
            services: $data['services'] ?? null,
            age_min: isset($data['age_min']) ? (int) $data['age_min'] : null,
            age_max: isset($data['age_max']) ? (int) $data['age_max'] : null,
            price_min: isset($data['price_min']) ? (int) $data['price_min'] : null,
            price_max: isset($data['price_max']) ? (int) $data['price_max'] : null,
            hair_color: $data['hair_color'] ?? null,
            eye_color: $data['eye_color'] ?? null,
            nationality: $data['nationality'] ?? null,
            features: $data['features'] ?? null,
            medical_certificate: isset($data['medical_certificate']) ? (bool) $data['medical_certificate'] : null,
            works_during_period: isset($data['works_during_period']) ? (bool) $data['works_during_period'] : null,
            sort_by: $data['sort_by'] ?? 'rating',
            sort_order: $data['sort_order'] ?? 'desc',
            per_page: isset($data['per_page']) ? (int) $data['per_page'] : 20,
            page: isset($data['page']) ? (int) $data['page'] : 1,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            radius: isset($data['radius']) ? (int) $data['radius'] : null,
        );
    }

    /**
     * Создать DTO из запроса
     */
    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return self::fromArray($request->all());
    }

    /**
     * Конвертировать в массив для фильтрации
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->status !== null) $data['status'] = $this->status;
        if ($this->statuses !== null) $data['status'] = $this->statuses;
        if ($this->level !== null) $data['level'] = $this->level;
        if ($this->levels !== null) $data['level'] = $this->levels;
        if ($this->city !== null) $data['city'] = $this->city;
        if ($this->district !== null) $data['district'] = $this->district;
        if ($this->metro_station !== null) $data['metro_station'] = $this->metro_station;
        if ($this->service_type !== null) $data['service_type'] = $this->service_type;
        if ($this->min_rating !== null) $data['min_rating'] = $this->min_rating;
        if ($this->min_experience !== null) $data['min_experience'] = $this->min_experience;
        if ($this->is_verified !== null) $data['is_verified'] = $this->is_verified;
        if ($this->is_premium !== null) $data['is_premium'] = $this->is_premium;
        if ($this->search !== null) $data['search'] = $this->search;
        if ($this->services !== null) $data['services'] = $this->services;
        if ($this->age_min !== null) $data['age_min'] = $this->age_min;
        if ($this->age_max !== null) $data['age_max'] = $this->age_max;
        if ($this->price_min !== null) $data['price_min'] = $this->price_min;
        if ($this->price_max !== null) $data['price_max'] = $this->price_max;
        if ($this->hair_color !== null) $data['hair_color'] = $this->hair_color;
        if ($this->eye_color !== null) $data['eye_color'] = $this->eye_color;
        if ($this->nationality !== null) $data['nationality'] = $this->nationality;
        if ($this->features !== null) $data['features'] = $this->features;
        if ($this->medical_certificate !== null) $data['medical_certificate'] = $this->medical_certificate;
        if ($this->works_during_period !== null) $data['works_during_period'] = $this->works_during_period;
        if ($this->sort_by !== null) $data['sort_by'] = $this->sort_by;
        if ($this->sort_order !== null) $data['sort_order'] = $this->sort_order;

        return $data;
    }

    /**
     * Получить параметры пагинации
     */
    public function getPaginationParams(): array
    {
        return [
            'per_page' => $this->per_page ?? 20,
            'page' => $this->page ?? 1,
        ];
    }

    /**
     * Проверить есть ли фильтры
     */
    public function hasFilters(): bool
    {
        $filters = $this->toArray();
        unset($filters['sort_by'], $filters['sort_order']);
        return !empty($filters);
    }

    /**
     * Проверить есть ли гео-фильтры
     */
    public function hasGeoFilter(): bool
    {
        return $this->latitude !== null && $this->longitude !== null && $this->radius !== null;
    }

    /**
     * Создать фильтр для активных мастеров
     */
    public static function active(): self
    {
        return new self(
            status: MasterStatus::ACTIVE,
            sort_by: 'rating',
            sort_order: 'desc',
        );
    }

    /**
     * Создать фильтр для премиум мастеров
     */
    public static function premium(): self
    {
        return new self(
            status: MasterStatus::ACTIVE,
            is_premium: true,
            sort_by: 'rating',
            sort_order: 'desc',
        );
    }

    /**
     * Создать фильтр для топ мастеров
     */
    public static function top(): self
    {
        return new self(
            status: MasterStatus::ACTIVE,
            min_rating: 4.5,
            sort_by: 'rating',
            sort_order: 'desc',
        );
    }

    /**
     * Создать фильтр для новых мастеров
     */
    public static function new(): self
    {
        return new self(
            status: MasterStatus::ACTIVE,
            sort_by: 'created_at',
            sort_order: 'desc',
        );
    }

    /**
     * Валидация параметров фильтрации
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->min_rating !== null && ($this->min_rating < 0 || $this->min_rating > 5)) {
            $errors['min_rating'] = 'Рейтинг должен быть от 0 до 5';
        }

        if ($this->min_experience !== null && $this->min_experience < 0) {
            $errors['min_experience'] = 'Опыт не может быть отрицательным';
        }

        if ($this->age_min !== null && $this->age_min < 18) {
            $errors['age_min'] = 'Минимальный возраст не может быть меньше 18';
        }

        if ($this->age_max !== null && $this->age_max > 80) {
            $errors['age_max'] = 'Максимальный возраст не может быть больше 80';
        }

        if ($this->age_min !== null && $this->age_max !== null && $this->age_min > $this->age_max) {
            $errors['age_range'] = 'Минимальный возраст не может быть больше максимального';
        }

        if ($this->price_min !== null && $this->price_min < 0) {
            $errors['price_min'] = 'Минимальная цена не может быть отрицательной';
        }

        if ($this->price_max !== null && $this->price_max < 0) {
            $errors['price_max'] = 'Максимальная цена не может быть отрицательной';
        }

        if ($this->price_min !== null && $this->price_max !== null && $this->price_min > $this->price_max) {
            $errors['price_range'] = 'Минимальная цена не может быть больше максимальной';
        }

        if ($this->service_type !== null && !in_array($this->service_type, ['home', 'salon', 'both'])) {
            $errors['service_type'] = 'Некорректный тип услуг';
        }

        if ($this->latitude !== null && ($this->latitude < -90 || $this->latitude > 90)) {
            $errors['latitude'] = 'Широта должна быть от -90 до 90';
        }

        if ($this->longitude !== null && ($this->longitude < -180 || $this->longitude > 180)) {
            $errors['longitude'] = 'Долгота должна быть от -180 до 180';
        }

        if ($this->radius !== null && ($this->radius < 1 || $this->radius > 100)) {
            $errors['radius'] = 'Радиус должен быть от 1 до 100 км';
        }

        if ($this->per_page !== null && ($this->per_page < 1 || $this->per_page > 100)) {
            $errors['per_page'] = 'Количество элементов на странице должно быть от 1 до 100';
        }

        if ($this->sort_by !== null && !in_array($this->sort_by, ['rating', 'experience', 'price', 'created_at', 'popularity'])) {
            $errors['sort_by'] = 'Некорректное поле для сортировки';
        }

        if ($this->sort_order !== null && !in_array($this->sort_order, ['asc', 'desc'])) {
            $errors['sort_order'] = 'Порядок сортировки может быть только asc или desc';
        }

        return $errors;
    }

    /**
     * Проверить валидность DTO
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}