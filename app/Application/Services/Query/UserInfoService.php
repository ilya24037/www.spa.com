<?php

namespace App\Application\Services\Query;

use App\Domain\User\Contracts\UserQueryInterface;

/**
 * Сервис получения информации о пользователях
 */
class UserInfoService
{
    public function __construct(
        private UserQueryInterface $userQuery
    ) {}

    /**
     * Получить информацию о пользователе для мастера
     */
    public function getUserInfoForMaster(int $userId): ?array
    {
        $users = $this->userQuery->searchUsers('', ['id' => $userId]);
        $user = $users->first();
        
        return $user ? [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'created_at' => $user->created_at,
            'email_verified_at' => $user->email_verified_at,
            'profile' => $user->profile ?? null,
            'settings' => $user->settings ?? null,
        ] : null;
    }

    /**
     * Получить расширенную информацию о пользователе
     */
    public function getExtendedUserInfo(int $userId): ?array
    {
        $basicInfo = $this->getUserInfoForMaster($userId);
        
        if (!$basicInfo) {
            return null;
        }

        return array_merge($basicInfo, [
            'account_age_days' => $this->calculateAccountAge($basicInfo['created_at']),
            'verification_status' => $this->getVerificationStatus($basicInfo),
            'activity_level' => $this->estimateActivityLevel($userId),
        ]);
    }

    /**
     * Получить информацию о пользователях для множества мастеров
     */
    public function getUsersInfoForMasters(array $userIds): array
    {
        $users = $this->userQuery->searchUsers('', ['ids' => $userIds]);
        
        return $users->keyBy('id')->map(function ($user) {
            return [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'created_at' => $user->created_at,
                'email_verified_at' => $user->email_verified_at,
                'profile' => $user->profile ?? null,
                'settings' => $user->settings ?? null,
            ];
        })->toArray();
    }

    /**
     * Рассчитать возраст аккаунта в днях
     */
    private function calculateAccountAge($createdAt): int
    {
        return now()->diffInDays($createdAt);
    }

    /**
     * Получить статус верификации
     */
    private function getVerificationStatus(array $userInfo): string
    {
        if (!empty($userInfo['email_verified_at'])) {
            return 'verified';
        }
        
        return 'unverified';
    }

    /**
     * Оценить уровень активности
     */
    private function estimateActivityLevel(int $userId): string
    {
        // Здесь должна быть интеграция с системой аналитики
        // Пока возвращаем заглушку
        return match(rand(1, 3)) {
            1 => 'low',
            2 => 'medium',
            3 => 'high',
        };
    }
}