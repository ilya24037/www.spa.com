<?php

namespace App\Domain\Master\DTOs;

use Illuminate\Http\UploadedFile;

/**
 * DTO для обновления профиля мастера
 */
class UpdateMasterDTO
{
    public function __construct(
        public readonly ?string $display_name = null,
        public readonly ?string $bio = null,
        public readonly ?string $phone = null,
        public readonly ?string $whatsapp = null,
        public readonly ?string $telegram = null,
        public readonly ?bool $show_contacts = null,
        public readonly ?int $experience_years = null,
        public readonly ?string $city = null,
        public readonly ?string $district = null,
        public readonly ?string $metro_station = null,
        public readonly ?bool $home_service = null,
        public readonly ?bool $salon_service = null,
        public readonly ?string $salon_address = null,
        public readonly ?int $age = null,
        public readonly ?int $height = null,
        public readonly ?int $weight = null,
        public readonly ?string $breast_size = null,
        public readonly ?string $hair_color = null,
        public readonly ?string $eye_color = null,
        public readonly ?string $nationality = null,
        public readonly ?array $features = null,
        public readonly ?array $services = null,
        public readonly ?string $services_additional_info = null,
        public readonly ?bool $medical_certificate = null,
        public readonly ?bool $works_during_period = null,
        public readonly ?array $additional_features = null,
        public readonly ?UploadedFile $avatar = null,
        public readonly ?array $certificates = null,
        public readonly ?array $education = null,
        public readonly ?string $meta_title = null,
        public readonly ?string $meta_description = null,
    ) {}

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            display_name: $data['display_name'] ?? null,
            bio: $data['bio'] ?? null,
            phone: $data['phone'] ?? null,
            whatsapp: $data['whatsapp'] ?? null,
            telegram: $data['telegram'] ?? null,
            show_contacts: isset($data['show_contacts']) ? (bool) $data['show_contacts'] : null,
            experience_years: isset($data['experience_years']) ? (int) $data['experience_years'] : null,
            city: $data['city'] ?? null,
            district: $data['district'] ?? null,
            metro_station: $data['metro_station'] ?? null,
            home_service: isset($data['home_service']) ? (bool) $data['home_service'] : null,
            salon_service: isset($data['salon_service']) ? (bool) $data['salon_service'] : null,
            salon_address: $data['salon_address'] ?? null,
            age: isset($data['age']) ? (int) $data['age'] : null,
            height: isset($data['height']) ? (int) $data['height'] : null,
            weight: isset($data['weight']) ? (int) $data['weight'] : null,
            breast_size: $data['breast_size'] ?? null,
            hair_color: $data['hair_color'] ?? null,
            eye_color: $data['eye_color'] ?? null,
            nationality: $data['nationality'] ?? null,
            features: $data['features'] ?? null,
            services: $data['services'] ?? null,
            services_additional_info: $data['services_additional_info'] ?? null,
            medical_certificate: isset($data['medical_certificate']) ? (bool) $data['medical_certificate'] : null,
            works_during_period: isset($data['works_during_period']) ? (bool) $data['works_during_period'] : null,
            additional_features: $data['additional_features'] ?? null,
            avatar: $data['avatar'] ?? null,
            certificates: $data['certificates'] ?? null,
            education: $data['education'] ?? null,
            meta_title: $data['meta_title'] ?? null,
            meta_description: $data['meta_description'] ?? null,
        );
    }

    /**
     * Создать DTO из запроса
     */
    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        $validated = $request->validated();
        
        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar');
        }

        return self::fromArray($validated);
    }

    /**
     * Конвертировать в массив для обновления модели
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->display_name !== null) $data['display_name'] = $this->display_name;
        if ($this->bio !== null) $data['bio'] = $this->bio;
        if ($this->phone !== null) $data['phone'] = $this->phone;
        if ($this->whatsapp !== null) $data['whatsapp'] = $this->whatsapp;
        if ($this->telegram !== null) $data['telegram'] = $this->telegram;
        if ($this->show_contacts !== null) $data['show_contacts'] = $this->show_contacts;
        if ($this->experience_years !== null) $data['experience_years'] = $this->experience_years;
        if ($this->city !== null) $data['city'] = $this->city;
        if ($this->district !== null) $data['district'] = $this->district;
        if ($this->metro_station !== null) $data['metro_station'] = $this->metro_station;
        if ($this->home_service !== null) $data['home_service'] = $this->home_service;
        if ($this->salon_service !== null) $data['salon_service'] = $this->salon_service;
        if ($this->salon_address !== null) $data['salon_address'] = $this->salon_address;
        if ($this->age !== null) $data['age'] = $this->age;
        if ($this->height !== null) $data['height'] = $this->height;
        if ($this->weight !== null) $data['weight'] = $this->weight;
        if ($this->breast_size !== null) $data['breast_size'] = $this->breast_size;
        if ($this->hair_color !== null) $data['hair_color'] = $this->hair_color;
        if ($this->eye_color !== null) $data['eye_color'] = $this->eye_color;
        if ($this->nationality !== null) $data['nationality'] = $this->nationality;
        if ($this->features !== null) $data['features'] = $this->features;
        if ($this->services !== null) $data['services'] = $this->services;
        if ($this->services_additional_info !== null) $data['services_additional_info'] = $this->services_additional_info;
        if ($this->medical_certificate !== null) $data['medical_certificate'] = $this->medical_certificate;
        if ($this->works_during_period !== null) $data['works_during_period'] = $this->works_during_period;
        if ($this->additional_features !== null) $data['additional_features'] = $this->additional_features;
        if ($this->certificates !== null) $data['certificates'] = $this->certificates;
        if ($this->education !== null) $data['education'] = $this->education;
        if ($this->meta_title !== null) $data['meta_title'] = $this->meta_title;
        if ($this->meta_description !== null) $data['meta_description'] = $this->meta_description;

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
     * Валидация данных для обновления
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->display_name !== null && strlen($this->display_name) < 2) {
            $errors['display_name'] = 'Имя должно содержать минимум 2 символа';
        }

        if ($this->bio !== null && strlen($this->bio) < 50) {
            $errors['bio'] = 'Описание должно содержать минимум 50 символов';
        }

        if ($this->phone !== null && !preg_match('/^[+]?[0-9\s\-\(\)]{10,20}$/', $this->phone)) {
            $errors['phone'] = 'Некорректный формат телефона';
        }

        if ($this->experience_years !== null && $this->experience_years < 0) {
            $errors['experience_years'] = 'Опыт работы не может быть отрицательным';
        }

        if ($this->age !== null && ($this->age < 18 || $this->age > 80)) {
            $errors['age'] = 'Возраст должен быть от 18 до 80 лет';
        }

        if ($this->height !== null && ($this->height < 140 || $this->height > 220)) {
            $errors['height'] = 'Рост должен быть от 140 до 220 см';
        }

        if ($this->weight !== null && ($this->weight < 40 || $this->weight > 150)) {
            $errors['weight'] = 'Вес должен быть от 40 до 150 кг';
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

        if ($this->salon_service === true && empty($this->salon_address)) {
            $errors['salon_address'] = 'Для услуг в салоне необходимо указать адрес';
        }

        if ($this->meta_title !== null && strlen($this->meta_title) > 160) {
            $errors['meta_title'] = 'Meta заголовок не должен превышать 160 символов';
        }

        if ($this->meta_description !== null && strlen($this->meta_description) > 300) {
            $errors['meta_description'] = 'Meta описание не должно превышать 300 символов';
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