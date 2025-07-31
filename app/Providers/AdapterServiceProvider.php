<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\BookingService as LegacyBookingService;
use App\Domain\Booking\Services\BookingService as ModernBookingService;
use App\Services\Adapters\BookingServiceAdapter;
use App\Services\MasterService as ModernMasterService;
use App\Services\Adapters\MasterServiceAdapter;
use App\Services\SearchService as LegacySearchService;
use App\Domain\Search\SearchEngine as ModernSearchEngine;
use App\Services\Adapters\SearchServiceAdapter;

class AdapterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Регистрация BookingService адаптера
        $this->app->bind(BookingServiceAdapter::class, function ($app) {
            return new BookingServiceAdapter(
                $app->make(LegacyBookingService::class),
                $app->make(ModernBookingService::class)
            );
        });

        // Регистрация MasterService адаптера
        $this->app->bind(MasterServiceAdapter::class, function ($app) {
            return new MasterServiceAdapter(
                $app->make(ModernMasterService::class)
            );
        });

        // Регистрация SearchService адаптера
        $this->app->bind(SearchServiceAdapter::class, function ($app) {
            // Legacy SearchService может не существовать
            $legacyService = null;
            if (class_exists(LegacySearchService::class)) {
                $legacyService = $app->make(LegacySearchService::class);
            }

            return new SearchServiceAdapter(
                $legacyService,
                $app->make(ModernSearchEngine::class)
            );
        });

        // Переопределение старых сервисов на адаптеры (опционально)
        if (config('features.use_adapters', false)) {
            $this->app->bind(LegacyBookingService::class, BookingServiceAdapter::class);
            $this->app->bind(LegacySearchService::class, SearchServiceAdapter::class);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Публикация конфигурации
        $this->publishes([
            __DIR__.'/../../config/adapters.php' => config_path('adapters.php'),
        ], 'adapters-config');
    }
}