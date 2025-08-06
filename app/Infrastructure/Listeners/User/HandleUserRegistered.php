<?php

namespace App\Infrastructure\Listeners\User;

use App\Domain\User\Events\UserRegistered;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\Services\UserProfileCreator;
use App\Domain\User\Services\WelcomeBonusService;
use App\Infrastructure\Notifications\WelcomeNotificationService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Координатор обработки регистрации пользователя
 */
class HandleUserRegistered
{
    private UserRepository $userRepository;
    private UserProfileCreator $profileCreator;
    private WelcomeBonusService $bonusService;
    private WelcomeNotificationService $notificationService;

    public function __construct(
        UserRepository $userRepository,
        UserProfileCreator $profileCreator,
        WelcomeBonusService $bonusService,
        WelcomeNotificationService $notificationService
    ) {
        $this->userRepository = $userRepository;
        $this->profileCreator = $profileCreator;
        $this->bonusService = $bonusService;
        $this->notificationService = $notificationService;
    }

    /**
     * Обработка события UserRegistered
     */
    public function handle(UserRegistered $event): void
    {
        DB::transaction(function () use ($event) {
            try {
                $user = $this->getUserOrFail($event->userId);

                // Создание профиля и настроек
                $this->profileCreator->create($user, $event);
                $this->profileCreator->createSettings($user, $event);
                $this->profileCreator->setupNotificationPreferences($user, $event);

                // Начисление бонусов
                $bonusResult = $this->bonusService->addWelcomeBonuses($user, $event);

                // Уведомления и аналитика
                $this->notificationService->sendWelcomeEmail($user, $event, $bonusResult);
                $this->notificationService->trackUserRegistration($user, $event);
                $this->notificationService->createRegistrationRecord($user, $event, $bonusResult);

                $this->logSuccessfulRegistration($event, $bonusResult);

            } catch (Exception $e) {
                $this->logFailedRegistration($event, $e);
                throw $e;
            }
        });
    }




    /**
     * Получить пользователя или выбросить исключение
     */
    private function getUserOrFail(int $userId)
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new Exception("Пользователь с ID {$userId} не найден");
        }
        return $user;
    }

    /**
     * Логировать успешную регистрацию
     */
    private function logSuccessfulRegistration(UserRegistered $event, array $bonusResult): void
    {
        Log::info('User registration processed successfully', [
            'user_id' => $event->userId,
            'email' => $event->email,
            'role' => $event->role,
            'registration_source' => $event->registrationSource,
            'welcome_bonus' => $bonusResult['total_bonus'] ?? 0,
        ]);
    }

    /**
     * Логировать неудачную регистрацию
     */
    private function logFailedRegistration(UserRegistered $event, Exception $e): void
    {
        Log::error('Failed to handle UserRegistered event', [
            'user_id' => $event->userId,
            'email' => $event->email,
            'error' => $e->getMessage(),
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