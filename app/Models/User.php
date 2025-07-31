<?php

namespace App\Models;

use App\Domain\User\Models\User as BaseUser;
use App\Domain\User\Traits\HasRoles;
use App\Domain\User\Traits\HasBookings;
use App\Domain\User\Traits\HasMasterProfile;

/**
 * Legacy User model для обратной совместимости
 * Использует новую доменную структуру
 */
class User extends BaseUser
{
    use HasRoles, HasBookings, HasMasterProfile;

    /**
     * Связь с объявлениями
     */
    public function ads()
    {
        return $this->hasMany(Ad::class);
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