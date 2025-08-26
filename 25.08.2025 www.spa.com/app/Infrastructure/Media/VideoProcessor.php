<?php

namespace App\Infrastructure\Media;

use App\Domain\Media\Models\Media;
use App\Infrastructure\Media\Processors\VideoInfoExtractor;
use App\Infrastructure\Media\Processors\VideoThumbnailGenerator;
use App\Infrastructure\Media\Processors\VideoOptimizer;
use App\Infrastructure\Media\Processors\VideoWatermarker;
use App\Infrastructure\Media\Processors\VideoConverter;
use Illuminate\Support\Facades\Log;

/**
 * Главный процессор видео - координатор
 */
class VideoProcessor
{
    protected VideoInfoExtractor $infoExtractor;
    protected VideoThumbnailGenerator $thumbnailGenerator;
    protected VideoOptimizer $optimizer;
    protected VideoWatermarker $watermarker;
    protected VideoConverter $converter;

    public function __construct()
    {
        $this->infoExtractor = new VideoInfoExtractor();
        $this->thumbnailGenerator = new VideoThumbnailGenerator();
        $this->optimizer = new VideoOptimizer();
        $this->watermarker = new VideoWatermarker();
        $this->converter = new VideoConverter();
    }

    /**
     * Обработать видео
     */
    public function process(Media $media): void
    {
        if (!$media->exists()) {
            throw new \Exception('Файл видео не найден');
        }

        try {
            // Извлечь информацию о видео
            $videoInfo = $this->infoExtractor->extract($media);
            
            // Сохранить метаданные
            $metadata = $media->getMetadata();
            $metadata = array_merge($metadata, $videoInfo);
            $media->updateMetadata($metadata);

            // Генерировать миниатюры
            $this->thumbnailGenerator->generate($media);
            
            // Оптимизировать если необходимо
            if ($this->optimizer->shouldOptimize($media, $videoInfo)) {
                $this->optimizer->optimize($media);
            }

            // Добавить водяной знак если поддерживается
            if ($media->type->supportsWatermark()) {
                $this->watermarker->addWatermark($media);
            }

            Log::info("Video processed successfully", [
                'media_id' => $media->id,
                'duration' => $videoInfo['duration'] ?? null,
                'dimensions' => $videoInfo['dimensions'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error("Video processing failed", [
                'media_id' => $media->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Конвертировать видео в другой формат
     */
    public function convertFormat(Media $media, string $newFormat): Media
    {
        return $this->converter->convertFormat($media, $newFormat);
    }

    /**
     * Извлечь клип из видео
     */
    public function extractClip(Media $media, float $startTime, float $duration): Media
    {
        return $this->converter->extractClip($media, $startTime, $duration);
    }

    /**
     * Генерировать миниатюру в конкретное время
     */
    public function generateThumbnailAtTime(Media $media, float $timeSeconds, string $name = 'custom'): void
    {
        $size = config('media.video.default_thumbnail_size', [640, 360]);
        $this->thumbnailGenerator->generateThumbnail($media, $name, $size, $timeSeconds);
    }

    /**
     * Оптимизировать видео с конкретным профилем
     */
    public function optimizeWithProfile(Media $media, string $profile = 'medium'): void
    {
        $this->optimizer->optimize($media, $profile);
    }

    /**
     * Проверить поддерживается ли формат
     */
    public function isFormatSupported(string $format): bool
    {
        return in_array($format, ['mp4', 'webm', 'avi', 'mov', 'mkv']);
    }

    /**
     * Получить информацию о видео без обработки
     */
    public function getVideoInfo(Media $media): array
    {
        return $this->infoExtractor->extract($media);
    }
}