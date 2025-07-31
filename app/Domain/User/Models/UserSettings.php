<?php

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Настройки пользователя
 * Согласно карте рефакторинга - 30 строк
 */
class UserSettings extends Model
{
    protected $fillable = [
        'user_id',
        'locale',
        'timezone',
        'currency',
        'email_notifications',
        'sms_notifications',
        'push_notifications',
        'marketing_emails',
        'two_factor_enabled',
        'privacy_settings',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'sms_notifications' => 'boolean',
        'push_notifications' => 'boolean',
        'marketing_emails' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'privacy_settings' => 'json',
    ];

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}