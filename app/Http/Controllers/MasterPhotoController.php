<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Models\MasterPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MasterPhotoController extends Controller
{
    /**
     * Загрузить фотографии мастера
     */
    public function upload(Request $request, MasterProfile $master)
    {
        $request->validate([
            'photos.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $uploadedPhotos = [];

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                // Генерируем уникальное имя файла
                $fileName = Str::random(20) . '.' . $file->getClientOriginalExtension();
                
                // Сохраняем в storage/app/public/masters
                $path = $file->storeAs('masters', $fileName, 'public');
                
                // Создаем запись в базе данных
                $photo = MasterPhoto::create([
                    'master_profile_id' => $master->id,
                    'path' => $path,
                    'is_main' => false,
                    'order' => $master->photos()->count() + 1,
                ]);

                $uploadedPhotos[] = [
                    'id' => $photo->id,
                    'url' => Storage::url($path),
                    'path' => $path,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Загружено ' . count($uploadedPhotos) . ' фотографий',
            'photos' => $uploadedPhotos,
        ]);
    }

    /**
     * Добавить фотографию из локального файла
     */
    public function addLocalPhoto(Request $request)
    {
        $request->validate([
            'master_id' => 'required|exists:master_profiles,id',
            'file_path' => 'required|string',
            'is_main' => 'boolean',
        ]);

        $master = MasterProfile::findOrFail($request->master_id);
        $localPath = $request->file_path;

        // Проверяем, что файл существует
        if (!file_exists(public_path($localPath))) {
            return response()->json(['error' => 'Файл не найден: ' . $localPath], 404);
        }

        // Создаем запись в базе данных
        $photo = MasterPhoto::create([
            'master_profile_id' => $master->id,
            'path' => $localPath,
            'is_main' => $request->boolean('is_main', false),
            'order' => $master->photos()->count() + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Фотография добавлена',
            'photo' => [
                'id' => $photo->id,
                'url' => asset($localPath),
                'path' => $localPath,
            ],
        ]);
    }

    /**
     * Удалить фотографию
     */
    public function destroy(MasterPhoto $photo)
    {
        // Удаляем файл из storage (если он там)
        if (Storage::disk('public')->exists($photo->path)) {
            Storage::disk('public')->delete($photo->path);
        }

        // Удаляем запись из базы данных
        $photo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Фотография удалена',
        ]);
    }

    /**
     * Установить главную фотографию
     */
    public function setMain(MasterPhoto $photo)
    {
        // Убираем флаг is_main у всех фотографий мастера
        MasterPhoto::where('master_profile_id', $photo->master_profile_id)
            ->update(['is_main' => false]);

        // Устанавливаем текущую фотографию как главную
        $photo->update(['is_main' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Главная фотография установлена',
        ]);
    }

    /**
     * Изменить порядок фотографий
     */
    public function reorder(Request $request, MasterProfile $master)
    {
        $request->validate([
            'photos' => 'required|array',
            'photos.*.id' => 'required|exists:master_photos,id',
            'photos.*.order' => 'required|integer|min:1',
        ]);

        foreach ($request->photos as $photoData) {
            MasterPhoto::where('id', $photoData['id'])
                ->where('master_profile_id', $master->id)
                ->update(['order' => $photoData['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Порядок фотографий изменен',
        ]);
    }
} 