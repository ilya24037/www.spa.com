<?php

namespace App\Domain\User\Models;

use App\Domain\User\Traits\HasProfile;
use App\Domain\User\Traits\HasRoles;
use App\Domain\User\Traits\HasMasterProfile;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;

/**
 * Основная модель пользователя (Clean Architecture V2)
 * ✅ Убраны Integration трейты (10 → 2)
 * ✅ Интеграция с доменами через UserIntegrationService
 * ✅ Соблюдается Single Responsibility Principle
 */
class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, Searchable;
    use HasRoles, HasProfile, HasMasterProfile;

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'email_verified_at',
        'phone',
        'avatar_url',
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
     * Связь с объявлениями пользователя
     */
    public function ads()
    {
        return $this->hasMany(\App\Domain\Ad\Models\Ad::class);
    }

    /**
     * Проверка доступа к панели Filament
     * Реализация интерфейса FilamentUser
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Разрешаем доступ только администраторам и модераторам
        // Проверяем и enum значения, и строковые значения для совместимости
        if ($this->role instanceof UserRole) {
            return in_array($this->role, [UserRole::ADMIN, UserRole::MODERATOR], true);
        }

        // Если роль хранится как строка (например, при проблемах с cast)
        return in_array($this->role, [UserRole::ADMIN->value, UserRole::MODERATOR->value], true);
    }

    // ✅ АРХИТЕКТУРНЫЙ РЕФАКТОРИНГ ЗАВЕРШЕН (V2):
    // - HasRoles: методы ролей и разрешений
    // - HasProfile: профиль и настройки пользователя
    // - УБРАНЫ Integration трейты (заменены на UserIntegrationService)
    // - Соблюдается Single Responsibility Principle
    // - Уменьшение Cognitive Load с 10 до 2 трейтов
    // - Clean Architecture: композиция через сервисы вместо трейтов
    
    /**
     * Получить сервис интеграции пользователя
     * Заменяет Integration трейты
     */
    public function integration()
    {
        return app(\App\Domain\User\Services\UserIntegrationService::class);
    }
    
    // ======== DEPRECATED МЕТОДЫ ДЛЯ ОБРАТНОЙ СОВМЕСТИМОСТИ ========
    // Постепенно удалим после рефакторинга всех вызовов
    
    /**
     * @deprecated Используйте integration()->getAds($user)
     */
    public function getAds()
    {
        return $this->integration()->getAds($this);
    }
    
    /**
     * @deprecated Используйте integration()->getActiveAds($user)
     */
    public function getActiveAds()
    {
        return $this->integration()->getActiveAds($this);
    }
    
    /**
     * @deprecated Используйте integration()->getBookings($user)
     */
    public function getBookings()
    {
        return $this->integration()->getBookings($this);
    }
    
    /**
     * @deprecated Используйте integration()->getFavoriteMasters($user)
     */
    public function getFavoriteMasters()
    {
        return $this->integration()->getFavoriteMasters($this);
    }
    
    /**
     * @deprecated Используйте integration()->getMasterProfile($user)
     */
    public function getMasterProfile()
    {
        return $this->integration()->getMasterProfile($this);
    }
    
    /**
     * Проверка, является ли пользователь администратором
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN || $this->is_admin === true;
    }
    
    /**
     * @deprecated Используйте integration()->getReceivedReviews($user)
     */
    public function getReceivedReviews()
    {
        return $this->integration()->getReceivedReviews($this);
    }

    /**
     * Получить массив данных для индексации в поисковой системе
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}