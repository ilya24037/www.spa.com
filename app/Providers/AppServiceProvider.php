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
        // Привязка MediaService к MasterMediaService для обратной совместимости
        $this->app->bind(
            \App\Domain\Media\Services\MediaService::class,
            \App\Domain\Media\Services\MasterMediaService::class
        );

        // Минимальная регистрация MediaRepository - заглушка
        $this->app->singleton(\App\Domain\Media\Repositories\MediaRepository::class);
        $this->app->bind(
            \App\Support\Contracts\MediaRepositoryInterface::class,
            \App\Domain\Media\Repositories\MediaRepository::class
        );

        // Регистрация Master интерфейсов - заглушки
        $this->app->bind(
            \App\Domain\Master\Contracts\MasterRepositoryInterface::class,
            \App\Domain\Master\Repositories\MasterRepository::class
        );
        $this->app->bind(
            \App\Domain\Master\Contracts\MasterServiceInterface::class,
            \App\Domain\Master\Services\MasterService::class
        );

        // Регистрация Search сервисов
        $this->registerSearchServices();
    }

    /**
     * Регистрация поисковых сервисов
     */
    protected function registerSearchServices(): void
    {
        // SearchRepository
        $this->app->singleton(\App\Domain\Search\Repositories\SearchRepository::class);

        // Search Engines
        $this->app->singleton(\App\Domain\Search\Engines\AdSearchEngine::class);
        $this->app->singleton(\App\Domain\Search\Engines\MasterSearchEngine::class);
        $this->app->singleton(\App\Domain\Search\Services\ServiceSearchEngine::class);
        $this->app->singleton(\App\Domain\Search\Services\GlobalSearchEngine::class);
        $this->app->singleton(\App\Domain\Search\Services\RecommendationEngine::class);

        // SearchService с всеми зависимостями
        $this->app->singleton(\App\Domain\Search\Services\SearchService::class, function ($app) {
            return new \App\Domain\Search\Services\SearchService(
                $app->make(\App\Domain\Search\Repositories\SearchRepository::class),
                $app->make(\App\Domain\Search\Engines\AdSearchEngine::class),
                $app->make(\App\Domain\Search\Engines\MasterSearchEngine::class),
                $app->make(\App\Domain\Search\Services\ServiceSearchEngine::class),
                $app->make(\App\Domain\Search\Services\GlobalSearchEngine::class),
                $app->make(\App\Domain\Search\Services\RecommendationEngine::class)
            );
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
