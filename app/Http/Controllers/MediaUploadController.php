<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Models\MasterPhoto;
use App\Models\MasterVideo;
use App\Services\MediaProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MediaUploadController extends Controller
{
    public function __construct(
        private MediaProcessingService $mediaService
    ) {}

    /**
     * Загрузить аватар мастера
     */
    public function uploadAvatar(Request $request, MasterProfile $master)
    {
        // Проверяем права доступа
        if (Auth::id() !== $master->user_id) {
            return response()->json(['error' => 'Нет доступа'], 403);
        }

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,webp|max:10240', // 10MB
        ]);

        try {
            $this->mediaService->uploadAvatar($master, $request->file('avatar'));

            return response()->json([
                'success' => true,
                'message' => 'Аватар загружен успешно',
                'avatar_url' => route('master.avatar', $master),
                'avatar_thumb_url' => route('master.avatar.thumb', $master),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка загрузки аватара: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Загрузить фотографии мастера
     */
    public function uploadPhotos(Request $request, MasterProfile $master)
    {
        // Проверяем права доступа (только для авторизованных маршрутов)
        if (Auth::check() && Auth::id() !== $master->user_id) {
            return response()->json(['error' => 'Нет доступа'], 403);
        }

        $request->validate([
            'photos' => 'required|array|min:1|max:10',
            'photos.*' => 'required|image|mimes:jpeg,png,webp|max:10240', // 10MB каждое
        ]);

        try {
            $photos = $this->mediaService->uploadPhotos($master, $request->file('photos'));

            return response()->json([
                'success' => true,
                'message' => 'Загружено ' . count($photos) . ' фотографий',
                'photos' => array_map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'filename' => $photo->filename,
                        'original_url' => $photo->original_url,
                        'medium_url' => $photo->medium_url,
                        'thumb_url' => $photo->thumb_url,
                        'is_main' => $photo->is_main,
                        'sort_order' => $photo->sort_order,
                    ];
                }, $photos),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка загрузки фотографий: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Загрузить видео мастера
     */
    public function uploadVideo(Request $request, MasterProfile $master)
    {
        // Проверяем права доступа
        if (Auth::id() !== $master->user_id) {
            return response()->json(['error' => 'Нет доступа'], 403);
        }

        $request->validate([
            'video' => 'required|file|mimes:mp4,webm,avi,mov|max:102400', // 100MB
        ]);

        try {
            $video = $this->mediaService->uploadVideo($master, $request->file('video'));

            return response()->json([
                'success' => true,
                'message' => 'Видео загружено успешно',
                'video' => [
                    'id' => $video->id,
                    'filename' => $video->filename,
                    'video_url' => $video->video_url,
                    'poster_url' => $video->poster_url,
                    'duration' => $video->formatted_duration,
                    'file_size' => $video->file_size,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка загрузки видео: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить фотографию
     */
    public function deletePhoto(MasterPhoto $photo)
    {
        // Проверяем права доступа
        if (Auth::id() !== $photo->masterProfile->user_id) {
            return response()->json(['error' => 'Нет доступа'], 403);
        }

        try {
            $this->mediaService->deletePhoto($photo);

            return response()->json([
                'success' => true,
                'message' => 'Фотография удалена',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка удаления фотографии: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить видео
     */
    public function deleteVideo(MasterVideo $video)
    {
        // Проверяем права доступа
        if (Auth::id() !== $video->masterProfile->user_id) {
            return response()->json(['error' => 'Нет доступа'], 403);
        }

        try {
            $this->mediaService->deleteVideo($video);

            return response()->json([
                'success' => true,
                'message' => 'Видео удалено',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка удаления видео: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Изменить порядок фотографий
     */
    public function reorderPhotos(Request $request, MasterProfile $master)
    {
        // Проверяем права доступа
        if (Auth::id() !== $master->user_id) {
            return response()->json(['error' => 'Нет доступа'], 403);
        }

        $request->validate([
            'photos' => 'required|array',
            'photos.*.id' => 'required|exists:master_photos,id',
            'photos.*.sort_order' => 'required|integer|min:1',
        ]);

        try {
            foreach ($request->photos as $photoData) {
                MasterPhoto::where('id', $photoData['id'])
                    ->where('master_profile_id', $master->id)
                    ->update(['sort_order' => $photoData['sort_order']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Порядок фотографий изменен',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка изменения порядка: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Установить главное фото
     */
    public function setMainPhoto(MasterPhoto $photo)
    {
        // Проверяем права доступа
        if (Auth::id() !== $photo->masterProfile->user_id) {
            return response()->json(['error' => 'Нет доступа'], 403);
        }

        try {
            // Убираем флаг is_main у всех фото мастера
            MasterPhoto::where('master_profile_id', $photo->master_profile_id)
                ->update(['is_main' => false]);

            // Устанавливаем главное фото
            $photo->update(['is_main' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Главное фото установлено',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка установки главного фото: ' . $e->getMessage()
            ], 500);
        }
    }
} 