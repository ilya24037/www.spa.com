<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdRequest;
use App\Http\Requests\UpdateAdRequest;
use App\Http\Resources\AdResource;
use App\Models\Ad;
use App\Services\AdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdController extends Controller
{
    public function __construct(
        protected AdService $adService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        try {
            $ads = $this->adService->getAds($request->all());
            return AdResource::collection($ads);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch ads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdRequest $request): JsonResponse
    {
        try {
            $ad = $this->adService->createAd($request->validated());
            return response()->json([
                'message' => 'Ad created successfully',
                'data' => new AdResource($ad)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create ad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Ad $ad): AdResource
    {
        try {
            return new AdResource($ad);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch ad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdRequest $request, Ad $ad): JsonResponse
    {
        try {
            $updatedAd = $this->adService->updateAd($ad, $request->validated());
            return response()->json([
                'message' => 'Ad updated successfully',
                'data' => new AdResource($updatedAd)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update ad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ad $ad): JsonResponse
    {
        try {
            $this->adService->deleteAd($ad);
            return response()->json([
                'message' => 'Ad deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete ad',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
