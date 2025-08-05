<?php

namespace App\Infrastructure\Listeners\User;

use App\Domain\User\Events\UserProfileUpdated;
use App\Domain\User\Repositories\UserRepository;
use App\Infrastructure\Services\EmailService;
use App\Infrastructure\Services\SearchIndexService;
use App\Infrastructure\Services\MediaService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик обновления профиля пользователя
 * 
 * ФУНКЦИИ:
 * - Синхронизация данных с мастер-профилем
 * - Обновление поискового индекса
 * - Валидация критических изменений
 * - Обработка медиа файлов
 * - Уведомления о важных изменениях
 */
class HandleUserProfileUpdated
{
    private UserRepository $userRepository;
    private EmailService $emailService;
    private SearchIndexService $searchIndexService;
    private MediaService $mediaService;

    public function __construct(
        UserRepository $userRepository,
        EmailService $emailService,
        SearchIndexService $searchIndexService,
        MediaService $mediaService
    ) {
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
        $this->searchIndexService = $searchIndexService;
        $this->mediaService = $mediaService;
    }

    /**
     * Обработка события UserProfileUpdated
     */
    public function handle(UserProfileUpdated $event): void
    {
        DB::transaction(function () use ($event) {
            try {
                // 1. Получаем пользователя
                $user = $this->userRepository->findById($event->userId);
                if (!$user) {
                    throw new Exception("Пользователь с ID {$event->userId} не найден");
                }

                // 2. Анализируем изменения
                $changes = $this->analyzeProfileChanges($user, $event->updatedFields, $event->oldData);

                // 3. Валидируем критические изменения
                $this->validateCriticalChanges($user, $changes);

                // 4. Обрабатываем медиа изменения
                $this->processMediaUpdates($user, $event->updatedFields, $changes);

                // 5. Синхронизируем с мастер-профилем если пользователь - мастер
                $this->syncWithMasterProfile($user, $changes);

                // 6. Обновляем поисковый индекс
                $this->updateSearchIndex($user, $changes);

                // 7. Рассчитываем новый процент заполнения профиля
                $completionPercentage = $this->calculateProfileCompletion($user);

                // 8. Создаем запись об изменениях
                $this->logProfileChanges($user, $event, $changes);

                // 9. Отправляем уведомления при критических изменениях
                $this->sendUpdateNotifications($user, $changes, $event);

                Log::info('User profile updated successfully', [
                    'user_id' => $event->userId,
                    'updated_fields' => $event->updatedFields,
                    'critical_changes' => $changes['critical'],
                    'completion_percentage' => $completionPercentage,
                ]);

            } catch (Exception $e) {
                Log::error('Failed to handle UserProfileUpdated event', [
                    'user_id' => $event->userId,
                    'updated_fields' => $event->updatedFields,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Анализировать изменения профиля
     */
    private function analyzeProfileChanges($user, array $updatedFields, array $oldData): array
    {
        $changes = [
            'critical' => [], // Критические изменения
            'important' => [], // Важные изменения
            'media' => [], // Медиа изменения
            'contact' => [], // Контактные данные
            'personal' => [], // Личная информация
        ];

        foreach ($updatedFields as $field) {
            $this->categorizeProfileChange($field, $changes, $user, $oldData);
        }

        return $changes;
    }

    /**
     * Категоризировать изменение профиля
     */
    private function categorizeProfileChange(string $field, array &$changes, $user, array $oldData): void
    {
        // Критические изменения (требуют верификации)
        $criticalFields = ['phone', 'email'];
        if (in_array($field, $criticalFields)) {
            $changes['critical'][] = $field;
            return;
        }

        // Важные изменения
        $importantFields = ['name', 'birth_date', 'city'];
        if (in_array($field, $importantFields)) {
            $changes['important'][] = $field;
            return;
        }

        // Медиа изменения
        if ($field === 'avatar_url') {
            $changes['media'][] = $field;
            return;
        }

        // Контактные данные
        $contactFields = ['address', 'telegram', 'whatsapp'];
        if (in_array($field, $contactFields)) {
            $changes['contact'][] = $field;
            return;
        }

        // Личная информация
        $personalFields = ['gender', 'about', 'interests', 'timezone', 'language'];
        if (in_array($field, $personalFields)) {
            $changes['personal'][] = $field;
            return;
        }
    }

    /**
     * Валидировать критические изменения
     */
    private function validateCriticalChanges($user, array $changes): void
    {
        if (empty($changes['critical'])) {
            return;
        }

        foreach ($changes['critical'] as $field) {
            switch ($field) {
                case 'phone':
                    $this->validatePhoneChange($user);
                    break;
                case 'email':
                    $this->validateEmailChange($user);
                    break;
            }
        }
    }

    /**
     * Валидировать изменение телефона
     */
    private function validatePhoneChange($user): void
    {
        $profile = $user->getProfile();
        if (!$profile || !$profile->phone) {
            return;
        }

        // Проверяем лимит изменений телефона (максимум 3 раза в месяц)
        $monthlyChanges = $this->userRepository->getPhoneChangesCount($user->id, 30);
        if ($monthlyChanges >= 3) {
            throw new Exception("Превышен лимит изменений номера телефона в месяц");
        }

        // Проверяем, что телефон не используется другим пользователем
        $existingUser = $this->userRepository->findByPhone($profile->phone);
        if ($existingUser && $existingUser->id !== $user->id) {
            throw new Exception("Номер телефона уже используется другим пользователем");
        }

        // Отправляем SMS верификацию
        $this->sendPhoneVerification($user, $profile->phone);
    }

    /**
     * Валидировать изменение email
     */
    private function validateEmailChange($user): void
    {
        // Проверяем лимит изменений email (максимум 2 раза в месяц)
        $monthlyChanges = $this->userRepository->getEmailChangesCount($user->id, 30);
        if ($monthlyChanges >= 2) {
            throw new Exception("Превышен лимит изменений email в месяц");
        }

        // Проверяем, что email не используется другим пользователем
        $existingUser = $this->userRepository->findByEmail($user->email);
        if ($existingUser && $existingUser->id !== $user->id) {
            throw new Exception("Email уже используется другим пользователем");
        }

        // Сбрасываем верификацию email
        $user->update(['email_verified_at' => null]);

        // Отправляем новое письмо верификации
        $this->sendEmailVerification($user);
    }

    /**
     * Отправить SMS верификацию телефона
     */
    private function sendPhoneVerification($user, string $phone): void
    {
        try {
            // Генерируем код верификации
            $verificationCode = rand(100000, 999999);
            
            // Сохраняем код в базе
            $this->userRepository->savePhoneVerificationCode($user->id, $phone, $verificationCode);
            
            // Отправляем SMS (здесь должна быть интеграция с SMS-сервисом)
            Log::info('Phone verification code generated', [
                'user_id' => $user->id,
                'phone' => $phone,
                'code' => $verificationCode, // В продакшене не логируем код
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send phone verification', [
                'user_id' => $user->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить email верификацию
     */
    private function sendEmailVerification($user): void
    {
        try {
            $this->emailService->sendEmailVerification($user->email, [
                'user_name' => $user->getProfile()?->name ?? 'Пользователь',
                'verification_url' => $this->generateEmailVerificationUrl($user),
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send email verification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Генерировать URL верификации email
     */
    private function generateEmailVerificationUrl($user): string
    {
        return url()->signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);
    }

    /**
     * Обработать медиа обновления
     */
    private function processMediaUpdates($user, array $updatedFields, array $changes): void
    {
        if (!in_array('avatar_url', $changes['media'])) {
            return;
        }

        try {
            $profile = $user->getProfile();
            if (!$profile || !$profile->avatar_url) {
                return;
            }

            // Обрабатываем новый аватар
            $this->mediaService->processUserAvatar($user->id, $profile->avatar_url);

        } catch (Exception $e) {
            Log::warning('Failed to process avatar update', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Синхронизировать с мастер-профилем
     */
    private function syncWithMasterProfile($user, array $changes): void
    {
        if (!$user->isMaster()) {
            return;
        }

        $masterProfile = $user->getMasterProfile();
        if (!$masterProfile) {
            return;
        }

        // Определяем поля для синхронизации
        $syncFields = array_intersect(
            array_merge($changes['important'], $changes['contact']),
            ['name', 'phone', 'city', 'avatar_url']
        );

        if (empty($syncFields)) {
            return;
        }

        try {
            // Обновляем мастер-профиль через событие
            $profile = $user->getProfile();
            $updateData = [];

            foreach ($syncFields as $field) {
                switch ($field) {
                    case 'name':
                        $updateData['display_name'] = $profile->name;
                        break;
                    case 'phone':
                        $updateData['phone'] = $profile->phone;
                        break;
                    case 'city':
                        $updateData['city'] = $profile->city;
                        break;
                    case 'avatar_url':
                        $updateData['avatar_url'] = $profile->avatar_url;
                        break;
                }
            }

            if (!empty($updateData)) {
                event(new \App\Domain\Master\Events\MasterProfileUpdated(
                    masterProfileId: $masterProfile->id,
                    userId: $user->id,
                    updatedData: $updateData,
                    changedFields: $syncFields,
                    updatedBy: $user->id
                ));
            }

        } catch (Exception $e) {
            Log::warning('Failed to sync user profile with master profile', [
                'user_id' => $user->id,
                'master_profile_id' => $masterProfile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обновить поисковый индекс
     */
    private function updateSearchIndex($user, array $changes): void
    {
        // Обновляем индекс только если изменились поисковые поля
        $searchableFields = ['name', 'city', 'about'];
        $needsReindex = !empty(array_intersect($searchableFields, 
            array_merge($changes['important'], $changes['personal'])));

        if (!$needsReindex) {
            return;
        }

        try {
            $this->searchIndexService->indexUser($user);

            // Если пользователь - мастер, обновляем его мастер-профиль в индексе
            if ($user->isMaster()) {
                $masterProfile = $user->getMasterProfile();
                if ($masterProfile) {
                    $this->searchIndexService->reindexMasterProfile($masterProfile);
                }
            }

        } catch (Exception $e) {
            Log::warning('Failed to update search index for user profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Рассчитать процент заполнения профиля
     */
    private function calculateProfileCompletion($user): int
    {
        $profile = $user->getProfile();
        if (!$profile) {
            return 0;
        }

        $requiredFields = [
            'name' => 20,      // 20% за имя
            'phone' => 15,     // 15% за телефон
            'city' => 10,      // 10% за город
            'birth_date' => 10, // 10% за дату рождения
            'gender' => 5,     // 5% за пол
            'avatar_url' => 15, // 15% за аватар
            'about' => 10,     // 10% за описание
            'interests' => 5,  // 5% за интересы
        ];

        $score = 0;
        foreach ($requiredFields as $field => $points) {
            if (!empty($profile->$field)) {
                $score += $points;
            }
        }

        // Дополнительные баллы за верифицированные контакты
        if ($user->hasVerifiedEmail()) {
            $score += 5;
        }

        if ($profile->phone_verified_at) {
            $score += 5;
        }

        // Обновляем процент в профиле
        $profile->update(['completion_percentage' => min(100, $score)]);

        return min(100, $score);
    }

    /**
     * Логировать изменения профиля
     */
    private function logProfileChanges($user, UserProfileUpdated $event, array $changes): void
    {
        $this->userRepository->createProfileChangeLog([
            'user_id' => $user->id,
            'updated_fields' => $event->updatedFields,
            'old_data' => $event->oldData,
            'changes_summary' => $changes,
            'updated_at' => $event->updatedAt,
            'ip_address' => $event->ipAddress ?? null,
            'user_agent' => $event->userAgent ?? null,
        ]);
    }

    /**
     * Отправить уведомления об обновлениях
     */
    private function sendUpdateNotifications($user, array $changes, UserProfileUpdated $event): void
    {
        try {
            // Уведомляем о критических изменениях
            if (!empty($changes['critical'])) {
                $this->emailService->sendCriticalProfileChangesNotification($user->email, [
                    'user_name' => $user->getProfile()?->name ?? 'Пользователь',
                    'changed_fields' => $changes['critical'],
                    'changed_at' => $event->updatedAt->format('d.m.Y H:i'),
                ]);
            }

            // Поздравляем с достижением высокого процента заполнения
            $completionPercentage = $this->calculateProfileCompletion($user);
            if ($completionPercentage >= 80 && $completionPercentage < 85) {
                $this->emailService->sendProfileCompletionCongratulations($user->email, [
                    'user_name' => $user->getProfile()?->name,
                    'completion_percentage' => $completionPercentage,
                ]);
            }

        } catch (Exception $e) {
            Log::warning('Failed to send profile update notifications', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Регистрация обработчика событий
     */
    public static function register(Dispatcher $events): void
    {
        $events->listen(UserProfileUpdated::class, [self::class, 'handle']);
    }
}