<?php

namespace App\Domain\Media\Handlers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Обработчик загрузки медиа файлов
 * Отвечает ТОЛЬКО за процесс загрузки: валидацию, сохранение, метаданные
 */
class UploadHandler
{
    private const MAX_PHOTO_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_VIDEO_SIZE = 100 * 1024 * 1024; // 100MB
    
    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg',
        'image/png', 
        'image/webp'
    ];
    
    private const ALLOWED_VIDEO_TYPES = [
        'video/mp4',
        'video/webm',
        'video/avi'
    ];

    /**
     * Валидировать фото файл
     */
    public function validatePhotoFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw ValidationException::withMessages([
                'file' => 'Загруженный файл поврежден'
            ]);
        }

        if ($file->getSize() > self::MAX_PHOTO_SIZE) {
            throw ValidationException::withMessages([
                'file' => 'Размер файла не должен превышать 10MB'
            ]);
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_IMAGE_TYPES)) {
            throw ValidationException::withMessages([
                'file' => 'Поддерживаются только форматы: JPEG, PNG, WebP'
            ]);
        }

        // Проверяем что это действительно изображение
        $imageInfo = getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            throw ValidationException::withMessages([
                'file' => 'Файл не является корректным изображением'
            ]);
        }
    }

    /**
     * Валидировать видео файл
     */
    public function validateVideoFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw ValidationException::withMessages([
                'file' => 'Загруженный файл поврежден'
            ]);
        }

        if ($file->getSize() > self::MAX_VIDEO_SIZE) {
            throw ValidationException::withMessages([
                'file' => 'Размер видео не должен превышать 100MB'
            ]);
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_VIDEO_TYPES)) {
            throw ValidationException::withMessages([
                'file' => 'Поддерживаются только форматы: MP4, WebM, AVI'
            ]);
        }
    }

    /**
     * Сохранить файл на диск
     */
    public function saveFile(UploadedFile $file, string $disk, string $path): string
    {
        $storage = Storage::disk($disk);
        
        // Создаем директорию если её нет
        $directory = dirname($path);
        if (!$storage->exists($directory)) {
            $storage->makeDirectory($directory);
        }
        
        // Сохраняем файл
        $savedPath = $file->storeAs($directory, basename($path), $disk);
        
        if (!$savedPath) {
            throw new \RuntimeException('Не удалось сохранить файл');
        }
        
        return $savedPath;
    }

    /**
     * Получить метаданные изображения
     */
    public function getImageMetadata(UploadedFile $file): array
    {
        $imageInfo = getimagesize($file->getRealPath());
        
        if ($imageInfo === false) {
            throw new \InvalidArgumentException('Не удалось получить информацию об изображении');
        }
        
        return [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'mime_type' => $imageInfo['mime'],
            'file_size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension()
        ];
    }

    /**
     * Получить метаданные видео (базовые)
     */
    public function getVideoMetadata(UploadedFile $file): array
    {
        return [
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension()
        ];
    }

    /**
     * Проверить доступность диска
     */
    public function validateDisk(string $disk): void
    {
        if (!Storage::disk($disk)->exists('')) {
            throw new \RuntimeException("Диск {$disk} недоступен");
        }
    }

    /**
     * Создать уникальное имя файла
     */
    public function generateUniqueFilename(string $prefix, string $extension): string
    {
        return $prefix . '_' . uniqid() . '_' . time() . '.' . $extension;
    }

    /**
     * Удалить файл с диска
     */
    public function deleteFile(string $disk, string $path): bool
    {
        $storage = Storage::disk($disk);
        
        if ($storage->exists($path)) {
            return $storage->delete($path);
        }
        
        return true; // Файл уже не существует
    }
}