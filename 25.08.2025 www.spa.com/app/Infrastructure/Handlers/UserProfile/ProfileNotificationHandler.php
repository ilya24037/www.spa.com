<?php

namespace App\Infrastructure\Handlers\UserProfile;

use App\Domain\User\Events\UserProfileUpdated;
use App\Infrastructure\Services\EmailService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик уведомлений об изменениях профиля
 */
class ProfileNotificationHandler
{
    public function __construct(
        private EmailService $emailService,
        private ProfileAnalyticsHandler $analyticsHandler
    ) {}

    /**
     * Отправить уведомления об обновлениях
     */
    public function sendUpdateNotifications($user, array $changes, UserProfileUpdated $event): void
    {
        try {
            // Уведомляем о критических изменениях
            if (!empty($changes['critical'])) {
                $this->sendCriticalChangesNotification($user, $changes, $event);
            }

            // Поздравляем с достижением высокого процента заполнения
            $this->sendCompletionCongratulations($user);

            // Отправляем рекомендации по улучшению профиля
            $this->sendImprovementSuggestions($user);

            // Уведомления о безопасности
            $this->sendSecurityNotifications($user, $changes, $event);

        } catch (Exception $e) {
            Log::warning('Failed to send profile update notifications', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомление о критических изменениях
     */
    protected function sendCriticalChangesNotification($user, array $changes, UserProfileUpdated $event): void
    {
        try {
            $this->emailService->sendCriticalProfileChangesNotification($user->email, [
                'user_name' => $user->getProfile()?->name ?? 'Пользователь',
                'changed_fields' => $changes['critical'],
                'changed_at' => $event->updatedAt->format('d.m.Y H:i'),
                'ip_address' => $event->ipAddress,
                'user_agent' => $event->userAgent,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send critical changes notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить поздравления с заполнением профиля
     */
    protected function sendCompletionCongratulations($user): void
    {
        $completionPercentage = $this->analyticsHandler->calculateProfileCompletion($user);
        
        // Отправляем поздравления только при достижении определенных уровней
        $milestones = [50, 75, 90, 100];
        
        foreach ($milestones as $milestone) {
            if ($completionPercentage >= $milestone && $this->shouldSendMilestoneCongratulations($user, $milestone)) {
                try {
                    $this->emailService->sendProfileCompletionCongratulations($user->email, [
                        'user_name' => $user->getProfile()?->name ?? 'Пользователь',
                        'completion_percentage' => $completionPercentage,
                        'milestone' => $milestone,
                        'benefits' => $this->getMilestoneBenefits($milestone),
                    ]);

                    // Отмечаем, что поздравление отправлено
                    $this->markMilestoneCongratulationSent($user, $milestone);

                } catch (Exception $e) {
                    Log::error('Failed to send completion congratulations', [
                        'user_id' => $user->id,
                        'milestone' => $milestone,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Отправить рекомендации по улучшению профиля
     */
    protected function sendImprovementSuggestions($user): void
    {
        // Отправляем рекомендации только раз в неделю и только если профиль заполнен менее чем на 70%
        $completionPercentage = $this->analyticsHandler->calculateProfileCompletion($user);
        
        if ($completionPercentage >= 70 || !$this->shouldSendImprovementSuggestions($user)) {
            return;
        }

        try {
            $suggestions = $this->analyticsHandler->getProfileImprovementSuggestions($user);
            
            if (!empty($suggestions)) {
                $this->emailService->sendProfileImprovementSuggestions($user->email, [
                    'user_name' => $user->getProfile()?->name ?? 'Пользователь',
                    'completion_percentage' => $completionPercentage,
                    'suggestions' => $suggestions,
                    'improvement_url' => route('profile.edit'),
                ]);

                // Отмечаем отправку рекомендаций
                $this->markImprovementSuggestionsSent($user);
            }

        } catch (Exception $e) {
            Log::error('Failed to send improvement suggestions', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомления о безопасности
     */
    protected function sendSecurityNotifications($user, array $changes, UserProfileUpdated $event): void
    {
        $riskLevel = $this->analyticsHandler->assessFraudRisk($user, $changes);
        
        if ($riskLevel === 'high') {
            try {
                $this->emailService->sendSecurityAlert($user->email, [
                    'user_name' => $user->getProfile()?->name ?? 'Пользователь',
                    'suspicious_changes' => array_merge($changes['critical'], $changes['important']),
                    'changed_at' => $event->updatedAt->format('d.m.Y H:i'),
                    'ip_address' => $event->ipAddress,
                    'security_url' => route('security.settings'),
                ]);

            } catch (Exception $e) {
                Log::error('Failed to send security alert', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Отправить уведомления администраторам
     */
    public function sendAdminNotifications($user, array $changes, UserProfileUpdated $event): void
    {
        // Уведомляем администраторов о подозрительной активности
        $riskLevel = $this->analyticsHandler->assessFraudRisk($user, $changes);
        
        if ($riskLevel === 'high') {
            try {
                $this->emailService->sendAdminSecurityAlert(config('app.admin_email'), [
                    'user_id' => $user->id,
                    'user_name' => $user->getProfile()?->name ?? 'Неизвестный',
                    'user_email' => $user->email,
                    'risk_level' => $riskLevel,
                    'suspicious_changes' => array_merge($changes['critical'], $changes['important']),
                    'changed_at' => $event->updatedAt->format('d.m.Y H:i'),
                    'ip_address' => $event->ipAddress,
                    'user_agent' => $event->userAgent,
                ]);

            } catch (Exception $e) {
                Log::error('Failed to send admin security alert', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Проверить, нужно ли отправлять поздравления с достижением
     */
    protected function shouldSendMilestoneCongratulations($user, int $milestone): bool
    {
        // Проверяем, не отправляли ли уже поздравления с этим достижением
        return !$this->wasMilestoneCongratulationSent($user, $milestone);
    }

    /**
     * Проверить, нужно ли отправлять рекомендации по улучшению
     */
    protected function shouldSendImprovementSuggestions($user): bool
    {
        // Отправляем не чаще раза в неделю
        $lastSent = $this->getLastImprovementSuggestionDate($user);
        
        return !$lastSent || $lastSent->diffInDays(now()) >= 7;
    }

    /**
     * Получить преимущества достижения уровня заполнения
     */
    protected function getMilestoneBenefits(int $milestone): array
    {
        $benefits = [
            50 => ['Базовый поиск', 'Возможность бронирования'],
            75 => ['Расширенный поиск', 'Приоритет в результатах', 'Скидки на услуги'],
            90 => ['VIP статус', 'Персональные рекомендации', 'Эксклюзивные предложения'],
            100 => ['Максимальная видимость', 'Премиум поддержка', 'Бонусные баллы'],
        ];

        return $benefits[$milestone] ?? [];
    }

    /**
     * Проверить, отправлялись ли поздравления с достижением
     */
    protected function wasMilestoneCongratulationSent($user, int $milestone): bool
    {
        // Здесь должна быть проверка в базе данных
        return false; // Заглушка
    }

    /**
     * Отметить отправку поздравлений с достижением
     */
    protected function markMilestoneCongratulationSent($user, int $milestone): void
    {
        // Здесь должна быть запись в базу данных
        Log::info('Milestone congratulation sent', [
            'user_id' => $user->id,
            'milestone' => $milestone,
        ]);
    }

    /**
     * Отметить отправку рекомендаций по улучшению
     */
    protected function markImprovementSuggestionsSent($user): void
    {
        // Здесь должна быть запись в базу данных
        Log::info('Improvement suggestions sent', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Получить дату последней отправки рекомендаций
     */
    protected function getLastImprovementSuggestionDate($user): ?\Carbon\Carbon
    {
        // Здесь должен быть запрос к базе данных
        return null; // Заглушка
    }
}