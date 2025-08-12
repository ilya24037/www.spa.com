<?php

namespace App\Services;

use App\Models\MasterPhoto;
use App\Models\MasterVideo;
use App\Models\MasterProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

class MediaProcessingService
{
    private const MAX_PHOTO_SIZE = 10 * 1024 * 1024; // 10MB для фото
    private const MAX_VIDEO_SIZE = 100 * 1024 * 1024; // 100MB для видео
    
    private const THUMB_SIZE = 300;   // 300x300 для миниатюр
    private const MEDIUM_SIZE = 800;  // 800x800 для средних
    private const AVATAR_SIZE = 400;  // 400x400 для аватара
    private const AVATAR_THUMB_SIZE = 150; // 150x150 для миниатюры аватара

    /**
     * Загрузить и обработать фотографии мастера
     */
    public function uploadPhotos(MasterProfile $master, array $files): array
    {
        $uploadedPhotos = [];
        
        foreach ($files as $index => $file) {
            $photo = $this->processPhoto($file, $master, $index + 1);
            $uploadedPhotos[] = $photo;
        }
        
        return $uploadedPhotos;
    }

    /**
     * Загрузить и обработать видео мастера
     */
    public function uploadVideo(MasterProfile $master, UploadedFile $file): MasterVideo
    {
        return $this->processVideo($file, $master);
    }

    /**
     * Загрузить и обработать аватар мастера
     */
    public function uploadAvatar(MasterProfile $master, UploadedFile $file): bool
    {
        $this->validatePhotoFile($file);
        
        $masterFolder = $master->folder_name;
        $publicDisk = Storage::disk('masters_public');
        
        // Создаем папку мастера если её нет
        $publicDisk->makeDirectory($masterFolder);
        
        // Обрабатываем аватар
        $image = Image::make($file->getRealPath());
        
        // Основной аватар 400x400
        $avatar = clone $image;
        $avatar->fit(self::AVATAR_SIZE, self::AVATAR_SIZE);
        $avatar->encode('jpg', 90);
        $publicDisk->put("{$masterFolder}/avatar.jpg", $avatar->stream());
        
        // Миниатюра аватара 150x150
        $thumb = clone $image;
        $thumb->fit(self::AVATAR_THUMB_SIZE, self::AVATAR_THUMB_SIZE);
        $thumb->encode('jpg', 85);
        $publicDisk->put("{$masterFolder}/avatar_thumb.jpg", $thumb->stream());
        
        return true;
    }

    /**
     * Обработать и сохранить фото
     */
    private function processPhoto(UploadedFile $file, MasterProfile $master, int $photoNumber): MasterPhoto
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
        
        // Сохраняем оригинал
        $original = clone $image;
        $original->encode('jpg', 95);
        $privateDisk->put("{$masterFolder}/photos/{$filename}", $original->stream());
        
        // Создаем средний размер
        $medium = clone $image;
        if ($medium->width() > self::MEDIUM_SIZE || $medium->height() > self::MEDIUM_SIZE) {
            $medium->resize(self::MEDIUM_SIZE, self::MEDIUM_SIZE, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }
        $medium->encode('jpg', 90);
        $mediumFilename = "photo_{$photoNumber}_medium.jpg";
        $privateDisk->put("{$masterFolder}/photos/{$mediumFilename}", $medium->stream());
        
        // Создаем миниатюру
        $thumb = clone $image;
        $thumb->fit(self::THUMB_SIZE, self::THUMB_SIZE);
        $thumb->encode('jpg', 85);
        $thumbFilename = "photo_{$photoNumber}_thumb.jpg";
        $privateDisk->put("{$masterFolder}/photos/{$thumbFilename}", $thumb->stream());
        
        // Сохраняем в базу данных
        return MasterPhoto::create([
            'master_profile_id' => $master->id,
            'filename' => $filename,
            'mime_type' => 'image/jpeg',
            'file_size' => $file->getSize(),
            'width' => $originalWidth,
            'height' => $originalHeight,
            'is_main' => $photoNumber === 1, // Первое фото - главное
            'sort_order' => $photoNumber,
            'is_approved' => false
        ]);
    }

