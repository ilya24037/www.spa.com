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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
