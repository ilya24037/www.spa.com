<?php

namespace App\Infrastructure\Media\Processors;

use App\Domain\Media\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Добавление водяного знака на видео
 */
class VideoWatermarker
{
    protected array $positions = [
        'top-left' => '10:10',
        'top-right' => 'W-w-10:10',
        'bottom-left' => '10:H-h-10',
        'bottom-right' => 'W-w-10:H-h-10',
        'center' => '(W-w)/2:(H-h)/2',
    ];

    /**
     * Добавить водяной знак
     */
    public function addWatermark(Media $media): void
    {
        $watermarkPath = config('media.watermark.video_path');
        
        if (!$watermarkPath || !file_exists($watermarkPath)) {
            return;
        }

        $watermarkedPath = $this->getWatermarkedPath($media);
        $fullWatermarkedPath = Storage::disk($media->disk)->path($watermarkedPath);

        $this->ensureDirectoryExists(dirname($fullWatermarkedPath));

        $position = config('media.watermark.video_position', 'bottom-right');
        $opacity = config('media.watermark.opacity', 0.5);
        
        $command = $this->buildWatermarkCommand($media->full_path, $watermarkPath, $fullWatermarkedPath, $position, $opacity);

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if ($process->isSuccessful() && file_exists($fullWatermarkedPath)) {
            $this->replaceOriginalWithWatermarked($media, $watermarkedPath, $position, $opacity);
        } else {
            $this->handleWatermarkFailure($media, $fullWatermarkedPath, $process);
        }
    }

    /**
     * Создать команду для добавления водяного знака
     */
    protected function buildWatermarkCommand(string $inputPath, string $watermarkPath, string $outputPath, string $position, float $opacity): array
    {
        $overlayFilter = $this->buildWatermarkFilter($position, $opacity);

        return [
            'ffmpeg',
            '-i', $inputPath,
            '-i', $watermarkPath,
            '-filter_complex', $overlayFilter,
            '-c:a', 'copy',
            '-y',
            $outputPath
        ];
    }

    /**
     * Построить фильтр для водяного знака
     */
    protected function buildWatermarkFilter(string $position, float $opacity): string
    {
        $pos = $this->positions[$position] ?? $this->positions['bottom-right'];
        
        return "[1:v]format=rgba,colorchannelmixer=aa={$opacity}[watermark];[0:v][watermark]overlay={$pos}";
    }

    /**
     * Заменить оригинал версией с водяным знаком
     */
    protected function replaceOriginalWithWatermarked(Media $media, string $watermarkedPath, string $position, float $opacity): void
    {
        Storage::disk($media->disk)->move($watermarkedPath, $media->file_name);
        
        $metadata = $media->getMetadata();
        $metadata['watermark'] = [
            'applied' => true,
            'position' => $position,
            'opacity' => $opacity,
            'applied_at' => now()->toISOString(),
        ];
        $media->updateMetadata($metadata);

        Log::info("Video watermark applied", [
            'media_id' => $media->id,
            'position' => $position
        ]);
    }

    /**
     * Обработать неудачное добавление водяного знака
     */
    protected function handleWatermarkFailure(Media $media, string $fullWatermarkedPath, Process $process): void
    {
        Log::warning("Video watermark application failed", [
            'media_id' => $media->id,
            'error' => $process->getErrorOutput()
        ]);
        
        if (file_exists($fullWatermarkedPath)) {
            unlink($fullWatermarkedPath);
        }
    }

    /**
     * Получить путь для видео с водяным знаком
     */
    protected function getWatermarkedPath(Media $media): string
    {
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
        $directory = dirname($media->file_name);
        
        return $directory . '/' . $baseName . '_watermarked.' . $extension;
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