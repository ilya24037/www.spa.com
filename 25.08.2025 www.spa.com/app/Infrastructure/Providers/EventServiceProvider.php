<?php

namespace App\Infrastructure\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Events\Dispatcher;

/**
 * Event Service Provider для DDD архитектуры
 * 
 * Автоматически регистрирует все Event Listeners
 * для обработки междоменных событий
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * Карта событий и их обработчиков
     * 
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Booking Events
        \App\Domain\Booking\Events\BookingRequested::class => [
            \App\Infrastructure\Listeners\Booking\HandleBookingRequested::class,
        ],
        
        \App\Domain\Booking\Events\BookingStatusChanged::class => [
            \App\Infrastructure\Listeners\Booking\HandleBookingStatusChanged::class,
        ],
        
        \App\Domain\Booking\Events\BookingCancelled::class => [
            \App\Infrastructure\Listeners\Booking\HandleBookingCancelled::class,
        ],
        
        \App\Domain\Booking\Events\BookingCompleted::class => [
            \App\Infrastructure\Listeners\Booking\HandleBookingCompleted::class,
        ],
        
        // Master Events
        \App\Domain\Master\Events\MasterProfileCreated::class => [
            \App\Infrastructure\Listeners\Master\HandleMasterProfileCreated::class,
        ],
        
        \App\Domain\Master\Events\MasterProfileUpdated::class => [
            \App\Infrastructure\Listeners\Master\HandleMasterProfileUpdated::class,
        ],
        
        \App\Domain\Master\Events\MasterStatusChanged::class => [
            \App\Infrastructure\Listeners\Master\HandleMasterStatusChanged::class,
        ],
        
        // User Events
        \App\Domain\User\Events\UserRegistered::class => [
            \App\Infrastructure\Listeners\User\HandleUserRegistered::class,
        ],
        
        \App\Domain\User\Events\UserRoleChanged::class => [
            \App\Infrastructure\Listeners\User\HandleUserRoleChanged::class,
        ],
        
        \App\Domain\User\Events\UserProfileUpdated::class => [
            \App\Infrastructure\Listeners\User\HandleUserProfileUpdated::class,
        ],
        
        // Favorite Events (Integration)
        \App\Domain\Favorite\Events\FavoriteAdded::class => [
            \App\Infrastructure\Listeners\Favorite\HandleFavoriteAdded::class,
        ],
        
        \App\Domain\Favorite\Events\FavoriteRemoved::class => [
            \App\Infrastructure\Listeners\Favorite\HandleFavoriteRemoved::class,
        ],
        
        // Review Events (Integration)
        \App\Domain\Review\Events\ReviewCreated::class => [
            \App\Infrastructure\Listeners\Review\HandleReviewCreated::class,
            \App\Infrastructure\Listeners\Review\UpdateMasterRating::class,
        ],
        
        \App\Domain\Review\Events\ReviewUpdated::class => [
            \App\Infrastructure\Listeners\Review\HandleReviewUpdated::class,
            \App\Infrastructure\Listeners\Review\RecalculateMasterRating::class,
        ],
        
        \App\Domain\Review\Events\ReviewDeleted::class => [
            \App\Infrastructure\Listeners\Review\HandleReviewDeleted::class,
            \App\Infrastructure\Listeners\Review\RecalculateMasterRating::class,
        ],
        
        // Ad Events (Integration)
        \App\Domain\Ad\Events\AdCreated::class => [
            \App\Infrastructure\Listeners\Ad\HandleAdCreated::class,
            \App\Infrastructure\Listeners\Ad\IncrementUserAdsCount::class,
        ],
        
        \App\Domain\Ad\Events\AdUpdated::class => [
            \App\Infrastructure\Listeners\Ad\HandleAdUpdated::class,
        ],
        
        \App\Domain\Ad\Events\AdPublished::class => [
            \App\Infrastructure\Listeners\Ad\HandleAdPublished::class,
            \App\Infrastructure\Listeners\Ad\NotifyAdPublished::class,
        ],
        
        \App\Domain\Ad\Events\AdArchived::class => [
            \App\Infrastructure\Listeners\Ad\HandleAdArchived::class,
        ],
        
        \App\Domain\Ad\Events\AdDeleted::class => [
            \App\Infrastructure\Listeners\Ad\HandleAdDeleted::class,
            \App\Infrastructure\Listeners\Ad\DecrementUserAdsCount::class,
        ],
    ];

    /**
     * Регистрация сервисов
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap сервисов
     */
    public function boot(): void
    {
        parent::boot();
        
        // Регистрируем дополнительные обработчики событий
        $this->registerCustomEventListeners();
        
        // Настраиваем глобальные обработчики событий
        $this->registerGlobalEventHandlers();
    }

    /**
     * Регистрация кастомных обработчиков событий
     */
    private function registerCustomEventListeners(): void
    {
        // Здесь можно зарегистрировать дополнительные обработчики
        // которые не входят в основную карту $listen
    }

    /**
     * Регистрация глобальных обработчиков событий
     */
    private function registerGlobalEventHandlers(): void
    {
        // Логирование всех событий в дебаг режиме (отключено)
        // if (config('app.debug')) {
        //     \Event::listen('*', function ($eventName, $data) {
        //         if (str_starts_with($eventName, 'App\\Domain\\')) {
        //             \Log::debug('Domain Event Fired', [
        //                 'event' => $eventName,
        //                 'data' => $data,
        //             ]);
        //         }
        //     });
        // }

        // Обработчик ошибок в Event Listeners
        \Event::listen('Illuminate\Queue\Events\JobFailed', function ($event) {
            if (str_contains($event->job->resolveName(), 'Listener')) {
                \Log::error('Event Listener Failed', [
                    'job' => $event->job->resolveName(),
                    'exception' => $event->exception->getMessage(),
                    'data' => $event->job->payload(),
                ]);
            }
        });

        // Метрики производительности событий
        \Event::listen('*', function ($eventName, $data) {
            if (str_starts_with($eventName, 'App\\Domain\\')) {
                $startTime = microtime(true);
                
                // Измеряем время обработки события
                \Event::listen($eventName . '.handled', function () use ($startTime, $eventName) {
                    $duration = microtime(true) - $startTime;
                    
                    if ($duration > 1.0) { // Более 1 секунды
                        \Log::warning('Slow Event Processing', [
                            'event' => $eventName,
                            'duration' => $duration,
                        ]);
                    }
                });
            }
        });
    }

    /**
     * Определить, должны ли события и слушатели автоматически обнаруживаться
     */
    public function shouldDiscoverEvents(): bool
    {
        return false; // Используем явную карту событий для лучшего контроля
    }

    /**
     * Получить пути для автообнаружения событий
     */
    protected function discoverEventsWithin(): array
    {
        return [
            $this->app->path('Domain'),
            $this->app->path('Infrastructure/Listeners'),
        ];
    }
}