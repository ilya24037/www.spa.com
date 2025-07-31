<?php

namespace App\Infrastructure\Media;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;

class ImageProcessor
{
    public function process(Media $media): void
    {
        if (!$media->exists()) {
            throw new \Exception('Файл изображения не найден');
        }

        try {
            $image = Image::make($media->full_path);
            
            $metadata = $media->getMetadata();
            $metadata['dimensions'] = [
                'width' => $image->width(),
                'height' => $image->height(),
                'aspect_ratio' => round($image->width() / $image->height(), 2),
            ];
            
            $metadata['exif'] = $this->extractExifData($image);
            $media->updateMetadata($metadata);

            $this->generateThumbnails($media, $image);
            
            if ($media->type->supportsOptimization()) {
                $this->optimizeImage($media, $image);
            }

            if ($media->type->supportsWatermark()) {
                $this->addWatermark($media, $image);
            }

            Log::info("Image processed successfully", [
                'media_id' => $media->id,
                'dimensions' => $metadata['dimensions']
            ]);

        } catch (\Exception $e) {
            Log::error("Image processing failed", [
                'media_id' => $media->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function generateThumbnails(Media $media, $image): void
    {
        $thumbnailSizes = $media->type->getThumbnailSizes();
        
        foreach ($thumbnailSizes as $name => $size) {
            $this->generateThumbnail($media, $image, $name, $size);
        }
    }

    protected function generateThumbnail(Media $media, $image, string $name, array $size): void
    {
        [$width, $height] = $size;
        
        $thumbnail = clone $image;
        
        $thumbnail->fit($width, $height, function ($constraint) {
            $constraint->upsize();
            $constraint->aspectRatio();
        });

        $thumbnailPath = $this->getThumbnailPath($media, $name);
        $fullThumbnailPath = Storage::disk($media->disk)->path($thumbnailPath);
        
        $directory = dirname($fullThumbnailPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $thumbnail->save($fullThumbnailPath, 85);

        $media->addConversion($name, [
            'file_name' => $thumbnailPath,
            'width' => $thumbnail->width(),
            'height' => $thumbnail->height(),
            'size' => filesize($fullThumbnailPath),
            'created_at' => now()->toISOString(),
        ]);
    }

    protected function optimizeImage(Media $media, $image): void
    {
        $maxWidth = config('media.optimization.max_width', 1920);
        $maxHeight = config('media.optimization.max_height', 1080);
        $quality = config('media.optimization.quality', 85);
        
        $originalSize = $media->size;
        $resized = false;

        if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
            $image->resize($maxWidth, $maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $resized = true;
        }

        if ($resized || $quality < 100) {
            $image->save($media->full_path, $quality);
            
            $newSize = filesize($media->full_path);
            $media->update(['size' => $newSize]);
            
            $compressionRatio = round((1 - $newSize / $originalSize) * 100, 1);
            
            $metadata = $media->getMetadata();
            $metadata['optimization'] = [
                'original_size' => $originalSize,
                'optimized_size' => $newSize,
                'compression_ratio' => $compressionRatio . '%',
                'optimized_at' => now()->toISOString(),
            ];
            $media->updateMetadata($metadata);
            
            Log::info("Image optimized", [
                'media_id' => $media->id,
                'compression_ratio' => $compressionRatio . '%'
            ]);
        }
    }

    protected function addWatermark(Media $media, $image): void
    {
        $watermarkPath = config('media.watermark.path');
        
        if (!$watermarkPath || !file_exists($watermarkPath)) {
            return;
        }

        if ($image->width() < 400 || $image->height() < 400) {
            return;
        }

        try {
            $watermark = Image::make($watermarkPath);
            
            $opacity = config('media.watermark.opacity', 50);
            $position = config('media.watermark.position', 'bottom-right');
            $padding = config('media.watermark.padding', 20);
            
            $watermarkSize = min($image->width(), $image->height()) * 0.15;
            $watermark->resize($watermarkSize, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            
            $watermark->opacity($opacity);
            
            $x = $y = $padding;
            
            switch ($position) {
                case 'top-left':
                    $x = $padding;
                    $y = $padding;
                    break;
                case 'top-right':
                    $x = $image->width() - $watermark->width() - $padding;
                    $y = $padding;
                    break;
                case 'bottom-left':
                    $x = $padding;
                    $y = $image->height() - $watermark->height() - $padding;
                    break;
                case 'bottom-right':
                default:
                    $x = $image->width() - $watermark->width() - $padding;
                    $y = $image->height() - $watermark->height() - $padding;
                    break;
                case 'center':
                    $x = ($image->width() - $watermark->width()) / 2;
                    $y = ($image->height() - $watermark->height()) / 2;
                    break;
            }
            
            $image->insert($watermark, $position, $x, $y);
            $image->save($media->full_path);
            
            $metadata = $media->getMetadata();
            $metadata['watermark'] = [
                'applied' => true,
                'position' => $position,
                'opacity' => $opacity,
                'applied_at' => now()->toISOString(),
            ];
            $media->updateMetadata($metadata);
            
        } catch (\Exception $e) {
            Log::warning("Watermark application failed", [
                'media_id' => $media->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function extractExifData($image): array
    {
        try {
            $exifData = $image->exif();
            
            if (!$exifData) {
                return [];
            }

            return [
                'camera_make' => $exifData['Make'] ?? null,
                'camera_model' => $exifData['Model'] ?? null,
                'datetime' => $exifData['DateTime'] ?? null,
                'orientation' => $exifData['Orientation'] ?? null,
                'x_resolution' => $exifData['XResolution'] ?? null,
                'y_resolution' => $exifData['YResolution'] ?? null,
                'software' => $exifData['Software'] ?? null,
                'exposure_time' => $exifData['ExposureTime'] ?? null,
                'f_number' => $exifData['FNumber'] ?? null,
                'iso' => $exifData['ISOSpeedRatings'] ?? null,
                'focal_length' => $exifData['FocalLength'] ?? null,
                'flash' => $exifData['Flash'] ?? null,
            ];
            
        } catch (\Exception $e) {
            Log::debug("EXIF extraction failed", [
                'media_id' => $this->media->id ?? null,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    protected function getThumbnailPath(Media $media, string $conversion): string
    {
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
        $directory = dirname($media->file_name);
        
        return $directory . '/thumbnails/' . $baseName . '_' . $conversion . '.' . $extension;
    }

    public function generateCropVariations(Media $media, array $crops): void
    {
        if (!$media->isImage()) {
            throw new \InvalidArgumentException('Обрезка доступна только для изображений');
        }

        $image = Image::make($media->full_path);
        
        foreach ($crops as $cropName => $cropData) {
            $this->generateCrop($media, $image, $cropName, $cropData);
        }
    }

    protected function generateCrop(Media $media, $image, string $cropName, array $cropData): void
    {
        $crop = clone $image;
        
        $crop->crop(
            $cropData['width'],
            $cropData['height'],
            $cropData['x'],
            $cropData['y']
        );

        if (isset($cropData['resize'])) {
            $crop->resize($cropData['resize']['width'], $cropData['resize']['height']);
        }

        $cropPath = $this->getCropPath($media, $cropName);
        $crop->save(Storage::disk($media->disk)->path($cropPath));

        $media->addConversion($cropName, [
            'file_name' => $cropPath,
            'width' => $crop->width(),
            'height' => $crop->height(),
            'size' => Storage::disk($media->disk)->size($cropPath),
            'type' => 'crop',
            'crop_data' => $cropData,
            'created_at' => now()->toISOString(),
        ]);
    }

    protected function getCropPath(Media $media, string $cropName): string
    {
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
        $directory = dirname($media->file_name);
        
        return $directory . '/crops/' . $baseName . '_' . $cropName . '.' . $extension;
    }

    public function convertFormat(Media $media, string $newFormat): Media
    {
        if (!$media->isImage()) {
            throw new \InvalidArgumentException('Конвертация формата доступна только для изображений');
        }

        $supportedFormats = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (!in_array(strtolower($newFormat), $supportedFormats)) {
            throw new \InvalidArgumentException('Неподдерживаемый формат: ' . $newFormat);
        }

        $image = Image::make($media->full_path);
        
        $newFileName = pathinfo($media->file_name, PATHINFO_FILENAME) . '_converted.' . $newFormat;
        $newPath = dirname($media->file_name) . '/' . $newFileName;
        
        $quality = $newFormat === 'png' ? null : 85;
        $image->save(Storage::disk($media->disk)->path($newPath), $quality);

        return $media->copy($newPath);
    }
}