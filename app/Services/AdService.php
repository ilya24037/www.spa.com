<?php

namespace App\Services;

use App\Domain\Ad\Services\AdService as DomainAdService;
use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Legacy-адаптер для AdService
 * @deprecated Используйте App\Domain\Ad\Services\AdService
 */
class AdService
{
    private DomainAdService $domainService;

    public function __construct(DomainAdService $domainService)
    {
        $this->domainService = $domainService;
    }

    /**
     * @deprecated Используйте DomainAdService::create()
     */
    public function create(array $data, User $user): Ad
    {
        Log::info('Using legacy AdService::create - consider migrating to Domain service');
        return $this->domainService->create($data, $user);
    }

    /**
     * @deprecated Используйте DomainAdService::update()
     */
    public function update(Ad $ad, array $data): Ad
    {
        Log::info('Using legacy AdService::update - consider migrating to Domain service');
        return $this->domainService->update($ad, $data);
    }

    /**
     * @deprecated Используйте DomainAdService::publish()
     */
    public function publish(Ad $ad): Ad
    {
        Log::info('Using legacy AdService::publish - consider migrating to Domain service');
        return $this->domainService->publish($ad);
    }

    /**
     * @deprecated Используйте DomainAdService::saveDraft()
     */
    public function saveDraft(array $data, User $user, ?Ad $ad = null): Ad
    {
        Log::info('Using legacy AdService::saveDraft - consider migrating to Domain service');
        return $this->domainService->saveDraft($data, $user, $ad);
    }

    /**
     * @deprecated Используйте DomainAdService::archive()
     */
    public function archive(Ad $ad): Ad
    {
        Log::info('Using legacy AdService::archive - consider migrating to Domain service');
        return $this->domainService->archive($ad);
    }

    /**
     * @deprecated Используйте DomainAdService::restore()
     */
    public function restore(Ad $ad): Ad
    {
        Log::info('Using legacy AdService::restore - consider migrating to Domain service');
        return $this->domainService->restore($ad);
    }

    /**
     * @deprecated Используйте DomainAdService::delete()
     */
    public function delete(Ad $ad): bool
    {
        Log::info('Using legacy AdService::delete - consider migrating to Domain service');
        return $this->domainService->delete($ad);
    }

    /**
     * @deprecated Используйте DomainAdService::getUserAdStats()
     */
    public function getUserAdStats(User $user): array
    {
        Log::info('Using legacy AdService::getUserAdStats - consider migrating to Domain service');
        return $this->domainService->getUserAdStats($user);
    }

    /**
     * Proxy для всех других методов
     */
    public function __call($method, $arguments)
    {
        Log::info("Using legacy AdService::{$method} - consider migrating to Domain service");
        return $this->domainService->$method(...$arguments);
    }
}