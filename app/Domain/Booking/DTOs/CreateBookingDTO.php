<?php

namespace App\Domain\Booking\DTOs;

use App\Enums\BookingType;
use Carbon\Carbon;

/**
 * DTO для создания бронирования
 */
class CreateBookingDTO
{
    public function __construct(
        public readonly int $client_id,
        public readonly int $master_id,
        public readonly int $service_id,
        public readonly BookingType $type,
        public readonly Carbon $start_time,
        public readonly int $duration_minutes,
        public readonly string $client_name,
        public readonly string $client_phone,
        public readonly ?string $client_email = null,
        public readonly ?string $client_address = null,
        public readonly ?string $master_address = null,
        public readonly ?string $master_phone = null,
        public readonly ?string $notes = null,
        public readonly ?string $internal_notes = null,
        public readonly ?array $equipment_required = null,
        public readonly ?string $platform = null,
        public readonly ?string $meeting_link = null,
        public readonly ?array $services_list = null,
        public readonly ?float $delivery_fee = null,
        public readonly ?array $metadata = null,
        public readonly ?string $source = null,
        public readonly bool $create_slots = false,
    ) {}

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            client_id: $data['client_id'],
            master_id: $data['master_id'],
            service_id: $data['service_id'],
            type: BookingType::from($data['type'] ?? BookingType::INCALL->value),
            start_time: Carbon::parse($data['start_time']),
            duration_minutes: $data['duration_minutes'] ?? 90,
            client_name: $data['client_name'],
            client_phone: $data['client_phone'],
            client_email: $data['client_email'] ?? null,
            client_address: $data['client_address'] ?? null,
            master_address: $data['master_address'] ?? null,
            master_phone: $data['master_phone'] ?? null,
            notes: $data['notes'] ?? null,
            internal_notes: $data['internal_notes'] ?? null,
            equipment_required: $data['equipment_required'] ?? null,
            platform: $data['platform'] ?? null,
            meeting_link: $data['meeting_link'] ?? null,
            services_list: $data['services_list'] ?? null,
            delivery_fee: isset($data['delivery_fee']) ? (float) $data['delivery_fee'] : null,
            metadata: $data['metadata'] ?? null,
            source: $data['source'] ?? null,
            create_slots: $data['create_slots'] ?? false,
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
     * Конвертировать в массив
     */
    public function toArray(): array
    {
        return [
            'client_id' => $this->client_id,
            'master_id' => $this->master_id,
            'service_id' => $this->service_id,
            'type' => $this->type->value,
            'start_time' => $this->start_time->toISOString(),
            'duration_minutes' => $this->duration_minutes,
            'client_name' => $this->client_name,
            'client_phone' => $this->client_phone,
            'client_email' => $this->client_email,
            'client_address' => $this->client_address,
            'master_address' => $this->master_address,
            'master_phone' => $this->master_phone,
            'notes' => $this->notes,
            'internal_notes' => $this->internal_notes,
            'equipment_required' => $this->equipment_required,
            'platform' => $this->platform,
            'meeting_link' => $this->meeting_link,
            'services_list' => $this->services_list,
            'delivery_fee' => $this->delivery_fee,
            'metadata' => $this->metadata,
            'source' => $this->source,
            'create_slots' => $this->create_slots,
        ];
    }

    /**
     * Валидация данных
     */
    public function validate(): array
    {
        $errors = [];

        // Базовая валидация
        if ($this->client_id <= 0) {
            $errors['client_id'] = 'Некорректный ID клиента';
        }

        if ($this->master_id <= 0) {
            $errors['master_id'] = 'Некорректный ID мастера';
        }

        if ($this->service_id <= 0) {
            $errors['service_id'] = 'Некорректный ID услуги';
        }

        if ($this->duration_minutes <= 0) {
            $errors['duration_minutes'] = 'Продолжительность должна быть больше 0';
        }

        if ($this->start_time->isPast()) {
            $errors['start_time'] = 'Время начала не может быть в прошлом';
        }

        if (empty($this->client_name)) {
            $errors['client_name'] = 'Имя клиента обязательно';
        }

        if (empty($this->client_phone)) {
            $errors['client_phone'] = 'Телефон клиента обязателен';
        } elseif (!preg_match('/^[+]?[0-9\s\-\(\)]{10,20}$/', $this->client_phone)) {
            $errors['client_phone'] = 'Некорректный формат телефона';
        }

        if ($this->client_email && !filter_var($this->client_email, FILTER_VALIDATE_EMAIL)) {
            $errors['client_email'] = 'Некорректный email';
        }

        // Валидация специфичных полей по типу
        $errors = array_merge($errors, $this->validateTypeSpecificFields());

        return $errors;
    }

