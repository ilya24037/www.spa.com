<?php

namespace App\Infrastructure\Adapters;

use App\Domain\Master\Services\MasterService as ModernMasterService;
use App\Domain\Master\DTOs\CreateMasterDTO;
use App\Domain\Master\DTOs\UpdateMasterDTO;
use App\DTOs\MasterFilterDTO;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Адаптер для MasterService с поддержкой старого API
 */
class MasterServiceAdapter
{
    private ModernMasterService $modernService;
    private bool $logLegacyCalls;

    public function __construct(ModernMasterService $modernService)
    {
        $this->modernService = $modernService;
        $this->logLegacyCalls = config('features.log_legacy_calls', true);
    }

    /**
     * Создать профиль мастера (старый метод)
     */
    public function createMasterProfile(array $data): MasterProfile
    {
        $this->logLegacyCall('createMasterProfile', $data);

        try {
            // Конвертируем в DTO
            $dto = CreateMasterDTO::fromArray($data);
            return $this->modernService->createProfile($dto);
        } catch (Throwable $e) {
            Log::error('Failed to create master profile via adapter', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Обновить профиль мастера (старый метод)
     */
    public function updateMasterProfile(int $masterId, array $data): MasterProfile
    {
        $this->logLegacyCall('updateMasterProfile', ['master_id' => $masterId, 'data' => $data]);

        try {
            $dto = UpdateMasterDTO::fromArray($data);
            return $this->modernService->updateProfile($masterId, $dto);
        } catch (Throwable $e) {
            Log::error('Failed to update master profile via adapter', [
                'error' => $e->getMessage(),
                'master_id' => $masterId
            ]);
            throw $e;
        }
    }

    /**
     * Поиск мастеров (старый метод)
     */
    public function searchMasters(array $filters = [], int $perPage = 20)
    {
        $this->logLegacyCall('searchMasters', $filters);

        try {
            // Конвертируем фильтры в DTO
            $filterDto = MasterFilterDTO::fromArray(array_merge($filters, [
                'per_page' => $perPage
            ]));
            
            return $this->modernService->search($filterDto);
        } catch (Throwable $e) {
            Log::error('Failed to search masters via adapter', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    /**
     * Получить мастера по ID (старый метод)
     */
    public function getMasterById(int $id): ?MasterProfile
    {
        $this->logLegacyCall('getMasterById', ['id' => $id]);

        try {
            // В новом сервисе этот метод может быть в репозитории
            return MasterProfile::with(['user', 'services', 'photos'])->find($id);
        } catch (Throwable $e) {
            Log::error('Failed to get master by id via adapter', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            throw $e;
        }
    }

    /**
     * Получить мастера по slug (прямой проксинг)
     */
    public function getBySlug(string $slug): ?MasterProfile
    {
        return $this->modernService->getBySlug($slug);
    }

    /**
     * Активировать профиль (прямой проксинг)
     */
    public function activateProfile(int $masterId): MasterProfile
    {
        return $this->modernService->activateProfile($masterId);
    }

    /**
     * Одобрить профиль (прямой проксинг)
     */
    public function approveProfile(int $masterId, User $moderator): MasterProfile
    {
        return $this->modernService->approveProfile($masterId, $moderator);
    }

    /**
     * Отклонить профиль (прямой проксинг)
     */
    public function rejectProfile(int $masterId, string $reason, User $moderator): MasterProfile
    {
        return $this->modernService->rejectProfile($masterId, $reason, $moderator);
    }

    /**
     * Получить топ мастеров (прямой проксинг)
     */
    public function getTopMasters(int $limit = 10)
    {
        return $this->modernService->getTopMasters($limit);
    }

    /**
     * Получить новых мастеров (прямой проксинг)
     */
    public function getNewMasters(int $limit = 10)
    {
        return $this->modernService->getNewMasters($limit);
    }

    /**
     * Установить отпуск (прямой проксинг)
     */
    public function setVacation(int $masterId, \DateTime $until, string $message = null): MasterProfile
    {
        return $this->modernService->setVacation($masterId, $until, $message);
    }

    /**
     * Логирование использования legacy методов
     */
    private function logLegacyCall(string $method, $params = null): void
    {
        if ($this->logLegacyCalls) {
            Log::channel('legacy')->info("Legacy call: MasterService::{$method}", [
                'params' => $params,
                'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? null
            ]);
        }
    }

    /**
     * Проксирование неопределенных методов
     */
    public function __call($method, $arguments)
    {
        $this->logLegacyCall($method, $arguments);
        
        if (method_exists($this->modernService, $method)) {
            return $this->modernService->$method(...$arguments);
        }
        
        throw new \BadMethodCallException("Method {$method} does not exist in MasterService");
    }
}