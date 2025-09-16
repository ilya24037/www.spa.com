<?php

namespace App\Domain\Media\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Media\Models\Video as MasterVideo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

/**
 * Сервис для обработки видео
 * Отвечает за валидацию, конвертацию и сохранение видео
 */
class VideoProcessor
{
    private \App\Domain\Media\Repositories\VideoRepository $videoRepository;
    
    public function __construct(\App\Domain\Media\Repositories\VideoRepository $videoRepository)
    {
        $this->videoRepository = $videoRepository;
    }
    private const MAX_VIDEO_SIZE = 50 * 1024 * 1024; // 50MB
    private const MIN_VIDEO_DURATION = 5; // 5 секунд
    private const MAX_VIDEO_DURATION = 60; // 60 секунд
    private const MAX_VIDEO_COUNT = 3; // Максимум 3 видео
    
    private const ALLOWED_MIME_TYPES = [
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/avi',
        'video/quicktime',  // MOV
        'video/x-msvideo',
        'video/x-ms-wmv'
    ];

    /**
     * Обработать и сохранить видео
     */
    public function processVideo(UploadedFile $file, MasterProfile $master): MasterVideo
    {
        $this->validateVideoFile($file);
        
        $masterFolder = $master->folder_name;
        $privateDisk = Storage::disk('masters_private');
        
        // Создаем папку если её нет
        $privateDisk->makeDirectory("{$masterFolder}/video");
        
        // Генерируем имя файла
        $filename = $this->generateVideoFilename();
        
        // Сохраняем оригинальное видео
        $privateDisk->put(
            "{$masterFolder}/video/{$filename}",
            file_get_contents($file->getRealPath())
        );
        
        // Генерируем постер
        $posterFilename = $this->generatePosterFilename($filename);
        $this->generateVideoPoster($privateDisk, $masterFolder, $posterFilename);
        
        // Получаем метаданные видео
        $videoInfo = $this->getVideoInfo($file);

        // Сохраняем в базу данных через репозиторий
        return $this->videoRepository->createVideo([
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
     * Удалить видео
     */
    public function deleteVideo(MasterVideo $video): void
    {
        $masterFolder = $video->masterProfile->folder_name;
        $privateDisk = Storage::disk('masters_private');

        // Удаляем файлы
        $privateDisk->delete("{$masterFolder}/video/{$video->filename}");

        if ($video->poster_filename) {
            $privateDisk->delete("{$masterFolder}/video/{$video->poster_filename}");
        }
        
        // Удаляем запись из БД через репозиторий
        $this->videoRepository->deleteVideo($video->id, $video->master_profile_id);
    }

    /**
     * Валидация видео файла
     */
    private function validateVideoFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_VIDEO_SIZE) {
            throw new \Exception('Файл слишком большой. Максимальный размер: 50MB');
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new \Exception(
                'Неподдерживаемый формат файла. Разрешены: MP4, MOV, AVI, WebM, OGG'
            );
        }
        
        // Проверка длительности видео
        $videoInfo = $this->getVideoInfo($file);
        $duration = $videoInfo['duration'] ?? 0;
        
        if ($duration < self::MIN_VIDEO_DURATION) {
            throw new \Exception('Видео слишком короткое. Минимальная длительность: 5 секунд');
        }
        
        if ($duration > self::MAX_VIDEO_DURATION) {
            throw new \Exception('Видео слишком длинное. Максимальная длительность: 60 секунд');
        }
    }

    /**
     * Генерировать постер для видео
     */
    private function generateVideoPoster($disk, string $masterFolder, string $posterFilename): void
    {
        // Проверяем наличие заглушки
        $placeholderPath = public_path('images/video-placeholder.jpg');
        
        if (file_exists($placeholderPath)) {
            $disk->put(
                "{$masterFolder}/video/{$posterFilename}",
                file_get_contents($placeholderPath)
            );
        } else {
            // Создаем простой постер
            $this->createDefaultPoster($disk, $masterFolder, $posterFilename);
        }
    }

    /**
     * Создать дефолтный постер
     */
    private function createDefaultPoster($disk, string $masterFolder, string $posterFilename): void
    {
        $image = Image::canvas(800, 600, '#1a1a1a');
        
        // Добавляем иконку play
        $image->circle(120, 400, 300, function ($draw) {
            $draw->background('#ffffff20');
            $draw->border(3, '#ffffff40');
        });
        
        // Треугольник play
        $image->polygon([360, 280, 440, 300, 360, 320], function ($draw) {
            $draw->background('#ffffff80');
        });
        
        $image->encode('jpg', 85);
        $disk->put("{$masterFolder}/video/{$posterFilename}", $image->stream());
    }

    /**
     * Получить информацию о видео
     */
    private function getVideoInfo(UploadedFile $file): array
    {
        // Пытаемся получить информацию через getID3
        if (class_exists('\getID3')) {
            try {
                $getID3 = new \getID3();
                $fileInfo = $getID3->analyze($file->getRealPath());
                
                return [
                    'duration' => $fileInfo['playtime_seconds'] ?? 30,
                    'width' => $fileInfo['video']['resolution_x'] ?? 1920,
                    'height' => $fileInfo['video']['resolution_y'] ?? 1080,
                    'bitrate' => $fileInfo['bitrate'] ?? null,
                    'framerate' => $fileInfo['video']['frame_rate'] ?? null,
                ];
            } catch (\Exception $e) {
                // Игнорируем ошибки
            }
        }

        // Возвращаем дефолтные значения
        return [
            'duration' => 30,
            'width' => 1920,
            'height' => 1080
        ];
    }

    /**
     * Генерировать имя файла для видео
     */
    private function generateVideoFilename(): string
    {
        return 'video_' . time() . '_' . uniqid() . '.mp4';
    }

    /**
     * Генерировать имя файла для постера
     */
    private function generatePosterFilename(string $videoFilename): string
    {
        return str_replace('.mp4', '_poster.jpg', $videoFilename);
    }

    /**
     * Проверить поддержку FFmpeg
     */
    public function isFFmpegAvailable(): bool
    {
        $output = shell_exec('ffmpeg -version 2>&1');
        return strpos($output, 'ffmpeg version') !== false;
    }

    /**
     * Извлечь кадр из видео (требует FFmpeg)
     */
    public function extractVideoFrame(string $videoPath, string $outputPath, int $second = 5): bool
    {
        if (!$this->isFFmpegAvailable()) {
            return false;
        }

        $command = sprintf(
            'ffmpeg -i %s -ss %d -vframes 1 -q:v 2 %s 2>&1',
            escapeshellarg($videoPath),
            $second,
            escapeshellarg($outputPath)
        );

        exec($command, $output, $returnCode);

        return $returnCode === 0;
    }
}