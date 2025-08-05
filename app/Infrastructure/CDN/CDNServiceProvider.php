<?php

namespace App\Infrastructure\CDN;

use Illuminate\Support\ServiceProvider;

/**
 * CDN Service Provider
 * Регистрирует CDN сервисы в Laravel контейнере
 */
class CDNServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Регистрируем CDNService как синглтон
        $this->app->singleton(CDNService::class, function ($app) {
            return new CDNService();
        });

        // Регистрируем CDNIntegration
        $this->app->singleton(CDNIntegration::class, function ($app) {
            return new CDNIntegration($app->make(CDNService::class));
        });

        // Регистрируем алиасы для удобства
        $this->app->alias(CDNService::class, 'cdn');
        $this->app->alias(CDNIntegration::class, 'cdn.integration');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Публикуем конфигурацию CDN
        $this->publishes([
            __DIR__ . '/../../../config/cdn.php' => config_path('cdn.php'),
        ], 'cdn-config');

        // Мерж конфигурации CDN
        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/cdn.php', 'cdn'
        );
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            CDNService::class,
            CDNIntegration::class,
            'cdn',
            'cdn.integration',
        ];
    }
}