<?php

namespace App\Infrastructure\Media\Processors;

use App\Domain\Media\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Генератор миниатюр для видео
 */
class VideoThumbnailGenerator
{
    /**
     * Генерировать миниатюры
     */
    public function generate(Media $media): void
    {
        $thumbnailSizes = $media->type->getThumbnailSizes();
        $duration = $media->getMetadata('duration', 0);
        
        foreach ($thumbnailSizes as $name => $size) {
            $this->generateThumbnail($media, $name, $size, $duration);
        }
    }

    /**
     * Генерировать одну миниатюру
     */
    public function generateThumbnail(Media $media, string $name, array $size, float $duration): void
    {
        [$width, $height] = $size;
        
        $thumbnailPath = $this->getThumbnailPath($media, $name);
        $fullThumbnailPath = Storage::disk($media->disk)->path($thumbnailPath);
        
        $this->ensureDirectoryExists(dirname($fullThumbnailPath));

        $timeOffset = $this->calculateTimeOffset($duration);

        $command = $this->buildThumbnailCommand($media, $fullThumbnailPath, $width, $height, $timeOffset);

        $process = new Process($command);
        $process->setTimeout(60);
        $process->run();

        if ($process->isSuccessful() && file_exists($fullThumbnailPath)) {
            $this->saveThumbnailMetadata($media, $name, $thumbnailPath, $width, $height, $fullThumbnailPath);
        } else {
            Log::warning("Video thumbnail generation failed", [
                'media_id' => $media->id,
                'name' => $name,
                'error' => $process->getErrorOutput()
            ]);
        }
    }

    /**
     * Создать команду для генерации миниатюры
     */
    protected function buildThumbnailCommand(Media $media, string $outputPath, int $width, int $height, string $timeOffset): array
    {
        return [
            'ffmpeg',
            '-i', $media->full_path,
            '-ss', $timeOffset,
            '-vframes', '1',
            '-f', 'image2',
            '-s', $width . 'x' . $height,
            '-q:v', '2',
            '-y',
            $outputPath
        ];
    }

    /**
     * Рассчитать смещение времени для миниатюры
     */
    protected function calculateTimeOffset(float $duration): string
    {
        if ($duration > 30) {
            return '00:00:10.000';
        } elseif ($duration > 10) {
            return '00:00:05.000';
        } else {
            return '00:00:01.000';
        }
    }

    /**
     * Сохранить метаданные миниатюры
     */
    protected function saveThumbnailMetadata(Media $media, string $name, string $path, int $width, int $height, string $fullPath): void
    {
        $media->addConversion($name, [
            'file_name' => $path,
            'width' => $width,
            'height' => $height,
            'size' => filesize($fullPath),
            'format' => 'jpg',
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Получить путь для миниатюры
     */
    protected function getThumbnailPath(Media $media, string $name): string
    {
        $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
        $directory = dirname($media->file_name);
        
        return $directory . '/thumbnails/' . $baseName . '_' . $name . '.jpg';
    }

    /**
     * Убедиться что директория существует
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}