<?php

namespace App\Application\Http\Controllers\Media;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Media\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MasterPhotoController extends Controller
{
    /**
     * Загрузить фотографии мастера
     */
    public function store(Request $request, MasterProfile $master)
    {
        // Проверка прав
        if (Auth::id() !== $master->user_id) {
            abort(403, 'Нет прав для загрузки фотографий');
        }

        $request->validate([
            'photos' => 'required|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB
        ]);

        $uploadedPhotos = [];

        DB::beginTransaction();
        try {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('masters/' . $master->id . '/photos', 'public');
                
                $masterPhoto = MasterPhoto::create([
                    'master_profile_id' => $master->id,
                    'photo_url' => Storage::url($path),
                    'photo_path' => $path,
                    'is_main' => $master->photos()->count() === 0, // Первое фото - главное
                    'sort_order' => $master->photos()->count(),
                ]);

                $uploadedPhotos[] = $masterPhoto;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'photos' => $uploadedPhotos,
                'message' => 'Фотографии успешно загружены'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Удаляем загруженные файлы
            foreach ($uploadedPhotos as $photo) {
                Storage::disk('public')->delete($photo->photo_path);
            }

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке фотографий'
            ], 500);
        }
    }

    /**
     * Удалить фотографию
     */
    public function destroy(MasterProfile $master, MasterPhoto $photo)
    {
        // Проверка прав
        if (Auth::id() !== $master->user_id || $photo->master_profile_id !== $master->id) {
            abort(403, 'Нет прав для удаления фотографии');
        }

        // Удаляем файл
        Storage::disk('public')->delete($photo->photo_path);
        
        // Удаляем запись
        $photo->delete();

        // Если удалили главное фото, делаем первое оставшееся главным
        if ($photo->is_main && $master->photos()->count() > 0) {
            $master->photos()->orderBy('sort_order')->first()->update(['is_main' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Фотография удалена'
        ]);
    }

    /**
     * Установить главное фото
     */
    public function setMain(MasterProfile $master, MasterPhoto $photo)
    {
        // Проверка прав
        if (Auth::id() !== $master->user_id || $photo->master_profile_id !== $master->id) {
            abort(403, 'Нет прав для изменения фотографии');
        }

        // Снимаем флаг с текущего главного
        $master->photos()->where('is_main', true)->update(['is_main' => false]);
        
        // Устанавливаем новое главное
        $photo->update(['is_main' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Главное фото установлено'
        ]);
    }

    /**
     * Изменить порядок фотографий
     */
    public function reorder(Request $request, MasterProfile $master)
    {
        // Проверка прав
        if (Auth::id() !== $master->user_id) {
            abort(403, 'Нет прав для изменения порядка фотографий');
        }

        $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'exists:master_photos,id'
        ]);

        foreach ($request->photo_ids as $index => $photoId) {
            MasterPhoto::where('id', $photoId)
                ->where('master_profile_id', $master->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Порядок фотографий обновлен'
        ]);
    }
}