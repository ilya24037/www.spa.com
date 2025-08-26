<?php

namespace App\Infrastructure\Handlers\UserProfile;

use App\Infrastructure\Services\MediaService;
use App\Infrastructure\Services\SearchIndexService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик синхронизации профиля с другими сервисами
 */
class ProfileSyncHandler
{
    public function __construct(
        private MediaService $mediaService,
        private SearchIndexService $searchIndexService
    ) {}

    /**
     * Обработать медиа обновления
     */
    public function processMediaUpdates($user, array $updatedFields, array $changes): void
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
    public function syncWithMasterProfile($user, array $changes): void
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
    public function updateSearchIndex($user, array $changes): void
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
     * Синхронизировать с внешними сервисами
     */
    public function syncWithExternalServices($user, array $changes): void
    {
        // Синхронизация с CRM системой
        $this->syncWithCRM($user, $changes);

        // Синхронизация с аналитикой
        $this->syncWithAnalytics($user, $changes);

        // Синхронизация с email-маркетингом
        $this->syncWithEmailMarketing($user, $changes);
    }

    /**
     * Синхронизация с CRM
     */
    protected function syncWithCRM($user, array $changes): void
    {
        if (empty(array_merge($changes['important'], $changes['contact']))) {
            return;
        }

        try {
            // Здесь будет интеграция с CRM системой
            Log::info('User profile synced with CRM', [
                'user_id' => $user->id,
                'synced_fields' => array_merge($changes['important'], $changes['contact']),
            ]);

        } catch (Exception $e) {
            Log::warning('Failed to sync user profile with CRM', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Синхронизация с аналитикой
     */
    protected function syncWithAnalytics($user, array $changes): void
    {
        try {
            // Отправляем событие в аналитику
            // Здесь будет интеграция с системой аналитики (Google Analytics, Mixpanel и т.д.)
            
            Log::info('User profile update tracked in analytics', [
                'user_id' => $user->id,
                'changed_fields' => array_keys($changes),
                'profile_completion' => $this->getProfileCompletion($user),
            ]);

        } catch (Exception $e) {
            Log::warning('Failed to track user profile update in analytics', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Синхронизация с email-маркетингом
     */
    protected function syncWithEmailMarketing($user, array $changes): void
    {
        // Обновляем данные только при изменении важных полей
        $relevantFields = array_merge($changes['important'], $changes['contact']);
        if (empty($relevantFields)) {
            return;
        }

        try {
            // Здесь будет интеграция с email-маркетингом (MailChimp, SendGrid и т.д.)
            Log::info('User profile synced with email marketing', [
                'user_id' => $user->id,
                'email' => $user->email,
                'synced_fields' => $relevantFields,
            ]);

        } catch (Exception $e) {
            Log::warning('Failed to sync user profile with email marketing', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Получить процент заполнения профиля (упрощенная версия)
     */
    protected function getProfileCompletion($user): int
    {
        $profile = $user->getProfile();
        return $profile ? ($profile->completion_percentage ?? 0) : 0;
    }

    /**
     * Обновить кеш пользователя
     */
    public function updateUserCache($user): void
    {
        try {
            // Инвалидируем кеш пользователя
            \Illuminate\Support\Facades\Cache::forget("user.{$user->id}");
            \Illuminate\Support\Facades\Cache::forget("user.profile.{$user->id}");
            
            if ($user->isMaster()) {
                $masterProfile = $user->getMasterProfile();
                if ($masterProfile) {
                    \Illuminate\Support\Facades\Cache::forget("master.profile.{$masterProfile->id}");
                }
            }

        } catch (Exception $e) {
            Log::warning('Failed to update user cache', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}