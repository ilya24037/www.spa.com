<?php

namespace App\Domain\Booking\DTOs;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use Carbon\Carbon;

/**
 * DTO для фильтрации бронирований
 */
class BookingFilterDTO
{
    public function __construct(
        public readonly ?int $client_id = null,
        public readonly ?int $master_id = null,
        public readonly ?int $service_id = null,
        public readonly ?BookingStatus $status = null,
        public readonly ?array $statuses = null,
        public readonly ?BookingType $type = null,
        public readonly ?array $types = null,
        public readonly ?Carbon $date_from = null,
        public readonly ?Carbon $date_to = null,
        public readonly ?Carbon $date = null,
        public readonly ?float $price_min = null,
        public readonly ?float $price_max = null,
        public readonly ?string $payment_method = null,
        public readonly ?string $payment_status = null,
        public readonly ?string $search = null,
        public readonly ?string $city = null,
        public readonly ?bool $with_reviews = null,
        public readonly ?bool $active = null,
        public readonly ?bool $upcoming = null,
        public readonly ?bool $past = null,
        public readonly ?bool $completed = null,
        public readonly ?bool $cancelled = null,
        public readonly ?string $sort_by = null,
        public readonly ?string $sort_order = null,
        public readonly ?int $per_page = null,
        public readonly ?int $page = null,
    ) {}

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            client_id: isset($data['client_id']) ? (int) $data['client_id'] : null,
            master_id: isset($data['master_id']) ? (int) $data['master_id'] : null,
            service_id: isset($data['service_id']) ? (int) $data['service_id'] : null,
            status: isset($data['status']) ? BookingStatus::from($data['status']) : null,
            statuses: isset($data['statuses']) ? array_map(fn($s) => BookingStatus::from($s), $data['statuses']) : null,
            type: isset($data['type']) ? BookingType::from($data['type']) : null,
            types: isset($data['types']) ? array_map(fn($t) => BookingType::from($t), $data['types']) : null,
            date_from: isset($data['date_from']) ? Carbon::parse($data['date_from']) : null,
            date_to: isset($data['date_to']) ? Carbon::parse($data['date_to']) : null,
            date: isset($data['date']) ? Carbon::parse($data['date']) : null,
            price_min: isset($data['price_min']) ? (float) $data['price_min'] : null,
            price_max: isset($data['price_max']) ? (float) $data['price_max'] : null,
            payment_method: $data['payment_method'] ?? null,
            payment_status: $data['payment_status'] ?? null,
            search: $data['search'] ?? null,
            city: $data['city'] ?? null,
            with_reviews: isset($data['with_reviews']) ? (bool) $data['with_reviews'] : null,
            active: isset($data['active']) ? (bool) $data['active'] : null,
            upcoming: isset($data['upcoming']) ? (bool) $data['upcoming'] : null,
            past: isset($data['past']) ? (bool) $data['past'] : null,
            completed: isset($data['completed']) ? (bool) $data['completed'] : null,
            cancelled: isset($data['cancelled']) ? (bool) $data['cancelled'] : null,
            sort_by: $data['sort_by'] ?? 'start_time',
            sort_order: $data['sort_order'] ?? 'desc',
            per_page: isset($data['per_page']) ? (int) $data['per_page'] : 15,
            page: isset($data['page']) ? (int) $data['page'] : 1,
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

        if ($this->client_id !== null) $data['client_id'] = $this->client_id;
        if ($this->master_id !== null) $data['master_id'] = $this->master_id;
        if ($this->service_id !== null) $data['service_id'] = $this->service_id;
        if ($this->status !== null) $data['status'] = $this->status;
        if ($this->statuses !== null) $data['status'] = $this->statuses;
        if ($this->type !== null) $data['type'] = $this->type;
        if ($this->types !== null) $data['type'] = $this->types;
        if ($this->date_from !== null) $data['date_from'] = $this->date_from;
        if ($this->date_to !== null) $data['date_to'] = $this->date_to;
        if ($this->date !== null) $data['date'] = $this->date;
        if ($this->price_min !== null) $data['price_min'] = $this->price_min;
        if ($this->price_max !== null) $data['price_max'] = $this->price_max;
        if ($this->payment_method !== null) $data['payment_method'] = $this->payment_method;
        if ($this->payment_status !== null) $data['payment_status'] = $this->payment_status;
        if ($this->search !== null) $data['search'] = $this->search;
        if ($this->city !== null) $data['city'] = $this->city;
        if ($this->with_reviews !== null) $data['with_reviews'] = $this->with_reviews;
        if ($this->active !== null) $data['active'] = $this->active;
        if ($this->upcoming !== null) $data['upcoming'] = $this->upcoming;
        if ($this->past !== null) $data['past'] = $this->past;
        if ($this->completed !== null) $data['completed'] = $this->completed;
        if ($this->cancelled !== null) $data['cancelled'] = $this->cancelled;
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
            'per_page' => $this->per_page ?? 15,
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
     * Получить только активные фильтры
     */
    public function getActiveFilters(): array
    {
        return array_filter($this->toArray(), fn($value) => $value !== null);
    }

