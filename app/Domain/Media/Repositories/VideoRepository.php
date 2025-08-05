<?php

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Models\Video;
use App\Support\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Репозиторий для работы с видео
 */
class VideoRepository extends BaseRepository
{
    public function __construct(Video $model)
    {
        parent::__construct($model);
    }

    /**
     * Создать новое видео
     */
    public function createVideo(array $data): Video
    {
        return $this->create($data);
    }

    /**
     * Найти видео мастера
     */
    public function findByMasterProfileId(int $masterProfileId): Collection
    {
        return $this->model->where('master_profile_id', $masterProfileId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Найти видео по имени файла
     */
    public function findByFilename(string $filename, int $masterProfileId): ?Video
    {
        return $this->model->where('filename', $filename)
            ->where('master_profile_id', $masterProfileId)
            ->first();
    }

    /**
     * Удалить видео по ID с файлами
     */
    public function deleteVideo(int $videoId, int $masterProfileId): bool
    {
        return DB::transaction(function () use ($videoId, $masterProfileId) {
            // Находим видео
            $video = $this->model->where('id', $videoId)
                ->where('master_profile_id', $masterProfileId)
                ->first();
            
            if (!$video) {
                return false;
            }
            
            // Удаляем физические файлы
            $this->deleteVideoFiles($video);
            
            // Удаляем запись из БД
            return $video->delete();
        });
    }

    /**
     * Удалить физические файлы видео
     */
    private function deleteVideoFiles(Video $video): void
    {
        $disk = Storage::disk('masters_private');
        $folderName = $video->masterProfile->folder_name;
        
        // Список всех файлов для удаления
        $filesToDelete = [
            "{$folderName}/video/{$video->filename}",  // Оригинальное видео
        ];
        
        // Добавляем постер если есть
        if (!empty($video->poster_filename)) {
            $filesToDelete[] = "{$folderName}/video/{$video->poster_filename}";
        }
        
        // Добавляем конвертированное видео если есть
        if (!empty($video->converted_path)) {
            $filesToDelete[] = $video->converted_path;
        }
        
        foreach ($filesToDelete as $filePath) {
            if ($disk->exists($filePath)) {
                $disk->delete($filePath);
            }
        }
    }

    /**
     * Подсчитать количество видео мастера
     */
    public function countByMasterProfileId(int $masterProfileId): int
    {
        return $this->model->where('master_profile_id', $masterProfileId)->count();
    }

    /**
     * Получить общий размер видео мастера в байтах
     */
    public function getTotalSizeByMasterProfileId(int $masterProfileId): int
    {
        return $this->model->where('master_profile_id', $masterProfileId)
            ->sum('size') ?? 0;
    }

    /**
     * Найти активные видео мастера
     */
    public function findActiveByMasterProfileId(int $masterProfileId): Collection
    {
        return $this->model->where('master_profile_id', $masterProfileId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Обновить статус видео
     */
    public function updateStatus(int $videoId, int $masterProfileId, bool $isActive): bool
    {
        return $this->model->where('id', $videoId)
            ->where('master_profile_id', $masterProfileId)
            ->update(['is_active' => $isActive]) > 0;
    }

    /**
     * Обновить информацию о конвертации
     */
    public function updateConversionInfo(int $videoId, array $conversionData): bool
    {
        return $this->model->where('id', $videoId)
            ->update([
                'is_converted' => $conversionData['is_converted'] ?? true,
                'converted_path' => $conversionData['converted_path'] ?? null,
                'conversion_error' => $conversionData['conversion_error'] ?? null,
                'converted_at' => $conversionData['converted_at'] ?? now(),
            ]) > 0;
    }

    /**
     * Найти видео для конвертации
     */
    public function findVideosForConversion(int $limit = 10): Collection
    {
        return $this->model->where('is_converted', false)
            ->whereNull('conversion_error')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }
}