<?php

declare(strict_types=1);

namespace App\Domain\Review\Models;

use App\Domain\Ad\Models\Ad;
use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Review extends Model
{
    use HasFactory, SoftDeletes, Searchable;

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
        'status' => \App\Enums\ReviewStatus::class,
        'is_visible' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'replied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Автор отзыва
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Полиморфная связь с объектом отзыва (мастер или объявление)
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Бронирование, связанное с отзывом
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
     * Проверка, является ли пользователь автором отзыва
     */
    public function isAuthor(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Форматированный рейтинг
     */
    public function getFormattedRatingAttribute(): string
    {
        return number_format($this->rating, 1);
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'master_reply' => $this->master_reply,
            'status' => $this->status,
            'is_verified' => $this->is_verified,
            'reviewer_name' => $this->reviewer?->name,
            'reviewer_email' => $this->reviewer?->email,
        ];
    }
}