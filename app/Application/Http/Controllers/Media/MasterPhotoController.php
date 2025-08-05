<?php

namespace App\Application\Http\Controllers\Media;

use App\Application\Http\Controllers\Controller;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Media\Models\Photo;
use App\Domain\Media\Services\MasterMediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class MasterPhotoController extends Controller
{
    protected MasterMediaService $mediaService;

    public function __construct(MasterMediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }
    /**
     * Загрузить фотографии мастера
     */
    public function store(Request $request, MasterProfile $master)
    {
        $this->authorize('update', $master);

        $request->validate([
            'photos' => 'required|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB
        ]);

        try {
            $uploadedPhotos = $this->mediaService->uploadPhotos(
                $master,
                $request->file('photos')
            );

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
    public function destroy(MasterProfile $master, Photo $photo)
    {
        $this->authorize('update', $master);

        if ($photo->master_profile_id !== $master->id) {
            abort(403, 'Фотография не принадлежит этому мастеру');
        }

        try {
            $this->mediaService->deletePhoto($photo);

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
     * Установить главное фото
     */
    public function setMain(MasterProfile $master, Photo $photo)
    {
        $this->authorize('update', $master);
        
        if ($photo->master_profile_id !== $master->id) {
            abort(403, 'Фотография не принадлежит этому мастеру');
        }

        try {
            $this->mediaService->setMainPhoto($photo);

            return response()->json([
                'success' => true,
                'message' => 'Главное фото установлено'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при установке главного фото: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Изменить порядок фотографий
     */
    public function reorder(Request $request, MasterProfile $master)
    {
        $this->authorize('update', $master);

        $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'exists:photos,id'
        ]);

        try {
            $this->mediaService->reorderPhotos($master, $request->photo_ids);

            return response()->json([
                'success' => true,
                'message' => 'Порядок фотографий обновлен'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при изменении порядка фотографий: ' . $e->getMessage()
            ], 500);
        }
    }
}
