<?php

namespace App\Services\AiContext;

class ContextConfig
{
    /**
     * Автоматическое определение структуры проекта
     */
    public static function getAutoDetectPatterns(): array
    {
        return [
            'models' => [
                'pattern' => 'app/Models/*.php',
                'expected' => ['User', 'MasterProfile', 'MassageCategory', 'Service', 'Booking', 'Review', 'Schedule', 'WorkZone', 'PaymentPlan', 'MasterSubscription']
            ],
            'controllers' => [
                'pattern' => 'app/Http/Controllers/*.php',
                'expected' => ['HomeController', 'MasterController', 'FavoriteController', 'CompareController', 'BookingController', 'SearchController', 'ReviewController', 'ProfileController']
            ],
            'migrations' => [
                'pattern' => 'database/migrations/*.php',
                'keywords' => ['users', 'master_profiles', 'massage_categories', 'services', 'bookings', 'reviews', 'schedules', 'work_zones', 'payment_plans']
            ],
            'vue_pages' => [
                'pattern' => 'resources/js/Pages/**/*.vue',
                'expected' => ['Home', 'Masters/Index', 'Masters/Show', 'Profile/Edit', 'Bookings/Create']
            ],
            'vue_components' => [
                'pattern' => 'resources/js/Components/**/*.vue',
                'expected' => ['Masters/MasterCard', 'Booking/BookingForm', 'Booking/Calendar', 'Common/Navbar', 'Common/FilterPanel']
            ]
        ];
    }

    /**
     * Паттерны для определения функциональности
     */
    public static function getFunctionalityPatterns(): array
    {
        return [
            'search' => [
                'files' => ['SearchController.php', 'search.vue', 'searchStore.js'],
                'code' => ['function search', 'query =', 'filter', 'Scout::search']
            ],
            'booking' => [
                'files' => ['BookingController.php', 'BookingForm.vue', 'Calendar.vue'],
                'code' => ['store.*booking', 'createBooking', 'available.*slots']
            ],
            'reviews' => [
                'files' => ['ReviewController.php', 'ReviewForm.vue', 'reviews.vue'],
                'code' => ['store.*review', 'rating', 'stars']
            ],
            'payments' => [
                'files' => ['PaymentController.php', 'payment', 'stripe', 'yookassa'],
                'code' => ['payment', 'charge', 'subscription']
            ],
            'notifications' => [
                'files' => ['NotificationController.php', 'mail', 'sms'],
                'code' => ['notify', 'Mail::send', 'SMS::send']
            ]
        ];
    }

    /**
     * Получить информацию о проекте
     */
    public static function getProjectInfo(): array
    {
        return [
            'name' => 'SPA Platform - Платформа услуг массажа',
            'tech_stack' => [
                'backend' => 'Laravel ' . app()->version() . ' (PHP ' . PHP_VERSION . ')',
                'frontend' => 'Vue.js 3 + Inertia.js',
                'state' => 'Pinia',
                'styles' => 'Tailwind CSS',
                'database' => config('database.default') === 'mysql' ? 'MySQL' : config('database.default'),
                'developer' => 'Один человек + ИИ помощник'
            ]
        ];
    }
}