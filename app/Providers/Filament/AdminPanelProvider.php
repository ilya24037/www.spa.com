<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Enums\UserRole;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Assets\Css;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->authGuard('web')
            ->brandName('MASSAGIST Admin')
            ->brandLogo(null)
            ->favicon('/favicon.ico')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->font('Inter')
            // Временно используем готовый CSS (KISS принцип)
            ->assets([
                Css::make('filament-styles', asset('css/filament.css')),
            ])
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Pages\Settings::class,
                \App\Filament\Pages\Reports::class,
                \App\Filament\Pages\SystemInfo::class,
                \App\Filament\Pages\Backup::class,
            ])
            ->widgets([
                \App\Filament\Widgets\StatsOverviewWidget::class,
                \App\Filament\Widgets\RecentAds::class,
                \App\Filament\Widgets\RecentComplaints::class,
                \App\Filament\Widgets\AdsChart::class,
                \App\Filament\Widgets\ModerationQueue::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\FilamentAdminAccess::class,
            ])
            ->maxContentWidth('full')
            ->breadcrumbs(true)
            ->globalSearch(true);
            // ->spa(); // Отключен из-за конфликта с CSRF токенами Livewire
    }
}
