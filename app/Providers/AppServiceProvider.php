<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // РЕГИСТРАЦИЯ МЕДИА-СЕРВИСОВ
        // Важно: НЕЛЬЗЯ биндить MediaService -> MasterMediaService (это создаёт циклическую зависимость),
        // так как MasterMediaService сам зависит от MediaService. Регистрируем их по отдельности.
        $this->app->singleton(\App\Domain\Media\Services\MediaService::class);
        $this->app->singleton(\App\Domain\Media\Services\MasterMediaService::class);

        // Минимальная регистрация MediaRepository - заглушка
        $this->app->singleton(\App\Domain\Media\Repositories\MediaRepository::class);
        $this->app->bind(
            \App\Support\Contracts\MediaRepositoryInterface::class,
            \App\Domain\Media\Repositories\MediaRepository::class
        );

        // Регистрация Master репозиториев и сервисов
        $this->app->singleton(\App\Domain\Master\Repositories\MasterRepository::class);
        $this->app->singleton(\App\Domain\Master\Services\MasterProfileService::class);
        $this->app->singleton(\App\Domain\Master\Services\MasterModerationService::class);
        $this->app->singleton(\App\Domain\Master\Services\MasterSearchService::class);
        $this->app->singleton(\App\Domain\Master\Services\MasterFullProfileService::class);
        $this->app->singleton(\App\Domain\Master\Services\MasterHelperService::class);
        $this->app->singleton(\App\Infrastructure\Notification\NotificationService::class);
        $this->app->singleton(\App\Domain\Master\Services\MasterService::class);
        
        // Регистрация Master интерфейсов
        $this->app->bind(
            \App\Domain\Master\Contracts\MasterRepositoryInterface::class,
            \App\Domain\Master\Repositories\MasterRepository::class
        );
        $this->app->bind(
            \App\Domain\Master\Contracts\MasterServiceInterface::class,
            \App\Domain\Master\Services\MasterService::class
        );
        
        // Регистрация интеграционных сервисов
        $this->app->singleton(\App\Application\Services\Integration\UserMasterIntegrationService::class);
        
        // Регистрация новых Review сервисов (после рефакторинга)
        $this->app->singleton(\App\Application\Services\Integration\ReviewValidator::class);
        $this->app->singleton(\App\Application\Services\Integration\UserReviewsReader::class);
        $this->app->singleton(\App\Application\Services\Integration\UserReviewsWriter::class);
        $this->app->singleton(\App\Application\Services\Integration\UserReviewsIntegrationService::class);
        
        $this->app->singleton(\App\Application\Services\Integration\UserFavoritesIntegrationService::class);
        $this->app->singleton(\App\Application\Services\Integration\UserAdsIntegrationService::class);
        $this->app->singleton(\App\Application\Services\Integration\UserBookingIntegrationService::class);
        
        // Регистрация UserIntegrationService (заменяет Integration трейты)
        $this->app->singleton(\App\Domain\User\Services\UserIntegrationService::class);

        // Регистрация User сервисов
        $this->registerUserServices();
        
        // Регистрация Ad сервисов  
        $this->registerAdServices();

        // Регистрация Booking сервисов
        $this->registerBookingServices();

        // Регистрация Search сервисов
        $this->registerSearchServices();
        
        // Регистрация Analytics сервисов
        $this->registerAnalyticsServices();
        
        // Регистрация Cache декораторов
        $this->registerCacheDecorators();
    }

    /**
     * Регистрация Ad сервисов
     */
    protected function registerAdServices(): void
    {
        // AdRepository
        $this->app->singleton(\App\Domain\Ad\Repositories\AdRepository::class);
        
        // AdService
        $this->app->singleton(\App\Domain\Ad\Services\AdService::class);
        
        // AdProfileService
        $this->app->singleton(\App\Domain\Ad\Services\AdProfileService::class);
        
        // AdDraftService
        $this->app->singleton(\App\Domain\Ad\Services\AdDraftService::class);
        
        // DraftService (для исправления дублирования черновиков)
        $this->app->singleton(\App\Domain\Ad\Services\DraftService::class);
        
        // AdModerationService
        $this->app->singleton(\App\Domain\Ad\Services\AdModerationService::class);
        
        // Ad Actions
        $this->app->singleton(\App\Domain\Ad\Actions\PublishAdAction::class);
        $this->app->singleton(\App\Domain\Ad\Actions\ArchiveAdAction::class);
        $this->app->singleton(\App\Domain\Ad\Actions\ModerateAdAction::class);
    }

    /**
     * Регистрация User сервисов
     */
    protected function registerUserServices(): void
    {
        // UserRepository
        $this->app->singleton(\App\Domain\User\Repositories\UserRepository::class);
        
        // UserService (без циклических зависимостей)
        $this->app->singleton(\App\Domain\User\Services\UserService::class, function ($app) {
            return new \App\Domain\User\Services\UserService(
                $app->make(\App\Domain\User\Repositories\UserRepository::class)
                // UserProfileService добавим позже если нужно
            );
        });
        
        // UserAuthService с зависимостями
        $this->app->singleton(\App\Domain\User\Services\UserAuthService::class, function ($app) {
            return new \App\Domain\User\Services\UserAuthService(
                $app->make(\App\Domain\User\Repositories\UserRepository::class),
                $app->make(\App\Domain\User\Services\UserService::class)
            );
        });
    }

    /**
     * Регистрация Booking сервисов
     */
    protected function registerBookingServices(): void
    {
        // BookingRepository
        $this->app->singleton(\App\Domain\Booking\Repositories\BookingRepository::class);
        
        // Привязка интерфейса к реализации
        $this->app->bind(
            \App\Domain\Booking\Contracts\BookingRepositoryInterface::class,
            \App\Domain\Booking\Repositories\BookingRepository::class
        );
        
        // BookingService
        $this->app->singleton(\App\Domain\Booking\Services\BookingService::class);
        
        // Привязка интерфейса BookingService к реализации
        $this->app->bind(
            \App\Domain\Booking\Contracts\BookingServiceInterface::class,
            \App\Domain\Booking\Services\BookingService::class
        );
        
        // Другие сервисы Booking
        $this->app->singleton(\App\Domain\Booking\Services\BookingSlotService::class);
        $this->app->singleton(\App\Domain\Booking\Services\AvailabilityService::class);
        $this->app->singleton(\App\Domain\Booking\Services\SlotService::class);
        $this->app->singleton(\App\Domain\Booking\Services\PricingService::class);
        $this->app->singleton(\App\Domain\Booking\Services\ValidationService::class);
        $this->app->singleton(\App\Domain\Booking\Services\NotificationService::class);
        
        // Booking Actions
        $this->app->singleton(\App\Domain\Booking\Actions\CreateBookingAction::class);
        $this->app->singleton(\App\Domain\Booking\Actions\ConfirmBookingAction::class);
        $this->app->singleton(\App\Domain\Booking\Actions\CancelBookingAction::class);
        $this->app->singleton(\App\Domain\Booking\Actions\CompleteBookingAction::class);
        $this->app->singleton(\App\Domain\Booking\Actions\RescheduleBookingAction::class);
    }

    /**
     * Регистрация поисковых сервисов
     */
    protected function registerSearchServices(): void
    {
        // Search Repositories
        $this->app->singleton(\App\Domain\Search\Repositories\AdSearchRepository::class);
        $this->app->singleton(\App\Domain\Search\Repositories\MasterSearchRepository::class);
        
        // SearchRepository
        $this->app->singleton(\App\Domain\Search\Repositories\SearchRepository::class);

        // Search Engines
        $this->app->singleton(\App\Domain\Search\Engines\AdSearchEngine::class);
        $this->app->singleton(\App\Domain\Search\Engines\MasterSearchEngine::class);
        $this->app->singleton(\App\Domain\Search\Services\ServiceSearchEngine::class);
        $this->app->singleton(\App\Domain\Search\Services\GlobalSearchEngine::class);
        $this->app->singleton(\App\Domain\Search\Services\RecommendationEngine::class);

        // Search Handlers
        $this->app->singleton(\App\Domain\Search\Services\Handlers\SearchEngineManager::class);
        $this->app->singleton(\App\Domain\Search\Services\Handlers\SearchValidator::class);
        $this->app->singleton(\App\Domain\Search\Services\Handlers\SearchAnalytics::class);
        $this->app->singleton(\App\Domain\Search\Services\Handlers\SearchSuggestionProvider::class);

        // SearchService с правильными зависимостями
        $this->app->singleton(\App\Domain\Search\Services\SearchService::class, function ($app) {
            return new \App\Domain\Search\Services\SearchService(
                $app->make(\App\Domain\Search\Services\Handlers\SearchEngineManager::class),
                $app->make(\App\Domain\Search\Services\Handlers\SearchValidator::class),
                $app->make(\App\Domain\Search\Services\Handlers\SearchAnalytics::class),
                $app->make(\App\Domain\Search\Services\Handlers\SearchSuggestionProvider::class)
            );
        });
    }

    /**
     * Регистрация Analytics сервисов
     */
    protected function registerAnalyticsServices(): void
    {
        // TODO: Реализовать Analytics сервисы
        // Analytics Repository
        // $this->app->singleton(\App\Domain\Analytics\Repositories\AnalyticsRepository::class);

        // Analytics Service Interface and Implementation
        // $this->app->bind(
        //     \App\Domain\Analytics\Contracts\AnalyticsServiceInterface::class,
        //     \App\Domain\Analytics\Services\AnalyticsService::class
        // );

        // Report Service Interface and Implementation
        // $this->app->bind(
        //     \App\Domain\Analytics\Contracts\ReportServiceInterface::class,
        //     \App\Domain\Analytics\Services\ReportService::class
        // );
    }

    /**
     * Регистрация кеширующих декораторов
     */
    protected function registerCacheDecorators(): void
    {
        // УПРОЩЕНО: временно отключаем декораторы кеша, чтобы избежать ошибок разрешения зависимостей
        // и неверных конструкторов. Регистрируем репозитории как простые singleton без аргументов.

        $this->app->singleton(\App\Domain\Master\Repositories\MasterRepository::class, function ($app) {
            return new \App\Domain\Master\Repositories\MasterRepository();
        });

        $this->app->singleton(\App\Domain\Ad\Repositories\AdRepository::class, function ($app) {
            return new \App\Domain\Ad\Repositories\AdRepository();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
