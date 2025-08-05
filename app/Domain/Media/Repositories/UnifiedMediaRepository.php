<?php

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Models\Media;
use App\Domain\Media\Models\Photo;
use App\Domain\Media\Models\Video;
use App\Domain\Common\Repositories\BaseRepository;
use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Унифицированный репозиторий для работы с медиафайлами
 * Поддерживает как новую архитектуру (Media), так и legacy (Photo/Video)
 */
class UnifiedMediaRepository extends BaseRepository
{
    private PhotoRepository $photoRepository;
    private VideoRepository $videoRepository;

    public function __construct(
        Media $model,
        PhotoRepository $photoRepository,
        VideoRepository $videoRepository
    ) {
        parent::__construct($model);
        $this->photoRepository = $photoRepository;
        $this->videoRepository = $videoRepository;
    }

    /**
     * Получить все медиафайлы мастера (унифицированный метод)
     */
    public function findByMasterProfile(int $masterProfileId, ?MediaType $type = null): Collection
    {
        $media = collect();

        // Получаем из новой таблицы media
        $query = $this->model->where('mediable_type', 'App\\Domain\\Master\\Models\\MasterProfile')
            ->where('mediable_id', $masterProfileId);
        
        if ($type) {
            $query->where('type', $type->value);
        }
        
        $mediaFiles = $query->orderBy('sort_order')->get();
        $media = $media->merge($mediaFiles);

        // Получаем из legacy таблиц (если они еще существуют)
        if (!$type || $type === MediaType::IMAGE) {
            $photos = $this->photoRepository->findByMasterProfileId($masterProfileId);
            $media = $media->merge($photos);
        }

        if (!$type || $type === MediaType::VIDEO) {
            $videos = $this->videoRepository->findByMasterProfileId($masterProfileId);
            $media = $media->merge($videos);
        }

        return $media->sortBy('sort_order');
    }

    /**
     * Получить все фотографии мастера (унифицированный метод)
     */
    public function findPhotosByMasterProfile(int $masterProfileId): Collection
    {
        return $this->findByMasterProfile($masterProfileId, MediaType::IMAGE);
    }

    /**
     * Получить все видео мастера (унифицированный метод)
     */
    public function findVideosByMasterProfile(int $masterProfileId): Collection
    {
        return $this->findByMasterProfile($masterProfileId, MediaType::VIDEO);
    }

    /**
     * Найти главное фото мастера (унифицированный метод)
     */
    public function findMainPhoto(int $masterProfileId): ?object
    {
        // Ищем в новой таблице media
        $mainMedia = $this->model->where('mediable_type', 'App\\Domain\\Master\\Models\\MasterProfile')
            ->where('mediable_id', $masterProfileId)
            ->where('type', MediaType::IMAGE->value)
            ->whereJsonContains('metadata->is_main', true)
            ->first();

        if ($mainMedia) {
            return $mainMedia;
        }

        // Ищем в legacy таблице
        return $this->photoRepository->findMainPhotoByMasterProfileId($masterProfileId);
    }

    /**
     * Создать медиафайл (унифицированный метод)
     */
    public function createMedia(array $data): object
    {
        // Определяем, куда сохранять на основе типа данных
        if (isset($data['file_name']) && isset($data['type'])) {
            // Новая архитектура - сохраняем в media
            return $this->create($data);
        } elseif (isset($data['filename']) && !isset($data['duration'])) {
            // Legacy архитектура - фото
            return $this->photoRepository->createPhoto($data);
        } elseif (isset($data['filename']) && isset($data['duration'])) {
            // Legacy архитектура - видео
            return $this->videoRepository->createVideo($data);
        }

        throw new \InvalidArgumentException('Invalid media data provided');
    }

    /**
     * Удалить медиафайл с файлами (унифицированный метод)
     */
    public function deleteMediaWithFiles(object $media, int $masterProfileId): bool
    {
        // Определяем тип модели и используем соответствующий репозиторий
        if ($media instanceof Media) {
            return $this->deleteMediaFile($media);
        } elseif ($media instanceof Photo) {
            return $this->photoRepository->deletePhoto($media->id, $masterProfileId);
        } elseif ($media instanceof Video) {
            return $this->videoRepository->deleteVideo($media->id, $masterProfileId);
        }

        return false;
    }

