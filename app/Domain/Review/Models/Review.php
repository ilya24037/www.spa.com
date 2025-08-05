<?php

namespace App\Domain\Review\Models;

use App\Enums\ReviewStatus;
use App\Enums\ReviewType;
use App\Enums\ReviewRating;
use App\Support\Traits\JsonFieldsTrait;
use App\Domain\User\Models\User;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Booking\Models\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

/**
 * Расширенная модель отзывов с поддержкой модерации и полиморфных связей
 */
class Review extends Model
{
    use HasFactory, SoftDeletes, JsonFieldsTrait;

    // Новые поля для расширенной функциональности
    protected $fillable = [
        // Основные поля
        'user_id',
        'reviewable_type',
        'reviewable_id',
        'booking_id',
        'type',
        'status',
        'rating',
        'title',
        'comment',
        'pros',
        'cons',
        'photos',
        
        // Настройки отзыва
        'is_anonymous',
        'is_verified',
        'is_recommended',
        
        // Счетчики
        'helpful_count',
        'not_helpful_count',
        'reply_count',
        
        // Модерация
        'moderated_at',
        'moderated_by',
        'moderation_notes',
        
        // Жалобы
        'flagged_at',
        'flagged_by',
        'flagged_reason',
        
        // Метаданные
        'metadata',
        
        // Поля для обратной совместимости
        'client_id',
        'master_profile_id',
        'would_recommend',
        'master_response',
        'responded_at',
    ];

    /**
     * JSON поля для использования с JsonFieldsTrait
     */
    protected $jsonFields = [
        'pros',
        'cons',
        'photos',
        'metadata',
    ];

    protected $casts = [
        // Новые енумы
        'type' => ReviewType::class,
        'status' => ReviewStatus::class,
        'rating' => ReviewRating::class,
        
        // Массивы
        // JSON поля обрабатываются через JsonFieldsTrait
        
        // Булевы
        'is_anonymous' => 'boolean',
        'is_verified' => 'boolean',
        'is_recommended' => 'boolean',
        'would_recommend' => 'boolean',
        
        // Числовые
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'reply_count' => 'integer',
        
        // Даты
        'moderated_at' => 'datetime',
        'flagged_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => ReviewStatus::PENDING,
        'type' => ReviewType::SERVICE,
        'is_anonymous' => false,
        'is_verified' => false,
        'is_recommended' => false,
        'helpful_count' => 0,
        'not_helpful_count' => 0,
        'reply_count' => 0,
    ];


    /**
     * Основной пользователь (новый подход)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Полиморфная связь с объектом отзыва
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Связанное бронирование
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Модератор
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Кто пожаловался
     */
    public function flagger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'flagged_by');
    }

    /**
     * Ответы на отзыв
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ReviewReply::class);
    }

    /**
     * Реакции на отзыв
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(ReviewReaction::class);
    }

    // ============ LEGACY COMPATIBILITY ============
    
    /**
     * @deprecated Используйте user()
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * @deprecated Используйте reviewable() с MasterProfile
     */
    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    // ============ SCOPES ============
    
    public function scopePublic($query)
    {
        return $query->where('status', ReviewStatus::APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', ReviewStatus::PENDING);
    }

    public function scopeByType($query, ReviewType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByRating($query, ReviewRating $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForReviewable($query, string $type, int $id)
    {
        return $query->where('reviewable_type', $type)
                    ->where('reviewable_id', $id);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeRecommended($query)
    {
        return $query->where('is_recommended', true);
    }

    public function scopeWithPhotos($query)
    {
        return $query->whereNotNull('photos')
                    ->where('photos', '!=', '[]');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopePopular($query)
    {
        return $query->orderByRaw('(helpful_count - not_helpful_count) DESC');
    }

    public function scopePositive($query)
    {
        return $query->whereIn('rating', [4, 5]);
    }

    public function scopeNegative($query)
    {
        return $query->whereIn('rating', [1, 2]);
    }

    // ============ HELPER METHODS ============
    
    /**
     * Проверить, виден ли отзыв публично
     */
    public function isPublic(): bool
    {
        return $this->status === ReviewStatus::APPROVED;
    }

    /**
     * Получить отображаемое имя автора
     */
    public function getAuthorName(): string
    {
        if ($this->is_anonymous) {
            return 'Анонимный пользователь';
        }

        $user = $this->user ?? $this->client;
        return $user->name ?? 'Пользователь';
    }

    /**
     * Получить краткий отзыв
     */
    public function getShortComment(int $length = 100): string
    {
        return strlen($this->comment) > $length 
            ? substr($this->comment, 0, $length) . '...'
            : $this->comment;
    }

    /**
     * Проверить, является ли отзыв положительным
     */
    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    /**
     * Одобрить отзыв
     */
    public function approve(?User $moderator = null): void
    {
        $this->update([
            'status' => ReviewStatus::APPROVED,
            'moderated_at' => now(),
            'moderated_by' => $moderator?->id,
        ]);
    }

    /**
     * Отклонить отзыв
     */
    public function reject(?User $moderator = null, ?string $reason = null): void
    {
        $this->update([
            'status' => ReviewStatus::REJECTED,
            'moderated_at' => now(),
            'moderated_by' => $moderator?->id,
            'moderation_notes' => $reason,
        ]);
    }

    /**
     * Пожаловаться на отзыв
     */
    public function flag(User $flagger, string $reason): void
    {
        $this->update([
            'status' => ReviewStatus::FLAGGED,
            'flagged_at' => now(),
            'flagged_by' => $flagger->id,
            'flagged_reason' => $reason,
        ]);
    }

    /**
     * Отметить как полезный
     */
    public function markAsHelpful(): void
    {
        $this->increment('helpful_count');
    }

    /**
     * Отметить как бесполезный
     */
    public function markAsNotHelpful(): void
    {
        $this->increment('not_helpful_count');
    }

    /**
     * Увеличить счетчик просмотров
     */
    public function incrementViews(): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['views'] = ($metadata['views'] ?? 0) + 1;
        $this->update(['metadata' => $metadata]);
    }

    /**
     * Получить URL фотографий
     */
    public function getPhotoUrls(): array
    {
        if (empty($this->photos)) {
            return [];
        }

        return array_map(function($photo) {
            return asset('storage/reviews/' . $photo);
        }, $this->photos);
    }

    /**
     * Форматирование для API
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'author_name' => $this->getAuthorName(),
            'short_comment' => $this->getShortComment(),
            'time_ago' => $this->created_at->diffForHumans(),
            'photo_urls' => $this->getPhotoUrls(),
            'is_positive' => $this->isPositive(),
            'can_be_edited' => $this->status->canBeEdited(),
        ]);
    }
}