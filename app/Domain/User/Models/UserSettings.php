<?php

namespace App\Domain\User\Models;

use App\Support\Traits\JsonFieldsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Настройки пользователя
 * Согласно карте рефакторинга - 30 строк
 */
class UserSettings extends Model
{
    use JsonFieldsTrait;
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

    /**
     * JSON поля для использования с JsonFieldsTrait
     */
    protected $jsonFields = [
        'privacy_settings'
    ];

    protected $casts = [
        // JSON поля обрабатываются через JsonFieldsTrait
        'email_notifications' => 'boolean',
        'sms_notifications' => 'boolean',
        'push_notifications' => 'boolean',
        'marketing_emails' => 'boolean',
        'two_factor_enabled' => 'boolean',
    ];

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}