    /**
     * Обработать и сохранить видео
     */
    private function processVideo(UploadedFile $file, MasterProfile $master): MasterVideo
    {
        $this->validateVideoFile($file);
        
        $masterFolder = $master->folder_name;
        $privateDisk = Storage::disk('masters_private');
        
        // Создаем папку если её нет
        $privateDisk->makeDirectory("{$masterFolder}/video");
        
        // Сохраняем оригинальное видео
        $filename = 'intro.mp4';
        $privateDisk->put("{$masterFolder}/video/{$filename}", file_get_contents($file->getRealPath()));
        
        // Генерируем постер (заглушка)
        $posterFilename = 'intro_poster.jpg';
        $this->generateVideoPoster($masterFolder, $posterFilename);
        
        // Получаем метаданные видео
        $videoInfo = $this->getVideoInfo($file);
        
        // Сохраняем в базу данных
        return MasterVideo::create([
            'master_profile_id' => $master->id,
            'filename' => $filename,
            'poster_filename' => $posterFilename,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'duration' => $videoInfo['duration'] ?? 30,
            'width' => $videoInfo['width'] ?? 1920,
            'height' => $videoInfo['height'] ?? 1080,
            'is_main' => true,
            'sort_order' => 1,
            'is_approved' => false,
            'processing_status' => 'completed'
        ]);
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
     * Удалить видео
     */
    public function deleteVideo(MasterVideo $video): void
    {
        $masterFolder = $video->masterProfile->folder_name;
        $privateDisk = Storage::disk('masters_private');
        
        // Удаляем файлы
        $privateDisk->delete("{$masterFolder}/video/{$video->filename}");
        $privateDisk->delete("{$masterFolder}/video/{$video->poster_filename}");
        
        // Удаляем запись из БД
        $video->delete();
    }

    /**
     * Генерировать постер для видео
     */
    private function generateVideoPoster(string $masterFolder, string $posterFilename): void
    {
        $privateDisk = Storage::disk('masters_private');
        
        // Создаем заглушку постера
        $placeholderPath = public_path('images/video-placeholder.jpg');
        if (file_exists($placeholderPath)) {
            $privateDisk->put("{$masterFolder}/video/{$posterFilename}", file_get_contents($placeholderPath));
        } else {
            // Создаем простой постер
            $image = Image::canvas(800, 600, '#f0f0f0');
            $image->text('Видео', 400, 300, function($font) {
                $font->size(48);
                $font->color('#666666');
                $font->align('center');
                $font->valign('middle');
            });
            $image->encode('jpg', 80);
            $privateDisk->put("{$masterFolder}/video/{$posterFilename}", $image->stream());
        }
    }

    /**
     * Получить информацию о видео
     */
    private function getVideoInfo(UploadedFile $file): array
    {
        // Здесь можно добавить библиотеку для работы с видео
        // Пока возвращаем заглушку
        return [
            'duration' => 30,
            'width' => 1920,
            'height' => 1080
        ];
    }

    /**
     * Валидация фото файла
     */
    private function validatePhotoFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_PHOTO_SIZE) {
            throw new \Exception('Файл слишком большой. Максимальный размер: 10MB');
        }

        if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'])) {
            throw new \Exception('Неподдерживаемый формат файла. Разрешены: JPEG, PNG, WebP');
        }
    }

    /**
     * Валидация видео файла
     */
    private function validateVideoFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_VIDEO_SIZE) {
            throw new \Exception('Файл слишком большой. Максимальный размер: 100MB');
        }

        if (!in_array($file->getMimeType(), ['video/mp4', 'video/webm', 'video/avi', 'video/quicktime'])) {
            throw new \Exception('Неподдерживаемый формат файла. Разрешены: MP4, WebM, AVI, MOV');
        }
    }
} 