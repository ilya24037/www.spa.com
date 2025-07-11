<?php

namespace App\Models;

use App\Traits\HasUniqueSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};
use Illuminate\Support\Str;
use App\Helpers\ImageHelper;            // ← Импорт хелпера здесь
use function route;
use function asset;

class MasterProfile extends Model
{
    use HasFactory;
    use HasUniqueSlug;

    protected $slugField  = 'slug';
    protected $slugSource = 'display_name';

    protected $fillable = [
        'user_id', 'display_name', 'slug', 'bio', 'avatar',
        'phone', 'whatsapp', 'telegram', 'show_contacts',
        'experience_years', 'certificates', 'education',
        'city', 'district', 'metro_station',
        'home_service', 'salon_service', 'salon_address',
        'rating', 'reviews_count', 'completed_bookings',
        'views_count', 'status', 'is_verified',
        'is_premium', 'premium_until',
        'meta_title', 'meta_description',
    ];

    protected $casts = [
        'certificates'  => 'array',
        'education'     => 'array',
        'show_contacts' => 'boolean',
        'home_service'  => 'boolean',
        'salon_service' => 'boolean',
        'is_verified'   => 'boolean',
        'is_premium'    => 'boolean',
        'premium_until' => 'datetime',
        'rating'        => 'decimal:2',
    ];

    protected $appends = [
        'url', 'full_salon_address', 'full_url',
        'share_url', 'avatar_url', 'all_photos',
    ];

    /* --------------------------------------------------------------------- */
    /*  Отношения                                                            */
    /* --------------------------------------------------------------------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function activeServices(): HasMany
    {
        return $this->services()->where('status', 'active');
    }

    public function workZones(): HasMany
    {
        return $this->hasMany(WorkZone::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function scheduleExceptions(): HasMany
    {
        return $this->hasMany(ScheduleException::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MasterSubscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(MasterSubscription::class)
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->latestOfMany();
    }

    public function photos(): HasMany
    {
        return $this->hasMany(MasterPhoto::class, 'master_profile_id');
    }

    public function mainPhoto(): HasOne
    {
        return $this->hasOne(MasterPhoto::class, 'master_profile_id')
                    ->where('is_main', true);
    }

    /* --------------------------------------------------------------------- */
    /*  Логика                                                               */
    /* --------------------------------------------------------------------- */

    public function isPremium(): bool
    {
        return $this->is_premium
            && $this->premium_until?->isFuture();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function updateRating(): void
    {
        $avg = $this->reviews()
            ->where('status', 'approved')
            ->avg('rating_overall');

        $this->update([
            'rating'        => round($avg, 2),
            'reviews_count' => $this->reviews()
                                   ->where('status', 'approved')
                                   ->count(),
        ]);
    }

    public function isAvailableNow(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $now        = now();
        $dow        = $now->dayOfWeek;
        $timeString = $now->format('H:i');

        $sched = $this->schedules()
            ->where('day_of_week', $dow)
            ->where('is_working_day', true)
            ->first();

        if (! $sched) {
            return $this->workZones()
                        ->where('is_active', true)
                        ->exists();
        }

        return $timeString >= $sched->start_time
            && $timeString <= $sched->end_time;
    }

    public function generateMetaTags(): self
    {
        if (empty($this->meta_title)) {
            $parts = [$this->display_name];

            $main = $this->services()
                         ->orderBy('bookings_count', 'desc')
                         ->value('name')
                ?: 'Массажист';

            $parts[] = $main;
            $parts[] = $this->district
                ? "{$this->district}, {$this->city}"
                : $this->city;

            $this->meta_title = implode(' • ', $parts);
        }

        if (empty($this->meta_description)) {
            $desc = [];
            $desc[] = $this->is_verified
                ? "✓ Верифицированный массажист {$this->display_name}"
                : "Массажист {$this->display_name}";

            if ($list = $this->services()
                             ->where('status', 'active')
                             ->orderBy('bookings_count', 'desc')
                             ->take(3)
                             ->pluck('name')
                             ->implode(', ')
            ) {
                $desc[] = "Услуги: {$list}";
            }

            if ($this->experience_years > 0) {
                $yearWord     = $this->getYearWord($this->experience_years);
                $desc[]       = "Опыт {$this->experience_years} {$yearWord}";
            }

            if ($this->rating > 0 && $this->reviews_count > 0) {
                $stars = str_repeat('★', round($this->rating));
                $desc[] = "Рейтинг {$this->rating} {$stars} ({$this->reviews_count} отзывов)";
            }

            if ($min = $this->services()
                            ->where('status', 'active')
                            ->min('price')
            ) {
                $desc[] = "Цены от " . number_format($min, 0, '', ' ') . " ₽";
            }

            $str = implode('. ', $desc) . '.';
            $this->meta_description = mb_strlen($str) > 160
                ? mb_substr($str, 0, 157) . '...'
                : $str;
        }

        return $this;
    }

    private function getYearWord(int $years): string
    {
        $lastTwo = $years % 100;
        if ($lastTwo >= 11 && $lastTwo <= 14) {
            return 'лет';
        }

        return match ($years % 10) {
            1       => 'год',
            2,3,4   => 'года',
            default => 'лет',
        };
    }

    /* --------------------------------------------------------------------- */
    /*  Accessors & Mutators                                                 */
    /* --------------------------------------------------------------------- */

    public function getUrlAttribute(): string
    {
        return route('masters.show', [
            'slug'   => $this->slug,
            'master' => $this->id,
        ]);
    }

    public function getFullSalonAddressAttribute(): string
    {
        $parts = array_filter([
            $this->city,
            $this->district,
            $this->metro_station ? "м. {$this->metro_station}" : null,
            $this->salon_address,
        ]);

        return implode(', ', $parts);
    }

    public function getFullUrlAttribute(): string
    {
        return $this->url;
    }

    public function getShareUrlAttribute(): string
    {
        return preg_replace('#^https?://#', '', $this->full_url);
    }

    public function getAvatarUrlAttribute(): string
    {
        return ImageHelper::getImageUrl(
            $this->avatar,
            asset('images/no-avatar.jpg')
        );
    }

    public function getAllPhotosAttribute(): array
    {
        $out = [];

        if ($this->avatar) {
            $out[] = $this->avatar_url;
        }

        foreach ($this->photos as $photo) {
            $out[] = ImageHelper::getImageUrl(
                $photo->path,
                asset('images/no-photo.jpg')
            );
        }

        if (empty($out)) {
            $out[] = asset('images/no-photo.jpg');
        }

        return $out;
    }

    /* --------------------------------------------------------------------- */
    /*  Scopes                                                               */
    /* --------------------------------------------------------------------- */

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function scopePremium($q)
    {
        return $q->where('is_premium', true)
                 ->where('premium_until', '>=', now());
    }

    public function scopeVerified($q)
    {
        return $q->where('is_verified', true);
    }

    public function scopeInCity($q, $city)
    {
        return $q->where('city', $city);
    }

    public function scopeInDistrict($q, $district)
    {
        return $q->where('district', $district);
    }
}
