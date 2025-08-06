<?php

namespace App\Domain\Master\Repositories;

use App\Domain\Master\Models\MasterProfile;
use App\Enums\MasterStatus;
use Illuminate\Database\Eloquent\Collection;

/**
 * Репозиторий для бизнес-операций с мастерами
 */
class MasterBusinessRepository
{
    private MasterProfile $model;

    public function __construct()
    {
        $this->model = new MasterProfile();
    }

    /**
     * Массовое обновление статусов
     */
    public function batchUpdateStatus(array $ids, MasterStatus $status): int
    {
        return $this->model->whereIn('id', $ids)->update(['status' => $status]);
    }

    /**
     * Автоматическая деактивация неактивных мастеров
     */
    public function deactivateInactiveMasters(int $days = 90): int
    {
        return $this->model->where('status', MasterStatus::ACTIVE)
            ->whereDoesntHave('bookings', function ($query) use ($days) {
                $query->where('created_at', '>=', now()->subDays($days));
            })
            ->update(['status' => MasterStatus::INACTIVE]);
    }

    /**
     * Активировать мастера после модерации
     */
    public function activate(int $masterId): bool
    {
        return $this->model->where('id', $masterId)
            ->where('status', MasterStatus::PENDING)
            ->update([
                'status' => MasterStatus::ACTIVE,
                'activated_at' => now(),
            ]) > 0;
    }

    /**
     * Деактивировать мастера
     */
    public function deactivate(int $masterId, ?string $reason = null): bool
    {
        return $this->model->where('id', $masterId)
            ->update([
                'status' => MasterStatus::INACTIVE,
                'deactivated_at' => now(),
                'deactivation_reason' => $reason,
            ]) > 0;
    }

    /**
     * Заблокировать мастера
     */
    public function block(int $masterId, string $reason): bool
    {
        return $this->model->where('id', $masterId)
            ->update([
                'status' => MasterStatus::BLOCKED,
                'blocked_at' => now(),
                'block_reason' => $reason,
            ]) > 0;
    }

    /**
     * Разблокировать мастера
     */
    public function unblock(int $masterId): bool
    {
        return $this->model->where('id', $masterId)
            ->where('status', MasterStatus::BLOCKED)
            ->update([
                'status' => MasterStatus::ACTIVE,
                'blocked_at' => null,
                'block_reason' => null,
                'unblocked_at' => now(),
            ]) > 0;
    }

    /**
     * Обновить премиум статус
     */
    public function updatePremiumStatus(int $masterId, bool $isPremium, ?\Carbon\Carbon $premiumUntil = null): bool
    {
        return $this->model->where('id', $masterId)->update([
            'is_premium' => $isPremium,
            'premium_until' => $isPremium ? $premiumUntil : null,
            'premium_updated_at' => now(),
        ]) > 0;
    }

    /**
     * Верифицировать мастера
     */
    public function verify(int $masterId): bool
    {
        return $this->model->where('id', $masterId)
            ->update([
                'is_verified' => true,
                'verified_at' => now(),
            ]) > 0;
    }

    /**
     * Убрать верификацию
     */
    public function unverify(int $masterId, ?string $reason = null): bool
    {
        return $this->model->where('id', $masterId)
            ->update([
                'is_verified' => false,
                'verified_at' => null,
                'unverification_reason' => $reason,
            ]) > 0;
    }

    /**
     * Получить просроченные премиум аккаунты
     */
    public function getExpiredPremium(): Collection
    {
        return $this->model->where('is_premium', true)
            ->where('premium_until', '<', now())
            ->get();
    }

    /**
     * Деактивировать просроченные премиум аккаунты
     */
    public function deactivateExpiredPremium(): int
    {
        return $this->model->where('is_premium', true)
            ->where('premium_until', '<', now())
            ->update([
                'is_premium' => false,
                'premium_until' => null,
                'premium_expired_at' => now(),
            ]);
    }

    /**
     * Получить мастеров для напоминания о продлении премиум
     */
    public function getPremiumExpiringReminders(int $days = 7): Collection
    {
        return $this->model->where('is_premium', true)
            ->where('premium_until', '>', now())
            ->where('premium_until', '<=', now()->addDays($days))
            ->whereNull('premium_reminder_sent_at')
            ->get();
    }

    /**
     * Отметить отправку напоминания о премиум
     */
    public function markPremiumReminderSent(int $masterId): bool
    {
        return $this->model->where('id', $masterId)
            ->update(['premium_reminder_sent_at' => now()]) > 0;
    }

    /**
     * Получить неактивных мастеров для уведомления
     */
    public function getInactiveForNotification(int $days = 30): Collection
    {
        return $this->model->where('status', MasterStatus::ACTIVE)
            ->whereDoesntHave('bookings', function ($query) use ($days) {
                $query->where('created_at', '>=', now()->subDays($days));
            })
            ->whereNull('inactivity_notification_sent_at')
            ->orWhere('inactivity_notification_sent_at', '<', now()->subDays($days))
            ->get();
    }

    /**
     * Отметить отправку уведомления о неактивности
     */
    public function markInactivityNotificationSent(int $masterId): bool
    {
        return $this->model->where('id', $masterId)
            ->update(['inactivity_notification_sent_at' => now()]) > 0;
    }

    /**
     * Получить статистику по статусам
     */
    public function getStatusStats(): array
    {
        return $this->model->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Восстановить удаленного мастера
     */
    public function restore(int $masterId): bool
    {
        $master = $this->model->withTrashed()->find($masterId);
        
        if ($master && $master->trashed()) {
            $master->restore();
            $master->update(['status' => MasterStatus::PENDING]);
            return true;
        }

        return false;
    }

    /**
     * Окончательно удалить мастера
     */
    public function forceDelete(int $masterId): bool
    {
        $master = $this->model->withTrashed()->find($masterId);
        
        if ($master) {
            $master->forceDelete();
            return true;
        }

        return false;
    }
}