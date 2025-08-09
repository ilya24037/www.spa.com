<?php

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Ad\Models\Ad;
use App\Domain\Master\Models\MasterProfile;

class UserFavorite extends Model
{
    protected $table = 'user_favorites';
    
    protected $fillable = [
        'user_id',
        'ad_id',
        'master_profile_id',
    ];
    
    protected $dates = [
        'created_at',
        'updated_at'
    ];
    
    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Связь с объявлением
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }
    
    /**
     * Связь с мастером
     */
    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }
}