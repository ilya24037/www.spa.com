<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\DTOs\CreateNotificationDTO;
use App\Domain\Notification\Handlers\NotificationChannelHandler;
use App\Domain\Notification\Handlers\NotificationSendHandler;
use App\Domain\Notification\Handlers\NotificationTemplateHandler;
use App\Domain\Notification\Handlers\NotificationUserHandler;
use App\Domain\Notification\Handlers\NotificationMaintenanceHandler;
use App\Domain\Notification\Repositories\NotificationRepository;
use App\Enums\NotificationType;
use Carbon\Carbon;

/**
 * Упрощенный сервис уведомлений
 * Делегирует логику специализированным обработчикам
 */
class NotificationService
{
    protected NotificationChannelHandler $channelHandler;
    protected NotificationSendHandler $sendHandler;
    protected NotificationTemplateHandler $templateHandler;
    protected NotificationUserHandler $userHandler;
    protected NotificationMaintenanceHandler $maintenanceHandler;

    public function __construct(
        protected NotificationRepository $repository,
        protected TemplateService $templateService
    ) {
        $this->channelHandler = new NotificationChannelHandler();
        $this->sendHandler = new NotificationSendHandler($repository, $this->channelHandler);
        $this->templateHandler = new NotificationTemplateHandler($templateService, $this->sendHandler);
        $this->userHandler = new NotificationUserHandler($repository);
        $this->maintenanceHandler = new NotificationMaintenanceHandler($repository);
    }

    // === СОЗДАНИЕ И ОТПРАВКА ===

    /**
     * Создать уведомление
     */
    public function create(CreateNotificationDTO $dto): Notification
    {
        return $this->sendHandler->create($dto);
    }

    /**
     * Создать уведомление из шаблона
     */
    public function createFromTemplate(
        string $templateName,
        int $userId,
        array $variables = [],
        array $options = []
    ): Notification {
        return $this->templateHandler->createFromTemplate($templateName, $userId, $variables, $options);
    }

    /**
     * Отправить уведомление немедленно
     */
    public function send(Notification $notification): array
    {
        return $this->sendHandler->send($notification);
    }

    /**
     * Добавить уведомление в очередь
     */
    public function queue(Notification $notification, string $queue = 'notifications'): void
    {
        $this->sendHandler->queue($notification, $queue);
    }

    /**
     * Запланировать отправку уведомления
     */
    public function schedule(Notification $notification, Carbon $when): void
    {
        $this->sendHandler->schedule($notification, $when);
    }

    // === МАССОВЫЕ ОПЕРАЦИИ ===

    /**
     * Массовая отправка уведомлений
     */
    public function sendBatch(array $notificationIds, string $queue = 'notifications'): void
    {
        $this->sendHandler->sendBatch($notificationIds, $queue);
    }

    /**
     * Отправить уведомление группе пользователей
     */
    public function sendToUsers(
        array $userIds,
        NotificationType $type,
        array $data = [],
        array $options = []
    ): array {
        return $this->sendHandler->sendToUsers($userIds, $type, $data, $options);
    }

    /**
     * Отправить уведомление всем пользователям
     */
    public function broadcast(
        NotificationType $type,
        array $data = [],
        array $options = []
    ): void {
        $this->sendHandler->broadcast($type, $data, $options);
    }

    // === ПОЛЬЗОВАТЕЛЬСКИЕ ОПЕРАЦИИ ===

    /**
     * Получить уведомления для пользователя
     */
    public function getForUser(int $userId, array $options = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->userHandler->getForUser($userId, $options);
    }

    /**
     * Пометить уведомление как прочитанное
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        return $this->userHandler->markAsRead($notificationId, $userId);
    }

    /**
     * Пометить все уведомления пользователя как прочитанные
     */
    public function markAllAsRead(int $userId): int
    {
        return $this->userHandler->markAllAsRead($userId);
    }

    /**
     * Получить количество непрочитанных уведомлений
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->userHandler->getUnreadCount($userId);
    }

    /**
     * Удалить уведомление
     */
    public function delete(int $notificationId, int $userId): bool
    {
        return $this->userHandler->delete($notificationId, $userId);
    }

    // === НАСТРОЙКИ ПОЛЬЗОВАТЕЛЯ ===

    /**
     * Проверить настройки уведомлений пользователя
     */
    public function canSendToUser(int $userId, NotificationType $type, array $channels): bool
    {
        return $this->userHandler->canSendToUser($userId, $type, $channels);
    }

    /**
     * Обновить настройки уведомлений пользователя
     */
    public function updateUserPreferences(int $userId, array $preferences): bool
    {
        return $this->userHandler->updateUserPreferences($userId, $preferences);
    }

    /**
     * Получить настройки уведомлений пользователя
     */
    public function getUserPreferences(int $userId): array
    {
        return $this->userHandler->getUserPreferences($userId);
    }

    // === ОБСЛУЖИВАНИЕ И СТАТИСТИКА ===

    /**
     * Очистить старые уведомления
     */
    public function cleanup(): array
    {
        return $this->maintenanceHandler->cleanup();
    }

    /**
     * Получить статистику уведомлений
     */
    public function getStats(array $filters = []): array
    {
        return $this->maintenanceHandler->getStats($filters);
    }

    /**
     * Получить детализированную статистику
     */
    public function getDetailedStats(array $filters = []): array
    {
        return $this->maintenanceHandler->getDetailedStats($filters);
    }

    /**
     * Повторить отправку неудачных уведомлений
     */
    public function retryFailed(array $filters = []): int
    {
        return $this->sendHandler->retryFailed($filters);
    }

    // === КАНАЛЫ ===

    /**
     * Зарегистрировать канал уведомлений
     */
    public function registerChannel(\App\Domain\Notification\Channels\NotificationChannelInterface $channel): void
    {
        $this->channelHandler->registerChannel($channel);
    }

    /**
     * Получить зарегистрированные каналы
     */
    public function getRegisteredChannels(): array
    {
        return $this->channelHandler->getRegisteredChannels();
    }

    // === ШАБЛОНЫ ===

    /**
     * Предварительный просмотр шаблона
     */
    public function previewTemplate(
        string $templateName,
        array $variables = [],
        string $locale = 'ru'
    ): array {
        return $this->templateHandler->previewTemplate($templateName, $variables, $locale);
    }

    /**
     * Получить доступные шаблоны
     */
    public function getAvailableTemplates(int $userId, string $locale = 'ru'): array
    {
        return $this->templateHandler->getAvailableTemplates($userId, $locale);
    }
}