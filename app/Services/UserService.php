<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

/**
 * Сервис для управления пользователями
 */
class UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Создать нового пользователя
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Создаем пользователя
            $user = $this->userRepository->create([
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'] ?? UserRole::CLIENT,
                'status' => UserStatus::PENDING,
            ]);

            // Обновляем профиль если есть дополнительные данные
            if (!empty($data['name']) || !empty($data['phone'])) {
                $this->updateProfile($user, [
                    'name' => $data['name'] ?? 'Пользователь',
                    'phone' => $data['phone'] ?? null,
                ]);
            }

            Log::info('User created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role->value,
            ]);

            return $user;
        });
    }

    /**
     * Обновить профиль пользователя
     */
    public function updateProfile(User $user, array $profileData): bool
    {
        try {
            $updated = $this->userRepository->updateProfile($user, $profileData);

            if ($updated) {
                Log::info('User profile updated', [
                    'user_id' => $user->id,
                    'updated_fields' => array_keys($profileData),
                ]);
            }

            return $updated;

        } catch (\Exception $e) {
            Log::error('Profile update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Обновить настройки пользователя
     */
    public function updateSettings(User $user, array $settingsData): bool
    {
        try {
            $updated = $this->userRepository->updateSettings($user, $settingsData);

            if ($updated) {
                Log::info('User settings updated', [
                    'user_id' => $user->id,
                ]);
            }

            return $updated;

        } catch (\Exception $e) {
            Log::error('Settings update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Изменить пароль пользователя
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): array
    {
        try {
            // Проверяем текущий пароль
            if (!Hash::check($currentPassword, $user->password)) {
                return [
                    'success' => false,
                    'error' => 'Неверный текущий пароль'
                ];
            }

            // Обновляем пароль
            $user->password = Hash::make($newPassword);
            $user->save();

            Log::info('Password changed', ['user_id' => $user->id]);

            return [
                'success' => true,
                'message' => 'Пароль успешно изменен'
            ];

        } catch (\Exception $e) {
            Log::error('Password change failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Ошибка при изменении пароля'
            ];
        }
    }

    /**
     * Загрузить аватар пользователя
     */
    public function uploadAvatar(User $user, UploadedFile $file): array
    {
        try {
            // Валидация файла
            if (!$file->isValid()) {
                return [
                    'success' => false,
                    'error' => 'Некорректный файл'
                ];
            }

            // Проверка типа файла
            $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
            $extension = strtolower($file->getClientOriginalExtension());
            
            if (!in_array($extension, $allowedTypes)) {
                return [
                    'success' => false,
                    'error' => 'Разрешены только изображения: ' . implode(', ', $allowedTypes)
                ];
            }

            // Проверка размера (5MB)
            if ($file->getSize() > 5 * 1024 * 1024) {
                return [
                    'success' => false,
                    'error' => 'Размер файла не должен превышать 5MB'
                ];
            }

            // Генерируем уникальное имя
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $extension;
            
            // Сохраняем файл
            $path = $file->storeAs('public/avatars', $filename);

            // Обновляем профиль пользователя
            $profile = $user->getProfile();
            $profile->updateAvatar($path);

            Log::info('Avatar uploaded', [
                'user_id' => $user->id,
                'filename' => $filename,
            ]);

            return [
                'success' => true,
                'avatar_url' => Storage::url($path),
                'message' => 'Аватар успешно загружен'
            ];

        } catch (\Exception $e) {
            Log::error('Avatar upload failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Ошибка при загрузке аватара'
            ];
        }
    }

    /**
     * Удалить аватар пользователя
     */
    public function deleteAvatar(User $user): bool
    {
        try {
            $profile = $user->getProfile();
            $profile->deleteAvatar();

            Log::info('Avatar deleted', ['user_id' => $user->id]);

            return true;

        } catch (\Exception $e) {
            Log::error('Avatar deletion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Активировать пользователя
     */
    public function activate(User $user): bool
    {
        try {
            $activated = $user->activate();

            if ($activated) {
                Log::info('User activated', ['user_id' => $user->id]);
                
                // Можно отправить уведомление пользователю
                // event(new UserActivated($user));
            }

            return $activated;

        } catch (\Exception $e) {
            Log::error('User activation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Деактивировать пользователя
     */
    public function deactivate(User $user): bool
    {
        try {
            $deactivated = $user->deactivate();

            if ($deactivated) {
                Log::info('User deactivated', ['user_id' => $user->id]);
            }

            return $deactivated;

        } catch (\Exception $e) {
            Log::error('User deactivation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Заблокировать пользователя
     */
    public function suspend(User $user, string $reason = ''): bool
    {
        try {
            $suspended = $user->suspend();

            if ($suspended) {
                Log::warning('User suspended', [
                    'user_id' => $user->id,
                    'reason' => $reason,
                ]);

                // Можно отправить уведомление пользователю
                // event(new UserSuspended($user, $reason));
            }

            return $suspended;

        } catch (\Exception $e) {
            Log::error('User suspension failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Забанить пользователя
     */
    public function ban(User $user, string $reason = ''): bool
    {
        try {
            $banned = $user->ban();

            if ($banned) {
                Log::warning('User banned', [
                    'user_id' => $user->id,
                    'reason' => $reason,
                ]);

                // Можно отправить уведомление пользователю
                // event(new UserBanned($user, $reason));
            }

            return $banned;

        } catch (\Exception $e) {
            Log::error('User ban failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Изменить роль пользователя
     */
    public function changeRole(User $user, UserRole $newRole): bool
    {
        try {
            $oldRole = $user->role;
            $changed = $user->changeRole($newRole);

            if ($changed) {
                Log::info('User role changed', [
                    'user_id' => $user->id,
                    'old_role' => $oldRole?->value,
                    'new_role' => $newRole->value,
                ]);

                // Если пользователь стал мастером, создаем профиль мастера
                if ($newRole === UserRole::MASTER && !$user->masterProfile) {
                    $user->masterProfile()->create([
                        'display_name' => $user->display_name,
                        'city' => $user->profile?->city ?? 'Москва',
                        'status' => 'draft',
                    ]);
                }
            }

            return $changed;

        } catch (\Exception $e) {
            Log::error('Role change failed', [
                'user_id' => $user->id,
                'new_role' => $newRole->value,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Получить статистику пользователя
     */
    public function getUserStats(User $user): array
    {
        $stats = [
            'profile_completion' => $user->profile_completion,
            'registration_date' => $user->created_at,
            'last_activity' => $user->updated_at,
            'email_verified' => !is_null($user->email_verified_at),
        ];

        // Добавляем статистику по роли
        if ($user->isClient()) {
            $stats['bookings_count'] = $user->bookings()->count();
            $stats['reviews_count'] = $user->reviews()->count();
            $stats['favorites_count'] = $user->favorites()->count();
        } elseif ($user->isMaster()) {
            $stats['ads_count'] = $user->ads()->count();
            $stats['active_ads_count'] = $user->ads()->where('status', 'active')->count();
            if ($user->masterProfile) {
                $stats['master_rating'] = $user->masterProfile->rating;
                $stats['master_reviews_count'] = $user->masterProfile->reviews_count;
            }
        }

        return $stats;
    }

    /**
     * Экспорт данных пользователя (GDPR)
     */
    public function exportUserData(User $user): array
    {
        return [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role_label,
                'status' => $user->status_label,
                'created_at' => $user->created_at,
                'email_verified_at' => $user->email_verified_at,
            ],
            'profile' => $user->profile?->toArray(),
            'settings' => $user->settings?->exportSettings(),
            'ads' => $user->ads()->get()->toArray(),
            'bookings' => $user->bookings()->get()->toArray(),
            'reviews' => $user->reviews()->get()->toArray(),
        ];
    }

    /**
     * Удалить все данные пользователя (GDPR)
     */
    public function deleteUserData(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            try {
                // Удаляем связанные данные
                $user->ads()->delete();
                $user->bookings()->delete();
                $user->reviews()->delete();
                $user->favorites()->detach();
                
                // Удаляем профиль и настройки
                $user->profile?->delete();
                $user->settings?->delete();
                $user->masterProfile?->delete();
                $user->balance?->delete();

                // Помечаем пользователя как удаленного
                $user->markAsDeleted();

                Log::info('User data deleted', ['user_id' => $user->id]);

                return true;

            } catch (\Exception $e) {
                Log::error('User data deletion failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        });
    }
}