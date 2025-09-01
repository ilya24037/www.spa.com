<?php

namespace App\Services;

use App\Models\Ad;
use App\Repositories\AdRepository;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdService
{
    public function __construct(
        protected AdRepository $adRepository,
        protected UserService $userService
    ) {}

    /**
     * Get paginated ads with filters.
     */
    public function getAds(array $filters = []): LengthAwarePaginator
    {
        try {
            return $this->adRepository->getAdsWithFilters($filters);
        } catch (\Exception $e) {
            Log::error('Failed to fetch ads', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create a new ad.
     */
    public function createAd(array $data): Ad
    {
        try {
            DB::beginTransaction();
            
            // Validate user can create ads
            $this->userService->validateAdCreation($data['user_id']);
            
            // Prepare data for storage
            $adData = $this->prepareAdData($data);
            
            // Create the ad
            $ad = $this->adRepository->create($adData);
            
            // Handle related data (photos, schedule, etc.)
            $this->handleRelatedData($ad, $data);
            
            DB::commit();
            
            Log::info('Ad created successfully', ['ad_id' => $ad->id]);
            
            return $ad;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create ad', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Update an existing ad.
     */
    public function updateAd(Ad $ad, array $data): Ad
    {
        try {
            DB::beginTransaction();
            
            // Validate user can update this ad
            $this->userService->validateAdUpdate($ad, $data['user_id']);
            
            // Prepare data for update
            $updateData = $this->prepareAdData($data);
            
            // Update the ad
            $updatedAd = $this->adRepository->update($ad, $updateData);
            
            // Handle related data updates
            $this->handleRelatedData($updatedAd, $data);
            
            DB::commit();
            
            Log::info('Ad updated successfully', ['ad_id' => $ad->id]);
            
            return $updatedAd;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update ad', [
                'ad_id' => $ad->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Delete an ad.
     */
    public function deleteAd(Ad $ad): bool
    {
        try {
            DB::beginTransaction();
            
            // Validate user can delete this ad
            $this->userService->validateAdDeletion($ad);
            
            // Soft delete the ad
            $deleted = $this->adRepository->delete($ad);
            
            DB::commit();
            
            Log::info('Ad deleted successfully', ['ad_id' => $ad->id]);
            
            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete ad', [
                'ad_id' => $ad->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Prepare ad data for storage.
     */
    protected function prepareAdData(array $data): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'],
            'price' => $data['price'],
            'user_id' => $data['user_id'],
            'category_id' => $data['category_id'],
            'location' => $data['location'],
            'is_active' => $data['is_active'] ?? true,
            'online_booking' => $data['online_booking'] ?? false
        ];
    }

    /**
     * Handle related data for ad.
     */
    protected function handleRelatedData(Ad $ad, array $data): void
    {
        // Handle photos
        if (isset($data['photos'])) {
            $ad->photos = $data['photos'];
        }
        
        // Handle schedule
        if (isset($data['schedule'])) {
            $ad->schedule = $data['schedule'];
        }
        
        $ad->save();
    }

    /**
     * Search ads by query.
     */
    public function searchAds(string $query, array $filters = []): Collection
    {
        try {
            return $this->adRepository->search($query, $filters);
        } catch (\Exception $e) {
            Log::error('Failed to search ads', [
                'query' => $query,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
