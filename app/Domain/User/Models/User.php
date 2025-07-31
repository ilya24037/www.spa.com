<?php

namespace App\Domain\User\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Основная модель пользователя (только authentication)
 * Согласно карте рефакторинга - 100 строк
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'role',
        'status',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    /**
     * Связь с профилем пользователя
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Связь с настройками пользователя
     */
    public function settings()
    {
        return $this->hasOne(UserSettings::class);
    }

    /**
     * Проверка роли
     */
    public function hasRole(string $role): bool
    {
        return $this->role->value === $role;
    }

    /**
     * Проверка, является ли пользователь мастером
     */
    public function isMaster(): bool
    {
        return $this->role === UserRole::MASTER;
    }

    /**
     * Проверка, является ли пользователь клиентом
     */
    public function isClient(): bool
    {
        return $this->role === UserRole::CLIENT;
    }

    /**
     * Проверка, является ли пользователь администратором
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Проверка, активен ли пользователь
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }
}