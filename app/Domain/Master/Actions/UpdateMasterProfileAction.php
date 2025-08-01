<?php

namespace App\Domain\Master\Actions;

use App\Domain\Master\DTOs\MasterData;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Repositories\MasterRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для обновления профиля мастера
 */
class UpdateMasterProfileAction
{
    private MasterRepository $masterRepository;

    public function __construct(MasterRepository $masterRepository)
    {
        $this->masterRepository = $masterRepository;
    }

    /**
     * Обновить профиль мастера
     */
    public function execute(int $masterId, MasterData $masterData): array
    {
        try {
            return DB::transaction(function () use ($masterId, $masterData) {
                $master = $this->masterRepository->findById($masterId);
                
                if (!$master) {
                    return [
                        'success' => false,
                        'message' => 'Профиль мастера не найден',
                    ];
                }

                // Обновляем основные данные
                $updated = $this->masterRepository->update($master, $masterData->toArray());

                if (!$updated) {
                    return [
                        'success' => false,
                        'message' => 'Не удалось обновить профиль',
                    ];
                }

                // Обновляем услуги если переданы
                if ($masterData->services !== null) {
                    $this->updateServices($master, $masterData->services);
                }

                // Обновляем уровень мастера
                $this->masterRepository->updateLevel($master);

                Log::info('Master profile updated', [
                    'master_id' => $master->id,
                    'updated_fields' => array_keys($masterData->toArray()),
                ]);

                return [
                    'success' => true,
                    'message' => 'Профиль успешно обновлен',
                    'master' => $master->fresh(['services', 'user']),
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to update master profile', [
                'master_id' => $masterId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при обновлении профиля',
            ];
        }
    }

    /**
     * Обновить услуги мастера
     */
    private function updateServices(MasterProfile $master, array $services): void
    {
        // Удаляем старые связи
        $master->services()->detach();
        
        // Добавляем новые
        foreach ($services as $service) {
            if (isset($service['service_id']) && isset($service['price'])) {
                $master->services()->attach($service['service_id'], [
                    'price' => $service['price'],
                    'duration' => $service['duration'] ?? 60,
                    'description' => $service['description'] ?? null,
                ]);
            }
        }
    }
}