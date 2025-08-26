<?php

namespace App\Infrastructure\Handlers\UserProfile;

use App\Domain\User\Repositories\UserRepository;
use App\Infrastructure\Services\EmailService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик валидации изменений профиля
 */
class ProfileValidationHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private EmailService $emailService
    ) {}

    /**
     * Валидировать критические изменения
     */
    public function validateCriticalChanges($user, array $changes): void
    {
        if (empty($changes['critical'])) {
            return;
        }

        foreach ($changes['critical'] as $field) {
            switch ($field) {
                case 'phone':
                    $this->validatePhoneChange($user);
                    break;
                case 'email':
                    $this->validateEmailChange($user);
                    break;
            }
        }
    }

    /**
     * Валидировать изменение телефона
     */
    public function validatePhoneChange($user): void
    {
        $profile = $user->getProfile();
        if (!$profile || !$profile->phone) {
            return;
        }

        // Проверяем лимит изменений телефона (максимум 3 раза в месяц)
        $monthlyChanges = $this->userRepository->getPhoneChangesCount($user->id, 30);
        if ($monthlyChanges >= 3) {
            throw new Exception("Превышен лимит изменений номера телефона в месяц");
        }

        // Проверяем, что телефон не используется другим пользователем
        $existingUser = $this->userRepository->findByPhone($profile->phone);
        if ($existingUser && $existingUser->id !== $user->id) {
            throw new Exception("Номер телефона уже используется другим пользователем");
        }

        // Отправляем SMS верификацию
        $this->sendPhoneVerification($user, $profile->phone);
    }

    /**
     * Валидировать изменение email
     */
    public function validateEmailChange($user): void
    {
        // Проверяем лимит изменений email (максимум 2 раза в месяц)
        $monthlyChanges = $this->userRepository->getEmailChangesCount($user->id, 30);
        if ($monthlyChanges >= 2) {
            throw new Exception("Превышен лимит изменений email в месяц");
        }

        // Проверяем, что email не используется другим пользователем
        $existingUser = $this->userRepository->findByEmail($user->email);
        if ($existingUser && $existingUser->id !== $user->id) {
            throw new Exception("Email уже используется другим пользователем");
        }

        // Сбрасываем верификацию email
        $user->update(['email_verified_at' => null]);

        // Отправляем новое письмо верификации
        $this->sendEmailVerification($user);
    }

    /**
     * Отправить SMS верификацию телефона
     */
    public function sendPhoneVerification($user, string $phone): void
    {
        try {
            // Генерируем код верификации
            $verificationCode = rand(100000, 999999);
            
            // Сохраняем код в базе
            $this->userRepository->savePhoneVerificationCode($user->id, $phone, $verificationCode);
            
            // Отправляем SMS (здесь должна быть интеграция с SMS-сервисом)
            Log::info('Phone verification code generated', [
                'user_id' => $user->id,
                'phone' => $phone,
                'code' => $verificationCode, // В продакшене не логируем код
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send phone verification', [
                'user_id' => $user->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить email верификацию
     */
    public function sendEmailVerification($user): void
    {
        try {
            $this->emailService->sendEmailVerification($user->email, [
                'user_name' => $user->getProfile()?->name ?? 'Пользователь',
                'verification_url' => $this->generateEmailVerificationUrl($user),
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send email verification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Генерировать URL верификации email
     */
    private function generateEmailVerificationUrl($user): string
    {
        return url()->signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);
    }

    /**
     * Проверить ограничения на изменения
     */
    public function checkChangeRateLimits($user, array $changes): array
    {
        $violations = [];

        // Проверяем лимиты для критических полей
        if (in_array('phone', $changes['critical'])) {
            $phoneChanges = $this->userRepository->getPhoneChangesCount($user->id, 30);
            if ($phoneChanges >= 3) {
                $violations[] = 'phone_limit_exceeded';
            }
        }

        if (in_array('email', $changes['critical'])) {
            $emailChanges = $this->userRepository->getEmailChangesCount($user->id, 30);
            if ($emailChanges >= 2) {
                $violations[] = 'email_limit_exceeded';
            }
        }

        return $violations;
    }

    /**
     * Валидировать формат данных
     */
    public function validateDataFormats($user, array $changes): array
    {
        $errors = [];

        $profile = $user->getProfile();
        if (!$profile) {
            return $errors;
        }

        // Валидация телефона
        if (in_array('phone', array_merge($changes['critical'], $changes['contact'])) && $profile->phone) {
            if (!preg_match('/^\+?[1-9]\d{1,14}$/', $profile->phone)) {
                $errors[] = 'invalid_phone_format';
            }
        }

        // Валидация email
        if (in_array('email', $changes['critical']) && $user->email) {
            if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'invalid_email_format';
            }
        }

        return $errors;
    }
}