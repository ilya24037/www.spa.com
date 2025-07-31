<?php

namespace App\Enums;

/**
 * Типы уведомлений в системе
 */
enum NotificationType: string
{
    case BOOKING_CREATED = 'booking_created';
    case BOOKING_CONFIRMED = 'booking_confirmed';
    case BOOKING_CANCELLED = 'booking_cancelled';
    case BOOKING_REMINDER = 'booking_reminder';
    case BOOKING_COMPLETED = 'booking_completed';
    
    case PAYMENT_COMPLETED = 'payment_completed';
    case PAYMENT_FAILED = 'payment_failed';
    case PAYMENT_REFUNDED = 'payment_refunded';
    
    case AD_APPROVED = 'ad_approved';
    case AD_REJECTED = 'ad_rejected';
    case AD_EXPIRED = 'ad_expired';
    
    case REVIEW_RECEIVED = 'review_received';
    case REVIEW_RESPONSE = 'review_response';
    
    case SYSTEM_MAINTENANCE = 'system_maintenance';
    case SYSTEM_UPDATE = 'system_update';
    
    case CHAT_MESSAGE = 'chat_message';
    case CALL_REQUEST = 'call_request';
    
    case PROMO_NEW = 'promo_new';
    case PROMO_EXPIRING = 'promo_expiring';

    /**
     * Получить заголовок уведомления
     */
    public function getTitle(): string
    {
        return match($this) {
            self::BOOKING_CREATED => 'Новое бронирование',
            self::BOOKING_CONFIRMED => 'Бронирование подтверждено',
            self::BOOKING_CANCELLED => 'Бронирование отменено',
            self::BOOKING_REMINDER => 'Напоминание о сеансе',
            self::BOOKING_COMPLETED => 'Сеанс завершен',
            
            self::PAYMENT_COMPLETED => 'Платеж успешен',
            self::PAYMENT_FAILED => 'Ошибка платежа',
            self::PAYMENT_REFUNDED => 'Возврат средств',
            
            self::AD_APPROVED => 'Объявление одобрено',
            self::AD_REJECTED => 'Объявление отклонено',
            self::AD_EXPIRED => 'Объявление истекло',
            
            self::REVIEW_RECEIVED => 'Новый отзыв',
            self::REVIEW_RESPONSE => 'Ответ на отзыв',
            
            self::SYSTEM_MAINTENANCE => 'Техническое обслуживание',
            self::SYSTEM_UPDATE => 'Обновление системы',
            
            self::CHAT_MESSAGE => 'Новое сообщение',
            self::CALL_REQUEST => 'Запрос звонка',
            
            self::PROMO_NEW => 'Новая акция',
            self::PROMO_EXPIRING => 'Акция заканчивается',
        };
    }

    /**
     * Получить описание по умолчанию
     */
    public function getDefaultMessage(): string
    {
        return match($this) {
            self::BOOKING_CREATED => 'У вас новое бронирование массажной услуги',
            self::BOOKING_CONFIRMED => 'Ваше бронирование подтверждено мастером',
            self::BOOKING_CANCELLED => 'Бронирование было отменено',
            self::BOOKING_REMINDER => 'Через час у вас сеанс массажа',
            self::BOOKING_COMPLETED => 'Сеанс массажа успешно завершен',
            
            self::PAYMENT_COMPLETED => 'Ваш платеж успешно обработан',
            self::PAYMENT_FAILED => 'Не удалось обработать платеж',
            self::PAYMENT_REFUNDED => 'Средства возвращены на ваш счет',
            
            self::AD_APPROVED => 'Ваше объявление прошло модерацию и опубликовано',
            self::AD_REJECTED => 'Объявление не прошло модерацию',
            self::AD_EXPIRED => 'Срок действия объявления истек',
            
            self::REVIEW_RECEIVED => 'Вы получили новый отзыв',
            self::REVIEW_RESPONSE => 'На ваш отзыв дан ответ',
            
            self::SYSTEM_MAINTENANCE => 'Плановые технические работы',
            self::SYSTEM_UPDATE => 'Система обновлена до новой версии',
            
            self::CHAT_MESSAGE => 'У вас новое сообщение',
            self::CALL_REQUEST => 'Запрос на звонок от клиента',
            
            self::PROMO_NEW => 'Новая выгодная акция для вас',
            self::PROMO_EXPIRING => 'Акция скоро закончится',
        };
    }

    /**
     * Получить иконку уведомления
     */
    public function getIcon(): string
    {
        return match($this) {
            self::BOOKING_CREATED, 
            self::BOOKING_CONFIRMED => '📅',
            self::BOOKING_CANCELLED => '❌',
            self::BOOKING_REMINDER => '⏰',
            self::BOOKING_COMPLETED => '✅',
            
            self::PAYMENT_COMPLETED => '💰',
            self::PAYMENT_FAILED => '❗',
            self::PAYMENT_REFUNDED => '💸',
            
            self::AD_APPROVED => '✅',
            self::AD_REJECTED => '❌',
            self::AD_EXPIRED => '⏰',
            
            self::REVIEW_RECEIVED => '⭐',
            self::REVIEW_RESPONSE => '💬',
            
            self::SYSTEM_MAINTENANCE, 
            self::SYSTEM_UPDATE => '🔧',
            
            self::CHAT_MESSAGE => '💬',
            self::CALL_REQUEST => '📞',
            
            self::PROMO_NEW, 
            self::PROMO_EXPIRING => '🎁',
        };
    }

