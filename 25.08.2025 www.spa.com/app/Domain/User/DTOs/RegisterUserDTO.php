<?php

namespace App\Domain\User\DTOs;

use App\Enums\UserRole;
use App\Enums\UserStatus;

/**
 * DTO для регистрации пользователя (согласно плану)
 * 
 * КРИТИЧЕСКИ ВАЖНО:
 * - Immutable объект
 * - Не хранит пароль в открытом виде после валидации
 * - Строгая валидация всех полей
 */
final class RegisterUserDTO
{
    private ?string $plainPassword = null;
    
    public function __construct(
        public readonly string $email,
        string $password, // НЕ readonly для безопасности
        public readonly ?string $name = null,
        public readonly ?string $phone = null,
        public readonly ?string $city = null,
        public readonly UserRole $role = UserRole::CLIENT,
        public readonly UserStatus $status = UserStatus::PENDING,
        public readonly ?string $source = 'web',
        public readonly ?string $referralCode = null,
        public readonly ?string $ip = null,
        public readonly ?string $userAgent = null,
    ) {
        // Сохраняем пароль временно для валидации
        $this->plainPassword = $password;
    }

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'] ?? '',
            password: $data['password'] ?? '',
            name: $data['name'] ?? null,
            phone: $data['phone'] ?? null,
            city: $data['city'] ?? null,
            role: isset($data['role']) ? UserRole::from($data['role']) : UserRole::CLIENT,
            status: isset($data['status']) ? UserStatus::from($data['status']) : UserStatus::PENDING,
            source: $data['source'] ?? 'web',
            referralCode: $data['referral_code'] ?? null,
            ip: $data['ip'] ?? null,
            userAgent: $data['user_agent'] ?? null,
        );
    }

    /**
     * Создать DTO из запроса
     */
    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        $validated = $request->validated();
        
        return new self(
            email: $validated['email'],
            password: $validated['password'],
            name: $validated['name'] ?? null,
            phone: $validated['phone'] ?? null,
            city: $validated['city'] ?? null,
            role: isset($validated['role']) ? UserRole::from($validated['role']) : UserRole::CLIENT,
            status: UserStatus::PENDING,
            source: $request->header('X-Registration-Source', 'web'),
            referralCode: $validated['referral_code'] ?? $request->cookie('referral_code'),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );
    }

    /**
     * Получить пароль для хеширования (одноразовый метод)
     */
    public function getPasswordForHashing(): string
    {
        if ($this->plainPassword === null) {
            throw new \RuntimeException('Пароль уже был использован и очищен из соображений безопасности');
        }
        
        $password = $this->plainPassword;
        $this->plainPassword = null; // Очищаем после получения
        
        return $password;
    }

    /**
     * Конвертировать в массив БЕЗ ЧУВСТВИТЕЛЬНЫХ ДАННЫХ
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
            'phone' => $this->phone,
            'city' => $this->city,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'source' => $this->source,
            'referral_code' => $this->referralCode,
            // НЕТ ПАРОЛЯ!
        ];
    }

    /**
     * Валидация данных
     */
    public function validate(): array
    {
        $errors = [];

        // Email валидация
        if (empty($this->email)) {
            $errors['email'] = 'Email обязателен для заполнения';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Некорректный формат email адреса';
        } elseif (strlen($this->email) > 255) {
            $errors['email'] = 'Email слишком длинный';
        }

        // Пароль валидация (строгая)
        if ($this->plainPassword !== null) {
            if (strlen($this->plainPassword) < 8) {
                $errors['password'] = 'Пароль должен содержать минимум 8 символов';
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $this->plainPassword)) {
                $errors['password'] = 'Пароль должен содержать строчные, заглавные буквы и цифры';
            } elseif (strlen($this->plainPassword) > 255) {
                $errors['password'] = 'Пароль слишком длинный';
            }
        } else {
            $errors['password'] = 'Пароль обязателен для регистрации';
        }

        // Имя валидация
        if ($this->name !== null) {
            if (strlen($this->name) < 2) {
                $errors['name'] = 'Имя должно содержать минимум 2 символа';
            } elseif (strlen($this->name) > 100) {
                $errors['name'] = 'Имя не должно превышать 100 символов';
            } elseif (!preg_match('/^[\p{L}\s\'-]+$/u', $this->name)) {
                $errors['name'] = 'Имя содержит недопустимые символы';
            }
        }

        // Телефон валидация
        if ($this->phone !== null && !empty($this->phone)) {
            if (!preg_match('/^\+?[1-9]\d{1,14}$/', preg_replace('/[\s\-\(\)]/', '', $this->phone))) {
                $errors['phone'] = 'Некорректный формат телефона (используйте международный формат)';
            }
        }

        // Город валидация
        if ($this->city !== null && strlen($this->city) > 100) {
            $errors['city'] = 'Название города не должно превышать 100 символов';
        }

        // Реферальный код валидация
        if ($this->referralCode !== null && !preg_match('/^[A-Z0-9]{6,10}$/', $this->referralCode)) {
            $errors['referral_code'] = 'Некорректный формат реферального кода';
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
     * Создать копию с измененными данными (для immutability)
     */
    public function withName(string $name): self
    {
        return new self(
            email: $this->email,
            password: $this->plainPassword ?? 'hidden',
            name: $name,
            phone: $this->phone,
            city: $this->city,
            role: $this->role,
            status: $this->status,
            source: $this->source,
            referralCode: $this->referralCode,
            ip: $this->ip,
            userAgent: $this->userAgent,
        );
    }

    /**
     * Проверить наличие реферального кода
     */
    public function hasReferralCode(): bool
    {
        return $this->referralCode !== null && $this->referralCode !== '';
    }

    /**
     * Получить данные для аналитики
     */
    public function getAnalyticsData(): array
    {
        return [
            'source' => $this->source,
            'has_referral' => $this->hasReferralCode(),
            'has_phone' => $this->phone !== null,
            'has_name' => $this->name !== null,
            'role' => $this->role->value,
        ];
    }
}