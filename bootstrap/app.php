<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Тестовые маршруты БЕЗ CSRF middleware (только в non-production)
            if (!app()->isProduction()) {
                Route::middleware([])->group(base_path('routes/test.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Application\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            // \App\Application\Http\Middleware\PerformanceMiddleware::class, // Временно отключено
        ]);

        // Регистрация middleware алиасов
        $middleware->alias([
            'guest' => \App\Application\Http\Middleware\RedirectIfAuthenticated::class,
            'cors' => \App\Http\Middleware\CorsMiddleware::class,
        ]);

        // Глобальные middleware (временно отключено)
        // $middleware->append([
        //     \App\Application\Http\Middleware\PerformanceMiddleware::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
