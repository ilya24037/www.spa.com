<?php

namespace App\Domain\Media\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Media\Models\Video as MasterVideo;
use App\Infrastructure\Media\VideoProcessor as InfrastructureVideoProcessor;
use Illuminate\Http\UploadedFile;

/**
 * Адаптер для обратной совместимости
 * Обертка над Infrastructure VideoProcessor для работы с MasterProfile
 */
class VideoProcessor
{
    private InfrastructureVideoProcessor $infrastructureProcessor;

    public function __construct(InfrastructureVideoProcessor $infrastructureProcessor)
    {
        $this->infrastructureProcessor = $infrastructureProcessor;
    }

    /**
     * Обработать и сохранить видео (адаптированный метод)
     */
    public function processVideo(UploadedFile $file, MasterProfile $master): MasterVideo
    {
        // Временная заглушка - нужна доработка для полной совместимости
        throw new \Exception('Method processVideo needs migration to Infrastructure layer');
    }

    /**
     * Удалить видео (адаптированный метод)
     */
    public function deleteVideo(MasterVideo $video): void
    {
        // Временная заглушка - нужна доработка для полной совместимости  
        throw new \Exception('Method deleteVideo needs migration to Infrastructure layer');
    }
}