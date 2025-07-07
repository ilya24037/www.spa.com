<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Профиль мастера
     */
    public function masterProfile()
    {
        return $this->hasOne(MasterProfile::class);
    }

    /**
     * 🔥 ДОБАВЛЕНО: Профили мастера (множественное число для Dashboard)
     * Некоторые мастера могут иметь несколько профилей/анкет
     */
    public function masterProfiles()
    {
        return $this->hasMany(MasterProfile::class);
    }

    /**
     * 🔥 ДОБАВЛЕНО: Избранные мастера
     */
    public function favorites()
    {
        return $this->belongsToMany(MasterProfile::class, 'favorites')
            ->withTimestamps();
    }

    /**
     * Бронирования клиента
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'client_id');
    }

    /**
     * Отзывы клиента
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'client_id');
    }

    /**
     * Реакции на отзывы
     */
    public function reviewReactions()
    {
        return $this->hasMany(ReviewReaction::class);
    }

    /**
     * Проверка роли пользователя
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * Проверка, является ли пользователь мастером
     */
    public function isMaster()
    {
        return $this->role === 'master';
    }

    /**
     * Проверка, является ли пользователь администратором
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Проверка, является ли пользователь клиентом
     */
    public function isClient()
    {
        return $this->role === 'client';
    }

    /**
     * Проверка, есть ли у мастера активный профиль
     */
    public function hasActiveMasterProfile()
    {
        return $this->isMaster() && 
               $this->masterProfile && 
               $this->masterProfile->status === 'active';
    }

    /**
     * Получить URL аватара
     */
    public function getAvatarUrlAttribute()
    {
        // Если пользователь - мастер с аватаром
        if ($this->isMaster() && $this->masterProfile && $this->masterProfile->avatar) {
            return Storage::url($this->masterProfile->avatar);
        }
        
        // Если есть аватар у пользователя
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }

        // Возвращаем дефолтный аватар
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Получить имя для отображения
     */
    public function getDisplayNameAttribute()
    {
        if ($this->isMaster() && $this->masterProfile) {
            return $this->masterProfile->display_name;
        }
        
        return $this->name;
    }

    /**
     * Получить статистику клиента
     */
    public function getClientStats(): array
    {
        if (!$this->isClient()) {
            return [];
        }

        $totalBookings = $this->bookings()->count();
        $completedBookings = $this->bookings()->where('status', 'completed')->count();
        $totalReviews = $this->reviews()->count();
        
        return [
            'total_bookings' => $totalBookings,
            'completed_bookings' => $completedBookings,
            'cancelled_bookings' => $this->bookings()->where('status', 'cancelled')->count(),
            'total_reviews' => $totalReviews,
            'total_spent' => $this->bookings()
                ->where('payment_status', 'paid')
                ->sum('total_price'),
        ];
    }

    /**
     * Получить статистику мастера
     */
    public function getMasterStats(): array
    {
        if (!$this->isMaster() || !$this->masterProfile) {
            return [];
        }

        return [
            'total_services' => $this->masterProfile->services()->count(),
            'active_services' => $this->masterProfile->activeServices()->count(),
            'total_bookings' => $this->masterProfile->bookings()->count(),
            'completed_bookings' => $this->masterProfile->completed_bookings,
            'rating' => $this->masterProfile->rating,
            'reviews_count' => $this->masterProfile->reviews_count,
            'views_count' => $this->masterProfile->views_count,
        ];
    }

    /**
     * Получить предстоящие бронирования клиента
     */
    public function getUpcomingBookings($limit = 5)
    {
        return $this->bookings()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('booking_date', '>=', now()->toDateString())
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->with(['masterProfile.user', 'service'])
            ->limit($limit)
            ->get();
    }

    /**
     * Boot метод для автоматического создания профиля мастера
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            // Если создаётся мастер, создаём профиль
            if ($user->role === 'master') {
                $user->masterProfile()->create([
                    'display_name' => $user->name,
                    'city' => 'Москва',
                    'status' => 'draft',
                ]);
            }
        });
    }
}