<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id', 'client_id', 'master_profile_id',
        'rating', 'comment', 'photos', 'is_verified',
        'would_recommend', 'master_response', 'responded_at'
    ];

    protected $casts = [
        'photos' => 'array',
        'is_verified' => 'boolean',
        'would_recommend' => 'boolean',
        'responded_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::created(function ($review) {
            // Обновляем рейтинг мастера
            $review->masterProfile->updateRating();
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeWithPhotos($query)
    {
        return $query->whereNotNull('photos')->where('photos', '!=', '[]');
    }
}