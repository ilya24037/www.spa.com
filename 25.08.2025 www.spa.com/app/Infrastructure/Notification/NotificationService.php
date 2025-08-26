<?php

namespace App\Infrastructure\Notification;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Repositories\NotificationRepository;
use App\Domain\Notification\Services\NotificationTemplateProcessor;
use App\Domain\Notification\Services\NotificationDeliveryManager;
use App\Domain\Notification\DTOs\CreateNotificationDTO;
use App\Domain\User\Models\User;
use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Models\Payment;
use App\Enums\NotificationType;
use App\Enums\NotificationChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Упрощенный сервис уведомлений
 * Основная логика вынесена в отдельные классы
 */
class NotificationService implements \App\Domain\Notification\Contracts\NotificationServiceInterface
{
    protected NotificationRepository $repository;
    protected NotificationTemplateProcessor $templateProcessor;
    protected NotificationDeliveryManager $deliveryManager;

    public function __construct(
        NotificationRepository $repository,
        NotificationTemplateProcessor $templateProcessor,
        NotificationDeliveryManager $deliveryManager
    ) {
        $this->repository = $repository;
        $this->templateProcessor = $templateProcessor;
        $this->deliveryManager = $deliveryManager;
    }

    /**
     * Создать и отправить уведомление
     */
    public function create(CreateNotificationDTO $dto): Notification
    {
        return DB::transaction(function () use ($dto) {
            // Обработка шаблона
            $processedTemplate = $this->templateProcessor->processTemplate(
                $dto->type,
                $dto->data,
                $dto->locale ?? 'ru'
            );

            // Создание уведомления
            $notification = $this->repository->create([
                'type' => $dto->type,
                'title' => $processedTemplate['title'],
                'body' => $processedTemplate['body'],
                'action_url' => $processedTemplate['action_url'],
                'data' => $dto->data,
                'template_id' => $processedTemplate['template_id'] ?? null,
                'created_by' => $dto->created_by
            ]);

            Log::info('Notification created', [
                'id' => $notification->id,
                'type' => $dto->type->value,
                'title' => $processedTemplate['title']
            ]);

            return $notification;
        });
    }

    /**
     * Отправить уведомление пользователю
     */
    public function sendToUser(
        Notification $notification,
        User $user,
        array $channels = []
    ): Collection {
        return $this->deliveryManager->send($notification, $user, $channels);
    }

    /**
     * Массовая отправка уведомлений
     */
    public function sendToUsers(
        Notification $notification,
        Collection $users,
        array $channels = []
    ): int {
        return $this->deliveryManager->sendBatch($notification, $users, $channels);
    }

    /**
     * Создать и отправить уведомление одному пользователю
     */
    public function createAndSend(
        NotificationType $type,
        User $user,
        array $data = [],
        array $channels = []
    ): Notification {
        $dto = new CreateNotificationDTO([
            'type' => $type,
            'data' => array_merge($data, ['user' => $user]),
            'created_by' => $user->id
        ]);

        $notification = $this->create($dto);
        $this->sendToUser($notification, $user, $channels);

        return $notification;
    }

    /**
     * Отметить уведомление как прочитанное
     */
    public function markAsRead(int $notificationId, User $user): bool
    {
        return $this->repository->markAsRead($notificationId, $user->id);
    }

    /**
     * Отметить все уведомления пользователя как прочитанные
     */
    public function markAllAsRead(User $user): int
    {
        return $this->repository->markAllAsRead($user->id);
    }

    /**
     * Получить уведомления пользователя
     */
    public function getUserNotifications(
        User $user,
        int $limit = 20,
        bool $unreadOnly = false
    ): Collection {
        return $this->repository->getUserNotifications($user->id, $limit, $unreadOnly);
    }

    /**
     * Получить количество непрочитанных уведомлений
     */
    public function getUnreadCount(User $user): int
    {
        return $this->repository->getUnreadCount($user->id);
    }

    /**
     * Удалить уведомление
     */
    public function delete(int $notificationId, User $user): bool
    {
        return $this->repository->deleteUserNotification($notificationId, $user->id);
    }

    // ============ CONVENIENCE METHODS ============

    /**
     * Уведомление о создании бронирования
     */
    public function bookingCreated(Booking $booking): Notification
    {
        return $this->createAndSend(
            NotificationType::BOOKING_CREATED,
            $booking->master->user,
            ['booking' => $booking, 'client' => $booking->user],
            [NotificationChannel::IN_APP, NotificationChannel::PUSH]
        );
    }

