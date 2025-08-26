<?php

declare(strict_types=1);

namespace App\Domain\Review\Models;

use App\Domain\User\Models\User;
use App\Domain\Booking\Models\Booking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель отзыва, адаптированная под текущую структуру БД
 */
class ReviewAdapted extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reviews';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'reviewer_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'status',
        'comment',
        'booking_id',
        'is_verified',
        'is_visible',
        'verified_at',
        'master_reply',
        'replied_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'is_visible' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'replied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Автор отзыва (reviewer_id в БД)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Автор отзыва (алиас для совместимости)
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Полиморфная связь - кому оставлен отзыв
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Связанное бронирование/объявление
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Scope для видимых отзывов
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope для проверенных отзывов
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope для одобренных отзывов
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Проверка, является ли пользователь автором отзыва
     */
    public function isAuthor(User $user): bool
    {
        return $this->reviewer_id === $user->id;
    }

    /**
     * Форматированный рейтинг
     */
    public function getFormattedRatingAttribute(): string
    {
        return number_format($this->rating, 1);
    }

    /**
     * Получить пользователя, которому оставлен отзыв (для совместимости)
     */
    public function getReviewableUserAttribute(): ?User
    {
        if ($this->reviewable_type === 'App\\Domain\\User\\Models\\User') {
            return User::find($this->reviewable_id);
        }
        return null;
    }
}