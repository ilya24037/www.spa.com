<?php

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Ad\Models\Ad;

class UserFavorite extends Model
{
    protected $table = 'user_favorites';

    protected $fillable = [
        'user_id',
        'ad_id',
        'favorited_user_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * Связь с пользователем (владелец избранного)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Связь с объявлением
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Связь с избранным пользователем (мастером)
     */
    public function favoritedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'favorited_user_id');
    }
}