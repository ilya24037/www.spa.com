<?php

namespace App\Infrastructure\Listeners\User;

use App\Domain\User\Events\UserRegistered;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\Services\UserService;
use App\Infrastructure\Services\EmailService;
use App\Infrastructure\Services\AnalyticsService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик регистрации пользователя
 * 
 * ФУНКЦИИ:
 * - Создание пользовательского профиля
 * - Отправка welcome email
 * - Настройка базовых настроек
 * - Добавление в аналитику
 * - Создание начального баланса/бонусов
 */
class HandleUserRegistered
{
    private UserRepository $userRepository;
    private UserService $userService;
    private EmailService $emailService;
    private AnalyticsService $analyticsService;

    public function __construct(
        UserRepository $userRepository,
        UserService $userService,
        EmailService $emailService,
        AnalyticsService $analyticsService
    ) {
        $this->userRepository = $userRepository;
        $this->userService = $userService;
        $this->emailService = $emailService;
        $this->analyticsService = $analyticsService;
    }

    /**
     * Обработка события UserRegistered
     */
    public function handle(UserRegistered $event): void
    {
        DB::transaction(function () use ($event) {
            try {
                // 1. Получаем пользователя
                $user = $this->userRepository->findById($event->userId);
                if (!$user) {
                    throw new Exception("Пользователь с ID {$event->userId} не найден");
                }

                // 2. Создаем базовый профиль
                $this->createUserProfile($user, $event);

                // 3. Создаем базовые настройки
                $this->createUserSettings($user, $event);

                // 4. Добавляем приветственные бонусы
                $bonusResult = $this->addWelcomeBonuses($user, $event);

                // 5. Настраиваем уведомления
                $this->setupNotificationPreferences($user, $event);

                // 6. Отправляем welcome email
                $this->sendWelcomeEmail($user, $event, $bonusResult);

                // 7. Добавляем в аналитику
                $this->trackUserRegistration($user, $event);

                // 8. Создаем запись о регистрации
                $this->createRegistrationRecord($user, $event, $bonusResult);

                Log::info('User registration processed successfully', [
                    'user_id' => $event->userId,
                    'email' => $event->email,
                    'role' => $event->role,
                    'registration_source' => $event->registrationSource,
                    'welcome_bonus' => $bonusResult['total_bonus'] ?? 0,
                ]);

            } catch (Exception $e) {
                Log::error('Failed to handle UserRegistered event', [
                    'user_id' => $event->userId,
                    'email' => $event->email,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Создать профиль пользователя
     */
    private function createUserProfile($user, UserRegistered $event): void
    {
        // Извлекаем имя из email если не указано
        $name = $event->userData['name'] ?? $this->extractNameFromEmail($event->email);

        $profileData = [
            'user_id' => $user->id,
            'name' => $name,
            'phone' => $event->userData['phone'] ?? null,
            'city' => $event->userData['city'] ?? $this->detectCityFromIP($event->registrationIP),
            'avatar_url' => null,
            'birth_date' => $event->userData['birth_date'] ?? null,
            'gender' => $event->userData['gender'] ?? null,
            'timezone' => $event->userData['timezone'] ?? config('app.timezone'),
            'language' => $event->userData['language'] ?? 'ru',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $this->userRepository->createProfile($profileData);

        Log::info('User profile created', [
            'user_id' => $user->id,
            'name' => $name,
            'city' => $profileData['city'],
        ]);
    }

    /**
     * Извлечь имя из email
     */
    private function extractNameFromEmail(string $email): string
    {
        $localPart = explode('@', $email)[0];
        
        // Убираем цифры и специальные символы
        $name = preg_replace('/[^a-zA-Zа-яёА-ЯЁ]/', ' ', $localPart);
        
        // Приводим к нормальному виду
        return ucwords(trim($name)) ?: 'Пользователь';
    }

    /**
     * Определить город по IP
     */
    private function detectCityFromIP(?string $ip): string
    {
        if (!$ip) {
            return 'Москва';
        }

        try {
            // Здесь можно использовать GeoIP сервис
            // Пока возвращаем дефолтный город
            return 'Москва';
        } catch (Exception $e) {
            return 'Москва';
        }
    }

    /**
     * Создать настройки пользователя
     */
    private function createUserSettings($user, UserRegistered $event): void
    {
        $settingsData = [
            'user_id' => $user->id,
            'email_notifications' => true,
            'sms_notifications' => !empty($event->userData['phone']),
            'push_notifications' => true,
            'marketing_emails' => $event->userData['marketing_consent'] ?? false,
            'privacy_level' => 'normal', // private, normal, public
            'search_visibility' => true,
            'two_factor_enabled' => false,
            'session_timeout' => 7200, // 2 hours
            'theme' => 'auto', // light, dark, auto
            'created_at' => now(),
        ];

        $this->userRepository->createSettings($settingsData);

        Log::info('User settings created', [
            'user_id' => $user->id,
            'email_notifications' => $settingsData['email_notifications'],
            'marketing_consent' => $settingsData['marketing_emails'],
        ]);
    }

    /**
     * Добавить приветственные бонусы
     */
    private function addWelcomeBonuses($user, UserRegistered $event): array
    {
        $bonusResult = [
            'welcome_bonus' => 0,
            'referral_bonus' => 0,
            'total_bonus' => 0,
            'loyalty_points' => 0,
        ];

        try {
            // Базовый приветственный бонус
            $welcomeBonus = config('bonuses.welcome_bonus', 500); // 500 рублей
            if ($welcomeBonus > 0) {
                $this->userRepository->addBalance($user->id, $welcomeBonus, 'welcome_bonus');
                $bonusResult['welcome_bonus'] = $welcomeBonus;
            }

            // Стартовые баллы лояльности
            $loyaltyPoints = config('bonuses.welcome_loyalty_points', 100);
            if ($loyaltyPoints > 0) {
                $this->userRepository->addLoyaltyPoints($user->id, $loyaltyPoints);
                $bonusResult['loyalty_points'] = $loyaltyPoints;
            }

            // Реферальный бонус если есть
            if ($event->referralCode) {
                $referralBonus = $this->processReferralBonus($user, $event->referralCode);
                $bonusResult['referral_bonus'] = $referralBonus;
            }

            $bonusResult['total_bonus'] = $bonusResult['welcome_bonus'] + $bonusResult['referral_bonus'];

            Log::info('Welcome bonuses added', [
                'user_id' => $user->id,
                'welcome_bonus' => $bonusResult['welcome_bonus'],
                'referral_bonus' => $bonusResult['referral_bonus'],
                'loyalty_points' => $bonusResult['loyalty_points'],
            ]);

        } catch (Exception $e) {
            Log::warning('Failed to add welcome bonuses', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $bonusResult;
    }

    /**
     * Обработать реферальный бонус
     */
    private function processReferralBonus($user, string $referralCode): float
    {
        try {
            // Находим пользователя по реферальному коду
            $referrer = $this->userRepository->findByReferralCode($referralCode);
            if (!$referrer) {
                Log::warning('Invalid referral code used', [
                    'user_id' => $user->id,
                    'referral_code' => $referralCode,
                ]);
                return 0;
            }

            $referralBonus = config('bonuses.referral_bonus', 300); // 300 рублей
            $referrerBonus = config('bonuses.referrer_bonus', 200); // 200 рублей рефереру

            // Добавляем бонус новому пользователю
            $this->userRepository->addBalance($user->id, $referralBonus, 'referral_bonus');

            // Добавляем бонус рефереру
            $this->userRepository->addBalance($referrer->id, $referrerBonus, 'referrer_bonus');

            // Создаем запись о реферале
            $this->userRepository->createReferralRecord([
                'referrer_id' => $referrer->id,
                'referred_id' => $user->id,
                'referral_code' => $referralCode,
                'bonus_amount' => $referralBonus,
                'referrer_bonus' => $referrerBonus,
                'created_at' => now(),
            ]);

            Log::info('Referral bonus processed', [
                'referrer_id' => $referrer->id,
                'referred_id' => $user->id,
                'referral_bonus' => $referralBonus,
                'referrer_bonus' => $referrerBonus,
            ]);

            return $referralBonus;

        } catch (Exception $e) {
            Log::error('Failed to process referral bonus', [
                'user_id' => $user->id,
                'referral_code' => $referralCode,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Настроить предпочтения уведомлений
     */
    private function setupNotificationPreferences($user, UserRegistered $event): void
    {
        $preferences = [
            'user_id' => $user->id,
            'booking_confirmations' => true,
            'booking_reminders' => true,
            'booking_changes' => true,
            'payment_notifications' => true,
            'promotional_offers' => $event->userData['marketing_consent'] ?? false,
            'master_updates' => true,
            'platform_news' => false,
            'reminder_hours_before' => 2, // напоминать за 2 часа
            'created_at' => now(),
        ];

        $this->userRepository->createNotificationPreferences($preferences);
    }

    /**
     * Отправить welcome email
     */
    private function sendWelcomeEmail($user, UserRegistered $event, array $bonusResult): void
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
     * Генерировать URL для подтверждения email
     */
    private function generateEmailVerificationUrl($user): string
    {
        // Генерируем signed URL для подтверждения email
        return url()->signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);
    }

    /**
     * Отследить регистрацию в аналитике
     */
    private function trackUserRegistration($user, UserRegistered $event): void
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
            $this->analyticsService->addToCohort($user->id, 'registered_' . $event->registeredAt->format('Y-m'));

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
    private function createRegistrationRecord($user, UserRegistered $event, array $bonusResult): void
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
     * Регистрация обработчика событий
     */
    public static function register(Dispatcher $events): void
    {
        $events->listen(UserRegistered::class, [self::class, 'handle']);
    }
}