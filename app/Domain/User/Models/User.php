<?php

namespace App\Domain\User\Models;

use App\Domain\User\Traits\HasAdsIntegration;
use App\Domain\User\Traits\HasBookingIntegration;
use App\Domain\User\Traits\HasFavoritesIntegration;
use App\Domain\User\Traits\HasMasterIntegration;
use App\Domain\User\Traits\HasProfile;
use App\Domain\User\Traits\HasReviewsIntegration;
use App\Domain\User\Traits\HasRoles;
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
    use HasRoles, HasProfile, HasBookingIntegration, HasMasterIntegration;
    use HasFavoritesIntegration, HasReviewsIntegration, HasAdsIntegration;

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

    // ✅ DDD РЕФАКТОРИНГ ЗАВЕРШЕН:
    // - HasRoles: методы ролей и разрешений
    // - HasProfile: профиль и настройки пользователя  
    // - HasBookingIntegration: интеграция с бронированиями через события
    // - HasMasterIntegration: интеграция с мастерами через события
    // - HasFavoritesIntegration: интеграция с избранным через события
    // - HasReviewsIntegration: интеграция с отзывами через события  
    // - HasAdsIntegration: интеграция с объявлениями через события
    // - Удалены прямые связи с другими доменами
    // - Используются Integration Services для взаимодействия
}