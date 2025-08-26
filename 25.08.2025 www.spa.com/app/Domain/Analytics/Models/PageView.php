<?php

namespace App\Domain\Analytics\Models;

use App\Domain\User\Models\User;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Ad\Models\Ad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Модель просмотров страниц для аналитики
 */
class PageView extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'viewable_type',
        'viewable_id',
        'url',
        'title',
        'referrer',
        'user_agent',
        'ip_address',
        'country',
        'city',
        'device_type',
        'browser',
        'platform',
        'is_mobile',
        'is_bot',
        'duration_seconds',
        'metadata',
        'viewed_at',
    ];

    protected $casts = [
        'is_mobile' => 'boolean',
        'is_bot' => 'boolean',
        'duration_seconds' => 'integer',
        'metadata' => 'array',
        'viewed_at' => 'datetime',
    ];

    protected $attributes = [
        'is_mobile' => false,
        'is_bot' => false,
        'duration_seconds' => 0,
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->viewed_at) {
                $model->viewed_at = now();
            }
        });
    }

    /**
     * Пользователь, который просмотрел страницу
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Полиморфная связь с просматриваемым объектом
     */
    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }

    // ============ SCOPES ============

    /**
     * Просмотры за период
     */
    public function scopeInPeriod(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('viewed_at', [$from, $to]);
    }

    /**
     * Просмотры за последние N дней
     */
    public function scopeLastDays(Builder $query, int $days): Builder
    {
        return $query->where('viewed_at', '>=', now()->subDays($days));
    }

    /**
     * Просмотры конкретного пользователя
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Просмотры гостей (без аутентификации)
     */
    public function scopeGuests(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    /**
     * Просмотры аутентифицированных пользователей
     */
    public function scopeAuthenticated(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Просмотры с мобильных устройств
     */
    public function scopeMobile(Builder $query): Builder
    {
        return $query->where('is_mobile', true);
    }

    /**
     * Просмотры с десктопных устройств
     */
    public function scopeDesktop(Builder $query): Builder
    {
        return $query->where('is_mobile', false);
    }

    /**
     * Исключить ботов
     */
    public function scopeNotBots(Builder $query): Builder
    {
        return $query->where('is_bot', false);
    }

    /**
     * Просмотры конкретного типа сущности
     */
    public function scopeByViewableType(Builder $query, string $type): Builder
    {
        return $query->where('viewable_type', $type);
    }

    /**
     * Просмотры мастеров
     */
    public function scopeMasterViews(Builder $query): Builder
    {
        return $query->where('viewable_type', MasterProfile::class);
    }

    /**
     * Просмотры объявлений
     */
    public function scopeAdViews(Builder $query): Builder
    {
        return $query->where('viewable_type', Ad::class);
    }

    /**
     * Группировка по странам
     */
    public function scopeGroupByCountry(Builder $query): Builder
    {
        return $query->groupBy('country');
    }

    /**
     * Группировка по устройствам
     */
    public function scopeGroupByDevice(Builder $query): Builder
    {
        return $query->groupBy('device_type');
    }

    /**
     * Уникальные просмотры (по IP)
     */
    public function scopeUnique(Builder $query): Builder
    {
        return $query->select('*')
            ->whereRaw('id IN (SELECT MIN(id) FROM page_views GROUP BY ip_address, viewable_type, viewable_id, DATE(viewed_at))');
    }

    /**
     * Просмотры с определенной длительностью
     */
    public function scopeWithDuration(Builder $query, int $minSeconds = 30): Builder
    {
        return $query->where('duration_seconds', '>=', $minSeconds);
    }

    // ============ HELPER METHODS ============

    /**
     * Проверить, является ли просмотр уникальным для пользователя
     */
    public function isUniqueForUser(): bool
    {
        $query = static::where('viewable_type', $this->viewable_type)
            ->where('viewable_id', $this->viewable_id)
            ->where('viewed_at', '<', $this->viewed_at);

        if ($this->user_id) {
            $query->where('user_id', $this->user_id);
        } else {
            $query->where('ip_address', $this->ip_address);
        }

        return !$query->exists();
    }

    /**
     * Получить страну из IP
     */
    public function detectCountry(): ?string
    {
        // Простая заглушка - в реальности используйте GeoIP сервис
        if (!$this->ip_address) {
            return null;
        }

        // Интеграция с MaxMind GeoIP или другим сервисом
        return 'Unknown';
    }

    /**
     * Определить тип устройства из User-Agent
     */
    public function detectDeviceInfo(): array
    {
        $userAgent = $this->user_agent ?? '';
        
        $isMobile = preg_match('/(Mobile|Android|iPhone|iPad|iPod|BlackBerry|Windows Phone)/i', $userAgent);
        $isBot = preg_match('/(bot|crawler|spider|scraper)/i', $userAgent);
        
        $browser = 'Unknown';
        if (preg_match('/Chrome/i', $userAgent)) $browser = 'Chrome';
        elseif (preg_match('/Firefox/i', $userAgent)) $browser = 'Firefox';
        elseif (preg_match('/Safari/i', $userAgent)) $browser = 'Safari';
        elseif (preg_match('/Edge/i', $userAgent)) $browser = 'Edge';

        $platform = 'Unknown';
        if (preg_match('/Windows/i', $userAgent)) $platform = 'Windows';
        elseif (preg_match('/Mac/i', $userAgent)) $platform = 'macOS';
        elseif (preg_match('/Linux/i', $userAgent)) $platform = 'Linux';
        elseif (preg_match('/Android/i', $userAgent)) $platform = 'Android';
        elseif (preg_match('/iOS/i', $userAgent)) $platform = 'iOS';

        $deviceType = $isMobile ? 'mobile' : 'desktop';
        if (preg_match('/tablet/i', $userAgent)) $deviceType = 'tablet';

        return [
            'is_mobile' => (bool) $isMobile,
            'is_bot' => (bool) $isBot,
            'browser' => $browser,
            'platform' => $platform,
            'device_type' => $deviceType,
        ];
    }

    /**
     * Обновить длительность просмотра
     */
    public function updateDuration(int $seconds): void
    {
        $this->update(['duration_seconds' => $seconds]);
    }

    /**
     * Получить читаемое время просмотра
     */
    public function getReadableDuration(): string
    {
        if ($this->duration_seconds < 60) {
            return $this->duration_seconds . ' сек';
        } elseif ($this->duration_seconds < 3600) {
            $minutes = intval($this->duration_seconds / 60);
            return $minutes . ' мин';
        } else {
            $hours = intval($this->duration_seconds / 3600);
            $minutes = intval(($this->duration_seconds % 3600) / 60);
            return $hours . 'ч ' . $minutes . 'м';
        }
    }

    /**
     * Получить краткую информацию о просмотре
     */
    public function getSummary(): array
    {
        return [
            'url' => $this->url,
            'title' => $this->title,
            'duration' => $this->getReadableDuration(),
            'device' => $this->device_type,
            'browser' => $this->browser,
            'country' => $this->country,
            'is_unique' => $this->isUniqueForUser(),
            'viewed_at' => $this->viewed_at->diffForHumans(),
        ];
    }

    /**
     * Форматирование для API
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'readable_duration' => $this->getReadableDuration(),
            'is_unique' => $this->isUniqueForUser(),
            'summary' => $this->getSummary(),
            'time_ago' => $this->viewed_at->diffForHumans(),
        ]);
    }
}