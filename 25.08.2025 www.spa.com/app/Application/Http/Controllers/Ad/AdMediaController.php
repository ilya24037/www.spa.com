<?php

namespace App\Application\Http\Controllers\Ad;

use App\Application\Http\Controllers\Controller;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\AdMediaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Контроллер для работы с медиа-файлами объявлений
 * Использует AdMediaService для всех операций с медиа
 */
class AdMediaController extends Controller
{
    private AdMediaService $adMediaService;
    
    public function __construct(AdMediaService $adMediaService)
    {
        $this->adMediaService = $adMediaService;
    }

    /**
     * Загрузить фотографии для объявления
     */
    public function uploadPhotos(Request $request, Ad $ad): JsonResponse
    {
        $this->authorize('update', $ad);
        
        $request->validate([
            'photos' => 'required|array',
            'photos.*' => 'required|image|max:10240' // 10MB
        ]);
        
        $result = $this->adMediaService->addMultiplePhotos($ad, $request->file('photos'));
        
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Загрузить одну фотографию
     */
    public function uploadPhoto(Request $request, Ad $ad): JsonResponse
    {
        $this->authorize('update', $ad);
        
        $request->validate([
            'photo' => 'required|image|max:10240' // 10MB
        ]);
        
        $result = $this->adMediaService->addPhoto($ad, $request->file('photo'));
        
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Удалить фотографию
     */
    public function deletePhoto(Request $request, Ad $ad): JsonResponse
    {
        $this->authorize('update', $ad);
        
        $request->validate([
            'photo_id' => 'required|string'
        ]);
        
        $success = $this->adMediaService->removePhoto($ad, $request->photo_id);
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Фотография удалена' : 'Фотография не найдена'
        ], $success ? 200 : 404);
    }

    /**
     * Изменить порядок фотографий
     */
    public function reorderPhotos(Request $request, Ad $ad): JsonResponse
    {
        $this->authorize('update', $ad);
        
        $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'string'
        ]);
        
        $success = $this->adMediaService->reorderPhotos($ad, $request->photo_ids);
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Порядок фотографий изменен' : 'Ошибка при изменении порядка'
        ]);
    }

    /**
     * Загрузить видео для объявления
     */
    public function uploadVideo(Request $request, Ad $ad): JsonResponse
    {
        $this->authorize('update', $ad);
        
        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/webm,video/avi|max:102400' // 100MB
        ]);
        
        $result = $this->adMediaService->setVideo($ad, $request->file('video'));
        
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Удалить видео
     */
    public function deleteVideo(Ad $ad): JsonResponse
    {
        $this->authorize('update', $ad);
        
        $success = $this->adMediaService->removeVideo($ad);
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Видео удалено' : 'Ошибка при удалении видео'
        ]);
    }

    /**
     * Получить информацию о медиа объявления
     */
    public function getMediaInfo(Ad $ad): JsonResponse
    {
        $this->authorize('view', $ad);
        
        $stats = $this->adMediaService->getMediaStats($ad);
        
        return response()->json([
            'success' => true,
            'data' => [
                'photos' => $ad->photos ?? [],
                'video' => $ad->video,
                'stats' => $stats
            ]
        ]);
    }
}