    /**
     * Удалить файл из таблицы media
     */
    private function deleteMediaFile(Media $media): bool
    {
        return DB::transaction(function () use ($media) {
            // Удаляем физические файлы
            $this->deletePhysicalFiles($media);
            
            // Удаляем запись из БД
            return $media->delete();
        });
    }

    /**
     * Удалить физические файлы для Media модели
     */
    private function deletePhysicalFiles(Media $media): void
    {
        $disk = \Illuminate\Support\Facades\Storage::disk($media->disk);
        
        // Получаем все пути файлов для удаления
        $filePaths = $media->getAllFilePaths();
        
        foreach ($filePaths as $filePath) {
            if ($disk->exists($filePath)) {
                $disk->delete($filePath);
            }
        }
    }

    /**
     * Подсчитать количество медиафайлов мастера (унифицированный метод)
     */
    public function countByMasterProfile(int $masterProfileId, ?MediaType $type = null): int
    {
        $count = 0;

        // Считаем из новой таблицы media
        $query = $this->model->where('mediable_type', 'App\\Domain\\Master\\Models\\MasterProfile')
            ->where('mediable_id', $masterProfileId);
        
        if ($type) {
            $query->where('type', $type->value);
        }
        
        $count += $query->count();

        // Считаем из legacy таблиц (если они еще существуют)
        if (!$type || $type === MediaType::IMAGE) {
            $count += $this->photoRepository->countByMasterProfileId($masterProfileId);
        }

        if (!$type || $type === MediaType::VIDEO) {
            $count += $this->videoRepository->countByMasterProfileId($masterProfileId);
        }

        return $count;
    }

    /**
     * Установить главное фото (унифицированный метод)
     */
    public function setMainPhoto(int $masterProfileId, object $media): bool
    {
        return DB::transaction(function () use ($masterProfileId, $media) {
            // Сбрасываем флаг у всех фото
            $this->resetMainFlags($masterProfileId);
            
            // Устанавливаем флаг у выбранного медиафайла
            if ($media instanceof Media) {
                $metadata = $media->metadata ?? [];
                $metadata['is_main'] = true;
                $media->metadata = $metadata;
                return $media->save();
            } elseif ($media instanceof Photo) {
                return $this->photoRepository->setAsMain($media->id, $masterProfileId);
            }
            
            return false;
        });
    }

    /**
     * Сбросить флаги главного фото (унифицированный метод)
     */
    private function resetMainFlags(int $masterProfileId): void
    {
        // Сбрасываем в новой таблице media
        $mediaFiles = $this->model->where('mediable_type', 'App\\Domain\\Master\\Models\\MasterProfile')
            ->where('mediable_id', $masterProfileId)
            ->where('type', MediaType::IMAGE->value)
            ->get();

        foreach ($mediaFiles as $mediaFile) {
            $metadata = $mediaFile->metadata ?? [];
            $metadata['is_main'] = false;
            $mediaFile->metadata = $metadata;
            $mediaFile->save();
        }

        // Сбрасываем в legacy таблице
        $this->photoRepository->resetMainFlag($masterProfileId);
    }

    /**
     * Обновить порядок сортировки (унифицированный метод)
     */
    public function updateSortOrders(array $mediaOrders): void
    {
        DB::transaction(function () use ($mediaOrders) {
            foreach ($mediaOrders as $mediaId => $order) {
                // Пробуем обновить в новой таблице media
                $updated = $this->model->where('id', $mediaId)
                    ->update(['sort_order' => $order]);
                
                // Если не нашли в media, пробуем в legacy таблицах
                if (!$updated) {
                    // Пробуем в photos
                    Photo::where('id', $mediaId)
                        ->update(['sort_order' => $order]);
                    
                    // Пробуем в videos
                    Video::where('id', $mediaId)
                        ->update(['sort_order' => $order]);
                }
            }
        });
    }

    /**
     * Найти медиафайл по имени файла (унифицированный метод)
     */
    public function findByFilename(string $filename, int $masterProfileId): ?object
    {
        // Ищем в новой таблице media
        $media = $this->model->where('mediable_type', 'App\\Domain\\Master\\Models\\MasterProfile')
            ->where('mediable_id', $masterProfileId)
            ->where('file_name', 'like', "%{$filename}")
            ->first();

        if ($media) {
            return $media;
        }

        // Ищем в legacy таблицах
        $photo = $this->photoRepository->findByFilename($filename, $masterProfileId);
        if ($photo) {
            return $photo;
        }

        return $this->videoRepository->findByFilename($filename, $masterProfileId);
    }
}