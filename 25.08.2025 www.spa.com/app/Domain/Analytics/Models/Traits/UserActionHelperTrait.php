<?php

namespace App\Domain\Analytics\Models\Traits;

/**
 * Трейт с helper методами для действий пользователей
 */
trait UserActionHelperTrait
{
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