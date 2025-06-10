<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bio',
        'specialization',
        'experience_years',
        'education',
        'certificates',
        'city',
        'district',
        'address',
        'latitude',
        'longitude',
        'working_hours',
        'social_links',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'working_hours' => 'array',
        'social_links' => 'array',
    ];

    // Отношения
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}