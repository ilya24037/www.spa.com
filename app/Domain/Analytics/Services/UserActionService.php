<?php

namespace App\Domain\Analytics\Services;

use App\Domain\Analytics\Models\UserAction;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Сервис для работы с действиями пользователей
 */
class UserActionService
{
    /**
     * Создать действие пользователя
     */
    public function createAction(
        ?User $user,
        string $actionType,
        ?Model $actionable = null,
        array $properties = [],
        ?string $sessionId = null
    ): UserAction {
        $data = [
            'user_id' => $user?->id,
            'session_id' => $sessionId ?? session()->getId(),
            'action_type' => $actionType,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer_url' => request()->header('referer'),
            'current_url' => request()->fullUrl(),
            'performed_at' => now(),
        ];

        if ($actionable) {
            $data['actionable_type'] = $actionable::class;
            $data['actionable_id'] = $actionable->id;
        }

        $action = UserAction::create($data);

        // Автоматически отмечаем конверсии
        if ($action->isConversionAction()) {
            $action->markAsConversion($this->calculateConversionValue($actionType, $actionable));
        }

        return $action;
    }

    /**
     * Создать действие для гостя
     */
    public function createGuestAction(
        string $actionType,
        ?Model $actionable = null,
        array $properties = []
    ): UserAction {
        return $this->createAction(null, $actionType, $actionable, $properties);
    }

    /**
     * Массовое создание действий
     */
    public function createBulkActions(array $actions): void
    {
        $data = [];
        foreach ($actions as $action) {
            $data[] = array_merge([
                'session_id' => session()->getId(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'performed_at' => now(),
            ], $action);
        }

        UserAction::insert($data);
    }

    /**
     * Получить статистику действий пользователя
     */
    public function getUserStats(User $user, int $days = 30): array
    {
        $actions = UserAction::byUser($user->id)
            ->lastDays($days)
            ->groupByActionType()
            ->selectRaw('action_type, COUNT(*) as count')
            ->pluck('count', 'action_type')
            ->toArray();

        return [
            'total_actions' => array_sum($actions),
            'actions_by_type' => $actions,
            'conversions' => UserAction::byUser($user->id)
                ->lastDays($days)
                ->conversions()
                ->count(),
        ];
    }

    /**
     * Получить аналитику по типам действий
     */
    public function getActionTypeAnalytics(int $days = 30): array
    {
        return UserAction::lastDays($days)
            ->groupByActionType()
            ->selectRaw('action_type, COUNT(*) as count, AVG(conversion_value) as avg_value')
            ->get()
            ->keyBy('action_type')
            ->toArray();
    }

    /**
     * Вычислить значение конверсии
     */
    private function calculateConversionValue(string $actionType, ?Model $actionable): float
    {
        return match($actionType) {
            UserAction::ACTION_REGISTER => 100,
            UserAction::ACTION_CREATE_AD => 500,
            UserAction::ACTION_BOOK_SERVICE => $actionable?->total_amount ?? 1000,
            UserAction::ACTION_MAKE_PAYMENT => $actionable?->amount ?? 0,
            UserAction::ACTION_COMPLETE_BOOKING => $actionable?->total_amount ?? 0,
            default => 0,
        };
    }
}