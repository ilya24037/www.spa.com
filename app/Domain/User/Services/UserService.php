<?php

namespace App\Domain\User\Services;

use App\Domain\User\Models\User;
use App\Domain\User\Repositories\UserRepository;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Support\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

/**
 * Сервис для управления пользователями
 */
class UserService extends BaseService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        parent::__construct($userRepository);
        $this->userRepository = $userRepository;
    }

    /**
     * Зарегистрировать нового пользователя (согласно плану)
     */
    public function register(array $data, ?string $ip = null, ?string $userAgent = null): User
    {
        // КРИТИЧЕСКАЯ ВАЛИДАЦИЯ
        $this->validateRegistrationData($data);
        
        return DB::transaction(function () use ($data, $ip, $userAgent) {
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

            Log::info('User registered', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role->value,
            ]);

            return $user;
        })->tap(function ($user) use ($data, $ip, $userAgent) {
            // Отправляем событие ПОСЛЕ успешной транзакции с безопасными данными
            event(new \App\Domain\User\Events\UserRegistered(
                userId: $user->id,
                email: $user->email,
                role: $user->role->value,
                userData: $this->sanitizeUserData($data), // ✅ БЕЗОПАСНЫЕ данные
                registrationSource: $data['source'] ?? 'web',
                referralCode: $data['referral_code'] ?? null,
                registrationIP: $ip,
                userAgent: $userAgent,
                registeredAt: now()
            ));
        });
    }

    /**
     * Валидация данных регистрации
     */
    private function validateRegistrationData(array $data): void
    {
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Некорректный email');
        }

        if (empty($data['password']) || strlen($data['password']) < 8) {
            throw new \InvalidArgumentException('Пароль должен содержать минимум 8 символов');
        }

        // Проверяем уникальность email
        if ($this->userRepository->findByEmail($data['email'])) {
            throw new \InvalidArgumentException('Пользователь с таким email уже существует');
        }
    }

    /**
     * Очистка пользовательских данных от чувствительной информации
     */
    private function sanitizeUserData(array $data): array
    {
        $safe = $data;
        unset($safe['password']); // ✅ УДАЛЯЕМ ПАРОЛЬ
        return $safe;
    }

    /**
     * Создать нового пользователя (устаревший метод, используйте register)
     * @deprecated Используйте register() вместо этого метода
     */
    public function create(array $data): User
    {
        return $this->register($data);
    }

    /**
     * Обновить профиль пользователя (теперь использует HasProfile трейт)
     */
    public function updateProfile(User $user, array $profileData): bool
    {
        // КРИТИЧЕСКАЯ ВАЛИДАЦИЯ ПРОФИЛЯ
        $this->validateProfileData($profileData);
        
        try {
            // Используем валидированный метод из трейта
            $updated = $user->updateProfile($profileData);

            if ($updated) {
                Log::info('User profile updated', [
                    'user_id' => $user->id,
                    'updated_fields' => array_keys($profileData),
                ]);

                // Отправляем событие обновления профиля
                event(new \App\Domain\User\Events\UserProfileUpdated(
                    userId: $user->id,
                    updatedFields: array_keys($profileData),
                    oldData: [], // TODO: Передавать старые данные для сравнения
                    updatedAt: now()
                ));
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
     * Валидация данных профиля
     */
    private function validateProfileData(array $data): void
    {
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Некорректный email в профиле');
        }

        if (isset($data['phone']) && !empty($data['phone'])) {
            if (!preg_match('/^\+?[1-9]\d{1,14}$/', $data['phone'])) {
                throw new \InvalidArgumentException('Некорректный формат телефона');
            }
        }

        if (isset($data['name']) && (empty($data['name']) || strlen($data['name']) > 255)) {
            throw new \InvalidArgumentException('Имя не может быть пустым или больше 255 символов');
        }
    }

    /**
     * Обновить настройки пользователя (теперь использует HasProfile трейт)
     */
    public function updateSettings(User $user, array $settingsData): bool
    {
        try {
            // Используем валидированный метод из трейта
            $updated = $user->updateSettings($settingsData);

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
            // КРИТИЧЕСКАЯ ВАЛИДАЦИЯ ПАРОЛЯ
            $this->validateNewPassword($newPassword, $currentPassword);
            
            // Проверяем текущий пароль
            if (!Hash::check($currentPassword, $user->password)) {
                return [
                    'success' => false,
                    'error' => 'Неверный текущий пароль'
                ];
            }

            // Проверяем что новый пароль отличается от текущего
            if (Hash::check($newPassword, $user->password)) {
                return [
                    'success' => false,
                    'error' => 'Новый пароль должен отличаться от текущего'
                ];
            }

            // Обновляем пароль (безопасным способом)
            $user->password = Hash::make($newPassword);
            $updated = $user->save();
            
            if (!$updated) {
                return [
                    'success' => false,
                    'error' => 'Не удалось обновить пароль'
                ];
            }

            Log::info('Password changed', ['user_id' => $user->id]);

            return [
                'success' => true,
                'message' => 'Пароль успешно изменен'
            ];

        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
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
     * Валидация нового пароля
     */
    private function validateNewPassword(string $newPassword, string $currentPassword): void
    {
        if (strlen($newPassword) < 8) {
            throw new \InvalidArgumentException('Пароль должен содержать минимум 8 символов');
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $newPassword)) {
            throw new \InvalidArgumentException('Пароль должен содержать строчные, заглавные буквы и цифры');
        }

        if ($newPassword === $currentPassword) {
            throw new \InvalidArgumentException('Новый пароль должен отличаться от текущего');
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
            $profile = $user->ensureProfile();
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
            // Для удаления используем безопасный геттер
            $profile = $user->getProfile();
            if ($profile) {
                $profile->deleteAvatar();
            }

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

                // Если пользователь стал мастером, создаем профиль мастера через событие
                if ($newRole === UserRole::MASTER && !$user->getMasterProfile()) {
                    $user->createMasterProfile([
                        'name' => $user->getProfile()?->name ?? 'Мастер',
                        'city' => $user->getProfile()?->city ?? 'Москва',
                        'services' => [],
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

        // Добавляем статистику по роли через Integration Services
        if ($user->isClient()) {
            $bookingStats = $user->getBookingStatistics();
            $stats['bookings_count'] = $bookingStats['total_bookings'] ?? 0;
            $stats['completed_bookings'] = $bookingStats['completed_bookings'] ?? 0;
            // Используем новые DDD методы интеграции
            $stats['reviews_count'] = $user->getReceivedReviewsCount();
            $stats['favorites_count'] = $user->getFavoritesCount();
        } elseif ($user->isMaster()) {
            $masterStats = $user->getMasterStatistics();
            $masterRating = $user->getMasterRating();
            $stats['master_rating'] = $masterRating['rating'] ?? 0;
            $stats['master_reviews_count'] = $masterRating['reviews_count'] ?? 0;
            // Используем новые DDD методы интеграции для объявлений
            $stats['ads_count'] = $user->getAdsCount();
            $stats['active_ads_count'] = $user->getActiveAds()->count();
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
            'profile' => $user->getProfile()?->toArray(),
            'settings' => $user->getSettings()?->toArray(),
            // Используем новые DDD методы интеграции
            'ads' => $user->getAds()->toArray(),
            'bookings' => $user->getBookings()->toArray(),
            'reviews' => $user->getReceivedReviews()->toArray(),
        ];
    }

    /**
     * Удалить все данные пользователя (GDPR)
     */
    public function deleteUserData(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            try {
                // Удаляем связанные данные через Integration Services
                $user->cancelAllBookings('User deletion');
                
                // Удаляем профиль и настройки
                $user->getProfile()?->delete();
                $user->getSettings()?->delete();
                
                // Деактивируем мастер профиль через интеграцию
                $masterProfile = $user->getMasterProfile();
                if ($masterProfile) {
                    $user->deactivateMasterProfile($masterProfile->id, 'User deletion');
                }
                
                // Удаляем связанные данные через DDD методы
                // Объявления удаляются через каскад
                // Отзывы сохраняются для истории
                // Избранное очищается через метод интеграции
                if (method_exists($user, 'clearAllFavorites')) {
                    $user->clearAllFavorites();
                }
                // $user->balance?->delete();

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

    /**
     * Переключить статус мастер-профиля
     */
    public function toggleMasterProfile(User $user, int $masterId): bool
    {
        $profile = $user->getMasterProfile($masterId);
        if (!$profile) {
            throw new \InvalidArgumentException('Профиль мастера не найден');
        }

        $newStatus = !$profile->is_active;
        $updated = $profile->update(['is_active' => $newStatus]);

        if ($updated) {
            Log::info('Master profile status toggled', [
                'user_id' => $user->id,
                'master_id' => $masterId,
                'new_status' => $newStatus
            ]);
        }

        return $updated;
    }

    /**
     * Опубликовать мастер-профиль
     */
    public function publishMasterProfile(User $user, int $masterId): array
    {
        $profile = $user->getMasterProfile($masterId);
        if (!$profile) {
            throw new \InvalidArgumentException('Профиль мастера не найден');
        }

        // Проверяем готовность к публикации
        if (!$this->canPublishMasterProfile($profile)) {
            return [
                'success' => false,
                'error' => 'Анкета не готова к публикации. Заполните все обязательные поля.'
            ];
        }

        $updated = $profile->update(['status' => 'active']);

        if ($updated) {
            Log::info('Master profile published', [
                'user_id' => $user->id,
                'master_id' => $masterId
            ]);
        }

        return [
            'success' => $updated,
            'message' => $updated ? 'Анкета опубликована' : 'Ошибка публикации'
        ];
    }

    /**
     * Восстановить мастер-профиль из архива
     */
    public function restoreMasterProfile(User $user, int $masterId): bool
    {
        $profile = $user->getMasterProfile($masterId);
        if (!$profile) {
            throw new \InvalidArgumentException('Профиль мастера не найден');
        }

        $updated = $profile->update(['status' => 'active']);

        if ($updated) {
            Log::info('Master profile restored', [
                'user_id' => $user->id,
                'master_id' => $masterId
            ]);
        }

        return $updated;
    }

    /**
     * Архивировать мастер-профиль
     */
    public function archiveMasterProfile(User $user, int $masterId): bool
    {
        $profile = $user->getMasterProfile($masterId);
        if (!$profile) {
            throw new \InvalidArgumentException('Профиль мастера не найден');
        }

        $updated = $profile->update(['status' => 'archived']);

        if ($updated) {
            Log::info('Master profile archived', [
                'user_id' => $user->id,
                'master_id' => $masterId
            ]);
        }

        return $updated;
    }

    /**
     * Удалить мастер-профиль
     */
    public function deleteMasterProfile(User $user, int $masterId): array
    {
        $profile = $user->getMasterProfile($masterId);
        if (!$profile) {
            throw new \InvalidArgumentException('Профиль мастера не найден');
        }

        // Проверяем, можно ли удалить
        if ($profile->hasActiveBookings()) {
            return [
                'success' => false,
                'error' => 'Невозможно удалить анкету с активными бронированиями'
            ];
        }

        $deleted = $profile->delete();

        if ($deleted) {
            Log::info('Master profile deleted', [
                'user_id' => $user->id,
                'master_id' => $masterId
            ]);
        }

        return [
            'success' => $deleted,
            'message' => $deleted ? 'Анкета удалена' : 'Ошибка удаления'
        ];
    }

    /**
     * Проверить готовность профиля к публикации
     */
    private function canPublishMasterProfile($profile): bool
    {
        // Проверяем обязательные поля
        $requiredFields = [
            'display_name',
            'city',
            'phone',
            'bio',
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($profile->$field)) {
                return false;
            }
        }
        
        // Проверяем наличие хотя бы одной услуги
        if ($profile->services()->count() === 0) {
            return false;
        }
        
        // Проверяем наличие фото
        if ($profile->photos()->count() === 0) {
            return false;
        }
        
        return true;
    }
}