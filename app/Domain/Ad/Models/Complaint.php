<?php

namespace App\Domain\Ad\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\User\Models\User;

/**
 * Модель жалоб на объявления
 * Следует принципам DDD - находится в домене Ad
 */
class Complaint extends Model
{
    protected $fillable = [
        'ad_id',
        'user_id',
        'resolved_by',
        'reason',
        'resolution_note',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Объявление, на которое пожаловались
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Пользователь, подавший жалобу
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Администратор, разрешивший жалобу
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Проверка: ожидает ли жалоба рассмотрения
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Проверка: разрешена ли жалоба
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Проверка: отклонена ли жалоба
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Разрешить жалобу
     */
    public function resolve(User $admin, string $note = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_by' => $admin->id,
            'resolution_note' => $note,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Отклонить жалобу
     */
    public function reject(User $admin, string $note = null): void
    {
        $this->update([
            'status' => 'rejected',
            'resolved_by' => $admin->id,
            'resolution_note' => $note,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Scope для неразрешенных жалоб
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope для разрешенных жалоб
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope для жалоб за период
     */
    public function scopeInPeriod($query, $from, $to = null)
    {
        $query->where('created_at', '>=', $from);

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }
}