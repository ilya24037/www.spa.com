<?php

namespace App\Domain\Master\Actions;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Repositories\MasterRepository;
use App\Enums\MasterStatus;
use Illuminate\Support\Facades\Log;

/**
 * Action для верификации мастера
 */
class VerifyMasterAction
{
    private MasterRepository $masterRepository;

    public function __construct(MasterRepository $masterRepository)
    {
        $this->masterRepository = $masterRepository;
    }

    /**
     * Верифицировать мастера
     */
    public function execute(int $masterId, array $verificationData = []): array
    {
        try {
            $master = $this->masterRepository->findById($masterId);
            
            if (!$master) {
                return [
                    'success' => false,
                    'message' => 'Профиль мастера не найден',
                ];
            }

            // Проверяем статус
            if ($master->is_verified) {
                return [
                    'success' => false,
                    'message' => 'Мастер уже верифицирован',
                ];
            }

            // Проверяем заполненность профиля
            $validation = $this->validateProfile($master);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Профиль не готов к верификации',
                    'errors' => $validation['errors'],
                ];
            }

            // Верифицируем
            $master->is_verified = true;
            $master->verified_at = now();
            
            // Если профиль в черновике, активируем
            if ($master->status === MasterStatus::DRAFT) {
                $master->status = MasterStatus::ACTIVE;
            }
            
            // Сохраняем данные верификации
            if (!empty($verificationData)) {
                $metadata = $master->metadata ?? [];
                $metadata['verification'] = array_merge(
                    $metadata['verification'] ?? [],
                    $verificationData
                );
                $master->metadata = $metadata;
            }
            
            $master->save();

            Log::info('Master verified', [
                'master_id' => $master->id,
                'verification_data' => $verificationData,
            ]);

            return [
                'success' => true,
                'message' => 'Мастер успешно верифицирован',
                'master' => $master,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to verify master', [
                'master_id' => $masterId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при верификации мастера',
            ];
        }
    }

    /**
     * Валидация профиля для верификации
     */
    private function validateProfile(MasterProfile $master): array
    {
        $errors = [];

        if (empty($master->display_name)) {
            $errors[] = 'Не указано имя';
        }

        if (empty($master->bio)) {
            $errors[] = 'Не заполнено описание';
        }

        if ($master->experience_years < 1) {
            $errors[] = 'Не указан опыт работы';
        }

        if (!$master->photos()->exists()) {
            $errors[] = 'Не загружены фотографии';
        }

        if (!$master->services()->exists()) {
            $errors[] = 'Не указаны услуги';
        }

        if (empty($master->city)) {
            $errors[] = 'Не указан город';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}