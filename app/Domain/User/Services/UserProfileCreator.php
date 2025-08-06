<?php

namespace App\Domain\User\Services;

use App\Domain\User\Events\UserRegistered;
use App\Domain\User\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;

/**
 * Сервис создания профиля пользователя
 */
class UserProfileCreator
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Создать профиль пользователя
     */
    public function create($user, UserRegistered $event): void
    {
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
     * Создать настройки пользователя
     */
    public function createSettings($user, UserRegistered $event): void
    {
        $settingsData = [
            'user_id' => $user->id,
            'email_notifications' => true,
            'sms_notifications' => !empty($event->userData['phone']),
            'push_notifications' => true,
            'marketing_emails' => $event->userData['marketing_consent'] ?? false,
            'privacy_level' => 'normal',
            'search_visibility' => true,
            'two_factor_enabled' => false,
            'session_timeout' => 7200,
            'theme' => 'auto',
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
     * Настроить предпочтения уведомлений
     */
    public function setupNotificationPreferences($user, UserRegistered $event): void
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
            'reminder_hours_before' => 2,
            'created_at' => now(),
        ];

        $this->userRepository->createNotificationPreferences($preferences);
    }

    /**
     * Извлечь имя из email
     */
    private function extractNameFromEmail(string $email): string
    {
        $localPart = explode('@', $email)[0];
        $name = preg_replace('/[^a-zA-Zа-яёА-ЯЁ]/', ' ', $localPart);
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
            return 'Москва';
        } catch (\Exception $e) {
            return 'Москва';
        }
    }
}