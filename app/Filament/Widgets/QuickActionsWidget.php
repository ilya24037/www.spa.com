<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Enums\AdStatus;
use App\Domain\Ad\Models\Complaint;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = -1; // Показывать первым
    
    /**
     * Получить данные для виджета
     */
    protected function getViewData(): array
    {
        return [
            'pendingModeration' => Ad::where('status', AdStatus::PENDING_MODERATION->value)->count(),
            'pendingComplaints' => Complaint::where('status', 'pending')->count(),
            'todayRegistrations' => \App\Domain\User\Models\User::whereDate('created_at', today())->count(),
            'todayBookings' => \App\Domain\Booking\Models\Booking::whereDate('created_at', today())->count(),
        ];
    }
    
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }
}