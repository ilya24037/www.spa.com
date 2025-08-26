<?php

namespace App\Domain\Analytics\Models\Traits;

/**
 * Трейт с типами действий пользователей
 */
trait UserActionTypesTrait
{
    // Авторизация и регистрация
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_REGISTER = 'register';
    
    // Профиль пользователя
    public const ACTION_VIEW_PROFILE = 'view_profile';
    public const ACTION_UPDATE_PROFILE = 'update_profile';
    
    // Объявления
    public const ACTION_CREATE_AD = 'create_ad';
    public const ACTION_UPDATE_AD = 'update_ad';
    public const ACTION_DELETE_AD = 'delete_ad';
    public const ACTION_VIEW_AD = 'view_ad';
    
    // Коммуникация
    public const ACTION_CONTACT_MASTER = 'contact_master';
    public const ACTION_SEND_MESSAGE = 'send_message';
    public const ACTION_CLICK_PHONE = 'click_phone';
    public const ACTION_CLICK_WHATSAPP = 'click_whatsapp';
    
    // Избранное
    public const ACTION_ADD_FAVORITE = 'add_favorite';
    public const ACTION_REMOVE_FAVORITE = 'remove_favorite';
    
    // Бронирование
    public const ACTION_BOOK_SERVICE = 'book_service';
    public const ACTION_CANCEL_BOOKING = 'cancel_booking';
    public const ACTION_COMPLETE_BOOKING = 'complete_booking';
    
    // Отзывы
    public const ACTION_LEAVE_REVIEW = 'leave_review';
    
    // Платежи
    public const ACTION_MAKE_PAYMENT = 'make_payment';
    public const ACTION_REFUND_PAYMENT = 'refund_payment';
    
    // Поиск и фильтрация
    public const ACTION_SEARCH = 'search';
    public const ACTION_FILTER = 'filter';
    
    // Медиа
    public const ACTION_UPLOAD_PHOTO = 'upload_photo';
    public const ACTION_DELETE_PHOTO = 'delete_photo';
    
    // Прочие действия
    public const ACTION_SHARE = 'share';
    public const ACTION_REPORT = 'report';

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
}