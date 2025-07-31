<?php

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Профиль пользователя
 * Согласно карте рефакторинга - 50 строк
 */
class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'city',
        'about',
        'avatar',
        'birth_date',
        'notifications_enabled',
        'newsletter_enabled',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'notifications_enabled' => 'boolean',
        'newsletter_enabled' => 'boolean',
    ];

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить полное имя
     */
    public function getFullNameAttribute(): string
    {
        return $this->name ?? 'Пользователь';
    }

    /**
     * Получить URL аватара
     */
    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar 
            ? asset('storage/' . $this->avatar)
            : asset('images/default-avatar.png');
    }
}