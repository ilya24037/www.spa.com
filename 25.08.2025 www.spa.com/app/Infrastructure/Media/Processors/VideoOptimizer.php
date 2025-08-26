<?php

namespace App\Infrastructure\Media\Processors;

use App\Domain\Media\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Оптимизатор видео
 */
class VideoOptimizer
{
    protected array $optimizationProfiles = [
        'high_quality' => [
            'preset' => 'slow',
            'crf' => '18',
            'bitrate' => '4000k',
        ],
        'medium' => [
            'preset' => 'medium',
            'crf' => '23',
            'bitrate' => '2000k',
        ],
        'low_quality' => [
            'preset' => 'fast',
            'crf' => '28',
            'bitrate' => '1000k',
        ],
    ];

    /**
     * Проверить необходимость оптимизации
     */
    public function shouldOptimize(Media $media, array $videoInfo): bool
    {
        $maxFileSize = config('media.video.max_optimized_size', 100 * 1024 * 1024); // 100MB
        $maxBitrate = config('media.video.max_bitrate', 2000000); // 2Mbps
        $maxDimension = config('media.video.max_dimension', 1920);

        if ($media->size > $maxFileSize) {
            return true;
        }

        if (($videoInfo['bitrate'] ?? 0) > $maxBitrate) {
            return true;
        }

        $dimensions = $videoInfo['dimensions'] ?? [];
        if (($dimensions['width'] ?? 0) > $maxDimension || ($dimensions['height'] ?? 0) > $maxDimension) {
            return true;
        }

        return false;
    }

    /**
     * Оптимизировать видео
     */
    public function optimize(Media $media, string $profile = 'medium'): void
    {
        $settings = $this->optimizationProfiles[$profile] ?? $this->optimizationProfiles['medium'];
        
        $optimizedPath = $this->getOptimizedPath($media);
        $fullOptimizedPath = Storage::disk($media->disk)->path($optimizedPath);

        $this->ensureDirectoryExists(dirname($fullOptimizedPath));

        $command = $this->buildOptimizationCommand($media->full_path, $fullOptimizedPath, $settings);

        $process = new Process($command);
        $process->setTimeout(600); // 10 minutes
        $process->run();

        if ($process->isSuccessful() && file_exists($fullOptimizedPath)) {
            $this->replaceOriginalWithOptimized($media, $optimizedPath, $fullOptimizedPath);
        } else {
            $this->handleOptimizationFailure($media, $fullOptimizedPath, $process);
        }
    }

    /**
     * Создать команду оптимизации
     */
    protected function buildOptimizationCommand(string $inputPath, string $outputPath, array $settings): array
    {
        return [
            'ffmpeg',
            '-i', $inputPath,
            '-c:v', 'libx264',
            '-preset', $settings['preset'],
            '-crf', $settings['crf'],
            '-c:a', 'aac',
            '-b:a', '128k',
            '-movflags', '+faststart',
            '-vf', 'scale=1920:1080:force_original_aspect_ratio=decrease',
            '-y',
            $outputPath
        ];
    }

    /**
     * Заменить оригинал оптимизированной версией
     */
    protected function replaceOriginalWithOptimized(Media $media, string $optimizedPath, string $fullOptimizedPath): void
    {
        $originalSize = $media->size;
        $optimizedSize = filesize($fullOptimizedPath);
        
        Storage::disk($media->disk)->move($optimizedPath, $media->file_name);
        
        $media->update(['size' => $optimizedSize]);
        
        $this->saveOptimizationMetadata($media, $originalSize, $optimizedSize);
    }

    /**
     * Сохранить метаданные оптимизации
     */
    protected function saveOptimizationMetadata(Media $media, int $originalSize, int $optimizedSize): void
    {
        $compressionRatio = round((1 - $optimizedSize / $originalSize) * 100, 1);
        
        $metadata = $media->getMetadata();
        $metadata['optimization'] = [
            'original_size' => $originalSize,
            'optimized_size' => $optimizedSize,
            'compression_ratio' => $compressionRatio . '%',
            'optimized_at' => now()->toISOString(),
        ];
        $media->updateMetadata($metadata);
        
        Log::info("Video optimized", [
            'media_id' => $media->id,
            'compression_ratio' => $compressionRatio . '%'
        ]);
    }

    /**
     * Обработать неудачную оптимизацию
     */
    protected function handleOptimizationFailure(Media $media, string $fullOptimizedPath, Process $process): void
    {
        Log::error("Video optimization failed", [
            'media_id' => $media->id,
            'error' => $process->getErrorOutput()
        ]);
        
        if (file_exists($fullOptimizedPath)) {
            unlink($fullOptimizedPath);
        }
    }

    /**
     * Получить путь для оптимизированного видео
     */
    protected function getOptimizedPath(Media $media): string
    {
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
        $directory = dirname($media->file_name);
        
        return $directory . '/' . $baseName . '_optimized.' . $extension;
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