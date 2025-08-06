<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\User;
use App\Enums\MasterStatus;
use App\Domain\Master\Repositories\MasterRepository;
use App\Infrastructure\Notification\NotificationService;
use Exception;

/**
 * Сервис модерации профилей мастеров
 */
class MasterModerationService
{
    public function __construct(
        private MasterRepository $repository,
        private NotificationService $notificationService
    ) {}

    /**
     * Одобрить профиль (для модератора)
     */
    public function approveProfile(int $masterId, User $moderator): MasterProfile
    {
        $profile = $this->repository->findById($masterId);
        
        if (!$profile) {
            throw new Exception('Профиль мастера не найден');
        }

        if ($profile->status !== MasterStatus::PENDING->value) {
            throw new Exception('Профиль не находится на модерации');
        }

        $this->repository->update($profile, [
            'status' => MasterStatus::ACTIVE->value,
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $moderator->id,
        ]);

        // Отправляем уведомление мастеру
        $this->notificationService->sendProfileApproved($profile);

        return $profile;
    }

    /**
     * Отклонить профиль (для модератора)
     */
    public function rejectProfile(int $masterId, string $reason, User $moderator): MasterProfile
    {
        $profile = $this->repository->findById($masterId);
        
        if (!$profile) {
            throw new Exception('Профиль мастера не найден');
        }

        if ($profile->status !== MasterStatus::PENDING->value) {
            throw new Exception('Профиль не находится на модерации');
        }

        $this->repository->update($profile, [
            'status' => MasterStatus::DRAFT->value,
            'moderation_notes' => $reason,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);

        // Отправляем уведомление с причиной отклонения
        $this->notificationService->sendProfileRejected($profile, $reason);

        return $profile;
    }

    /**
     * Заблокировать профиль
     */
    public function blockProfile(int $masterId, string $reason, User $admin): MasterProfile
    {
        $profile = $this->repository->findById($masterId);
        
        if (!$profile) {
            throw new Exception('Профиль мастера не найден');
        }

        $this->repository->update($profile, [
            'status' => MasterStatus::BLOCKED->value,
            'block_reason' => $reason,
            'blocked_by' => $admin->id,
            'blocked_at' => now(),
        ]);

        // Отменяем все активные бронирования
        $this->cancelActiveBookings($profile);

        // Отправляем уведомление
        $this->notificationService->sendProfileBlocked($profile, $reason);

        return $profile;
    }

    /**
     * Отменить активные бронирования
     */
    private function cancelActiveBookings(MasterProfile $profile): void
    {
        $activeBookings = $profile->bookings()
            ->whereIn('status', ['pending', 'confirmed'])
            ->get();

        foreach ($activeBookings as $booking) {
            $booking->cancel('Мастер временно недоступен');
        }
    }
}