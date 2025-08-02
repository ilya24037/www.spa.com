<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Media\Models\Photo;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AddMasterPhotos extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'master:add-photos {master_id} {--photos=*} {--main}';

    /**
     * The console command description.
     */
    protected $description = 'Добавить фотографии для мастера';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $masterId = $this->argument('master_id');
        $photoPaths = $this->option('photos');
        $isMain = $this->option('main');

        // Проверяем, что мастер существует
        $master = MasterProfile::find($masterId);
        if (!$master) {
            $this->error("❌ Мастер с ID {$masterId} не найден");
            return 1;
        }

        $this->info("📸 Добавляем фотографии для мастера: {$master->display_name}");

        // Если фотографии не указаны, используем тестовые для Елены (ID 3)
        if (empty($photoPaths) && $masterId == 3) {
            $photoPaths = [
                'public/images/masters/elena1.jpg',
                'public/images/masters/elena2.jpg',
                'public/images/masters/elena3.jpg',
                'public/images/masters/elena4.jpg',
                'public/images/masters/elena5.jpg',
                'public/images/masters/elena6.jpg'
            ];
            $this->info("📁 Используем тестовые фотографии для Елены");
        }

        if (empty($photoPaths)) {
            $this->error("❌ Не указаны пути к фотографиям");
            $this->info("💡 Использование: php artisan master:add-photos 3 --photos=path1.jpg --photos=path2.jpg --main");
            return 1;
        }

        // Создаем папку если не существует
        $folderName = $master->folder_name;
        $masterDir = storage_path("app/private/masters/{$folderName}");
        $photosDir = "{$masterDir}/photos";

        if (!is_dir($photosDir)) {
            mkdir($photosDir, 0755, true);
            $this->info("📁 Создана папка: {$photosDir}");
        }

        $uploadedCount = 0;
        $currentOrder = $master->photos()->max('sort_order') ?? 0;

        foreach ($photoPaths as $photoPath) {
            if (!file_exists($photoPath)) {
                $this->error("❌ Файл не найден: {$photoPath}");
                continue;
            }

            try {
                // Генерируем уникальное имя файла
                $extension = pathinfo($photoPath, PATHINFO_EXTENSION);
                $filename = 'photo_' . (time() + $uploadedCount) . '.' . $extension;
                $targetPath = "{$photosDir}/{$filename}";

                // Копируем оригинал
                if (!copy($photoPath, $targetPath)) {
                    $this->error("❌ Ошибка копирования файла: {$photoPath}");
                    continue;
                }

                // Получаем размеры изображения
                $imageInfo = getimagesize($photoPath);
                $width = $imageInfo[0];
                $height = $imageInfo[1];
                $mimeType = $imageInfo['mime'];
                $fileSize = filesize($photoPath);

                // Создаем миниатюру и средний размер (если доступна библиотека Intervention)
                $this->createImageVersions($photoPath, $photosDir, $filename);

                // Добавляем запись в базу данных
                $currentOrder++;
                MasterPhoto::create([
                    'master_profile_id' => $masterId,
                    'filename' => $filename,
                    'mime_type' => $mimeType,
                    'file_size' => $fileSize,
                    'width' => $width,
                    'height' => $height,
                    'is_main' => $isMain && $uploadedCount === 0,
                    'sort_order' => $currentOrder,
                    'is_approved' => true,
                ]);

                $this->info("✅ Загружено: {$filename} (размер: {$width}x{$height}, {$fileSize} байт)");
                $uploadedCount++;

            } catch (\Exception $e) {
                $this->error("❌ Ошибка обработки {$photoPath}: " . $e->getMessage());
            }
        }

        $this->info("📊 Всего загружено: {$uploadedCount} фотографий");
        
        if ($uploadedCount > 0) {
            $this->info("✅ Готово! Проверьте результат на странице мастера:");
            $this->info("🌐 http://127.0.0.1:8000/masters/{$master->slug}-{$master->id}");
        }

        return 0;
    }

    /**
     * Создать версии изображения (миниатюра и средний размер)
     */
    private function createImageVersions($sourcePath, $photosDir, $filename)
    {
        try {
            // Проверяем, доступна ли библиотека Intervention Image
            if (class_exists(ImageManager::class)) {
                $imageManager = new ImageManager(new Driver());

                // Создаем миниатюру (300x300)
                $thumbFilename = 'thumb_' . $filename;
                $thumbPath = "{$photosDir}/{$thumbFilename}";
                
                $image = $imageManager->read($sourcePath);
                $image->cover(300, 300);
                $image->save($thumbPath);
                
                $this->info("✅ Создана миниатюра: {$thumbFilename}");

                // Создаем средний размер (800px)
                $mediumFilename = 'medium_' . $filename;
                $mediumPath = "{$photosDir}/{$mediumFilename}";
                
                $image = $imageManager->read($sourcePath);
                $image->scale(width: 800, height: 800);
                $image->save($mediumPath);
                
                $this->info("✅ Создан средний размер: {$mediumFilename}");
            } else {
                // Используем стандартные PHP функции
                $this->createThumbnailWithGD($sourcePath, $photosDir, $filename);
            }
        } catch (\Exception $e) {
            $this->warn("⚠️ Не удалось создать версии изображения: " . $e->getMessage());
        }
    }

    /**
     * Создать миниатюру с помощью GD
     */
    private function createThumbnailWithGD($sourcePath, $photosDir, $filename)
    {
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // Создаем изображение из источника
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }

        // Создаем миниатюру 300x300
        $thumbSize = 300;
        $thumbImage = imagecreatetruecolor($thumbSize, $thumbSize);

        // Для PNG сохраняем прозрачность
        if ($mimeType === 'image/png') {
            imagealphablending($thumbImage, false);
            imagesavealpha($thumbImage, true);
        }

        // Вычисляем размеры для обрезки
        $sourceRatio = $sourceWidth / $sourceHeight;
        if ($sourceRatio > 1) {
            // Источник шире
            $newWidth = $sourceHeight;
            $newHeight = $sourceHeight;
            $srcX = ($sourceWidth - $newWidth) / 2;
            $srcY = 0;
        } else {
            // Источник выше
            $newWidth = $sourceWidth;
            $newHeight = $sourceWidth;
            $srcX = 0;
            $srcY = ($sourceHeight - $newHeight) / 2;
        }

        // Копируем и изменяем размер
        imagecopyresampled(
            $thumbImage, $sourceImage,
            0, 0, $srcX, $srcY,
            $thumbSize, $thumbSize, $newWidth, $newHeight
        );

        // Сохраняем миниатюру
        $thumbFilename = 'thumb_' . $filename;
        $thumbPath = "{$photosDir}/{$thumbFilename}";
        
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($thumbImage, $thumbPath, 85);
                break;
            case 'image/png':
                imagepng($thumbImage, $thumbPath, 6);
                break;
            case 'image/gif':
                imagegif($thumbImage, $thumbPath);
                break;
        }

        // Освобождаем память
        imagedestroy($sourceImage);
        imagedestroy($thumbImage);

        $this->info("✅ Создана миниатюра: {$thumbFilename}");
        return true;
    }
} 