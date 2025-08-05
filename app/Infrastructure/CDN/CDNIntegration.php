<?php

namespace App\Infrastructure\CDN;

use App\Domain\Media\Traits\MediaTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * CDN интеграция для Media моделей
 * Расширяет MediaTrait функциональностью CDN
 */
class CDNIntegration
{
    private CDNService $cdnService;

    public function __construct(CDNService $cdnService)
    {
        $this->cdnService = $cdnService;
    }

    /**
     * Интеграция с MediaTrait - получить CDN URL
     */
    public function getCDNUrl(object $mediaModel): string
    {
        // Если модель использует MediaTrait
        if (in_array(MediaTrait::class, class_uses_recursive($mediaModel))) {
            $filePath = $mediaModel->getFilePath();
            return $this->cdnService->getUrl($filePath);
        }

        return '';
    }

    /**
     * Интеграция с MediaTrait - получить оптимизированный URL изображения
     */
    public function getOptimizedImageUrl(object $mediaModel, array $transformations = []): string
    {
        if (!$mediaModel->isImage()) {
            return $this->getCDNUrl($mediaModel);
        }

        $filePath = $mediaModel->getFilePath();
        return $this->cdnService->getOptimizedImageUrl($filePath, $transformations);
    }

    /**
     * Загрузить медиафайл на CDN при создании
     */
    public function uploadToCDN(UploadedFile $file, string $context = 'media'): array
    {
        try {
            return $this->cdnService->upload($file, $context);
        } catch (\Exception $e) {
            Log::error('CDN upload failed, falling back to local', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);
            
            // Fallback к локальному хранению
            $path = $file->store('public/' . $context);
            return [
                'path' => $path,
                'url' => \Illuminate\Support\Facades\Storage::url($path),
                'provider' => 'local'
            ];
        }
    }

    /**
     * Удалить файл с CDN при удалении модели
     */
    public function deleteFromCDN(object $mediaModel): bool
    {
        if (in_array(MediaTrait::class, class_uses_recursive($mediaModel))) {
            // Получаем все пути файлов для удаления
            $filePaths = $mediaModel->getAllFilePaths();
            
            $success = true;
            foreach ($filePaths as $path) {
                if (!$this->cdnService->delete($path)) {
                    $success = false;
                }
            }
            
            return $success;
        }

        return false;
    }

    /**
     * Инвалидировать кеш CDN для модели
     */
    public function invalidateCache(object $mediaModel): bool
    {
        if (in_array(MediaTrait::class, class_uses_recursive($mediaModel))) {
            $filePaths = $mediaModel->getAllFilePaths();
            
            $success = true;
            foreach ($filePaths as $path) {
                if (!$this->cdnService->invalidate($path)) {
                    $success = false;
                }
            }
            
            return $success;
        }

        return false;
    }

    /**
     * Получить предустановленные размеры изображений
     */
    public function getPresetImageSizes(object $mediaModel): array
    {
        if (!$mediaModel->isImage()) {
            return [];
        }

        $basePath = $mediaModel->getFilePath();
        $sizes = config('cdn.image_optimization.sizes', []);
        $urls = [];

        foreach ($sizes as $sizeName => $dimensions) {
            $urls[$sizeName] = $this->cdnService->getOptimizedImageUrl($basePath, $dimensions);
        }

        return $urls;
    }

    /**
     * Получить адаптивный набор изображений для responsive design
     */
    public function getResponsiveImageSet(object $mediaModel): array
    {
        if (!$mediaModel->isImage()) {
            return [];
        }

        $basePath = $mediaModel->getFilePath();
        
        return [
            'srcset' => [
                $this->cdnService->getOptimizedImageUrl($basePath, ['width' => 320]) . ' 320w',
                $this->cdnService->getOptimizedImageUrl($basePath, ['width' => 640]) . ' 640w',
                $this->cdnService->getOptimizedImageUrl($basePath, ['width' => 768]) . ' 768w',
                $this->cdnService->getOptimizedImageUrl($basePath, ['width' => 1024]) . ' 1024w',
                $this->cdnService->getOptimizedImageUrl($basePath, ['width' => 1200]) . ' 1200w',
            ],
            'sizes' => '(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 33vw',
            'src' => $this->cdnService->getOptimizedImageUrl($basePath, ['width' => 640]),
        ];
    }

    /**
     * Проверить доступность CDN
     */
    public function isAvailable(): bool
    {
        return config('cdn.enabled', false);
    }

    /**
     * Получить статистику CDN
     */
    public function getStats(): array
    {
        return $this->cdnService->getStats();
    }
}