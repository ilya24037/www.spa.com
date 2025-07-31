<?php

namespace App\Application\Http\Controllers\Ad;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Services\MediaProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Контроллер для работы с медиа-файлами объявлений
 * Управляет загрузкой и удалением фото/видео
 */
class AdMediaController extends Controller
{
    private MediaProcessingService $mediaService;
    
    public function __construct(MediaProcessingService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Загрузить фотографии для объявления
     */
    public function uploadPhotos(Request $request, Ad $ad)
    {
        $this->authorize('update', $ad);
        
        $request->validate([
            'photos' => 'required|array',
            'photos.*' => 'required|image|max:10240' // 10MB
        ]);
        
        try {
            $uploadedPhotos = [];
            
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('ads/' . $ad->id . '/photos', 'public');
                
                $uploadedPhotos[] = [
                    'url' => Storage::url($path),
                    'path' => $path,
                    'size' => $photo->getSize(),
                    'name' => $photo->getClientOriginalName()
                ];
            }
            
            // Добавляем к существующим фото
            $currentPhotos = $ad->media ? $ad->media->photos : [];
            $allPhotos = array_merge($currentPhotos, $uploadedPhotos);
            
            // Обновляем медиа объявления
            if ($ad->media) {
                $ad->media->update(['photos' => $allPhotos]);
            } else {
                $ad->media()->create(['photos' => $allPhotos]);
            }
            
            return response()->json([
                'success' => true,
                'photos' => $uploadedPhotos,
                'message' => 'Фотографии успешно загружены'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке фотографий: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить фотографию
     */
    public function deletePhoto(Request $request, Ad $ad)
    {
        $this->authorize('update', $ad);
        
        $request->validate([
            'photo_index' => 'required|integer|min:0'
        ]);
        
        try {
            if (!$ad->media || empty($ad->media->photos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Фотографии не найдены'
                ], 404);
            }
            
            $photos = $ad->media->photos;
            $photoIndex = $request->photo_index;
            
            if (!isset($photos[$photoIndex])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Фотография не найдена'
                ], 404);
            }
            
            // Удаляем файл
            if (isset($photos[$photoIndex]['path'])) {
                Storage::disk('public')->delete($photos[$photoIndex]['path']);
            }
            
            // Удаляем из массива
            array_splice($photos, $photoIndex, 1);
            
            // Обновляем медиа
            $ad->media->update(['photos' => $photos]);
            
            return response()->json([
                'success' => true,
                'message' => 'Фотография удалена'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении фотографии: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Изменить порядок фотографий
     */
    public function reorderPhotos(Request $request, Ad $ad)
    {
        $this->authorize('update', $ad);
        
        $request->validate([
            'photo_order' => 'required|array',
            'photo_order.*' => 'integer|min:0'
        ]);
        
        try {
            if (!$ad->media || empty($ad->media->photos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Фотографии не найдены'
                ], 404);
            }
            
            $photos = $ad->media->photos;
            $newOrder = $request->photo_order;
            $reorderedPhotos = [];
            
            // Переупорядочиваем фотографии
            foreach ($newOrder as $oldIndex) {
                if (isset($photos[$oldIndex])) {
                    $reorderedPhotos[] = $photos[$oldIndex];
                }
            }
            
            // Обновляем медиа
            $ad->media->update(['photos' => $reorderedPhotos]);
            
            return response()->json([
                'success' => true,
                'message' => 'Порядок фотографий изменен'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при изменении порядка: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Загрузить видео для объявления
     */
    public function uploadVideo(Request $request, Ad $ad)
    {
        $this->authorize('update', $ad);
        
        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/webm|max:102400' // 100MB
        ]);
        
        try {
            $video = $request->file('video');
            $path = $video->store('ads/' . $ad->id . '/videos', 'public');
            
            $videoData = [
                'url' => Storage::url($path),
                'path' => $path,
                'size' => $video->getSize(),
                'name' => $video->getClientOriginalName(),
                'duration' => null // Можно добавить определение длительности
            ];
            
            // Обновляем медиа объявления
            if ($ad->media) {
                $ad->media->update(['video' => [$videoData]]);
            } else {
                $ad->media()->create(['video' => [$videoData]]);
            }
            
            return response()->json([
                'success' => true,
                'video' => $videoData,
                'message' => 'Видео успешно загружено'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке видео: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить видео
     */
    public function deleteVideo(Ad $ad)
    {
        $this->authorize('update', $ad);
        
        try {
            if (!$ad->media || empty($ad->media->video)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Видео не найдено'
                ], 404);
            }
            
            $videos = $ad->media->video;
            
            // Удаляем файлы
            foreach ($videos as $video) {
                if (isset($video['path'])) {
                    Storage::disk('public')->delete($video['path']);
                }
            }
            
            // Очищаем видео
            $ad->media->update(['video' => []]);
            
            return response()->json([
                'success' => true,
                'message' => 'Видео удалено'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении видео: ' . $e->getMessage()
            ], 500);
        }
    }
}