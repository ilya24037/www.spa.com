<?php

namespace App\Domain\Analytics\Collectors;

use App\Domain\Analytics\Contracts\AnalyticsServiceInterface;
use App\Domain\Analytics\DTOs\TrackUserActionDTO;
use App\Domain\Analytics\Models\UserAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Сборщик пользовательских взаимодействий (поиск, избранное, загрузка фото, жалобы)
 */
class InteractionCollector
{
    protected AnalyticsServiceInterface $analyticsService;

    public function __construct(AnalyticsServiceInterface $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Собрать добавление в избранное
     */
    public function collectFavoriteAdd(
        string $favoriteableType,
        int $favoriteableId,
        Request $request,
        array $additionalData = []
    ): ?UserAction {
        try {
            $properties = array_merge([
                'favoriteable_type' => $favoriteableType,
                'favoriteable_id' => $favoriteableId,
                'action_source' => 'favorite_button',
            ], $additionalData);

            $dto = new TrackUserActionDTO(
                actionType: UserAction::ACTION_ADD_FAVORITE,
                userId: Auth::id(),
                actionableType: $favoriteableType,
                actionableId: $favoriteableId,
                properties: $properties,
                sessionId: $request->session()->getId(),
                isConversion: false,
                conversionValue: 0
            );

            return $this->analyticsService->trackUserAction($dto);

        } catch (\Exception $e) {
            Log::error('Failed to collect favorite add action', [
                'error' => $e->getMessage(),
                'favoriteable_type' => $favoriteableType,
                'favoriteable_id' => $favoriteableId,
            ]);

            return null;
        }
    }

    /**
     * Собрать поиск
     */
    public function collectSearch(
        string $query,
        int $resultsCount,
        Request $request,
        array $filters = []
    ): ?UserAction {
        try {
            $properties = [
                'search_query' => $query,
                'results_count' => $resultsCount,
                'filters' => $filters,
                'search_type' => $request->get('type', 'general'),
                'sort_by' => $request->get('sort'),
                'page' => $request->get('page', 1),
            ];

            $dto = new TrackUserActionDTO(
                actionType: UserAction::ACTION_SEARCH,
                userId: Auth::id(),
                actionableType: null,
                actionableId: null,
                properties: $properties,
                sessionId: $request->session()->getId(),
                isConversion: false,
                conversionValue: 0
            );

            return $this->analyticsService->trackUserAction($dto);

        } catch (\Exception $e) {
            Log::error('Failed to collect search action', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);

            return null;
        }
    }

    /**
     * Собрать загрузку фото
     */
    public function collectPhotoUpload(
        string $uploadableType,
        int $uploadableId,
        int $photoCount,
        Request $request,
        array $additionalData = []
    ): ?UserAction {
        try {
            $properties = array_merge([
                'uploadable_type' => $uploadableType,
                'uploadable_id' => $uploadableId,
                'photo_count' => $photoCount,
                'upload_source' => $request->get('source', 'form'),
            ], $additionalData);

            $dto = new TrackUserActionDTO(
                actionType: UserAction::ACTION_UPLOAD_PHOTO,
                userId: Auth::id(),
                actionableType: $uploadableType,
                actionableId: $uploadableId,
                properties: $properties,
                sessionId: $request->session()->getId(),
                isConversion: false,
                conversionValue: 0
            );

            return $this->analyticsService->trackUserAction($dto);

        } catch (\Exception $e) {
            Log::error('Failed to collect photo upload action', [
                'error' => $e->getMessage(),
                'uploadable_type' => $uploadableType,
                'uploadable_id' => $uploadableId,
            ]);

            return null;
        }
    }

    /**
     * Собрать жалобу
     */
    public function collectReport(
        string $reportableType,
        int $reportableId,
        string $reason,
        Request $request,
        array $additionalData = []
    ): ?UserAction {
        try {
            $properties = array_merge([
                'reportable_type' => $reportableType,
                'reportable_id' => $reportableId,
                'reason' => $reason,
                'report_timestamp' => now()->format('Y-m-d H:i:s'),
            ], $additionalData);

            $dto = new TrackUserActionDTO(
                actionType: UserAction::ACTION_REPORT,
                userId: Auth::id(),
                actionableType: $reportableType,
                actionableId: $reportableId,
                properties: $properties,
                sessionId: $request->session()->getId(),
                isConversion: false,
                conversionValue: 0
            );

            return $this->analyticsService->trackUserAction($dto);

        } catch (\Exception $e) {
            Log::error('Failed to collect report action', [
                'error' => $e->getMessage(),
                'reportable_type' => $reportableType,
                'reportable_id' => $reportableId,
            ]);

            return null;
        }
    }

    /**
     * Массовый сбор действий (для исторических данных)
     */
    public function collectBatch(array $actions): array
    {
        $results = ['success' => 0, 'failed' => 0, 'conversions' => 0];

        foreach ($actions as $actionData) {
            try {
                $dto = new TrackUserActionDTO(
                    actionType: $actionData['action_type'],
                    userId: $actionData['user_id'] ?? null,
                    actionableType: $actionData['actionable_type'] ?? null,
                    actionableId: $actionData['actionable_id'] ?? null,
                    properties: $actionData['properties'] ?? [],
                    sessionId: $actionData['session_id'] ?? null,
                    isConversion: $actionData['is_conversion'] ?? false,
                    conversionValue: $actionData['conversion_value'] ?? 0
                );
                
                $userAction = $this->analyticsService->trackUserAction($dto);

                $results['success']++;
                
                if ($userAction->is_conversion) {
                    $results['conversions']++;
                }

            } catch (\Exception $e) {
                $results['failed']++;
                
                Log::warning('Failed to collect user action in batch', [
                    'error' => $e->getMessage(),
                    'data' => $actionData,
                ]);
            }
        }

        return $results;
    }
}