    /**
     * Уведомление о подтверждении бронирования
     */
    public function bookingConfirmed(Booking $booking): Notification
    {
        return $this->createAndSend(
            NotificationType::BOOKING_CONFIRMED,
            $booking->user,
            ['booking' => $booking, 'master' => $booking->master->user],
            [NotificationChannel::IN_APP, NotificationChannel::EMAIL, NotificationChannel::SMS]
        );
    }

    /**
     * Уведомление об отмене бронирования
     */
    public function bookingCancelled(Booking $booking, User $cancelledBy): Collection
    {
        $notifications = collect();

        // Уведомляем клиента
        if ($cancelledBy->id !== $booking->user_id) {
            $notifications->push($this->createAndSend(
                NotificationType::BOOKING_CANCELLED,
                $booking->user,
                ['booking' => $booking, 'cancelled_by' => $cancelledBy]
            ));
        }

        // Уведомляем мастера
        if ($cancelledBy->id !== $booking->master->user_id) {
            $notifications->push($this->createAndSend(
                NotificationType::BOOKING_CANCELLED,
                $booking->master->user,
                ['booking' => $booking, 'cancelled_by' => $cancelledBy]
            ));
        }

        return $notifications;
    }

    /**
     * Уведомление об успешном платеже
     */
    public function paymentSuccessful(Payment $payment): Notification
    {
        return $this->createAndSend(
            NotificationType::PAYMENT_SUCCESSFUL,
            $payment->user,
            ['payment' => $payment]
        );
    }

    /**
     * Уведомление о неудачном платеже
     */
    public function paymentFailed(Payment $payment): Notification
    {
        return $this->createAndSend(
            NotificationType::PAYMENT_FAILED,
            $payment->user,
            ['payment' => $payment],
            [NotificationChannel::IN_APP, NotificationChannel::EMAIL]
        );
    }

    /**
     * Получить статистику доставки
     */
    public function getDeliveryStats(Notification $notification): array
    {
        return $this->deliveryManager->getDeliveryStats($notification);
    }

    /**
     * Очистить старые уведомления
     */
    public function cleanup(int $daysOld = 30): array
    {
        $deletedNotifications = $this->repository->deleteOld($daysOld);
        $deletedDeliveries = $this->deliveryManager->cleanupOldDeliveries($daysOld);

        Log::info('Notification cleanup completed', [
            'deleted_notifications' => $deletedNotifications,
            'deleted_deliveries' => $deletedDeliveries,
            'days_old' => $daysOld
        ]);

        return [
            'notifications' => $deletedNotifications,
            'deliveries' => $deletedDeliveries
        ];
    }

    /**
     * Реализация методов NotificationServiceInterface
     */
    
    /**
     * Отправить уведомление пользователю
     */
    public function send($user, string $type, array $data = []): void
    {
        // Маппинг типов уведомлений
        $typeMap = [
            'booking.created' => NotificationType::BOOKING_CREATED,
            'booking.confirmed' => NotificationType::BOOKING_CONFIRMED,
            'booking.cancelled' => NotificationType::BOOKING_CANCELLED,
            'booking.reminder_day_before' => NotificationType::BOOKING_REMINDER,
            'booking.reminder_2hours' => NotificationType::BOOKING_REMINDER,
            'booking.rescheduled' => NotificationType::BOOKING_RESCHEDULED,
            'booking.new_for_master' => NotificationType::BOOKING_CREATED,
            'booking.cancelled_for_master' => NotificationType::BOOKING_CANCELLED,
            'booking.rescheduled_for_master' => NotificationType::BOOKING_RESCHEDULED,
            'master.daily_schedule' => NotificationType::GENERAL,
        ];

        $notificationType = $typeMap[$type] ?? NotificationType::GENERAL;
        
        $this->createAndSend(
            $notificationType,
            $user,
            $data,
            [NotificationChannel::IN_APP, NotificationChannel::EMAIL]
        );
    }
    
    /**
     * Отправить уведомление о завершении бронирования
     */
    public function sendBookingCompleted(Booking $booking): void
    {
        $this->bookingCompleted($booking);
    }
    
    /**
     * Отправить запрос на отзыв
     */
    public function sendReviewRequest(Booking $booking): void
    {
        $this->createAndSend(
            NotificationType::REVIEW_REQUEST,
            $booking->user,
            ['booking' => $booking],
            [NotificationChannel::IN_APP, NotificationChannel::EMAIL]
        );
    }
    
    /**
     * Отправить SMS о завершении
     */
    public function sendCompletionSMS(Booking $booking): void
    {
        if ($booking->client_phone) {
            $this->createAndSend(
                NotificationType::BOOKING_COMPLETED,
                $booking->user,
                ['booking' => $booking],
                [NotificationChannel::SMS]
            );
        }
    }
}