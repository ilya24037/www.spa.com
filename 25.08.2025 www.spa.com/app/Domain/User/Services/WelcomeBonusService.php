<?php

namespace App\Domain\User\Services;

use App\Domain\User\Events\UserRegistered;
use App\Domain\User\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Сервис начисления приветственных бонусов
 */
class WelcomeBonusService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Добавить приветственные бонусы
     */
    public function addWelcomeBonuses($user, UserRegistered $event): array
    {
        $bonusResult = [
            'welcome_bonus' => 0,
            'referral_bonus' => 0,
            'total_bonus' => 0,
            'loyalty_points' => 0,
        ];

        try {
            // Базовый приветственный бонус
            $bonusResult['welcome_bonus'] = $this->addWelcomeBonus($user);
            
            // Стартовые баллы лояльности
            $bonusResult['loyalty_points'] = $this->addLoyaltyPoints($user);

            // Реферальный бонус если есть
            if ($event->referralCode) {
                $bonusResult['referral_bonus'] = $this->processReferralBonus($user, $event->referralCode);
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
     * Добавить приветственный бонус
     */
    public function addWelcomeBonus($user): float
    {
        $welcomeBonus = config('bonuses.welcome_bonus', 500);
        
        if ($welcomeBonus > 0) {
            $this->userRepository->addBalance($user->id, $welcomeBonus, 'welcome_bonus');
            return $welcomeBonus;
        }
        
        return 0;
    }

    /**
     * Добавить баллы лояльности
     */
    public function addLoyaltyPoints($user): int
    {
        $loyaltyPoints = config('bonuses.welcome_loyalty_points', 100);
        
        if ($loyaltyPoints > 0) {
            $this->userRepository->addLoyaltyPoints($user->id, $loyaltyPoints);
            return $loyaltyPoints;
        }
        
        return 0;
    }

    /**
     * Обработать реферальный бонус
     */
    public function processReferralBonus($user, string $referralCode): float
    {
        try {
            $referrer = $this->userRepository->findByReferralCode($referralCode);
            if (!$referrer) {
                Log::warning('Invalid referral code used', [
                    'user_id' => $user->id,
                    'referral_code' => $referralCode,
                ]);
                return 0;
            }

            $referralBonus = config('bonuses.referral_bonus', 300);
            $referrerBonus = config('bonuses.referrer_bonus', 200);

            // Добавляем бонус новому пользователю
            $this->userRepository->addBalance($user->id, $referralBonus, 'referral_bonus');

            // Добавляем бонус рефереру
            $this->userRepository->addBalance($referrer->id, $referrerBonus, 'referrer_bonus');

            // Создаем запись о реферале
            $this->createReferralRecord($user, $referrer, $referralCode, $referralBonus, $referrerBonus);

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
     * Создать запись о реферале
     */
    private function createReferralRecord($user, $referrer, string $referralCode, float $referralBonus, float $referrerBonus): void
    {
        $this->userRepository->createReferralRecord([
            'referrer_id' => $referrer->id,
            'referred_id' => $user->id,
            'referral_code' => $referralCode,
            'bonus_amount' => $referralBonus,
            'referrer_bonus' => $referrerBonus,
            'created_at' => now(),
        ]);
    }
}