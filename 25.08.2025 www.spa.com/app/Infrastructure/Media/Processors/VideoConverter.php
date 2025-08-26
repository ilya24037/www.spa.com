<?php

namespace App\Infrastructure\Media\Processors;

use App\Domain\Media\Models\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Конвертер видео форматов
 */
class VideoConverter
{
    protected array $supportedFormats = ['mp4', 'webm', 'avi', 'mov', 'mkv'];
    
    protected array $ffmpegFormats = [
        'mp4' => 'libx264',
        'webm' => 'libvpx-vp9',
        'avi' => 'libxvid',
        'mov' => 'libx264',
        'mkv' => 'libx264',
    ];

    /**
     * Конвертировать видео в другой формат
     */
    public function convertFormat(Media $media, string $newFormat): Media
    {
        if (!in_array($newFormat, $this->supportedFormats)) {
            throw new \InvalidArgumentException('Неподдерживаемый формат: ' . $newFormat);
        }

        $codec = $this->ffmpegFormats[$newFormat] ?? 'libx264';
        
        $convertedPath = $this->getConvertedPath($media, $newFormat);
        $fullConvertedPath = Storage::disk($media->disk)->path($convertedPath);

        $command = $this->buildConversionCommand($media->full_path, $fullConvertedPath, $codec);

        $process = new Process($command);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('Video conversion failed: ' . $process->getErrorOutput());
        }

        return $media->copy($convertedPath);
    }

    /**
     * Извлечь клип из видео
     */
    public function extractClip(Media $media, float $startTime, float $duration): Media
    {
        $clipPath = $this->getClipPath($media, $startTime, $duration);
        $fullClipPath = Storage::disk($media->disk)->path($clipPath);

        $command = $this->buildClipCommand($media->full_path, $fullClipPath, $startTime, $duration);

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('Video clip extraction failed: ' . $process->getErrorOutput());
        }

        $clipMedia = $media->copy($clipPath);
        
        $this->saveClipMetadata($clipMedia, $media, $startTime, $duration);

        return $clipMedia;
    }

    /**
     * Создать команду конвертации
     */
    protected function buildConversionCommand(string $inputPath, string $outputPath, string $codec): array
    {
        return [
            'ffmpeg',
            '-i', $inputPath,
            '-c:v', $codec,
            '-c:a', 'aac',
            '-y',
            $outputPath
        ];
    }

    /**
     * Создать команду извлечения клипа
     */
    protected function buildClipCommand(string $inputPath, string $outputPath, float $startTime, float $duration): array
    {
        return [
            'ffmpeg',
            '-i', $inputPath,
            '-ss', $startTime,
            '-t', $duration,
            '-c', 'copy',
            '-y',
            $outputPath
        ];
    }

    /**
     * Сохранить метаданные клипа
     */
    protected function saveClipMetadata(Media $clipMedia, Media $originalMedia, float $startTime, float $duration): void
    {
        $metadata = $clipMedia->getMetadata();
        $metadata['clip'] = [
            'start_time' => $startTime,
            'duration' => $duration,
            'source_media_id' => $originalMedia->id,
            'created_at' => now()->toISOString(),
        ];
        $clipMedia->updateMetadata($metadata);
    }

    /**
     * Получить путь для конвертированного видео
     */
    protected function getConvertedPath(Media $media, string $format): string
    {
        $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
        $directory = dirname($media->file_name);
        
        return $directory . '/' . $baseName . '_converted.' . $format;
    }

    /**
     * Получить путь для клипа
     */
    protected function getClipPath(Media $media, float $startTime, float $duration): string
    {
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
        $directory = dirname($media->file_name);
        
        $startFormatted = str_replace('.', '_', $startTime);
        $durationFormatted = str_replace('.', '_', $duration);
        
        return $directory . '/clips/' . $baseName . '_clip_' . $startFormatted . '_' . $durationFormatted . '.' . $extension;
    }
}