    /**
     * Получить цвет уведомления
     */
    public function getColor(): string
    {
        return match($this) {
            self::BOOKING_CREATED, 
            self::BOOKING_CONFIRMED,
            self::PAYMENT_COMPLETED,
            self::AD_APPROVED,
            self::BOOKING_COMPLETED => 'success',
            
            self::BOOKING_CANCELLED,
            self::PAYMENT_FAILED,
            self::AD_REJECTED => 'error',
            
            self::BOOKING_REMINDER,
            self::AD_EXPIRED,
            self::PROMO_EXPIRING => 'warning',
            
            self::SYSTEM_MAINTENANCE => 'info',
            
            default => 'primary',
        };
    }

    /**
     * Получить приоритет уведомления
     */
    public function getPriority(): string
    {
        return match($this) {
            self::BOOKING_REMINDER,
            self::PAYMENT_FAILED,
            self::CALL_REQUEST => 'high',
            
            self::BOOKING_CANCELLED,
            self::PAYMENT_COMPLETED,
            self::SYSTEM_MAINTENANCE => 'medium',
            
            default => 'low',
        };
    }

    /**
     * Нужно ли отправлять push уведомление
     */
    public function shouldSendPush(): bool
    {
        return match($this) {
            self::BOOKING_REMINDER,
            self::BOOKING_CONFIRMED,
            self::BOOKING_CANCELLED,
            self::PAYMENT_COMPLETED,
            self::PAYMENT_FAILED,
            self::CALL_REQUEST,
            self::CHAT_MESSAGE => true,
            
            default => false,
        };
    }

    /**
     * Нужно ли отправлять email
     */
    public function shouldSendEmail(): bool
    {
        return match($this) {
            self::BOOKING_CONFIRMED,
            self::BOOKING_CANCELLED,
            self::PAYMENT_COMPLETED,
            self::PAYMENT_REFUNDED,
            self::AD_APPROVED,
            self::AD_REJECTED,
            self::SYSTEM_MAINTENANCE => true,
            
            default => false,
        };
    }

    /**
     * Нужно ли отправлять SMS
     */
    public function shouldSendSms(): bool
    {
        return match($this) {
            self::BOOKING_REMINDER,
            self::BOOKING_CANCELLED,
            self::PAYMENT_FAILED,
            self::CALL_REQUEST => true,
            
            default => false,
        };
    }

    /**
     * Время жизни уведомления в днях
     */
    public function getLifetimeDays(): int
    {
        return match($this) {
            self::BOOKING_REMINDER => 1,
            self::CHAT_MESSAGE => 7,
            self::PROMO_EXPIRING => 3,
            self::SYSTEM_MAINTENANCE => 1,
            
            default => 30,
        };
    }

    /**
     * Можно ли группировать уведомления этого типа
     */
    public function canBeGrouped(): bool
    {
        return match($this) {
            self::CHAT_MESSAGE,
            self::REVIEW_RECEIVED,
            self::PROMO_NEW => true,
            
            default => false,
        };
    }

    /**
     * Получить все типы для определенной категории
     */
    public static function getByCategory(string $category): array
    {
        return match($category) {
            'booking' => [
                self::BOOKING_CREATED,
                self::BOOKING_CONFIRMED,
                self::BOOKING_CANCELLED,
                self::BOOKING_REMINDER,
                self::BOOKING_COMPLETED,
            ],
            'payment' => [
                self::PAYMENT_COMPLETED,
                self::PAYMENT_FAILED,
                self::PAYMENT_REFUNDED,
            ],
            'ad' => [
                self::AD_APPROVED,
                self::AD_REJECTED,
                self::AD_EXPIRED,
            ],
            'communication' => [
                self::CHAT_MESSAGE,
                self::CALL_REQUEST,
                self::REVIEW_RECEIVED,
                self::REVIEW_RESPONSE,
            ],
            'system' => [
                self::SYSTEM_MAINTENANCE,
                self::SYSTEM_UPDATE,
            ],
            'marketing' => [
                self::PROMO_NEW,
                self::PROMO_EXPIRING,
            ],
            default => [],
        };
    }

    /**
     * Получить шаблон для уведомления
     */
    public function getTemplate(): string
    {
        return match($this) {
            self::BOOKING_CREATED => 'notifications.booking.created',
            self::BOOKING_CONFIRMED => 'notifications.booking.confirmed',
            self::BOOKING_CANCELLED => 'notifications.booking.cancelled',
            self::BOOKING_REMINDER => 'notifications.booking.reminder',
            self::BOOKING_COMPLETED => 'notifications.booking.completed',
            
            self::PAYMENT_COMPLETED => 'notifications.payment.completed',
            self::PAYMENT_FAILED => 'notifications.payment.failed',
            self::PAYMENT_REFUNDED => 'notifications.payment.refunded',
            
            self::AD_APPROVED => 'notifications.ad.approved',
            self::AD_REJECTED => 'notifications.ad.rejected',
            self::AD_EXPIRED => 'notifications.ad.expired',
            
            self::REVIEW_RECEIVED => 'notifications.review.received',
            self::REVIEW_RESPONSE => 'notifications.review.response',
            
            self::SYSTEM_MAINTENANCE => 'notifications.system.maintenance',
            self::SYSTEM_UPDATE => 'notifications.system.update',
            
            self::CHAT_MESSAGE => 'notifications.chat.message',
            self::CALL_REQUEST => 'notifications.call.request',
            
            self::PROMO_NEW => 'notifications.promo.new',
            self::PROMO_EXPIRING => 'notifications.promo.expiring',
        };
    }
}