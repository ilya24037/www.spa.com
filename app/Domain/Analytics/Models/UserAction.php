<?php

namespace App\Domain\Analytics\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Модель действий пользователей для аналитики
 */
class UserAction extends Model
{
    use HasFactory;

    // Типы действий
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_REGISTER = 'register';
    public const ACTION_VIEW_PROFILE = 'view_profile';
    public const ACTION_UPDATE_PROFILE = 'update_profile';
    public const ACTION_CREATE_AD = 'create_ad';
    public const ACTION_UPDATE_AD = 'update_ad';
    public const ACTION_DELETE_AD = 'delete_ad';
    public const ACTION_VIEW_AD = 'view_ad';
    public const ACTION_CONTACT_MASTER = 'contact_master';
    public const ACTION_ADD_FAVORITE = 'add_favorite';
    public const ACTION_REMOVE_FAVORITE = 'remove_favorite';
    public const ACTION_BOOK_SERVICE = 'book_service';
    public const ACTION_CANCEL_BOOKING = 'cancel_booking';
    public const ACTION_COMPLETE_BOOKING = 'complete_booking';
    public const ACTION_LEAVE_REVIEW = 'leave_review';
    public const ACTION_MAKE_PAYMENT = 'make_payment';
    public const ACTION_REFUND_PAYMENT = 'refund_payment';
    public const ACTION_SEARCH = 'search';
    public const ACTION_FILTER = 'filter';
    public const ACTION_UPLOAD_PHOTO = 'upload_photo';
    public const ACTION_DELETE_PHOTO = 'delete_photo';
    public const ACTION_SEND_MESSAGE = 'send_message';
    public const ACTION_CLICK_PHONE = 'click_phone';
    public const ACTION_CLICK_WHATSAPP = 'click_whatsapp';
    public const ACTION_SHARE = 'share';
    public const ACTION_REPORT = 'report';

    protected $fillable = [
        'user_id',
        'session_id',
        'action_type',
        'actionable_type',
        'actionable_id',
        'properties',
        'ip_address',
        'user_agent',
        'referrer_url',
        'current_url',
        'is_conversion',
        'conversion_value',
        'performed_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'is_conversion' => 'boolean',
        'conversion_value' => 'decimal:2',
        'performed_at' => 'datetime',
    ];