    /**
     * Валидация полей специфичных для типа
     */
    protected function validateTypeSpecificFields(): array
    {
        $errors = [];

        switch ($this->type) {
            case BookingType::OUTCALL:
                if (empty($this->client_address)) {
                    $errors['client_address'] = 'Для выезда необходимо указать адрес клиента';
                }
                if ($this->equipment_required === null) {
                    $errors['equipment_required'] = 'Для выезда необходимо указать требуемое оборудование';
                }
                break;

            case BookingType::INCALL:
                if (empty($this->master_address)) {
                    $errors['master_address'] = 'Для приема в салоне необходимо указать адрес мастера';
                }
                break;

            case BookingType::ONLINE:
                if (empty($this->platform)) {
                    $errors['platform'] = 'Для онлайн консультации необходимо указать платформу';
                }
                if ($this->platform && !in_array($this->platform, ['zoom', 'skype', 'whatsapp', 'telegram', 'google_meet'])) {
                    $errors['platform'] = 'Неподдерживаемая платформа';
                }
                if ($this->duration_minutes > 120) {
                    $errors['duration_minutes'] = 'Максимальная продолжительность онлайн консультации: 2 часа';
                }
                break;

            case BookingType::PACKAGE:
                if (empty($this->services_list) || !is_array($this->services_list)) {
                    $errors['services_list'] = 'Для пакета услуг необходимо указать список услуг';
                } else {
                    $errors = array_merge($errors, $this->validateServicesList());
                }
                break;
        }

        return $errors;
    }

    /**
     * Валидация списка услуг для пакета
     */
    protected function validateServicesList(): array
    {
        $errors = [];

        foreach ($this->services_list as $index => $service) {
            if (empty($service['service_id'])) {
                $errors["services_list.{$index}.service_id"] = 'ID услуги обязателен';
            }

            if (!isset($service['quantity']) || $service['quantity'] <= 0) {
                $errors["services_list.{$index}.quantity"] = 'Количество должно быть больше 0';
            }

            if (isset($service['price']) && $service['price'] < 0) {
                $errors["services_list.{$index}.price"] = 'Цена не может быть отрицательной';
            }

            if (isset($service['duration']) && $service['duration'] <= 0) {
                $errors["services_list.{$index}.duration"] = 'Продолжительность должна быть больше 0';
            }
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

    /**
     * Получить конечное время бронирования
     */
    public function getEndTime(): Carbon
    {
        return $this->start_time->copy()->addMinutes($this->duration_minutes);
    }

    /**
     * Получить дату бронирования
     */
    public function getBookingDate(): Carbon
    {
        return $this->start_time->startOfDay();
    }

    /**
     * Проверить является ли бронирование на сегодня
     */
    public function isToday(): bool
    {
        return $this->start_time->isToday();
    }

    /**
     * Получить продолжительность в часах
     */
    public function getDurationHours(): float
    {
        return $this->duration_minutes / 60;
    }

    /**
     * Проверить требует ли тип предоплаты
     */
    public function requiresDeposit(): bool
    {
        return $this->type->supportsPrepayment();
    }

    /**
     * Проверить требует ли тип адрес
     */
    public function requiresAddress(): bool
    {
        return $this->type->requiresAddress();
    }

    /**
     * Получить описание для логов
     */
    public function getLogDescription(): string
    {
        return "Booking {$this->type->getLabel()} for client {$this->client_name} " .
               "with master {$this->master_id} on {$this->start_time->format('d.m.Y H:i')} " .
               "({$this->duration_minutes} min)";
    }
}