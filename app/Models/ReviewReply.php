<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель ответов на отзывы
 */
class ReviewReply extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'review_id',
        'user_id',
        'reply',
        'status',
        'is_official',
        'moderated_at',
        'moderated_by',
        'moderation_notes',
    ];

    protected $casts = [
        'status' => ReviewStatus::class,
        'is_official' => 'boolean',
        'moderated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => ReviewStatus::PENDING,
        'is_official' => false,
    ];

    /**
     * Отзыв, на который дан ответ
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Автор ответа
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Модератор
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Скоупы
     */
    public function scopeApproved($query)
    {
        return $query->where('status', ReviewStatus::APPROVED);
    }

    public function scopeOfficial($query)
    {
        return $query->where('is_official', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', ReviewStatus::PENDING);
    }

    /**
     * Проверки состояния
     */
    public function isApproved(): bool
    {
        return $this->status === ReviewStatus::APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === ReviewStatus::PENDING;
    }

    /**
     * Одобрить ответ
     */
    public function approve(User $moderator = null): void
    {
        $this->update([
            'status' => ReviewStatus::APPROVED,
            'moderated_at' => now(),
            'moderated_by' => $moderator?->id,
        ]);

        // Увеличиваем счетчик ответов в отзыве
        $this->review->increment('reply_count');
    }

    /**
     * Отклонить ответ
     */
    public function reject(User $moderator = null, string $reason = null): void
    {
        $this->update([
            'status' => ReviewStatus::REJECTED,
            'moderated_at' => now(),
            'moderated_by' => $moderator?->id,
            'moderation_notes' => $reason,
        ]);
    }

    /**
     * Получить краткий ответ
     */
    public function getShortReply(int $length = 100): string
    {
        return strlen($this->reply) > $length 
            ? substr($this->reply, 0, $length) . '...'
            : $this->reply;
    }

    /**
     * Время с момента создания
     */
    public function getTimeAgo(): string
    {
        return $this->created_at->diffForHumans();
    }
}