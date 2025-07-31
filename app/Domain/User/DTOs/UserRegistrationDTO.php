<?php

namespace App\Domain\User\DTOs;

use App\Enums\UserRole;

/**
 * DTO для регистрации пользователя
 */
class UserRegistrationDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $name = null,
        public readonly ?string $phone = null,
        public readonly UserRole $role = UserRole::CLIENT,
    ) {}

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
            name: $data['name'] ?? null,
            phone: $data['phone'] ?? null,
            role: isset($data['role']) ? UserRole::from($data['role']) : UserRole::CLIENT,
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
            'email' => $this->email,
            'password' => $this->password,
            'name' => $this->name,
            'phone' => $this->phone,
            'role' => $this->role,
        ];
    }

    /**
     * Валидация данных
     */
    public function validate(): array
    {
        $errors = [];

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Некорректный email адрес';
        }

        if (strlen($this->password) < 6) {
            $errors['password'] = 'Пароль должен содержать минимум 6 символов';
        }

        if ($this->name && strlen($this->name) < 2) {
            $errors['name'] = 'Имя должно содержать минимум 2 символа';
        }

        if ($this->phone && !preg_match('/^[+]?[0-9\s\-\(\)]{10,20}$/', $this->phone)) {
            $errors['phone'] = 'Некорректный формат номера телефона';
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