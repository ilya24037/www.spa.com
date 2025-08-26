<?php

namespace App\Infrastructure\Notifications;

use App\Domain\User\Events\UserRegistered;
use App\Infrastructure\Services\EmailService;
use App\Infrastructure\Services\AnalyticsService;
use App\Domain\User\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Сервис отправки приветственных уведомлений и аналитики
 */
class WelcomeNotificationService
{
    private EmailService $emailService;
    private AnalyticsService $analyticsService;
    private UserRepository $userRepository;

    public function __construct(
        EmailService $emailService,
        AnalyticsService $analyticsService,
        UserRepository $userRepository
    ) {
        $this->emailService = $emailService;
        $this->analyticsService = $analyticsService;
        $this->userRepository = $userRepository;
    }

    /**
     * Отправить welcome email
     */
    public function sendWelcomeEmail($user, UserRegistered $event, array $bonusResult): void
    {
        try {
            $emailData = [
                'user_name' => $user->getProfile()?->name ?? 'Пользователь',
                'welcome_bonus' => $bonusResult['welcome_bonus'],
                'loyalty_points' => $bonusResult['loyalty_points'],
                'verification_url' => $this->generateEmailVerificationUrl($user),
                'app_download_links' => [
                    'ios' => config('app.download_links.ios'),
                    'android' => config('app.download_links.android'),
                ],
                'support_email' => config('mail.support_email'),
                'registration_source' => $event->registrationSource,
            ];

            $this->emailService->sendWelcomeEmail($user->email, $emailData);

            Log::info('Welcome email sent', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

        } catch (Exception $e) {
            Log::warning('Failed to send welcome email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отследить регистрацию в аналитике
     */
    public function trackUserRegistration($user, UserRegistered $event): void
    {
        try {
            $this->analyticsService->track('user_registered', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $event->role,
                'registration_source' => $event->registrationSource,
                'has_referral' => !empty($event->referralCode),
                'marketing_consent' => $event->userData['marketing_consent'] ?? false,
                'ip_address' => $event->registrationIP,
                'user_agent' => $event->userAgent,
                'registered_at' => $event->registeredAt->format('Y-m-d H:i:s'),
            ]);

            // Добавляем пользователя в соответствующую когорту
            $this->addToCohort($user->id, $event);

        } catch (Exception $e) {
            Log::warning('Failed to track user registration in analytics', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Создать запись о регистрации
     */
    public function createRegistrationRecord($user, UserRegistered $event, array $bonusResult): void
    {
        $this->userRepository->createRegistrationRecord([
            'user_id' => $user->id,
            'registration_source' => $event->registrationSource,
            'referral_code' => $event->referralCode,
            'ip_address' => $event->registrationIP,
            'user_agent' => $event->userAgent,
            'welcome_bonus_given' => $bonusResult['welcome_bonus'],
            'referral_bonus_given' => $bonusResult['referral_bonus'],
            'loyalty_points_given' => $bonusResult['loyalty_points'],
            'email_verification_sent' => true,
            'registered_at' => $event->registeredAt,
            'created_at' => now(),
        ]);
    }

    /**
     * Генерировать URL для подтверждения email
     */
    private function generateEmailVerificationUrl($user): string
    {
        return url()->signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);
    }

    /**
     * Добавить пользователя в когорту
     */
    private function addToCohort(int $userId, UserRegistered $event): void
    {
        $cohortName = 'registered_' . $event->registeredAt->format('Y-m');
        $this->analyticsService->addToCohort($userId, $cohortName);
    }
}