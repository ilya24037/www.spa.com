<?php

namespace App\Domain\Master\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne, MorphMany};

use App\Domain\Master\Traits\HasSlug;
use App\Domain\Master\Traits\GeneratesMetaTags;
use App\Support\Traits\JsonFieldsTrait;
class MasterProfile extends Model
{
    use HasFactory;
    use HasSlug;
    use GeneratesMetaTags;
    use JsonFieldsTrait;


    protected $slugField  = 'slug';
    protected $slugSource = 'display_name';

    protected $fillable = [
        'user_id', 'display_name', 'slug', 'bio', 'description', 'avatar',
        'phone', 'whatsapp', 'telegram', 'show_contacts',
        'experience_years', 'certificates', 'education',
        'rating', 'reviews_count', 'completed_bookings',
        'views_count', 'status', 'is_verified',
        'is_premium', 'premium_until',
        'meta_title', 'meta_description',
        // Физические параметры
        'age', 'height', 'weight', 'breast_size',
        // Параметры внешности
        'hair_color', 'eye_color', 'nationality',
        // Особенности мастера
        'features', 'medical_certificate', 'works_during_period', 'additional_features',
        // Поля модерации
        'is_published', 'moderated_at'
    ];

    protected $jsonFields = [
        'certificates',
        'education',
        'features'
    ];

    protected $casts = [
        // JSON поля обрабатываются через JsonFieldsTrait
        'show_contacts' => 'boolean',
        'is_verified'   => 'boolean',
        'is_premium'    => 'boolean',
        'premium_until' => 'datetime',
        'rating'        => 'decimal:2',
        // Поля модерации
        'is_published'  => 'boolean',
        'moderated_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class);
    }

    public function masterServices(): HasMany
    {
        return $this->hasMany(\App\Domain\Master\Models\MasterService::class);
    }

    public function activeMasterServices(): HasMany
    {
        return $this->masterServices()->active()->ordered();
    }

    public function locations(): HasMany
    {
        return $this->hasMany(\App\Domain\Master\Models\MasterLocation::class);
    }

    public function activeLocations(): HasMany
    {
        return $this->locations()->active()->ordered();
    }

    public function primaryLocation(): HasOne
    {
        return $this->hasOne(\App\Domain\Master\Models\MasterLocation::class)
                    ->where('is_primary', true);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(\App\Domain\Media\Models\Photo::class, 'master_profile_id');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(\App\Domain\Media\Models\Video::class, 'master_profile_id');
    }
    
    public function services(): HasMany
    {
        return $this->hasMany(\App\Domain\Master\Models\MasterService::class, 'master_profile_id');
    }
    
    public function reviews(): MorphMany
    {
        return $this->morphMany(\App\Domain\Review\Models\Review::class, 'reviewable');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(\App\Domain\Master\Models\Schedule::class);
    }

    public function workZones(): HasMany
    {
        return $this->hasMany(\App\Domain\Master\Models\WorkZone::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(\App\Domain\Master\Models\MasterSubscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(\App\Domain\Master\Models\MasterSubscription::class)
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->latestOfMany();
    }

    public function isPremium(): bool
    {
        return $this->is_premium && $this->premium_until?->isFuture();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }


    public function isAvailableNow(): bool
    {
        return $this->isActive();
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar 
            ? (filter_var($this->avatar, FILTER_VALIDATE_URL) ? $this->avatar : asset('storage/' . $this->avatar))
            : null;
    }

    public function getPriceFromAttribute(): ?float
    {
        return $this->activeMasterServices()->min('price');
    }

    public function getPriceToAttribute(): ?float
    {
        return $this->activeMasterServices()->max('price');
    }
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
        return $q->whereHas('activeLocations', function($query) use ($city) {
            $query->where('city', $city);
        });
    }

    public function scopeInDistrict($q, $district)
    {
        return $q->whereHas('activeLocations', function($query) use ($district) {
            $query->where('district', $district);
        });
    }
}