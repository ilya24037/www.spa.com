<?php

namespace App\Infrastructure\Listeners\Master;

use App\Domain\Master\Events\MasterProfileCreated;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Master\Services\MasterService;
use App\Domain\Master\Services\MasterNotificationService;
use App\Infrastructure\Services\SearchIndexService;
use App\Infrastructure\Services\MediaService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик создания профиля мастера
 * 
 * ФУНКЦИИ:
 * - Создание записи профиля в БД
 * - Индексация в поиске
 * - Создание дефолтных настроек
 * - Отправка welcome уведомлений
 * - Валидация и обработка медиа
 */
class HandleMasterProfileCreated
{
    private MasterRepository $masterRepository;
    private MasterService $masterService;
    private MasterNotificationService $notificationService;
    private SearchIndexService $searchIndexService;
    private MediaService $mediaService;

    public function __construct(
        MasterRepository $masterRepository,
        MasterService $masterService,
        MasterNotificationService $notificationService,
        SearchIndexService $searchIndexService,
        MediaService $mediaService
    ) {
        $this->masterRepository = $masterRepository;
        $this->masterService = $masterService;
        $this->notificationService = $notificationService;
        $this->searchIndexService = $searchIndexService;
        $this->mediaService = $mediaService;
    }

    /**
     * Обработка события MasterProfileCreated
     */
    public function handle(MasterProfileCreated $event): void
    {
        DB::transaction(function () use ($event) {
            try {
                // 1. Создаем профиль мастера
                $masterProfile = $this->createMasterProfile($event);

                // 2. Создаем дефолтные настройки
                $this->createDefaultSettings($masterProfile, $event);

                // 3. Обрабатываем медиа файлы
                $this->processProfileMedia($masterProfile, $event);

                // 4. Создаем дефолтное расписание
                $this->createDefaultSchedule($masterProfile);

                // 5. Добавляем в поисковый индекс
                $this->addToSearchIndex($masterProfile);

                // 6. Обновляем статистику пользователя
                $this->updateUserMasterStats($event->userId);

                // 7. Отправляем уведомления
                $this->sendProfileCreatedNotifications($masterProfile, $event);

                Log::info('Master profile created successfully', [
                    'master_profile_id' => $masterProfile->id,
                    'user_id' => $event->userId,
                    'name' => $masterProfile->display_name,
                    'city' => $masterProfile->city,
                    'services_count' => count($event->profileData['services'] ?? []),
                ]);

            } catch (Exception $e) {
                Log::error('Failed to handle MasterProfileCreated event', [
                    'user_id' => $event->userId,
                    'profile_data' => $event->profileData,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Создать профиль мастера
     */
    private function createMasterProfile(MasterProfileCreated $event)
    {
        $profileData = [
            'user_id' => $event->userId,
            'display_name' => $event->profileData['name'] ?? 'Мастер',
            'city' => $event->profileData['city'] ?? 'Москва',
            'bio' => $event->profileData['bio'] ?? null,
            'phone' => $event->profileData['phone'] ?? null,
            'experience_years' => $event->profileData['experience'] ?? 0,
            'education' => $event->profileData['education'] ?? null,
            'certifications' => json_encode($event->profileData['certifications'] ?? []),
            'languages' => json_encode($event->profileData['languages'] ?? ['ru']),
            'working_hours' => json_encode($event->profileData['working_hours'] ?? $this->getDefaultWorkingHours()),
            'location_type' => $event->profileData['location_type'] ?? 'client_place',
            'travel_distance' => $event->profileData['travel_distance'] ?? 10,
            'status' => 'draft', // Новый профиль всегда в черновике
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $masterProfile = $this->masterRepository->create($profileData);

        // Добавляем услуги
        if (!empty($event->profileData['services'])) {
            $this->attachServices($masterProfile, $event->profileData['services']);
        }

        return $masterProfile->fresh(['services']);
    }

    /**
     * Получить дефолтные рабочие часы
     */
    private function getDefaultWorkingHours(): array
    {
        return [
            'monday' => ['start' => '09:00', 'end' => '18:00', 'enabled' => true],
            'tuesday' => ['start' => '09:00', 'end' => '18:00', 'enabled' => true],
            'wednesday' => ['start' => '09:00', 'end' => '18:00', 'enabled' => true],
            'thursday' => ['start' => '09:00', 'end' => '18:00', 'enabled' => true],
            'friday' => ['start' => '09:00', 'end' => '18:00', 'enabled' => true],
            'saturday' => ['start' => '10:00', 'end' => '16:00', 'enabled' => false],
            'sunday' => ['start' => '10:00', 'end' => '16:00', 'enabled' => false],
        ];
    }

    /**
     * Привязать услуги к профилю
     */
    private function attachServices($masterProfile, array $services): void
    {
        foreach ($services as $serviceData) {
            $this->masterRepository->attachService($masterProfile->id, [
                'service_type' => $serviceData['type'] ?? 'massage',
                'name' => $serviceData['name'] ?? 'Массаж',
                'description' => $serviceData['description'] ?? null,
                'duration' => $serviceData['duration'] ?? 60,
                'price' => $serviceData['price'] ?? 0,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Создать дефолтные настройки
     */
    private function createDefaultSettings($masterProfile, MasterProfileCreated $event): void
    {
        $defaultSettings = [
            'master_profile_id' => $masterProfile->id,
            'auto_accept_bookings' => false,
            'require_prepayment' => true,
            'prepayment_percentage' => 30,
            'cancellation_policy' => 'moderate', // flexible, moderate, strict
            'min_booking_notice' => 120, // минут
            'max_booking_advance' => 30, // дней
            'booking_buffer_time' => 15, // минут между заказами
            'notifications_enabled' => true,
            'email_notifications' => true,
            'sms_notifications' => false,
            'push_notifications' => true,
            'weekend_surcharge' => 0,
            'holiday_surcharge' => 0,
            'created_at' => now(),
        ];

        $this->masterRepository->createSettings($defaultSettings);
    }

    /**
     * Обработать медиа файлы профиля
     */
    private function processProfileMedia($masterProfile, MasterProfileCreated $event): void
    {
        // Обрабатываем аватар если указан
        if (!empty($event->profileData['avatar'])) {
            $this->processProfileAvatar($masterProfile, $event->profileData['avatar']);
        }

        // Обрабатываем фото портфолио
        if (!empty($event->profileData['portfolio_photos'])) {
            $this->processPortfolioPhotos($masterProfile, $event->profileData['portfolio_photos']);
        }

        // Обрабатываем сертификаты
        if (!empty($event->profileData['certificate_photos'])) {
            $this->processCertificatePhotos($masterProfile, $event->profileData['certificate_photos']);
        }
    }

    /**
     * Обработать аватар профиля
     */
    private function processProfileAvatar($masterProfile, $avatarData): void
    {
        try {
            $processedAvatar = $this->mediaService->processProfilePhoto(
                $avatarData,
                $masterProfile->id,
                'avatar'
            );

            $masterProfile->update(['avatar_url' => $processedAvatar['url']]);

        } catch (Exception $e) {
            Log::warning('Failed to process master profile avatar', [
                'master_profile_id' => $masterProfile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обработать фото портфолио
     */
    private function processPortfolioPhotos($masterProfile, array $photos): void
    {
        foreach ($photos as $photoData) {
            try {
                $processedPhoto = $this->mediaService->processPortfolioPhoto(
                    $photoData,
                    $masterProfile->id
                );

                $this->masterRepository->addPortfolioPhoto($masterProfile->id, [
                    'url' => $processedPhoto['url'],
                    'thumbnail_url' => $processedPhoto['thumbnail_url'],
                    'description' => $photoData['description'] ?? null,
                    'is_primary' => $photoData['is_primary'] ?? false,
                ]);

            } catch (Exception $e) {
                Log::warning('Failed to process portfolio photo', [
                    'master_profile_id' => $masterProfile->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Обработать фото сертификатов
     */
    private function processCertificatePhotos($masterProfile, array $certificates): void
    {
        foreach ($certificates as $certData) {
            try {
                $processedCert = $this->mediaService->processCertificatePhoto(
                    $certData,
                    $masterProfile->id
                );

                $this->masterRepository->addCertificate($masterProfile->id, [
                    'name' => $certData['name'] ?? 'Сертификат',
                    'issuer' => $certData['issuer'] ?? null,
                    'issued_at' => $certData['issued_at'] ?? null,
                    'photo_url' => $processedCert['url'],
                ]);

            } catch (Exception $e) {
                Log::warning('Failed to process certificate photo', [
                    'master_profile_id' => $masterProfile->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Создать дефолтное расписание
     */
    private function createDefaultSchedule($masterProfile): void
    {
        // Создаем базовое расписание на ближайшие 30 дней
        $this->masterService->generateDefaultSchedule($masterProfile->id, 30);
    }

    /**
     * Добавить в поисковый индекс
     */
    private function addToSearchIndex($masterProfile): void
    {
        try {
            $this->searchIndexService->indexMasterProfile($masterProfile);

            Log::info('Master profile added to search index', [
                'master_profile_id' => $masterProfile->id,
            ]);

        } catch (Exception $e) {
            Log::warning('Failed to add master profile to search index', [
                'master_profile_id' => $masterProfile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обновить статистику пользователя
     */
    private function updateUserMasterStats(int $userId): void
    {
        // Обновляем счетчик мастер-профилей у пользователя
        $this->masterRepository->incrementUserMasterProfilesCount($userId);

        // Обновляем роль пользователя если нужно
        $this->masterRepository->ensureUserHasMasterRole($userId);
    }

    /**
     * Отправить уведомления о создании профиля
     */
    private function sendProfileCreatedNotifications($masterProfile, MasterProfileCreated $event): void
    {
        try {
            // Welcome email мастеру
            $this->notificationService->sendMasterWelcomeEmail($masterProfile);

            // Уведомление о необходимости заполнить профиль
            $this->notificationService->sendProfileCompletionReminder($masterProfile);

            // Уведомление администраторам о новом мастере
            $this->notificationService->sendNewMasterNotificationToAdmins($masterProfile);

            Log::info('Master profile creation notifications sent', [
                'master_profile_id' => $masterProfile->id,
            ]);

        } catch (Exception $e) {
            Log::warning('Failed to send master profile creation notifications', [
                'master_profile_id' => $masterProfile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Регистрация обработчика событий
     */
    public static function register(Dispatcher $events): void
    {
        $events->listen(MasterProfileCreated::class, [self::class, 'handle']);
    }
}