<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// Domain Events
use App\Domain\Booking\Events\BookingCreated;
use App\Domain\Booking\Events\BookingCancelled;
use App\Domain\Booking\Events\BookingCompleted;

// Domain Listeners
use App\Domain\Booking\Listeners\SendBookingNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // Booking Domain Events
        BookingCreated::class => [
            SendBookingNotification::class . '@handleBookingCreated',
        ],
        
        BookingCancelled::class => [
            SendBookingNotification::class . '@handleBookingCancelled',
        ],
        
        BookingCompleted::class => [
            SendBookingNotification::class . '@handleBookingCompleted',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}