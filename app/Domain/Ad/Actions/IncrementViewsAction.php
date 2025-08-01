<?php

namespace App\Domain\Ad\Actions;

use App\Domain\Ad\Repositories\AdRepository;
use App\Enums\AdStatus;
use Illuminate\Support\Facades\Cache;

/**
 * Action для увеличения счетчика просмотров объявления
 */
class IncrementViewsAction
{
    private AdRepository $adRepository;

    public function __construct(AdRepository $adRepository)
    {
        $this->adRepository = $adRepository;
    }

    /**
     * Увеличить счетчик просмотров
     */
    public function execute(int $adId, ?string $userIp = null): bool
    {
        try {
            $ad = $this->adRepository->find($adId);
            
            if (!$ad || $ad->status !== AdStatus::ACTIVE) {
                return false;
            }

            // Проверяем, был ли уже просмотр с этого IP
            if ($userIp) {
                $cacheKey = "ad_view_{$adId}_{$userIp}";
                
                if (Cache::has($cacheKey)) {
                    return false;
                }
                
                // Запоминаем на 1 час
                Cache::put($cacheKey, true, 3600);
            }

            // Увеличиваем счетчик
            $this->adRepository->incrementViews($ad);

            return true;
        } catch (\Exception $e) {
            // Логируем ошибку, но не прерываем работу
            report($e);
            return false;
        }
    }
}