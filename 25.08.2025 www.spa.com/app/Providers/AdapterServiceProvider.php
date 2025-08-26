<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Booking\Services\BookingService;
use App\Infrastructure\Adapters\BookingServiceAdapter;
use App\Domain\Master\Services\MasterService;
use App\Infrastructure\Adapters\MasterServiceAdapter;
use App\Domain\Search\Services\SearchService;
use App\Infrastructure\Adapters\SearchServiceAdapter;

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
                $app->make(BookingService::class)
            );
        });

        // Регистрация MasterService адаптера
        $this->app->bind(MasterServiceAdapter::class, function ($app) {
            return new MasterServiceAdapter(
                $app->make(MasterService::class)
            );
        });

        // Регистрация SearchService адаптера
        $this->app->bind(SearchServiceAdapter::class, function ($app) {
            return new SearchServiceAdapter(
                $app->make(SearchService::class)
            );
        });

        // Адаптеры теперь используются по умолчанию
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