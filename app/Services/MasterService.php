<?php

namespace App\Services;

use App\Domain\Master\Services\MasterService as DomainMasterService;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Legacy-адаптер для MasterService
 * @deprecated Используйте App\Domain\Master\Services\MasterService
 */
class MasterService
{
    private DomainMasterService $domainService;

    public function __construct(DomainMasterService $domainService)
    {
        $this->domainService = $domainService;
    }

    /**
     * @deprecated Используйте DomainMasterService::createProfile()
     */
    public function createProfile($dto): MasterProfile
    {
        Log::info('Using legacy MasterService::createProfile - consider migrating to Domain service');
        return $this->domainService->createProfile($dto);
    }

    /**
     * @deprecated Используйте DomainMasterService::updateProfile()
     */
    public function updateProfile(int $masterId, $dto): MasterProfile
    {
        Log::info('Using legacy MasterService::updateProfile - consider migrating to Domain service');
        return $this->domainService->updateProfile($masterId, $dto);
    }

    /**
     * @deprecated Используйте DomainMasterService::activateProfile()
     */
    public function activateProfile(int $masterId): MasterProfile
    {
        Log::info('Using legacy MasterService::activateProfile - consider migrating to Domain service');
        return $this->domainService->activateProfile($masterId);
    }

    /**
     * @deprecated Используйте DomainMasterService::deactivateProfile()
     */
    public function deactivateProfile(int $masterId, string $reason = null): MasterProfile
    {
        Log::info('Using legacy MasterService::deactivateProfile - consider migrating to Domain service');
        return $this->domainService->deactivateProfile($masterId, $reason);
    }

    /**
     * @deprecated Используйте DomainMasterService::approveProfile()
     */
    public function approveProfile(int $masterId, User $moderator): MasterProfile
    {
        Log::info('Using legacy MasterService::approveProfile - consider migrating to Domain service');
        return $this->domainService->approveProfile($masterId, $moderator);
    }

    /**
     * @deprecated Используйте DomainMasterService::rejectProfile()
     */
    public function rejectProfile(int $masterId, string $reason, User $moderator): MasterProfile
    {
        Log::info('Using legacy MasterService::rejectProfile - consider migrating to Domain service');
        return $this->domainService->rejectProfile($masterId, $reason, $moderator);
    }

    /**
     * @deprecated Используйте DomainMasterService::search()
     */
    public function search($filters)
    {
        Log::info('Using legacy MasterService::search - consider migrating to Domain service');
        return $this->domainService->search($filters);
    }

    /**
     * @deprecated Используйте DomainMasterService::getBySlug()
     */
    public function getBySlug(string $slug): ?MasterProfile
    {
        Log::info('Using legacy MasterService::getBySlug - consider migrating to Domain service');
        return $this->domainService->getBySlug($slug);
    }

    /**
     * Proxy для всех других методов
     */
    public function __call($method, $arguments)
    {
        Log::info("Using legacy MasterService::{$method} - consider migrating to Domain service");
        return $this->domainService->$method(...$arguments);
    }
}