    /**
     * Проверить применен ли поиск по тексту
     */
    public function hasTextSearch(): bool
    {
        return !empty($this->search);
    }

    /**
     * Проверить применен ли фильтр по дате
     */
    public function hasDateFilter(): bool
    {
        return $this->date !== null || $this->date_from !== null || $this->date_to !== null;
    }

    /**
     * Проверить применен ли фильтр по цене
     */
    public function hasPriceFilter(): bool
    {
        return $this->price_min !== null || $this->price_max !== null;
    }

    /**
     * Получить период фильтрации
     */
    public function getDateRange(): ?array
    {
        if ($this->date !== null) {
            return [
                'from' => $this->date->startOfDay(),
                'to' => $this->date->endOfDay(),
            ];
        }

        if ($this->date_from !== null || $this->date_to !== null) {
            return [
                'from' => $this->date_from?->startOfDay(),
                'to' => $this->date_to?->endOfDay(),
            ];
        }

        return null;
    }

    /**
     * Получить ценовой диапазон
     */
    public function getPriceRange(): ?array
    {
        if ($this->price_min !== null || $this->price_max !== null) {
            return [
                'min' => $this->price_min,
                'max' => $this->price_max,
            ];
        }

        return null;
    }

    /**
     * Создать фильтр для сегодняшних бронирований
     */
    public static function today(): self
    {
        return new self(
            date: now()->startOfDay(),
            active: true,
        );
    }

    /**
     * Создать фильтр для предстоящих бронирований
     */
    public static function upcoming(): self
    {
        return new self(
            upcoming: true,
            active: true,
        );
    }

    /**
     * Создать фильтр для завершенных бронирований
     */
    public static function completed(): self
    {
        return new self(
            completed: true,
        );
    }

    /**
     * Создать фильтр для отмененных бронирований
     */
    public static function cancelled(): self
    {
        return new self(
            cancelled: true,
        );
    }

    /**
     * Создать фильтр для клиента
     */
    public static function forClient(int $clientId): self
    {
        return new self(
            client_id: $clientId,
            sort_by: 'start_time',
            sort_order: 'desc',
        );
    }

    /**
     * Создать фильтр для мастера
     */
    public static function forMaster(int $masterId): self
    {
        return new self(
            master_id: $masterId,
            sort_by: 'start_time',
            sort_order: 'asc',
        );
    }

    /**
     * Создать фильтр для услуги
     */
    public static function forService(int $serviceId): self
    {
        return new self(
            service_id: $serviceId,
            sort_by: 'start_time',
            sort_order: 'desc',
        );
    }

    /**
     * Создать фильтр по периоду
     */
    public static function forPeriod(Carbon $from, Carbon $to): self
    {
        return new self(
            date_from: $from,
            date_to: $to,
            sort_by: 'start_time',
            sort_order: 'asc',
        );
    }

    /**
     * Валидация параметров фильтрации
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->client_id !== null && $this->client_id <= 0) {
            $errors['client_id'] = 'Некорректный ID клиента';
        }

        if ($this->master_id !== null && $this->master_id <= 0) {
            $errors['master_id'] = 'Некорректный ID мастера';
        }

        if ($this->service_id !== null && $this->service_id <= 0) {
            $errors['service_id'] = 'Некорректный ID услуги';
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

        if ($this->date_from !== null && $this->date_to !== null && $this->date_from->gt($this->date_to)) {
            $errors['date_range'] = 'Дата начала не может быть позже даты окончания';
        }

        if ($this->per_page !== null && ($this->per_page < 1 || $this->per_page > 100)) {
            $errors['per_page'] = 'Количество элементов на странице должно быть от 1 до 100';
        }

        if ($this->page !== null && $this->page < 1) {
            $errors['page'] = 'Номер страницы должен быть больше 0';
        }

        if ($this->sort_by !== null && !in_array($this->sort_by, ['start_time', 'created_at', 'total_price', 'status'])) {
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