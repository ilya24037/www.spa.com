<?php

namespace App\Domain\Master\DTOs;

use Illuminate\Http\UploadedFile;

/**
 * DTO для создания профиля мастера
 */
class CreateMasterDTO
{
    public function __construct(
        public readonly int $user_id,
        public readonly string $display_name,
        public readonly string $bio,
        public readonly string $phone,
        public readonly string $city,
        public readonly int $experience_years = 0,
        public readonly bool $home_service = false,
        public readonly bool $salon_service = false,
        public readonly ?string $whatsapp = null,
        public readonly ?string $telegram = null,
        public readonly ?string $district = null,
        public readonly ?string $metro_station = null,
        public readonly ?string $salon_address = null,
        public readonly ?int $age = null,
        public readonly ?array $features = null,
        public readonly ?array $services = null,
        public readonly ?UploadedFile $avatar = null,
        public readonly ?array $certificates = null,
        public readonly ?array $education = null,
    ) {}

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            user_id: $data['user_id'],
            display_name: $data['display_name'],
            bio: $data['bio'],
            phone: $data['phone'],
            whatsapp: $data['whatsapp'] ?? null,
            telegram: $data['telegram'] ?? null,
            experience_years: $data['experience_years'] ?? 0,
            city: $data['city'],
            district: $data['district'] ?? null,
            metro_station: $data['metro_station'] ?? null,
            home_service: $data['home_service'] ?? false,
            salon_service: $data['salon_service'] ?? false,
            salon_address: $data['salon_address'] ?? null,
            age: isset($data['age']) ? (int) $data['age'] : null,
            features: $data['features'] ?? null,
            services: $data['services'] ?? null,
            avatar: $data['avatar'] ?? null,
            certificates: $data['certificates'] ?? null,
            education: $data['education'] ?? null,
        );
    }

    /**
     * Создать DTO из запроса
     */
    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;
        
        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar');
        }

        return self::fromArray($validated);
    }

    /**
     * Валидация данных
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->display_name)) {
            $errors['display_name'] = 'Имя обязательно для заполнения';
        } elseif (strlen($this->display_name) < 2) {
            $errors['display_name'] = 'Имя должно содержать минимум 2 символа';
        }

        if (empty($this->bio)) {
            $errors['bio'] = 'Описание обязательно для заполнения';
        } elseif (strlen($this->bio) < 50) {
            $errors['bio'] = 'Описание должно содержать минимум 50 символов';
        }

        if (empty($this->phone)) {
            $errors['phone'] = 'Телефон обязателен для заполнения';
        } elseif (!preg_match('/^[+]?[0-9\s\-\(\)]{10,20}$/', $this->phone)) {
            $errors['phone'] = 'Некорректный формат телефона';
        }

        if (empty($this->city)) {
            $errors['city'] = 'Город обязателен для заполнения';
        }

        if ($this->experience_years < 0) {
            $errors['experience_years'] = 'Опыт работы не может быть отрицательным';
        }

        if (!$this->home_service && !$this->salon_service) {
            $errors['service_type'] = 'Выберите хотя бы один тип услуг (выезд или салон)';
        }

        if ($this->salon_service && empty($this->salon_address)) {
            $errors['salon_address'] = 'Для услуг в салоне необходимо указать адрес';
        }

        if ($this->age !== null && ($this->age < 18 || $this->age > 80)) {
            $errors['age'] = 'Возраст должен быть от 18 до 80 лет';
        }

        if ($this->avatar) {
            if (!$this->avatar->isValid()) {
                $errors['avatar'] = 'Файл поврежден или недействителен';
            } elseif (!in_array($this->avatar->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg'])) {
                $errors['avatar'] = 'Аватар должен быть в формате JPG, JPEG или PNG';
            } elseif ($this->avatar->getSize() > 5 * 1024 * 1024) { // 5MB
                $errors['avatar'] = 'Размер файла не должен превышать 5MB';
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
     * Конвертировать в массив
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'display_name' => $this->display_name,
            'bio' => $this->bio,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'telegram' => $this->telegram,
            'experience_years' => $this->experience_years,
            'city' => $this->city,
            'district' => $this->district,
            'metro_station' => $this->metro_station,
            'home_service' => $this->home_service,
            'salon_service' => $this->salon_service,
            'salon_address' => $this->salon_address,
            'age' => $this->age,
            'features' => $this->features,
            'services' => $this->services,
            'certificates' => $this->certificates,
            'education' => $this->education,
        ];
    }
}