<?php

namespace App\Domain\Booking\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * История изменений бронирований
 * Аудит всех операций с бронированиями для отслеживания
 */
class BookingHistory extends Model
{
    use HasFactory;

    protected $table = 'booking_history';

    protected $fillable = [
        'booking_id',
        'user_id',
        'action',
        'previous_status',
        'new_status',
        'previous_data',
        'new_data',
        'reason',
        'notes',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'previous_data' => 'array',
        'new_data' => 'array', 
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Типы действий
    const ACTION_CREATED = 'created';
    const ACTION_CONFIRMED = 'confirmed';
    const ACTION_CANCELLED = 'cancelled';
    const ACTION_COMPLETED = 'completed';
    const ACTION_UPDATED = 'updated';
    const ACTION_PAYMENT_RECEIVED = 'payment_received';
    const ACTION_REMINDER_SENT = 'reminder_sent';
    const ACTION_NO_SHOW = 'no_show';
    const ACTION_RESCHEDULED = 'rescheduled';

    // =================== СВЯЗИ ===================

    /**
     * Бронирование
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Пользователь, который выполнил действие
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =================== МЕТОДЫ ===================

    /**
     * Зафиксировать действие с бронированием
     */
    public static function logAction(
        int $bookingId,
        string $action,
        ?int $userId = null,
        ?string $previousStatus = null,
        ?string $newStatus = null,
        array $previousData = [],
        array $newData = [],
        ?string $reason = null,
        ?string $notes = null,
        array $metadata = []
    ): self {
        return self::create([
            'booking_id' => $bookingId,
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'previous_data' => $previousData,
            'new_data' => $newData,
            'reason' => $reason,
            'notes' => $notes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Зафиксировать создание бронирования
     */
    public static function logCreated(Booking $booking, ?int $userId = null): self
    {
        return self::logAction(
            $booking->id,
            self::ACTION_CREATED,
            $userId,
            null,
            $booking->status,
            [],
            $booking->toArray()
        );
    }

    /**
     * Зафиксировать изменение статуса
     */
    public static function logStatusChange(
        Booking $booking,
        string $previousStatus,
        string $newStatus,
        ?string $reason = null,
        ?int $userId = null
    ): self {
        return self::logAction(
            $booking->id,
            self::getActionByStatus($newStatus),
            $userId,
            $previousStatus,
            $newStatus,
            ['status' => $previousStatus],
            ['status' => $newStatus],
            $reason
        );
    }

    /**
     * Зафиксировать обновление данных
     */
    public static function logUpdate(
        Booking $booking,
        array $previousData,
        array $newData,
        ?string $notes = null,
        ?int $userId = null
    ): self {
        return self::logAction(
            $booking->id,
            self::ACTION_UPDATED,
            $userId,
            null,
            null,
            $previousData,
            $newData,
            null,
            $notes
        );
    }

    /**
     * Получить действие по статусу
     */
    private static function getActionByStatus(string $status): string
    {
        return match($status) {
            'confirmed' => self::ACTION_CONFIRMED,
            'cancelled' => self::ACTION_CANCELLED,
            'completed' => self::ACTION_COMPLETED,
            'no_show' => self::ACTION_NO_SHOW,
            default => self::ACTION_UPDATED,
        };
    }

    // =================== АТРИБУТЫ ===================

    /**
     * Название действия на русском
     */
    public function getActionTextAttribute(): string
    {
        return match($this->action) {
            self::ACTION_CREATED => 'Создано',
            self::ACTION_CONFIRMED => 'Подтверждено',
            self::ACTION_CANCELLED => 'Отменено',
            self::ACTION_COMPLETED => 'Завершено',
            self::ACTION_UPDATED => 'Обновлено',
            self::ACTION_PAYMENT_RECEIVED => 'Оплата получена',
            self::ACTION_REMINDER_SENT => 'Напоминание отправлено',
            self::ACTION_NO_SHOW => 'Клиент не пришел',
            self::ACTION_RESCHEDULED => 'Перенесено',
            default => 'Неизвестное действие',
        };
    }

    /**
     * Цвет действия для UI
     */
    public function getActionColorAttribute(): string
    {
        return match($this->action) {
            self::ACTION_CREATED => 'blue',
            self::ACTION_CONFIRMED => 'green',
            self::ACTION_CANCELLED => 'red',
            self::ACTION_COMPLETED => 'emerald',
            self::ACTION_UPDATED => 'amber',
            self::ACTION_PAYMENT_RECEIVED => 'green',
            self::ACTION_REMINDER_SENT => 'indigo',
            self::ACTION_NO_SHOW => 'gray',
            self::ACTION_RESCHEDULED => 'purple',
            default => 'gray',
        };
    }

    /**
     * Иконка действия для UI
     */
    public function getActionIconAttribute(): string
    {
        return match($this->action) {
            self::ACTION_CREATED => 'plus-circle',
            self::ACTION_CONFIRMED => 'check-circle',
            self::ACTION_CANCELLED => 'x-circle',
            self::ACTION_COMPLETED => 'badge-check',
            self::ACTION_UPDATED => 'pencil',
            self::ACTION_PAYMENT_RECEIVED => 'credit-card',
            self::ACTION_REMINDER_SENT => 'bell',
            self::ACTION_NO_SHOW => 'user-x',
            self::ACTION_RESCHEDULED => 'calendar',
            default => 'activity',
        };
    }

    /**
     * Получить измененные поля
     */
    public function getChangedFieldsAttribute(): array
    {
        if (empty($this->previous_data) || empty($this->new_data)) {
            return [];
        }

        $changes = [];
        foreach ($this->new_data as $field => $newValue) {
            $oldValue = $this->previous_data[$field] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    // =================== SCOPES ===================

    /**
     * История для конкретного бронирования
     */
    public function scopeForBooking($query, int $bookingId)
    {
        return $query->where('booking_id', $bookingId);
    }

    /**
     * История действий пользователя
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * История по типу действия
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Последние изменения
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->whereDate('created_at', '>=', now()->subDays($days));
    }

    /**
     * Упорядочить по времени (новые первыми)
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Только изменения статусов
     */
    public function scopeStatusChanges($query)
    {
        return $query->whereNotNull('previous_status')
                     ->whereNotNull('new_status');
    }
}