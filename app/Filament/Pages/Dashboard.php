<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string $routePath = '/';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-home';
    }

    public static function getNavigationLabel(): string
    {
        return 'Главная';
    }

    public function getHeading(): string
    {
        return 'Панель управления MASSAGIST';
    }

    public function getSubheading(): ?string
    {
        return 'Добро пожаловать в административную панель';
    }
}