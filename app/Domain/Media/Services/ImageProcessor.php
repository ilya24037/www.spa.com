<?php

namespace App\Domain\Media\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Media\Models\Photo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

/**
 * Сервис для обработки изображений
 * Отвечает за валидацию, оптимизацию и сохранение изображений
 */
class ImageProcessor
{
    private const MAX_PHOTO_SIZE = 10 * 1024 * 1024; // 10MB
    private const AVATAR_SIZE = 400;
    private const AVATAR_THUMB_SIZE = 150;
    
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    private ThumbnailGenerator $thumbnailGenerator;

    public function __construct(ThumbnailGenerator $thumbnailGenerator)
    {
        $this->thumbnailGenerator = $thumbnailGenerator;
    }

    /**
     * Обработать и сохранить фотографию
     */
    public function processPhoto(UploadedFile $file, MasterProfile $master, int $photoNumber): MasterPhoto
    {
        $this->validatePhotoFile($file);
        
        $masterFolder = $master->folder_name;
        $privateDisk = Storage::disk('masters_private');
        
        // Создаем папки если их нет
        $privateDisk->makeDirectory("{$masterFolder}/photos");
        
        // Генерируем имя файла
        $filename = "photo_{$photoNumber}.jpg";
        
        // Получаем размеры оригинала
        $imageInfo = getimagesize($file->getRealPath());
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        
        // Обрабатываем изображение
        $image = Image::make($file->getRealPath());
        
        // Оптимизируем и сохраняем оригинал
        $this->optimizeAndSaveImage($image, $privateDisk, "{$masterFolder}/photos/{$filename}", 95);
        
        // Создаем размеры
        $this->thumbnailGenerator->generateSizes($image, $privateDisk, $masterFolder, $photoNumber);
        
        // Сохраняем в базу данных
        return MasterPhoto::create([
            'master_profile_id' => $master->id,
            'filename' => $filename,
            'mime_type' => 'image/jpeg',
            'file_size' => $file->getSize(),
            'width' => $originalWidth,
            'height' => $originalHeight,
            'is_main' => $photoNumber === 1,
            'sort_order' => $photoNumber,
            'is_approved' => false
        ]);
    }

    /**
     * Обработать аватар
     */
    public function processAvatar(UploadedFile $file, MasterProfile $master): string
    {
        $this->validatePhotoFile($file);
        
        $masterFolder = $master->folder_name;
        $publicDisk = Storage::disk('masters_public');
        
        // Создаем папку мастера если её нет
        $publicDisk->makeDirectory($masterFolder);
        
        // Обрабатываем аватар
        $image = Image::make($file->getRealPath());
        
        // Основной аватар
        $avatar = clone $image;
        $avatar->fit(self::AVATAR_SIZE, self::AVATAR_SIZE);
        $this->optimizeAndSaveImage($avatar, $publicDisk, "{$masterFolder}/avatar.jpg", 90);
        
        // Миниатюра аватара
        $thumb = clone $image;
        $thumb->fit(self::AVATAR_THUMB_SIZE, self::AVATAR_THUMB_SIZE);
        $this->optimizeAndSaveImage($thumb, $publicDisk, "{$masterFolder}/avatar_thumb.jpg", 85);
        
        return "{$masterFolder}/avatar.jpg";
    }

    /**
     * Удалить фотографию
     */
    public function deletePhoto(MasterPhoto $photo): void
    {
        $masterFolder = $photo->masterProfile->folder_name;
        $privateDisk = Storage::disk('masters_private');
        
        // Удаляем все размеры
        $privateDisk->delete("{$masterFolder}/photos/{$photo->filename}");
        $privateDisk->delete("{$masterFolder}/photos/{$photo->getMediumFilename()}");
        $privateDisk->delete("{$masterFolder}/photos/{$photo->getThumbFilename()}");
        
        // Удаляем запись из БД
        $photo->delete();
    }

    /**
     * Валидация файла изображения
     */
    private function validatePhotoFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_PHOTO_SIZE) {
            throw new \Exception('Файл слишком большой. Максимальный размер: 10MB');
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new \Exception('Неподдерживаемый формат файла. Разрешены: JPEG, PNG, WebP');
        }

        // Проверка на реальное изображение
        $imageInfo = @getimagesize($file->getRealPath());
        if (!$imageInfo) {
            throw new \Exception('Файл не является изображением');
        }

        // Проверка минимального размера
        if ($imageInfo[0] < 400 || $imageInfo[1] < 400) {
            throw new \Exception('Минимальный размер изображения: 400x400px');
        }
    }

    /**
     * Оптимизировать и сохранить изображение
     */
    private function optimizeAndSaveImage($image, $disk, string $path, int $quality): void
    {
        // Удаляем метаданные
        $image->stripImage();
        
        // Конвертируем в RGB если нужно
        if ($image->mime() === 'image/png' || $image->mime() === 'image/webp') {
            $image->limitColors(255);
        }
        
        // Сохраняем
        $image->encode('jpg', $quality);
        $disk->put($path, $image->stream());
    }

    /**
     * Наложить водяной знак
     */
    public function applyWatermark($image, string $watermarkPath): void
    {
        if (!file_exists($watermarkPath)) {
            return;
        }

        $watermark = Image::make($watermarkPath);
        
        // Изменяем размер водяного знака
        $watermark->resize(150, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        
        // Накладываем водяной знак в правый нижний угол
        $image->insert($watermark, 'bottom-right', 20, 20);
    }

    /**
     * Автоматическая коррекция изображения
     */
    public function autoEnhance($image): void
    {
        // Автоматическая коррекция яркости и контраста
        $image->brightness(5);
        $image->contrast(5);
        
        // Легкая резкость
        $image->sharpen(10);
    }

    /**
     * Валидировать файл изображения (публичный метод для использования в MediaService)
     */
    public function validatePhotoFilePublic(UploadedFile $file): void
    {
        $this->validatePhotoFile($file);
    }

    /**
     * Обработать и сохранить изображение в нескольких размерах для объявлений
     */
    public function processAndSaveMultipleSizes(UploadedFile $file, string $filename, string $context = 'ad'): array
    {
        $paths = [];
        $basePath = "public/{$context}s/photos";
        
        // Размеры для объявлений
        $sizes = [
            'thumbnail' => [200, 200],
            'medium' => [600, 600],
            'large' => [1200, 1200],
            'original' => null // Оригинал без изменения размера
        ];
        
        foreach ($sizes as $sizeName => $dimensions) {
            $image = Image::make($file->getRealPath());
            
            if ($dimensions !== null) {
                // Изменяем размер с сохранением пропорций
                $image->fit($dimensions[0], $dimensions[1], function ($constraint) {
                    $constraint->upsize();
                });
            }
            
            // Оптимизация качества
            $quality = $sizeName === 'thumbnail' ? 80 : 90;
            $image->encode('jpg', $quality);
            
            // Путь для сохранения
            $sizePath = "{$basePath}/{$sizeName}/{$filename}";
            
            // Сохраняем
            Storage::put($sizePath, $image->stream());
            
            $paths[$sizeName] = $sizePath;
        }
        
        return $paths;
    }
}