<?php

namespace App\Infrastructure\Media\Services;

use App\Domain\Media\Models\Media;

/**
 * Сервис извлечения метаданных из медиафайлов
 */
class MediaMetadataService
{
    /**
     * Извлечение метаданных изображения
     */
    public function extractImageMetadata(Media $media, $image): void
    {
        $metadata = $media->getMetadata();
        $metadata['dimensions'] = [
            'width' => $image->width(),
            'height' => $image->height()
        ];
        
        $media->updateMetadata($metadata);
    }

    /**
     * Извлечение метаданных видео
     */
    public function extractVideoMetadata(Media $media): void
    {
        $videoInfo = $this->getVideoInfo($media->full_path);
        
        $metadata = $media->getMetadata();
        $metadata = array_merge($metadata, $videoInfo);
        $media->updateMetadata($metadata);
    }

    /**
     * Извлечение метаданных аудио
     */
    public function extractAudioMetadata(Media $media): void
    {
        $audioInfo = $this->getAudioInfo($media->full_path);
        
        $metadata = $media->getMetadata();
        $metadata = array_merge($metadata, $audioInfo);
        $media->updateMetadata($metadata);
    }

    /**
     * Извлечение метаданных документа
     */
    public function extractDocumentMetadata(Media $media): void
    {
        $metadata = $media->getMetadata();
        $metadata['file_size_readable'] = $media->human_readable_size;
        $media->updateMetadata($metadata);
    }

    /**
     * Получение информации о видео
     */
    private function getVideoInfo(string $path): array
    {
        $command = sprintf('ffprobe -v quiet -print_format json -show_format -show_streams "%s"', $path);
        $output = shell_exec($command);
        
        if (!$output) {
            return [];
        }

        $info = json_decode($output, true);
        
        $videoStream = collect($info['streams'] ?? [])
            ->first(fn($stream) => $stream['codec_type'] === 'video');

        return [
            'duration' => $info['format']['duration'] ?? null,
            'bitrate' => $info['format']['bit_rate'] ?? null,
            'dimensions' => $videoStream ? [
                'width' => $videoStream['width'] ?? null,
                'height' => $videoStream['height'] ?? null,
            ] : null,
            'codec' => $videoStream['codec_name'] ?? null,
        ];
    }

    /**
     * Получение информации об аудио
     */
    private function getAudioInfo(string $path): array
    {
        $command = sprintf('ffprobe -v quiet -print_format json -show_format -show_streams "%s"', $path);
        $output = shell_exec($command);
        
        if (!$output) {
            return [];
        }

        $info = json_decode($output, true);
        
        $audioStream = collect($info['streams'] ?? [])
            ->first(fn($stream) => $stream['codec_type'] === 'audio');

        return [
            'duration' => $info['format']['duration'] ?? null,
            'bitrate' => $info['format']['bit_rate'] ?? null,
            'codec' => $audioStream['codec_name'] ?? null,
            'sample_rate' => $audioStream['sample_rate'] ?? null,
            'channels' => $audioStream['channels'] ?? null,
        ];
    }
}