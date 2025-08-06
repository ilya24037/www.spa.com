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
        
        // Регистрация Analytics сервисов
        $this->registerAnalyticsServices();
        
        // Регистрация Cache декораторов
        $this->registerCacheDecorators();
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
        // Проверяем, включено ли кеширование
        if (config('cache.default') === 'redis' || config('cache.default') === 'database') {
            // Сначала регистрируем оригинальные репозитории как отдельные сервисы
            $this->app->singleton('ad.repository.original', function ($app) {
                return new \App\Domain\Ad\Repositories\AdRepository(
                    new \App\Domain\Ad\Models\Ad()
                );
            });
            
            $this->app->singleton('master.repository.original', function ($app) {
                return new \App\Domain\Master\Repositories\MasterRepository(
                    new \App\Domain\Master\Models\MasterProfile()
                );
            });

            // Теперь регистрируем декорированные версии
            $this->app->singleton(\App\Domain\Ad\Repositories\AdRepository::class, function ($app) {
                return new \App\Infrastructure\Cache\Decorators\CachedAdRepository(
                    $app->make('ad.repository.original'),
                    $app->make(\App\Infrastructure\Cache\CacheService::class),
                    $app->make(\App\Infrastructure\Cache\Strategies\AdCacheStrategy::class)
                );
            });

            $this->app->singleton(\App\Domain\Master\Repositories\MasterRepository::class, function ($app) {
                return new \App\Infrastructure\Cache\Decorators\CachedMasterRepository(
                    $app->make('master.repository.original'),
                    $app->make(\App\Infrastructure\Cache\CacheService::class),
                    $app->make(\App\Infrastructure\Cache\Strategies\MasterCacheStrategy::class)
                );
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
