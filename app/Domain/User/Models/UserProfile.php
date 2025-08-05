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

    /**
     * Обновить аватар пользователя
     */
    public function updateAvatar(string $path): bool
    {
        $this->avatar = $path;
        return $this->save();
    }

    /**
     * Удалить аватар пользователя
     */
    public function deleteAvatar(): bool
    {
        // Удаляем файл с диска если существует
        if ($this->avatar && \Storage::exists($this->avatar)) {
            \Storage::delete($this->avatar);
        }
        
        // Очищаем поле в БД
        $this->avatar = null;
        return $this->save();
    }

    /**
     * Проверить завершенность профиля
     */
    public function isComplete(): bool
    {
        return !empty($this->name) && 
               !empty($this->phone) && 
               !empty($this->city);
    }

    /**
     * Получить процент заполненности профиля
     */
    public function getCompletionPercentageAttribute(): int
    {
        $fields = ['name', 'phone', 'city', 'about', 'avatar'];
        $filled = 0;

        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $filled++;
            }
        }

        return round(($filled / count($fields)) * 100);
    }

    /**
     * Получить имя для отображения
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: 'Пользователь';
    }
}