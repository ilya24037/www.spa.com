<?php

namespace App\Domain\Analytics\Analytics;

use App\Domain\Analytics\Models\UserAction;
use Illuminate\Support\Facades\DB;

/**
 * Анализатор конверсий и воронки продаж
 */
class ConversionAnalyzer
{
    // Стоимости конверсий (можно вынести в конфиг)
    protected array $conversionValues = [
        UserAction::ACTION_REGISTER => 10.0,
        UserAction::ACTION_CREATE_AD => 50.0,
        UserAction::ACTION_BOOK_SERVICE => 100.0,
        UserAction::ACTION_COMPLETE_BOOKING => 200.0,
        UserAction::ACTION_MAKE_PAYMENT => 300.0,
        UserAction::ACTION_LEAVE_REVIEW => 15.0,
        UserAction::ACTION_CONTACT_MASTER => 25.0,
    ];

    public function __construct(array $conversionValues = [])
    {
        if (!empty($conversionValues)) {
            $this->conversionValues = array_merge($this->conversionValues, $conversionValues);
        }
    }

    /**
     * Получить воронку конверсий для пользователя
     */
    public function getUserConversionFunnel(int $userId, int $days = 30): array
    {
        $from = now()->subDays($days);
        $actions = UserAction::byUser($userId)
            ->where('performed_at', '>=', $from)
            ->orderBy('performed_at')
            ->get();

        $funnel = [];
        $conversionActions = [
            UserAction::ACTION_REGISTER,
            UserAction::ACTION_UPDATE_PROFILE,
            UserAction::ACTION_VIEW_AD,
            UserAction::ACTION_CONTACT_MASTER,
            UserAction::ACTION_BOOK_SERVICE,
            UserAction::ACTION_MAKE_PAYMENT,
            UserAction::ACTION_COMPLETE_BOOKING,
            UserAction::ACTION_LEAVE_REVIEW,
        ];

        foreach ($conversionActions as $actionType) {
            $actionCount = $actions->where('action_type', $actionType)->count();
            $lastAction = $actions->where('action_type', $actionType)->last();
            
            $funnel[] = [
                'action' => $actionType,
                'count' => $actionCount,
                'last_performed' => $lastAction ? $lastAction->performed_at->format('Y-m-d H:i:s') : null,
                'conversion_value' => $actions->where('action_type', $actionType)->sum('conversion_value'),
            ];
        }

        return [
            'user_id' => $userId,
            'period_days' => $days,
            'funnel' => $funnel,
            'total_conversion_value' => $actions->where('is_conversion', true)->sum('conversion_value'),
            'total_conversions' => $actions->where('is_conversion', true)->count(),
        ];
    }

    /**
     * Получить статистику конверсий
     */
    public function getConversionStats(): array
    {
        $today = now()->startOfDay();
        
        return [
            'today_conversions' => UserAction::conversions()
                ->where('performed_at', '>=', $today)->count(),
            'today_conversion_value' => UserAction::conversions()
                ->where('performed_at', '>=', $today)->sum('conversion_value'),
            'week_conversions' => UserAction::conversions()
                ->where('performed_at', '>=', now()->startOfWeek())->count(),
            'month_conversions' => UserAction::conversions()
                ->where('performed_at', '>=', now()->startOfMonth())->count(),
            'total_conversions' => UserAction::conversions()->count(),
            'total_conversion_value' => UserAction::conversions()->sum('conversion_value'),
            'top_conversion_types' => UserAction::conversions()
                ->where('performed_at', '>=', now()->startOfMonth())
                ->select('action_type', DB::raw('count(*) as count'), DB::raw('sum(conversion_value) as value'))
                ->groupBy('action_type')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get()
                ->toArray(),
        ];
    }

    /**
     * Получить общую воронку конверсий
     */
    public function getGlobalConversionFunnel(int $days = 30): array
    {
        $from = now()->subDays($days);
        
        $conversionActions = [
            UserAction::ACTION_REGISTER,
            UserAction::ACTION_CREATE_AD,
            UserAction::ACTION_CONTACT_MASTER,
            UserAction::ACTION_BOOK_SERVICE,
            UserAction::ACTION_MAKE_PAYMENT,
            UserAction::ACTION_COMPLETE_BOOKING,
        ];

        $funnel = [];
        foreach ($conversionActions as $actionType) {
            $count = UserAction::where('action_type', $actionType)
                ->where('performed_at', '>=', $from)
                ->count();
                
            $value = UserAction::where('action_type', $actionType)
                ->where('performed_at', '>=', $from)
                ->sum('conversion_value');
            
            $funnel[] = [
                'action' => $actionType,
                'count' => $count,
                'conversion_value' => $value,
            ];
        }

        return [
            'period_days' => $days,
            'funnel' => $funnel,
        ];
    }

    /**
     * Установить кастомную стоимость конверсии
     */
    public function setConversionValue(string $actionType, float $value): void
    {
        $this->conversionValues[$actionType] = $value;
    }

    /**
     * Получить стоимость конверсии
     */
    public function getConversionValue(string $actionType): float
    {
        return $this->conversionValues[$actionType] ?? 0.0;
    }

    /**
     * Получить все стоимости конверсий
     */
    public function getAllConversionValues(): array
    {
        return $this->conversionValues;
    }
}