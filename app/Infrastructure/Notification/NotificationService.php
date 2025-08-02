<?php

namespace App\Infrastructure\Notification;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Models\NotificationDelivery;
use App\Domain\User\Models\User;
use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Models\Payment;
use App\Domain\Notification\Repositories\NotificationRepository;
use App\Enums\NotificationType;
use App\Enums\NotificationStatus;
use App\Enums\NotificationChannel;
use App\Domain\Notification\DTOs\CreateNotificationDTO;
use App\Infrastructure\Notification\ChannelManager;
use App\Events\Notification\NotificationCreated;
use App\Events\Notification\NotificationSent;
use App\Events\Notification\NotificationDelivered;
use App\Events\Notification\NotificationFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * Сервис для работы с уведомлениями
 */
class NotificationService
{
    protected NotificationRepository $repository;
    protected ChannelManager $channelManager;
    protected LegacyNotificationService $legacyService;

    public function __construct(
        NotificationRepository $repository,
        ChannelManager $channelManager,
        LegacyNotificationService $legacyService
    ) {
        $this->repository = $repository;
        $this->channelManager = $channelManager;
        $this->legacyService = $legacyService;
    }

    // ============ LEGACY COMPATIBILITY METHODS ============
    
    /**
     * @deprecated Используйте новые методы создания уведомлений
     */
    public function sendBookingCreated(Booking $booking): void
    {
        // Вызываем старый метод для совместимости
        $this->legacyService->sendBookingCreated($booking);
        
        // Создаем новое уведомление
        $this->createBookingNotification($booking, NotificationType::BOOKING_CREATED);
    }

    /**
     * @deprecated Используйте новые методы создания уведомлений
     */
    public function sendBookingConfirmed(Booking $booking): void
    {
        $this->legacyService->sendBookingConfirmed($booking);
        $this->createBookingNotification($booking, NotificationType::BOOKING_CONFIRMED);
    }

    /**
     * @deprecated Используйте новые методы создания уведомлений
     */
    public function sendBookingCancelled(Booking $booking, User $cancelledBy): void
    {
        $this->legacyService->sendBookingCancelled($booking, $cancelledBy);
        $this->createBookingNotification($booking, NotificationType::BOOKING_CANCELLED);
    }

    /**
     * @deprecated Используйте новые методы создания уведомлений
     */
    public function sendReviewRequest(Booking $booking): void
    {
        $this->legacyService->sendReviewRequest($booking);
        $this->createBookingNotification($booking, NotificationType::REVIEW_RECEIVED);
    }

    /**
     * @deprecated Используйте новые методы создания уведомлений
     */
    public function sendPaymentCompleted(Payment $payment): void
    {
        $this->legacyService->sendPaymentCompleted($payment);
        $this->createPaymentNotification($payment, NotificationType::PAYMENT_COMPLETED);
    }

    // ============ NEW NOTIFICATION SYSTEM ============

    /**
     * Создать уведомление
     */
    public function create(CreateNotificationDTO $dto): Notification
    {
        try {
            DB::beginTransaction();

            // Определяем каналы доставки
            $channels = $dto->channels ?: NotificationChannel::getDefaultChannels($dto->type);

            // Создаем уведомление
            $notification = $this->repository->create([
                'user_id' => $dto->userId,
                'type' => $dto->type,
                'title' => $dto->title,
                'message' => $dto->message,
                'data' => $dto->data,
                'channels' => array_map(fn($c) => $c->value, $channels),
                'notifiable_type' => $dto->notifiableType,
                'notifiable_id' => $dto->notifiableId,
                'scheduled_at' => $dto->scheduledAt,
                'expires_at' => $dto->expiresAt,
                'priority' => $dto->priority,
                'group_key' => $dto->groupKey,
                'template' => $dto->template,
                'locale' => $dto->locale,
                'max_retries' => $dto->maxRetries,
                'metadata' => $dto->metadata,
            ]);

            // Создаем записи доставки для каждого канала
            foreach ($channels as $channel) {
                $this->createDelivery($notification, $channel, $dto);
            }

            DB::commit();

            // Событие создания уведомления
            event(new NotificationCreated($notification));

            // Если не запланировано на будущее - отправляем сразу
            if (!$dto->scheduledAt || $dto->scheduledAt->isPast()) {
                $this->send($notification);
            }

            Log::info('Notification created', [
                'notification_id' => $notification->id,
                'user_id' => $dto->userId,
                'type' => $dto->type->value,
            ]);

            return $notification;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create notification', [
                'error' => $e->getMessage(),
                'user_id' => $dto->userId,
                'type' => $dto->type->value,
            ]);

