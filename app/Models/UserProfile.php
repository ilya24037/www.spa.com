<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Helpers\ImageHelper;

/**
 * Профиль пользователя (личная информация)
 */
class UserProfile extends Model
{
    use HasFactory;

    protected $table = 'user_profiles';

    protected $fillable = [
        'user_id',
        'name',
        'avatar',
        'phone',
        'birth_date',
        'gender',
        'city',
        'about',
        'website',
        'social_links',
        'language',
        'timezone',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'social_links' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить URL аватара
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return ImageHelper::getImageUrl($this->avatar, '/images/no-avatar.jpg');
        }

        // Генерируем аватар на основе имени
        return ImageHelper::getUserAvatar(null, $this->name);
    }

    /**
     * Получить полное имя для отображения
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: 'Без имени';
    }

    /**
     * Получить возраст пользователя
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }

        return $this->birth_date->age;
    }

    /**
     * Получить форматированную дату рождения
     */
    public function getFormattedBirthDateAttribute(): ?string
    {
        if (!$this->birth_date) {
            return null;
        }

        return $this->birth_date->format('d.m.Y');
    }

    /**
     * Получить читаемый пол
     */
    public function getGenderLabelAttribute(): string
    {
        return match($this->gender) {
            'male' => 'Мужской',
            'female' => 'Женский',
            default => 'Не указан',
        };
    }

    /**
     * Проверить заполненность профиля
     */
    public function isComplete(): bool
    {
        $requiredFields = ['name', 'phone', 'city'];
        
        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Получить процент заполненности профиля
     */
    public function getCompletionPercentageAttribute(): int
    {
        $totalFields = 8;
        $filledFields = 0;

        $fields = ['name', 'avatar', 'phone', 'birth_date', 'gender', 'city', 'about', 'website'];

        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $filledFields++;
            }
        }

        return intval(($filledFields / $totalFields) * 100);
    }

    /**
     * Получить недостающие поля профиля
     */
    public function getMissingFields(): array
    {
        $missing = [];
        $fields = [
            'name' => 'Имя',
            'phone' => 'Телефон',
            'city' => 'Город',
            'birth_date' => 'Дата рождения',
            'gender' => 'Пол',
            'about' => 'О себе',
        ];

        foreach ($fields as $field => $label) {
            if (empty($this->$field)) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    /**
     * Получить социальные ссылки в читаемом виде
     */
    public function getFormattedSocialLinksAttribute(): array
    {
        if (empty($this->social_links)) {
            return [];
        }

        $formatted = [];
        $platforms = [
            'vk' => 'ВКонтакте',
            'instagram' => 'Instagram',
            'telegram' => 'Telegram',
            'whatsapp' => 'WhatsApp',
            'facebook' => 'Facebook',
            'twitter' => 'Twitter',
        ];

        foreach ($this->social_links as $platform => $url) {
            if (!empty($url)) {
                $formatted[] = [
                    'platform' => $platform,
                    'name' => $platforms[$platform] ?? ucfirst($platform),
                    'url' => $url,
                    'icon' => $this->getSocialIcon($platform),
                ];
            }
        }

        return $formatted;
    }

    /**
     * Получить иконку социальной сети
     */
    private function getSocialIcon(string $platform): string
    {
        return match($platform) {
            'vk' => '📘',
            'instagram' => '📷',
            'telegram' => '✈️',
            'whatsapp' => '💚',
            'facebook' => '📖',
            'twitter' => '🐦',
            default => '🔗',
        };
    }

    /**
     * Проверить валидность номера телефона
     */
    public function hasValidPhone(): bool
    {
        if (!$this->phone) {
            return false;
        }

        // Простая проверка российского номера
        $cleaned = preg_replace('/[^\d]/', '', $this->phone);
        
        return preg_match('/^[78]\d{10}$/', $cleaned) || 
               preg_match('/^9\d{9}$/', $cleaned);
    }

    /**
     * Форматированный номер телефона
     */
    public function getFormattedPhoneAttribute(): ?string
    {
        if (!$this->phone) {
            return null;
        }

        $cleaned = preg_replace('/[^\d]/', '', $this->phone);
        
        if (preg_match('/^(\d)(\d{3})(\d{3})(\d{2})(\d{2})$/', $cleaned, $matches)) {
            return "+{$matches[1]} ({$matches[2]}) {$matches[3]}-{$matches[4]}-{$matches[5]}";
        }

        return $this->phone;
    }

    /**
     * Обновить аватар
     */
    public function updateAvatar(string $avatarPath): void
    {
        // Удаляем старый аватар если есть
        if ($this->avatar && $this->avatar !== $avatarPath) {
            ImageHelper::deleteImage($this->avatar);
        }

        $this->avatar = $avatarPath;
        $this->save();
    }

    /**
     * Удалить аватар
     */
    public function deleteAvatar(): void
    {
        if ($this->avatar) {
            ImageHelper::deleteImage($this->avatar);
            $this->avatar = null;
            $this->save();
        }
    }
}