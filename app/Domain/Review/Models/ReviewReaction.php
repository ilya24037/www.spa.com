<?php

namespace App\Domain\Review\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Модель реакций на отзывы (полезный/бесполезный)
 */
class ReviewReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'user_id',
        'is_helpful',
        'reaction_type',
        'metadata',
    ];

    protected $casts = [
        'is_helpful' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'is_helpful' => true,
        'reaction_type' => 'helpful',
    ];

    /**
     * Отзыв, на который дана реакция
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Пользователь, оставивший реакцию
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ============ SCOPES ============
    
    /**
     * Положительные реакции (полезно)
     */
    public function scopeHelpful(Builder $query): Builder
    {
        return $query->where('is_helpful', true);
    }

    /**
     * Отрицательные реакции (бесполезно)
     */
    public function scopeNotHelpful(Builder $query): Builder
    {
        return $query->where('is_helpful', false);
    }

    /**
     * Реакции конкретного пользователя
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Реакции на конкретный отзыв
     */
    public function scopeForReview(Builder $query, int $reviewId): Builder
    {
        return $query->where('review_id', $reviewId);
    }

    // ============ HELPER METHODS ============

    /**
     * Проверить, является ли реакция положительной
     */
    public function isHelpful(): bool
    {
        return $this->is_helpful === true;
    }

    /**
     * Проверить, является ли реакция отрицательной
     */
    public function isNotHelpful(): bool
    {
        return $this->is_helpful === false;
    }

    /**
     * Переключить тип реакции
     */
    public function toggle(): void
    {
        $this->update([
            'is_helpful' => !$this->is_helpful,
            'reaction_type' => $this->is_helpful ? 'not_helpful' : 'helpful',
        ]);
    }

    /**
     * Установить как "полезно"
     */
    public function setHelpful(): void
    {
        if (!$this->is_helpful) {
            $this->update([
                'is_helpful' => true,
                'reaction_type' => 'helpful',
            ]);
        }
    }

    /**
     * Установить как "бесполезно"
     */
    public function setNotHelpful(): void
    {
        if ($this->is_helpful) {
            $this->update([
                'is_helpful' => false,
                'reaction_type' => 'not_helpful',
            ]);
        }
    }

    /**
     * Получить тип реакции как строку
     */
    public function getReactionTypeAttribute(): string
    {
        return $this->is_helpful ? 'helpful' : 'not_helpful';
    }

    /**
     * Получить текстовое описание реакции
     */
    public function getReactionText(): string
    {
        return $this->is_helpful ? 'Полезно' : 'Бесполезно';
    }

    /**
     * Получить иконку для реакции
     */
    public function getReactionIcon(): string
    {
        return $this->is_helpful ? '👍' : '👎';
    }

    /**
     * Форматирование для API
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'reaction_text' => $this->getReactionText(),
            'reaction_icon' => $this->getReactionIcon(),
            'created_time_ago' => $this->created_at->diffForHumans(),
        ]);
    }
}