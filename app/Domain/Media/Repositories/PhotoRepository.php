<?php

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Models\Photo;
use App\Support\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Репозиторий для работы с фотографиями
 */
class PhotoRepository extends BaseRepository
{
    public function __construct(Photo $model)
    {
        parent::__construct($model);
    }

    /**
     * Создать новую фотографию
     */
    public function createPhoto(array $data): Photo
    {
        return $this->create($data);
    }

    /**
     * Найти фотографии мастера
     */
    public function findByMasterProfileId(int $masterProfileId): Collection
    {
        return $this->model->where('master_profile_id', $masterProfileId)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Найти главную фотографию мастера
     */
    public function findMainPhotoByMasterProfileId(int $masterProfileId): ?Photo
    {
        return $this->model->where('master_profile_id', $masterProfileId)
            ->where('is_main', true)
            ->first();
    }

    /**
     * Обновить порядок сортировки фотографии
     */
    public function updateSortOrder(int $photoId, int $masterProfileId, int $sortOrder): bool
    {
        return $this->model->where('id', $photoId)
            ->where('master_profile_id', $masterProfileId)
            ->update(['sort_order' => $sortOrder]) > 0;
    }

    /**
     * Сбросить флаг главной фотографии для всех фото мастера
     */
    public function resetMainFlag(int $masterProfileId): int
    {
        return $this->model->where('master_profile_id', $masterProfileId)
            ->update(['is_main' => false]);
    }

    /**
     * Установить фотографию как главную
     */
    public function setAsMain(int $photoId, int $masterProfileId): bool
    {
        return DB::transaction(function () use ($photoId, $masterProfileId) {
            // Сбрасываем флаг у всех фото
            $this->resetMainFlag($masterProfileId);
            
            // Устанавливаем флаг у выбранной фотографии
            return $this->model->where('id', $photoId)
                ->where('master_profile_id', $masterProfileId)
                ->update(['is_main' => true]) > 0;
        });
    }

    /**
     * Удалить фотографию по ID с файлами
     */
    public function deletePhoto(int $photoId, int $masterProfileId): bool
    {
        return DB::transaction(function () use ($photoId, $masterProfileId) {
            // Находим фотографию
            $photo = $this->model->where('id', $photoId)
                ->where('master_profile_id', $masterProfileId)
                ->first();
            
            if (!$photo) {
                return false;
            }
            
            // Удаляем физические файлы
            $this->deletePhotoFiles($photo);
            
            // Удаляем запись из БД
            return $photo->delete();
        });
    }

    /**
     * Удалить физические файлы фотографии
     */
    private function deletePhotoFiles(Photo $photo): void
    {
        $disk = Storage::disk('masters_private');
        $folderName = $photo->masterProfile->folder_name;
        
        // Список всех файлов для удаления
        $filesToDelete = [
            "{$folderName}/photos/{$photo->filename}",  // Оригинал
            "{$folderName}/photos/" . $this->getThumbFilename($photo->filename),   // Миниатюра
            "{$folderName}/photos/" . $this->getMediumFilename($photo->filename),  // Средний размер
        ];
        
        foreach ($filesToDelete as $filePath) {
            if ($disk->exists($filePath)) {
                $disk->delete($filePath);
            }
        }
    }

    /**
     * Получить имя файла миниатюры
     */
    private function getThumbFilename(string $originalFilename): string
    {
        $info = pathinfo($originalFilename);
        return $info['filename'] . '_thumb.' . $info['extension'];
    }

    /**
     * Получить имя файла среднего размера  
     */
    private function getMediumFilename(string $originalFilename): string
    {
        $info = pathinfo($originalFilename);
        return $info['filename'] . '_medium.' . $info['extension'];
    }

    /**
     * Найти фотографию по имени файла
     */
    public function findByFilename(string $filename, int $masterProfileId): ?Photo
    {
        return $this->model->where('filename', $filename)
            ->where('master_profile_id', $masterProfileId)
            ->first();
    }

    /**
     * Подсчитать количество фотографий мастера
     */
    public function countByMasterProfileId(int $masterProfileId): int
    {
        return $this->model->where('master_profile_id', $masterProfileId)->count();
    }

    /**
     * Получить следующий порядковый номер для фотографии
     */
    public function getNextSortOrder(int $masterProfileId): int
    {
        $maxOrder = $this->model->where('master_profile_id', $masterProfileId)
            ->max('sort_order');
        
        return ($maxOrder ?? 0) + 1;
    }

    /**
     * Массовое обновление порядка сортировки
     */
    public function updateMultipleSortOrders(array $photoOrders): void
    {
        DB::transaction(function () use ($photoOrders) {
            foreach ($photoOrders as $photoId => $order) {
                $this->model->where('id', $photoId)
                    ->update(['sort_order' => $order]);
            }
        });
    }
}