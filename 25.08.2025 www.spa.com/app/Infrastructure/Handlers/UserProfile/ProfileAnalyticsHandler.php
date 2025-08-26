<?php

namespace App\Infrastructure\Handlers\UserProfile;

use App\Domain\User\Events\UserProfileUpdated;
use App\Domain\User\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик аналитики и метрик профиля
 */
class ProfileAnalyticsHandler
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Анализировать изменения профиля
     */
    public function analyzeProfileChanges($user, array $updatedFields, array $oldData): array
    {
        $changes = [
            'critical' => [], // Критические изменения
            'important' => [], // Важные изменения
            'media' => [], // Медиа изменения
            'contact' => [], // Контактные данные
            'personal' => [], // Личная информация
        ];

        foreach ($updatedFields as $field) {
            $this->categorizeProfileChange($field, $changes, $user, $oldData);
        }

        return $changes;
    }

    /**
     * Категоризировать изменение профиля
     */
    public function categorizeProfileChange(string $field, array &$changes, $user, array $oldData): void
    {
        // Критические изменения (требуют верификации)
        $criticalFields = ['phone', 'email'];
        if (in_array($field, $criticalFields)) {
            $changes['critical'][] = $field;
            return;
        }

        // Важные изменения
        $importantFields = ['name', 'birth_date', 'city'];
        if (in_array($field, $importantFields)) {
            $changes['important'][] = $field;
            return;
        }

        // Медиа изменения
        if ($field === 'avatar_url') {
            $changes['media'][] = $field;
            return;
        }

        // Контактные данные
        $contactFields = ['address', 'telegram', 'whatsapp'];
        if (in_array($field, $contactFields)) {
            $changes['contact'][] = $field;
            return;
        }

        // Личная информация
        $personalFields = ['gender', 'about', 'interests', 'timezone', 'language'];
        if (in_array($field, $personalFields)) {
            $changes['personal'][] = $field;
            return;
        }
    }

    /**
     * Рассчитать процент заполнения профиля
     */
    public function calculateProfileCompletion($user): int
    {
        $profile = $user->getProfile();
        if (!$profile) {
            return 0;
        }

        $requiredFields = [
            'name' => 20,      // 20% за имя
            'phone' => 15,     // 15% за телефон
            'city' => 10,      // 10% за город
            'birth_date' => 10, // 10% за дату рождения
            'gender' => 5,     // 5% за пол
            'avatar_url' => 15, // 15% за аватар
            'about' => 10,     // 10% за описание
            'interests' => 5,  // 5% за интересы
        ];

        $score = 0;
        foreach ($requiredFields as $field => $points) {
            if (!empty($profile->$field)) {
                $score += $points;
            }
        }

        // Дополнительные баллы за верифицированные контакты
        if ($user->hasVerifiedEmail()) {
            $score += 5;
        }

        if ($profile->phone_verified_at) {
            $score += 5;
        }

        // Обновляем процент в профиле
        $profile->update(['completion_percentage' => min(100, $score)]);

        return min(100, $score);
    }

    /**
     * Логировать изменения профиля
     */
    public function logProfileChanges($user, UserProfileUpdated $event, array $changes): void
    {
        $this->userRepository->createProfileChangeLog([
            'user_id' => $user->id,
            'updated_fields' => $event->updatedFields,
            'old_data' => $event->oldData,
            'changes_summary' => $changes,
            'updated_at' => $event->updatedAt,
            'ip_address' => $event->ipAddress ?? null,
            'user_agent' => $event->userAgent ?? null,
        ]);
    }

    /**
     * Получить метрики изменений профиля
     */
    public function getProfileChangeMetrics($user): array
    {
        return [
            'total_changes' => $this->userRepository->getProfileChangesCount($user->id),
            'recent_changes' => $this->userRepository->getProfileChangesCount($user->id, 30),
            'critical_changes' => $this->userRepository->getCriticalChangesCount($user->id, 90),
            'completion_history' => $this->getCompletionHistory($user),
            'change_frequency' => $this->getChangeFrequency($user),
        ];
    }

    /**
     * Получить историю заполнения профиля
     */
    protected function getCompletionHistory($user): array
    {
        // Возвращаем историю изменения процента заполнения
        return $this->userRepository->getCompletionHistory($user->id, 6); // последние 6 месяцев
    }

    /**
     * Получить частоту изменений
     */
    protected function getChangeFrequency($user): string
    {
        $changes = $this->userRepository->getProfileChangesCount($user->id, 30);
        
        if ($changes >= 10) {
            return 'high';
        } elseif ($changes >= 3) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Определить риск мошенничества на основе паттернов изменений
     */
    public function assessFraudRisk($user, array $changes): string
    {
        $riskScore = 0;

        // Высокий риск: частые изменения критических полей
        $criticalChanges = $this->userRepository->getCriticalChangesCount($user->id, 7);
        if ($criticalChanges >= 3) {
            $riskScore += 30;
        }

        // Средний риск: изменение нескольких важных полей одновременно
        $importantFields = array_merge($changes['critical'], $changes['important']);
        if (count($importantFields) >= 3) {
            $riskScore += 20;
        }

        // Низкий риск: изменения в нерабочее время
        $hour = now()->hour;
        if ($hour < 6 || $hour > 23) {
            $riskScore += 10;
        }

        // Оценка нового аккаунта
        if ($user->created_at->diffInDays(now()) < 7) {
            $riskScore += 15;
        }

        if ($riskScore >= 50) {
            return 'high';
        } elseif ($riskScore >= 25) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Получить рекомендации по улучшению профиля
     */
    public function getProfileImprovementSuggestions($user): array
    {
        $profile = $user->getProfile();
        if (!$profile) {
            return ['create_profile' => 'Создайте профиль для начала работы'];
        }

        $suggestions = [];

        // Проверяем отсутствующие обязательные поля
        $requiredFields = ['name', 'phone', 'city', 'avatar_url', 'about'];
        foreach ($requiredFields as $field) {
            if (empty($profile->$field)) {
                $suggestions[] = "add_{$field}";
            }
        }

        // Проверяем верификацию
        if (!$user->hasVerifiedEmail()) {
            $suggestions[] = 'verify_email';
        }

        if (!$profile->phone_verified_at && $profile->phone) {
            $suggestions[] = 'verify_phone';
        }

        // Специальные предложения для мастеров
        if ($user->isMaster()) {
            $masterProfile = $user->getMasterProfile();
            if ($masterProfile) {
                if (empty($masterProfile->services)) {
                    $suggestions[] = 'add_services';
                }
                
                if (empty($masterProfile->gallery)) {
                    $suggestions[] = 'add_portfolio';
                }
            }
        }

        return $suggestions;
    }

    /**
     * Отследить конверсионные события
     */
    public function trackConversionEvents($user, array $changes): void
    {
        $completionPercentage = $this->calculateProfileCompletion($user);
        
        // Отслеживаем достижения в заполнении профиля
        $milestones = [25, 50, 75, 90, 100];
        
        foreach ($milestones as $milestone) {
            if ($completionPercentage >= $milestone) {
                $this->trackMilestone($user, "profile_completion_{$milestone}");
            }
        }

        // Отслеживаем первую верификацию
        if (in_array('email', $changes['critical']) && $user->hasVerifiedEmail()) {
            $this->trackMilestone($user, 'first_email_verification');
        }

        if (in_array('phone', $changes['critical'])) {
            $this->trackMilestone($user, 'phone_added');
        }
    }

    /**
     * Отследить достижение
     */
    protected function trackMilestone($user, string $event): void
    {
        Log::info('User milestone achieved', [
            'user_id' => $user->id,
            'event' => $event,
            'achieved_at' => now(),
        ]);

        // Здесь можно добавить отправку в аналитику
    }
}