<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Политики авторизации для приложения.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Domain\Ad\Models\Ad::class => \App\Policies\AdPolicy::class,
        \App\Domain\User\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Domain\Master\Models\MasterProfile::class => \App\Policies\MasterProfilePolicy::class,
        \App\Domain\Ad\Models\Complaint::class => \App\Policies\ComplaintPolicy::class,
        \App\Domain\Review\Models\Review::class => \App\Policies\ReviewPolicy::class,
    ];

    /**
     * Регистрирует любые сервисы аутентификации/авторизации.
     */
    public function boot(): void
    {
        // Регистрируем все политики
        $this->registerPolicies();

        // Дополнительные Gates можно определить здесь при необходимости
        Gate::before(function ($user, $ability) {
            // Суперадмин имеет все разрешения
            if ($user->hasRole('admin') && $user->hasPermission('manage_system')) {
                return true;
            }
        });
    }
}