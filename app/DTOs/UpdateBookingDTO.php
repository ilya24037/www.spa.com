<?php

namespace App\DTOs;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use Carbon\Carbon;

/**
 * DTO для обновления бронирования
 */
class UpdateBookingDTO
{
    public function __construct(
        public readonly ?BookingType $type = null,
        public readonly ?BookingStatus $status = null,
        public readonly ?Carbon $start_time = null,
        public readonly ?Carbon $end_time = null,
        public readonly ?int $duration_minutes = null,
        public readonly ?string $client_name = null,
        public readonly ?string $client_phone = null,
        public readonly ?string $client_email = null,
        public readonly ?string $client_address = null,
        public readonly ?string $master_address = null,
        public readonly ?string $master_phone = null,
        public readonly ?string $notes = null,
        public readonly ?string $internal_notes = null,
        public readonly ?array $equipment_required = null,
        public readonly ?string $platform = null,
        public readonly ?string $meeting_link = null,
        public readonly ?float $base_price = null,
        public readonly ?float $service_price = null,
        public readonly ?float $delivery_fee = null,
        public readonly ?float $discount_amount = null,
        public readonly ?float $total_price = null,
        public readonly ?float $deposit_amount = null,
        public readonly ?string $payment_method = null,
        public readonly ?string $payment_status = null,
        public readonly ?array $metadata = null,
    ) {}

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: isset($data['type']) ? BookingType::from($data['type']) : null,
            status: isset($data['status']) ? BookingStatus::from($data['status']) : null,
            start_time: isset($data['start_time']) ? Carbon::parse($data['start_time']) : null,
            end_time: isset($data['end_time']) ? Carbon::parse($data['end_time']) : null,
            duration_minutes: isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : null,
            client_name: $data['client_name'] ?? null,
            client_phone: $data['client_phone'] ?? null,
            client_email: $data['client_email'] ?? null,
            client_address: $data['client_address'] ?? null,
            master_address: $data['master_address'] ?? null,
            master_phone: $data['master_phone'] ?? null,
            notes: $data['notes'] ?? null,
            internal_notes: $data['internal_notes'] ?? null,
            equipment_required: $data['equipment_required'] ?? null,
            platform: $data['platform'] ?? null,
            meeting_link: $data['meeting_link'] ?? null,
            base_price: isset($data['base_price']) ? (float) $data['base_price'] : null,
            service_price: isset($data['service_price']) ? (float) $data['service_price'] : null,
            delivery_fee: isset($data['delivery_fee']) ? (float) $data['delivery_fee'] : null,
            discount_amount: isset($data['discount_amount']) ? (float) $data['discount_amount'] : null,
            total_price: isset($data['total_price']) ? (float) $data['total_price'] : null,
            deposit_amount: isset($data['deposit_amount']) ? (float) $data['deposit_amount'] : null,
            payment_method: $data['payment_method'] ?? null,
            payment_status: $data['payment_status'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Создать DTO из запроса
     */
    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return self::fromArray($request->validated());
    }

    /**
     * Конвертировать в массив для обновления модели
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->type !== null) $data['type'] = $this->type;
        if ($this->status !== null) $data['status'] = $this->status;
        if ($this->start_time !== null) $data['start_time'] = $this->start_time;
        if ($this->end_time !== null) $data['end_time'] = $this->end_time;
        if ($this->duration_minutes !== null) $data['duration_minutes'] = $this->duration_minutes;
        if ($this->client_name !== null) $data['client_name'] = $this->client_name;
        if ($this->client_phone !== null) $data['client_phone'] = $this->client_phone;
        if ($this->client_email !== null) $data['client_email'] = $this->client_email;
        if ($this->client_address !== null) $data['client_address'] = $this->client_address;
        if ($this->master_address !== null) $data['master_address'] = $this->master_address;
        if ($this->master_phone !== null) $data['master_phone'] = $this->master_phone;
        if ($this->notes !== null) $data['notes'] = $this->notes;
        if ($this->internal_notes !== null) $data['internal_notes'] = $this->internal_notes;
        if ($this->equipment_required !== null) $data['equipment_required'] = $this->equipment_required;
        if ($this->platform !== null) $data['platform'] = $this->platform;
        if ($this->meeting_link !== null) $data['meeting_link'] = $this->meeting_link;
        if ($this->base_price !== null) $data['base_price'] = $this->base_price;
        if ($this->service_price !== null) $data['service_price'] = $this->service_price;
        if ($this->delivery_fee !== null) $data['delivery_fee'] = $this->delivery_fee;
        if ($this->discount_amount !== null) $data['discount_amount'] = $this->discount_amount;
        if ($this->total_price !== null) $data['total_price'] = $this->total_price;
        if ($this->deposit_amount !== null) $data['deposit_amount'] = $this->deposit_amount;
        if ($this->payment_method !== null) $data['payment_method'] = $this->payment_method;
        if ($this->payment_status !== null) $data['payment_status'] = $this->payment_status;
        if ($this->metadata !== null) $data['metadata'] = $this->metadata;

        return $data;
    }

    /**
     * Проверить есть ли данные для обновления
     */
    public function hasUpdates(): bool
    {
        return !empty($this->toArray());
    }

    /**
     * Получить только заполненные поля
     */
    public function getFilledFields(): array
    {
        $data = $this->toArray();
        return array_keys($data);
    }

    /**
     * Валидация данных для обновления
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->duration_minutes !== null && $this->duration_minutes <= 0) {
            $errors['duration_minutes'] = 'Продолжительность должна быть больше 0';
        }

        if ($this->start_time !== null && $this->start_time->isPast()) {
            $errors['start_time'] = 'Время начала не может быть в прошлом';
        }

        if ($this->client_phone !== null && !preg_match('/^[+]?[0-9\s\-\(\)]{10,20}$/', $this->client_phone)) {
            $errors['client_phone'] = 'Некорректный формат телефона';
        }

        if ($this->client_email !== null && !filter_var($this->client_email, FILTER_VALIDATE_EMAIL)) {
            $errors['client_email'] = 'Некорректный email';
        }

        if ($this->base_price !== null && $this->base_price < 0) {
            $errors['base_price'] = 'Базовая цена не может быть отрицательной';
        }

        if ($this->service_price !== null && $this->service_price < 0) {
            $errors['service_price'] = 'Цена услуги не может быть отрицательной';
        }

        if ($this->delivery_fee !== null && $this->delivery_fee < 0) {
            $errors['delivery_fee'] = 'Плата за доставку не может быть отрицательной';
        }

        if ($this->discount_amount !== null && $this->discount_amount < 0) {
            $errors['discount_amount'] = 'Размер скидки не может быть отрицательным';
        }

        if ($this->total_price !== null && $this->total_price < 0) {
            $errors['total_price'] = 'Общая стоимость не может быть отрицательной';
        }

        if ($this->platform !== null && !in_array($this->platform, ['zoom', 'skype', 'whatsapp', 'telegram', 'google_meet'])) {
            $errors['platform'] = 'Неподдерживаемая платформа';
        }

        if ($this->payment_method !== null && !in_array($this->payment_method, ['cash', 'card', 'online', 'transfer'])) {
            $errors['payment_method'] = 'Неподдерживаемый способ оплаты';
        }

        if ($this->payment_status !== null && !in_array($this->payment_status, ['pending', 'paid', 'partial', 'failed', 'refunded'])) {
            $errors['payment_status'] = 'Некорректный статус оплаты';
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