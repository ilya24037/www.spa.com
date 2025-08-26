<?php

namespace App\Domain\Master\Actions;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Repositories\MasterRepository;
use Illuminate\Support\Facades\Log;

/**
 * Action для управления премиум статусом мастера
 */
class TogglePremiumAction
{
    private MasterRepository $masterRepository;

    public function __construct(MasterRepository $masterRepository)
    {
        $this->masterRepository = $masterRepository;
    }

    /**
     * Активировать премиум статус
     */
    public function activate(int $masterId, int $days = 30): array
    {
        try {
            $master = $this->masterRepository->findById($masterId);
            
            if (!$master) {
                return [
                    'success' => false,
                    'message' => 'Профиль мастера не найден',
                ];
            }

            // Проверяем активный премиум
            if ($master->isPremium()) {
                // Продлеваем существующий
                $master->premium_until = $master->premium_until->addDays($days);
            } else {
                // Активируем новый
                $master->is_premium = true;
                $master->premium_until = now()->addDays($days);
            }
            
            $master->save();

            Log::info('Premium activated', [
                'master_id' => $master->id,
                'days' => $days,
                'until' => $master->premium_until,
            ]);

            return [
                'success' => true,
                'message' => "Премиум активирован на {$days} дней",
                'master' => $master,
                'premium_until' => $master->premium_until,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to activate premium', [
                'master_id' => $masterId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при активации премиума',
            ];
        }
    }

    /**
     * Деактивировать премиум статус
     */
    public function deactivate(int $masterId): array
    {
        try {
            $master = $this->masterRepository->findById($masterId);
            
            if (!$master) {
                return [
                    'success' => false,
                    'message' => 'Профиль мастера не найден',
                ];
            }

            if (!$master->is_premium) {
                return [
                    'success' => false,
                    'message' => 'Премиум уже не активен',
                ];
            }

            $master->is_premium = false;
            $master->premium_until = null;
            $master->save();

            Log::info('Premium deactivated', [
                'master_id' => $master->id,
            ]);

            return [
                'success' => true,
                'message' => 'Премиум деактивирован',
                'master' => $master,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to deactivate premium', [
                'master_id' => $masterId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при деактивации премиума',
            ];
        }
    }

    /**
     * Проверить и деактивировать истекшие премиумы
     */
    public function checkExpired(): int
    {
        try {
            $expired = MasterProfile::where('is_premium', true)
                ->where('premium_until', '<', now())
                ->get();

            $count = 0;
            foreach ($expired as $master) {
                $master->is_premium = false;
                $master->save();
                $count++;
                
                Log::info('Premium expired', [
                    'master_id' => $master->id,
                    'expired_at' => $master->premium_until,
                ]);
            }

            return $count;
        } catch (\Exception $e) {
            Log::error('Failed to check expired premiums', [
                'error' => $e->getMessage(),
            ]);
            
            return 0;
        }
    }
}