            throw $e;
        }
    }

    /**
     * Создать запись доставки для канала
     */
    protected function createDelivery(
        Notification $notification, 
        NotificationChannel $channel,
        CreateNotificationDTO $dto
    ): NotificationDelivery {
        return NotificationDelivery::create([
            'notification_id' => $notification->id,
            'channel' => $channel,
            'status' => NotificationStatus::PENDING,
            'recipient' => $this->getRecipientForChannel($notification->user, $channel),
            'content' => $this->prepareContentForChannel($dto, $channel),
            'max_retries' => $dto->maxRetries,
        ]);
    }

    /**
     * Получить получателя для канала
     */
    protected function getRecipientForChannel(User $user, NotificationChannel $channel): ?string
    {
        return match($channel) {
            NotificationChannel::EMAIL => $user->email,
            NotificationChannel::SMS => $user->phone,
            NotificationChannel::TELEGRAM => $user->telegram_id,
            NotificationChannel::PUSH => $this->getUserPushTokens($user),
            default => null,
        };
    }

    /**
     * Подготовить контент для канала
     */
    protected function prepareContentForChannel(
        CreateNotificationDTO $dto, 
        NotificationChannel $channel
    ): array {
        $content = [
            'title' => $dto->title,
            'message' => $dto->message,
            'data' => $dto->data,
        ];

        // Адаптируем контент под канал
        if ($channel === NotificationChannel::SMS) {
            // Для SMS объединяем заголовок и сообщение
            $content['message'] = trim($dto->title . ': ' . $dto->message);
            
            // Обрезаем до лимита SMS
            $maxLength = $channel->getMaxMessageLength();
            if ($maxLength && strlen($content['message']) > $maxLength) {
                $content['message'] = substr($content['message'], 0, $maxLength - 3) . '...';
            }
        }

        if ($channel === NotificationChannel::PUSH) {
            // Для Push добавляем специфичные поля
            $content['badge'] = 1;
            $content['sound'] = 'default';
            $content['click_action'] = $dto->data['action_url'] ?? null;
        }

        return $content;
    }

    /**
     * Получить Push токены пользователя
     */
    protected function getUserPushTokens(User $user): ?string
    {
        // TODO: Реализовать получение push токенов
        return null;
    }

    /**
     * Отправить уведомление
     */
    public function send(Notification $notification): array
    {
        $results = [];

        try {
            $notification->markAsSent();

            $deliveries = $notification->deliveries()->pending()->get();

            foreach ($deliveries as $delivery) {
                $result = $this->sendViaChannel($delivery);
                $results[$delivery->channel->value] = $result;
            }

            // Если все доставки успешны - помечаем уведомление как доставленное
            $allDelivered = $notification->deliveries()
                ->whereIn('status', [NotificationStatus::DELIVERED, NotificationStatus::SENT])
                ->count() === $notification->deliveries()->count();

            if ($allDelivered) {
                $notification->markAsDelivered();
                event(new NotificationDelivered($notification));
            }

            event(new NotificationSent($notification, $results));

            Log::info('Notification sent', [
                'notification_id' => $notification->id,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            
            event(new NotificationFailed($notification, $e->getMessage()));

            Log::error('Failed to send notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $results;
    }

    /**
     * Отправить через определенный канал
     */
    protected function sendViaChannel(NotificationDelivery $delivery): array
    {
        try {
            $channel = $this->channelManager->getChannel($delivery->channel);
            
            if (!$channel->isAvailable()) {
                $delivery->markAsFailed('Channel not available');
                return ['success' => false, 'error' => 'Channel not available'];
            }

            $result = $channel->send($delivery);

            if ($result['success']) {
                $delivery->markAsSent($result['external_id'] ?? null);
                
                // Для некоторых каналов сразу помечаем как доставленное
                if (in_array($delivery->channel, [NotificationChannel::DATABASE, NotificationChannel::WEBSOCKET])) {
                    $delivery->markAsDelivered();
                }
            } else {
                $delivery->markAsFailed($result['error'] ?? 'Unknown error');
            }

            return $result;

        } catch (\Exception $e) {
            $delivery->markAsFailed($e->getMessage());
            
            Log::error('Failed to send via channel', [
                'delivery_id' => $delivery->id,
                'channel' => $delivery->channel->value,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ============ HELPER METHODS ============

    /**
     * Создать уведомление для бронирования
     */
    protected function createBookingNotification(Booking $booking, NotificationType $type): Notification
    {
        $dto = new CreateNotificationDTO(
            userId: $booking->client_id,
            type: $type,
            title: $type->getTitle(),
            message: $this->getBookingMessage($booking, $type),
            notifiableType: 'App\\Models\\Booking',
            notifiableId: $booking->id,
            data: [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'action_url' => route('bookings.show', $booking->id),
                'action_text' => 'Посмотреть бронирование',
            ]
        );

        return $this->create($dto);
    }

    /**
     * Создать уведомление для платежа
     */
    protected function createPaymentNotification(Payment $payment, NotificationType $type): Notification
    {
        $dto = new CreateNotificationDTO(
            userId: $payment->user_id,
            type: $type,
            title: $type->getTitle(),
            message: $this->getPaymentMessage($payment, $type),
            notifiableType: 'App\\Models\\Payment',
            notifiableId: $payment->id,
            data: [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'action_url' => route('payments.show', $payment->id),
                'action_text' => 'Посмотреть платеж',
            ]
        );

        return $this->create($dto);
    }

    /**
     * Получить сообщение для бронирования
     */
    protected function getBookingMessage(Booking $booking, NotificationType $type): string
    {
        return match($type) {
            NotificationType::BOOKING_CREATED => "Ваше бронирование #{$booking->booking_number} создано и ожидает подтверждения",
            NotificationType::BOOKING_CONFIRMED => "Бронирование #{$booking->booking_number} подтверждено мастером",
            NotificationType::BOOKING_CANCELLED => "Бронирование #{$booking->booking_number} было отменено",
            NotificationType::BOOKING_REMINDER => "Напоминание: через час у вас сеанс массажа",
            NotificationType::BOOKING_COMPLETED => "Сеанс массажа успешно завершен",
            default => $type->getDefaultMessage(),
        };
    }

    /**
     * Получить сообщение для платежа
     */
    protected function getPaymentMessage(Payment $payment, NotificationType $type): string
    {
        $amount = number_format($payment->amount, 0, ',', ' ') . ' ₽';
        
        return match($type) {
            NotificationType::PAYMENT_COMPLETED => "Платеж на сумму {$amount} успешно обработан",
            NotificationType::PAYMENT_FAILED => "Не удалось обработать платеж на сумму {$amount}",
            NotificationType::PAYMENT_REFUNDED => "Возврат средств на сумму {$amount} обработан",
            default => $type->getDefaultMessage(),
        };
    }

    /**
     * Пометить как прочитанное
     */
    public function markAsRead(int $notificationId, int $userId = null): bool
    {
        $notification = $this->repository->find($notificationId);

        if (!$notification) {
            return false;
        }

        // Проверяем права доступа
        if ($userId && $notification->user_id !== $userId) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }

    /**
     * Пометить все как прочитанные
     */
    public function markAllAsRead(int $userId): int
    {
        return $this->repository->markAllAsReadForUser($userId);
    }

    /**
     * Получить уведомления пользователя
     */
    public function getForUser(int $userId, array $filters = []): Collection
    {
        return $this->repository->getPaginatedForUser($userId, 20, $filters);
    }

    /**
     * Получить количество непрочитанных
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->repository->getUnreadCountForUser($userId);
    }

    /**
     * Создать системное уведомление
     */
    public function createSystem(
        string $title,
        string $message,
        array $userIds = [],
        array $data = []
    ): Collection {
        $dto = new CreateNotificationDTO(
            userId: 0, // Системное
            type: NotificationType::SYSTEM_UPDATE,
            title: $title,
            message: $message,
            data: $data,
            priority: 'high'
        );

        if (empty($userIds)) {
            // Отправляем всем активным пользователям
            $userIds = User::active()->pluck('id')->toArray();
        }

        return $this->createForUsers($userIds, $dto);
    }

    /**
     * Создать уведомление для нескольких пользователей
     */
    public function createForUsers(array $userIds, CreateNotificationDTO $dto): Collection
    {
        $notifications = collect();

        DB::transaction(function() use ($userIds, $dto, $notifications) {
            foreach ($userIds as $userId) {
                $userDto = clone $dto;
                $userDto->userId = $userId;
                
                $notifications->push($this->create($userDto));
            }
        });

        return $notifications;
    }

    /**
     * Получить статистику
     */
    public function getStats(int $days = 7): array
    {
        return $this->repository->getStats($days);
    }

    /**
     * Очистка старых уведомлений
     */
    public function cleanup(): array
    {
        $deletedOld = $this->repository->deleteOld(30);
        $deletedExpired = $this->repository->deleteExpired();
        $cleanedNotifications = Notification::cleanup();

        return [
            'deleted_old' => $deletedOld,
            'deleted_expired' => $deletedExpired,
            'cleaned_notifications' => $cleanedNotifications,
            'total_cleaned' => $deletedOld + $deletedExpired + $cleanedNotifications,
        ];
    }

    /**
     * Тестовое уведомление
     */
    public function sendTest(int $userId, NotificationChannel $channel = null): Notification
    {
        $channels = $channel ? [$channel] : [NotificationChannel::DATABASE];

        $dto = new CreateNotificationDTO(
            userId: $userId,
            type: NotificationType::SYSTEM_UPDATE,
            title: 'Тестовое уведомление',
            message: 'Это тестовое уведомление для проверки системы',
            channels: $channels,
            data: [
                'test' => true,
                'timestamp' => now()->toISOString(),
            ]
        );

        return $this->create($dto);
    }

    // ============ DOMAIN COMPATIBILITY LAYER ============

    /**
     * Отправить уведомление по шаблону (для совместимости с Domain слоем)
     */
    public function send(User $user, string $template, array $data): void
    {
        try {
            $type = $this->getNotificationTypeFromTemplate($template);
            $title = $this->getTemplateTitle($template, $data);
            $message = $this->compileTemplate($template, $data);

            $dto = new CreateNotificationDTO(
                userId: $user->id,
                type: $type,
                title: $title,
                message: $message,
                data: $data,
                priority: $type->getPriority(),
                channels: $this->getChannelsForType($type),
            );

            $this->create($dto);

            Log::info('Template notification sent', [
                'user_id' => $user->id,
                'template' => $template,
                'type' => $type->value,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send template notification', [
                'user_id' => $user->id,
                'template' => $template,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Получить тип уведомления по шаблону
     */
    protected function getNotificationTypeFromTemplate(string $template): NotificationType
    {
        $templateMapping = [
            // Booking templates
            'booking.created' => NotificationType::BOOKING_CREATED,
            'booking.confirmed' => NotificationType::BOOKING_CONFIRMED,
            'booking.cancelled' => NotificationType::BOOKING_CANCELLED,
            'booking.reminder' => NotificationType::BOOKING_REMINDER,
            'booking.completed' => NotificationType::BOOKING_COMPLETED,
            'booking.rescheduled' => NotificationType::BOOKING_CONFIRMED, // Переназначение как подтверждение

            // Payment templates
            'payment.completed' => NotificationType::PAYMENT_COMPLETED,
            'payment.failed' => NotificationType::PAYMENT_FAILED,
            'payment.refunded' => NotificationType::PAYMENT_REFUNDED,

            // Review templates
            'review.received' => NotificationType::REVIEW_RECEIVED,
            'review.response' => NotificationType::REVIEW_RESPONSE,

            // Ad templates
            'ad.approved' => NotificationType::AD_APPROVED,
            'ad.rejected' => NotificationType::AD_REJECTED,
            'ad.expired' => NotificationType::AD_EXPIRED,

            // System templates
            'system.maintenance' => NotificationType::SYSTEM_MAINTENANCE,
            'system.update' => NotificationType::SYSTEM_UPDATE,

            // Communication templates
            'chat.message' => NotificationType::CHAT_MESSAGE,
            'call.request' => NotificationType::CALL_REQUEST,

            // Marketing templates
            'promo.new' => NotificationType::PROMO_NEW,
            'promo.expiring' => NotificationType::PROMO_EXPIRING,
        ];

        if (!isset($templateMapping[$template])) {
            throw new \InvalidArgumentException("Unknown notification template: {$template}");
        }

        return $templateMapping[$template];
    }

    /**
     * Получить заголовок для шаблона
     */
    protected function getTemplateTitle(string $template, array $data): string
    {
        $type = $this->getNotificationTypeFromTemplate($template);
        $baseTitle = $type->getTitle();

        // Кастомизация заголовков для специфичных шаблонов
        return match($template) {
            'booking.rescheduled' => 'Бронирование перенесено',
            default => $baseTitle,
        };
    }

    /**
     * Скомпилировать шаблон с данными
     */
    protected function compileTemplate(string $template, array $data): string
    {
        $type = $this->getNotificationTypeFromTemplate($template);
        
        // Базовое сообщение из енума
        $message = $type->getDefaultMessage();

        // Кастомизация сообщений для специфичных шаблонов с данными
        return match($template) {
            'booking.created' => $this->compileBookingCreatedMessage($data),
            'booking.confirmed' => $this->compileBookingConfirmedMessage($data),
            'booking.cancelled' => $this->compileBookingCancelledMessage($data),
            'booking.reminder' => $this->compileBookingReminderMessage($data),
            'booking.completed' => $this->compileBookingCompletedMessage($data),
            'booking.rescheduled' => $this->compileBookingRescheduledMessage($data),
            
            'payment.completed' => $this->compilePaymentMessage($data, 'completed'),
            'payment.failed' => $this->compilePaymentMessage($data, 'failed'),
            
            default => $message,
        };
    }

    /**
     * Получить каналы для типа уведомления
     */
    protected function getChannelsForType(NotificationType $type): array
    {
        $channels = [NotificationChannel::DATABASE]; // Всегда сохраняем в БД

        if ($type->shouldSendEmail()) {
            $channels[] = NotificationChannel::EMAIL;
        }

        if ($type->shouldSendSms()) {
            $channels[] = NotificationChannel::SMS;
        }

        if ($type->shouldSendPush()) {
            $channels[] = NotificationChannel::PUSH;
        }

        return $channels;
    }

    // ============ MESSAGE COMPILERS ============

    /**
     * Скомпилировать сообщение для создания бронирования
     */
    protected function compileBookingCreatedMessage(array $data): string
    {
        $bookingNumber = $data['booking_number'] ?? 'N/A';
        return "Ваше бронирование #{$bookingNumber} создано и ожидает подтверждения мастером";
    }

    /**
     * Скомпилировать сообщение для подтверждения бронирования
     */
    protected function compileBookingConfirmedMessage(array $data): string
    {
        $bookingNumber = $data['booking_number'] ?? 'N/A';
        $masterName = $data['master_name'] ?? 'мастером';
        $date = $data['date'] ?? '';
        $time = $data['time'] ?? '';
        
        return "Бронирование #{$bookingNumber} подтверждено {$masterName} на {$date} в {$time}";
    }

    /**
     * Скомпилировать сообщение для отмены бронирования
     */
    protected function compileBookingCancelledMessage(array $data): string
    {
        $bookingNumber = $data['booking_number'] ?? 'N/A';
        $reason = $data['reason'] ?? 'Причина не указана';
        
        return "Бронирование #{$bookingNumber} было отменено. Причина: {$reason}";
    }

    /**
     * Скомпилировать сообщение напоминания
     */
    protected function compileBookingReminderMessage(array $data): string
    {
        $masterName = $data['master_name'] ?? 'мастер';
        $time = $data['time'] ?? '';
        $address = $data['address'] ?? '';
        
        $message = "Напоминание: через час у вас сеанс массажа у {$masterName}";
        
        if ($time) {
            $message .= " в {$time}";
        }
        
        if ($address) {
            $message .= " по адресу: {$address}";
        }
        
        return $message;
    }

    /**
     * Скомпилировать сообщение о завершении
     */
    protected function compileBookingCompletedMessage(array $data): string
    {
        $masterName = $data['master_name'] ?? 'мастер';
        $bookingNumber = $data['booking_number'] ?? 'N/A';
        
        return "Ваш сеанс массажа у {$masterName} (#{$bookingNumber}) успешно завершен. Спасибо за использование нашего сервиса!";
    }

    /**
     * Скомпилировать сообщение о переносе
     */
    protected function compileBookingRescheduledMessage(array $data): string
    {
        $bookingNumber = $data['booking_number'] ?? 'N/A';
        $oldDate = $data['old_date'] ?? '';
        $oldTime = $data['old_time'] ?? '';
        $newDate = $data['new_date'] ?? '';
        $newTime = $data['new_time'] ?? '';
        
        return "Бронирование #{$bookingNumber} перенесено с {$oldDate} {$oldTime} на {$newDate} {$newTime}";
    }

    /**
     * Скомпилировать сообщение о платеже
     */
    protected function compilePaymentMessage(array $data, string $status): string
    {
        $amount = $data['amount'] ?? 0;
        $formattedAmount = number_format($amount, 0, ',', ' ') . ' ₽';
        
        return match($status) {
            'completed' => "Платеж на сумму {$formattedAmount} успешно обработан",
            'failed' => "Не удалось обработать платеж на сумму {$formattedAmount}. Попробуйте еще раз",
            'refunded' => "Возврат средств на сумму {$formattedAmount} обработан",
            default => "Операция с платежом на сумму {$formattedAmount}",
        };
    }
} 