    protected $attributes = [
        'is_conversion' => false,
        'conversion_value' => 0,
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->performed_at) {
                $model->performed_at = now();
            }
        });
    }

    /**
     * Пользователь, который выполнил действие
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Полиморфная связь с объектом действия
     */
    public function actionable(): MorphTo
    {
        return $this->morphTo();
    }

    // ============ SCOPES ============

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

    // ============ HELPER METHODS ============

    /**
     * Проверить, является ли действие конверсией
     */
    public function isConversionAction(): bool
    {
        $conversionActions = [
            self::ACTION_REGISTER,
            self::ACTION_CREATE_AD,
            self::ACTION_BOOK_SERVICE,
            self::ACTION_COMPLETE_BOOKING,
            self::ACTION_MAKE_PAYMENT,
            self::ACTION_LEAVE_REVIEW,
            self::ACTION_CONTACT_MASTER,
        ];

        return in_array($this->action_type, $conversionActions);
    }

    /**
     * Получить описание действия
     */
    public function getActionDescription(): string
    {
        $descriptions = [
            self::ACTION_LOGIN => 'Авторизация',
            self::ACTION_LOGOUT => 'Выход из системы',
            self::ACTION_REGISTER => 'Регистрация',
            self::ACTION_VIEW_PROFILE => 'Просмотр профиля',
            self::ACTION_UPDATE_PROFILE => 'Обновление профиля',
            self::ACTION_CREATE_AD => 'Создание объявления',
            self::ACTION_UPDATE_AD => 'Редактирование объявления',
            self::ACTION_DELETE_AD => 'Удаление объявления',
            self::ACTION_VIEW_AD => 'Просмотр объявления',
            self::ACTION_CONTACT_MASTER => 'Связь с мастером',
            self::ACTION_ADD_FAVORITE => 'Добавление в избранное',
            self::ACTION_REMOVE_FAVORITE => 'Удаление из избранного',
            self::ACTION_BOOK_SERVICE => 'Бронирование услуги',
            self::ACTION_CANCEL_BOOKING => 'Отмена бронирования',
            self::ACTION_COMPLETE_BOOKING => 'Завершение бронирования',
            self::ACTION_LEAVE_REVIEW => 'Оставление отзыва',
            self::ACTION_MAKE_PAYMENT => 'Совершение платежа',
            self::ACTION_REFUND_PAYMENT => 'Возврат платежа',
            self::ACTION_SEARCH => 'Поиск',
            self::ACTION_FILTER => 'Фильтрация',
            self::ACTION_UPLOAD_PHOTO => 'Загрузка фото',
            self::ACTION_DELETE_PHOTO => 'Удаление фото',
            self::ACTION_SEND_MESSAGE => 'Отправка сообщения',
            self::ACTION_CLICK_PHONE => 'Клик по телефону',
            self::ACTION_CLICK_WHATSAPP => 'Клик по WhatsApp',
            self::ACTION_SHARE => 'Поделиться',
            self::ACTION_REPORT => 'Жалоба',
        ];

        return $descriptions[$this->action_type] ?? 'Неизвестное действие';
    }

    /**
     * Получить категорию действия
     */
    public function getActionCategory(): string
    {
        $authActions = [self::ACTION_LOGIN, self::ACTION_LOGOUT, self::ACTION_REGISTER];
        $profileActions = [self::ACTION_VIEW_PROFILE, self::ACTION_UPDATE_PROFILE];
        $adActions = [self::ACTION_CREATE_AD, self::ACTION_UPDATE_AD, self::ACTION_DELETE_AD, self::ACTION_VIEW_AD];
        $bookingActions = [self::ACTION_BOOK_SERVICE, self::ACTION_CANCEL_BOOKING, self::ACTION_COMPLETE_BOOKING];
        $communicationActions = [self::ACTION_CONTACT_MASTER, self::ACTION_SEND_MESSAGE, self::ACTION_CLICK_PHONE, self::ACTION_CLICK_WHATSAPP];
        $searchActions = [self::ACTION_SEARCH, self::ACTION_FILTER];
        $mediaActions = [self::ACTION_UPLOAD_PHOTO, self::ACTION_DELETE_PHOTO];

        if (in_array($this->action_type, $authActions)) return 'Авторизация';
        if (in_array($this->action_type, $profileActions)) return 'Профиль';
        if (in_array($this->action_type, $adActions)) return 'Объявления';
        if (in_array($this->action_type, $bookingActions)) return 'Бронирование';
        if (in_array($this->action_type, $communicationActions)) return 'Коммуникация';
        if (in_array($this->action_type, $searchActions)) return 'Поиск';
        if (in_array($this->action_type, $mediaActions)) return 'Медиа';

        return 'Прочее';
    }

    /**
     * Добавить свойство к действию
     */
    public function addProperty(string $key, $value): void
    {
        $properties = $this->properties ?? [];
        $properties[$key] = $value;
        $this->update(['properties' => $properties]);
    }

    /**
     * Получить свойство действия
     */
    public function getProperty(string $key, $default = null)
    {
        return ($this->properties ?? [])[$key] ?? $default;
    }

    /**
     * Проверить наличие свойства
     */
    public function hasProperty(string $key): bool
    {
        return isset(($this->properties ?? [])[$key]);
    }

    /**
     * Отметить как конверсию
     */
    public function markAsConversion(float $value = 0): void
    {
        $this->update([
            'is_conversion' => true,
            'conversion_value' => $value,
        ]);
    }

    /**
     * Получить краткую сводку
     */
    public function getSummary(): array
    {
        return [
            'action' => $this->getActionDescription(),
            'category' => $this->getActionCategory(),
            'user' => $this->user ? $this->user->name : 'Гость',
            'is_conversion' => $this->is_conversion,
            'conversion_value' => $this->conversion_value,
            'performed_at' => $this->performed_at->diffForHumans(),
        ];
    }

    /**
     * Получить все доступные типы действий
     */
    public static function getActionTypes(): array
    {
        return [
            self::ACTION_LOGIN,
            self::ACTION_LOGOUT,
            self::ACTION_REGISTER,
            self::ACTION_VIEW_PROFILE,
            self::ACTION_UPDATE_PROFILE,
            self::ACTION_CREATE_AD,
            self::ACTION_UPDATE_AD,
            self::ACTION_DELETE_AD,
            self::ACTION_VIEW_AD,
            self::ACTION_CONTACT_MASTER,
            self::ACTION_ADD_FAVORITE,
            self::ACTION_REMOVE_FAVORITE,
            self::ACTION_BOOK_SERVICE,
            self::ACTION_CANCEL_BOOKING,
            self::ACTION_COMPLETE_BOOKING,
            self::ACTION_LEAVE_REVIEW,
            self::ACTION_MAKE_PAYMENT,
            self::ACTION_REFUND_PAYMENT,
            self::ACTION_SEARCH,
            self::ACTION_FILTER,
            self::ACTION_UPLOAD_PHOTO,
            self::ACTION_DELETE_PHOTO,
            self::ACTION_SEND_MESSAGE,
            self::ACTION_CLICK_PHONE,
            self::ACTION_CLICK_WHATSAPP,
            self::ACTION_SHARE,
            self::ACTION_REPORT,
        ];
    }

    /**
     * Форматирование для API
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'action_description' => $this->getActionDescription(),
            'action_category' => $this->getActionCategory(),
            'is_conversion_action' => $this->isConversionAction(),
            'summary' => $this->getSummary(),
            'time_ago' => $this->performed_at->diffForHumans(),
        ]);
    }
}