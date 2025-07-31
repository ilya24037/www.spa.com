<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Traits\HasUserProfile;
use App\Traits\HasUserRoles;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasUserProfile, HasUserRoles;

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
     * Связь с объявлениями
     */
    public function ads()
    {
        return $this->hasMany(Ad::class);
    }

    /**
     * Профиль мастера
     */
    public function masterProfile()
    {
        return $this->hasOne(MasterProfile::class);
    }

    /**
     * Профили мастера (множественное число для Dashboard)
     * Некоторые мастера могут иметь несколько профилей/анкет
     */
    public function masterProfiles()
    {
        return $this->hasMany(MasterProfile::class);
    }

    /**
     * Избранные мастера
     */
    public function favorites()
    {
        return $this->belongsToMany(MasterProfile::class, 'favorites')
            ->withTimestamps();
    }

    /**
     * Бронирования клиента
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'client_id');
    }

    /**
     * Отзывы клиента
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'client_id');
    }

    /**
     * Реакции на отзывы
     */
    public function reviewReactions()
    {
        return $this->hasMany(ReviewReaction::class);
    }

    /**
     * Связь с балансом пользователя
     */
    public function balance()
    {
        return $this->hasOne(UserBalance::class);
    }

    /**
     * Проверка, есть ли у мастера активный профиль
     */
    public function hasActiveMasterProfile(): bool
    {
        return $this->isMaster() && 
               $this->masterProfile && 
               $this->masterProfile->status === 'active';
    }

    /**
     * Получить или создать баланс пользователя
     */
    public function getBalance()
    {
        if (!$this->balance) {
            $this->balance()->create([
                'user_id' => $this->id
            ]);
            $this->refresh();
        }
        
        return $this->balance;
    }

    /**
     * Boot метод для автоматического создания связанных записей
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            // Создаём профиль пользователя
            $user->profile()->create([
                'user_id' => $user->id,
                'name' => 'Пользователь',
            ]);

            // Создаём настройки пользователя
            $user->settings()->create([
                'user_id' => $user->id,
            ]);

            // Если создаётся мастер, создаём профиль мастера
            if ($user->isMaster()) {
                $user->masterProfile()->create([
                    'display_name' => 'Мастер',
                    'city' => 'Москва',
                    'status' => 'draft',
                ]);
            }
            
            // Создаём баланс для всех пользователей
            $user->balance()->create([
                'user_id' => $user->id
            ]);
        });
    }
}