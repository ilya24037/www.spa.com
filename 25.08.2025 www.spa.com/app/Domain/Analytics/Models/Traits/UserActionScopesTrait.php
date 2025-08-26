<?php

namespace App\Domain\Analytics\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Трейт со скоупами для фильтрации действий пользователей
 */
trait UserActionScopesTrait
{
    /**
     * Действия за период
     */
    public function scopeInPeriod(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('performed_at', [$from, $to]);
    }

    /**
     * Действия за последние N дней
     */
    public function scopeLastDays(Builder $query, int $days): Builder
    {
        return $query->where('performed_at', '>=', now()->subDays($days));
    }

    /**
     * Действия конкретного пользователя
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Действия гостей
     */
    public function scopeGuests(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    /**
     * Действия по типу
     */
    public function scopeByActionType(Builder $query, string $actionType): Builder
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Конверсионные действия
     */
    public function scopeConversions(Builder $query): Builder
    {
        return $query->where('is_conversion', true);
    }

    /**
     * Действия с объектами определенного типа
     */
    public function scopeByActionableType(Builder $query, string $type): Builder
    {
        return $query->where('actionable_type', $type);
    }

    /**
     * Авторизационные действия
     */
    public function scopeAuthActions(Builder $query): Builder
    {
        return $query->whereIn('action_type', [
            self::ACTION_LOGIN,
            self::ACTION_LOGOUT,
            self::ACTION_REGISTER,
        ]);
    }

    /**
     * Действия с объявлениями
     */
    public function scopeAdActions(Builder $query): Builder
    {
        return $query->whereIn('action_type', [
            self::ACTION_CREATE_AD,
            self::ACTION_UPDATE_AD,
            self::ACTION_DELETE_AD,
            self::ACTION_VIEW_AD,
        ]);
    }

    /**
     * Действия с бронированием
     */
    public function scopeBookingActions(Builder $query): Builder
    {
        return $query->whereIn('action_type', [
            self::ACTION_BOOK_SERVICE,
            self::ACTION_CANCEL_BOOKING,
            self::ACTION_COMPLETE_BOOKING,
        ]);
    }

    /**
     * Коммуникационные действия
     */
    public function scopeCommunicationActions(Builder $query): Builder
    {
        return $query->whereIn('action_type', [
            self::ACTION_CONTACT_MASTER,
            self::ACTION_SEND_MESSAGE,
            self::ACTION_CLICK_PHONE,
            self::ACTION_CLICK_WHATSAPP,
        ]);
    }

    /**
     * Поисковые действия
     */
    public function scopeSearchActions(Builder $query): Builder
    {
        return $query->whereIn('action_type', [
            self::ACTION_SEARCH,
            self::ACTION_FILTER,
        ]);
    }

    /**
     * Группировка по типу действия
     */
    public function scopeGroupByActionType(Builder $query): Builder
    {
        return $query->groupBy('action_type');
    }

    /**
     * Группировка по пользователям
     */
    public function scopeGroupByUser(Builder $query): Builder
    {
        return $query->groupBy('user_id');
    }

    /**
     * Группировка по дням
     */
    public function scopeGroupByDay(Builder $query): Builder
    {
        return $query->groupByRaw('DATE(performed_at)');
    }
}