<?php

namespace App\Domain\User\Actions;

use App\Domain\User\Models\User;
use App\Domain\User\DTOs\UserProfileData;
use App\Domain\User\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для обновления профиля пользователя
 */
class UpdateProfileAction
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Обновить профиль пользователя
     */
    public function execute(User $user, UserProfileData $profileData): array
    {
        try {
            return DB::transaction(function () use ($user, $profileData) {
                // Обновляем профиль
                $updated = $this->userService->updateProfile($user, $profileData->toArray());

                if (!$updated) {
                    return [
                        'success' => false,
                        'message' => 'Не удалось обновить профиль',
                    ];
                }

                // Обновляем процент заполнения профиля
                $this->updateProfileCompletion($user);

                Log::info('Profile updated', [
                    'user_id' => $user->id,
                    'updated_fields' => array_keys($profileData->toArray()),
                ]);

                return [
                    'success' => true,
                    'message' => 'Профиль успешно обновлен',
                    'user' => $user->fresh(['profile']),
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to update profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при обновлении профиля',
            ];
        }
    }

    /**
     * Обновить процент заполнения профиля
     */
    private function updateProfileCompletion(User $user): void
    {
        $profile = $user->profile;
        if (!$profile) {
            return;
        }

        $fields = [
            'name' => 20,
            'phone' => 20,
            'city' => 10,
            'birth_date' => 10,
            'about' => 20,
            'avatar' => 20,
        ];

        $completion = 0;
        foreach ($fields as $field => $weight) {
            if (!empty($profile->$field)) {
                $completion += $weight;
            }
        }

        $user->profile_completion = min($completion, 100);
        $user->save();
    }
}