<?php

namespace App\Domain\Moderation\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class StopWord extends Model
{
    use HasFactory;

    protected $fillable = [
        'word',
        'category',
        'weight',
        'severity',
        'context',
        'description',
        'is_active',
        'is_regex',
        'hits_count',
        'false_positives',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_regex' => 'boolean',
        'weight' => 'integer',
        'hits_count' => 'integer',
        'false_positives' => 'integer',
    ];

    /**
     * Категории стоп-слов
     */
    const CATEGORIES = [
        'illegal' => 'Незаконное',
        'adult' => 'Интим/18+',
        'medical' => 'Медицина',
        'financial' => 'Финансы',
        'scam' => 'Мошенничество',
        'offensive' => 'Оскорбления',
        'spam' => 'Спам',
        'other' => 'Другое',
    ];

    /**
     * Уровни серьезности
     */
    const SEVERITIES = [
        'low' => 'Низкая',
        'medium' => 'Средняя',
        'high' => 'Высокая',
        'critical' => 'Критическая',
    ];

    /**
     * Контексты применения
     */
    const CONTEXTS = [
        'all' => 'Везде',
        'ads' => 'Объявления',
        'reviews' => 'Отзывы',
        'messages' => 'Сообщения',
    ];

    /**
     * Пороговые значения для модерации
     */
    const THRESHOLDS = [
        'auto_block' => 10,     // Автоматическая блокировка
        'to_moderation' => 5,   // Отправка на модерацию
        'warning' => 3,         // Предупреждение
    ];

    /**
     * Создавший пользователь
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Обновивший пользователь
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Увеличить счетчик срабатываний
     */
    public function incrementHits(): void
    {
        $this->increment('hits_count');
    }

    /**
     * Отметить как ложное срабатывание
     */
    public function markAsFalsePositive(): void
    {
        $this->increment('false_positives');
    }

    /**
     * Получить процент ложных срабатываний
     */
    public function getFalsePositiveRateAttribute(): float
    {
        if ($this->hits_count == 0) {
            return 0;
        }
        
        return round(($this->false_positives / $this->hits_count) * 100, 2);
    }

    /**
     * Получить эффективность слова
     */
    public function getEffectivenessAttribute(): string
    {
        $rate = $this->false_positive_rate;
        
        if ($rate < 10) return 'Высокая';
        if ($rate < 30) return 'Средняя';
        if ($rate < 50) return 'Низкая';
        
        return 'Очень низкая';
    }

    /**
     * Scope для активных слов
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для контекста
     */
    public function scopeForContext($query, string $context)
    {
        return $query->whereIn('context', [$context, 'all']);
    }

    /**
     * Scope для категории
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Получить все активные слова с кэшированием
     */
    public static function getCachedWords(string $context = 'all'): array
    {
        return Cache::remember("stop_words_{$context}", 3600, function () use ($context) {
            return self::active()
                ->forContext($context)
                ->get()
                ->groupBy('category')
                ->map(function ($words) {
                    return $words->pluck('word', 'weight')->toArray();
                })
                ->toArray();
        });
    }

    /**
     * Очистить кэш стоп-слов
     */
    public static function clearCache(): void
    {
        foreach (self::CONTEXTS as $context => $label) {
            Cache::forget("stop_words_{$context}");
        }
    }

    /**
     * После сохранения очищаем кэш
     */
    protected static function booted()
    {
        static::saved(function ($model) {
            self::clearCache();
        });

        static::deleted(function ($model) {
            self::clearCache();
        });
    }

    /**
     * Проверить текст на наличие стоп-слова
     */
    public function checkText(string $text): bool
    {
        $text = mb_strtolower($text);
        $word = mb_strtolower($this->word);

        if ($this->is_regex) {
            return preg_match("/{$word}/iu", $text) === 1;
        }

        return mb_strpos($text, $word) !== false;
    }

    /**
     * Получить цвет для отображения в админке
     */
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'gray',
            default => 'secondary',
        };
    }
}