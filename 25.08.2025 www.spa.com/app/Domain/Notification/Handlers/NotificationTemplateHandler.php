<?php

namespace App\Domain\Notification\Handlers;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\DTOs\CreateNotificationDTO;
use App\Domain\Notification\Services\TemplateService;
use App\Domain\User\Models\User;

/**
 * Обработчик шаблонов уведомлений
 */
class NotificationTemplateHandler
{
    public function __construct(
        protected TemplateService $templateService,
        protected NotificationSendHandler $sendHandler
    ) {}

    /**
     * Создать уведомление из шаблона
     */
    public function createFromTemplate(
        string $templateName,
        int $userId,
        array $variables = [],
        array $options = []
    ): Notification {
        $user = User::findOrFail($userId);
        $template = $this->templateService->getTemplate($templateName, $user->locale ?? 'ru');

        if (!$template) {
            throw new \Exception("Template not found: {$templateName}");
        }

        // Рендерить шаблон
        $rendered = $template->render($variables);

        // Создать DTO
        $dto = new CreateNotificationDTO([
            'userId' => $userId,
            'type' => $template->type,
            'title' => $rendered['title'],
            'message' => $rendered['content'],
            'data' => array_merge($variables, $options['data'] ?? []),
            'channels' => $options['channels'] ?? $template->channels,
            'priority' => $options['priority'] ?? $template->priority,
            'template' => $templateName,
            'locale' => $user->locale ?? 'ru',
            'scheduledAt' => $options['scheduled_at'] ?? null,
            'expiresAt' => $options['expires_at'] ?? null,
            'groupKey' => $options['group_key'] ?? null,
            'metadata' => $options['metadata'] ?? [],
        ]);

        return $this->sendHandler->create($dto);
    }

    /**
     * Создать массовые уведомления из шаблона
     */
    public function createBatchFromTemplate(
        string $templateName,
        array $userIds,
        array $variables = [],
        array $options = []
    ): array {
        $notifications = [];

        foreach ($userIds as $userId) {
            try {
                $notifications[] = $this->createFromTemplate(
                    $templateName,
                    $userId,
                    $variables,
                    $options
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to create notification from template', [
                    'template' => $templateName,
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $notifications;
    }

    /**
     * Получить доступные шаблоны для пользователя
     */
    public function getAvailableTemplates(int $userId, string $locale = 'ru'): array
    {
        return $this->templateService->getAvailableTemplates($locale);
    }

    /**
     * Предварительный просмотр шаблона
     */
    public function previewTemplate(
        string $templateName,
        array $variables = [],
        string $locale = 'ru'
    ): array {
        $template = $this->templateService->getTemplate($templateName, $locale);

        if (!$template) {
            throw new \Exception("Template not found: {$templateName}");
        }

        return $template->render($variables);
    }

    /**
     * Валидировать переменные шаблона
     */
    public function validateTemplateVariables(
        string $templateName,
        array $variables = [],
        string $locale = 'ru'
    ): array {
        $template = $this->templateService->getTemplate($templateName, $locale);

        if (!$template) {
            throw new \Exception("Template not found: {$templateName}");
        }

        return $template->validateVariables($variables);
    }
}