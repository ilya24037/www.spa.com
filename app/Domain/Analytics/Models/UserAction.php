<?php

namespace App\Domain\Analytics\Models;

use App\Domain\User\Models\User;
use App\Domain\Analytics\Models\Traits\UserActionTypesTrait;
use App\Domain\Analytics\Models\Traits\UserActionScopesTrait;
use App\Domain\Analytics\Models\Traits\UserActionHelperTrait;
use App\Domain\Analytics\Models\Traits\UserActionPropertiesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Модель действий пользователей для аналитики - координатор
 */
class UserAction extends Model
{
    use HasFactory,
        UserActionTypesTrait,
        UserActionScopesTrait,
        UserActionHelperTrait,
        UserActionPropertiesTrait;

    protected $fillable = [
        'user_id',
        'session_id',
        'action_type',
        'actionable_type',
        'actionable_id',
        'properties',
        'ip_address',
        'user_agent',
        'referrer_url',
        'current_url',
        'is_conversion',
        'conversion_value',
        'performed_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'is_conversion' => 'boolean',
        'conversion_value' => 'decimal:2',
        'performed_at' => 'datetime',
    ];

    protected $attributes = [
        'is_conversion' => false,
        'conversion_value' => 0,
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->performed_at) {
                $model->performed_at = now();
            }
        });
    }

    /**
     * Пользователь, который выполнил действие
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Полиморфная связь с объектом действия
     */
    public function actionable(): MorphTo
    {
        return $this->morphTo();
    }
}