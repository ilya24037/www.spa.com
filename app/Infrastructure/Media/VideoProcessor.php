<?php

namespace App\Infrastructure\Media;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class VideoProcessor
{
    protected array $supportedFormats = ['mp4', 'webm', 'avi', 'mov', 'mkv'];
    protected array $ffmpegFormats = [
        'mp4' => 'libx264',
        'webm' => 'libvpx-vp9',
        'avi' => 'libxvid',
    ];

    public function process(Media $media): void
    {
        if (!$media->exists()) {
            throw new \Exception('Файл видео не найден');
        }

        try {
            $videoInfo = $this->extractVideoInfo($media);
            
            $metadata = $media->getMetadata();
            $metadata = array_merge($metadata, $videoInfo);
            $media->updateMetadata($metadata);

            $this->generateThumbnails($media);
            
            if ($this->shouldOptimize($media, $videoInfo)) {
                $this->optimizeVideo($media);
            }

            if ($media->type->supportsWatermark()) {
                $this->addWatermark($media);
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

    protected function extractVideoInfo(Media $media): array
    {
        $command = [
            'ffprobe',
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            $media->full_path
        ];

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('Failed to extract video info: ' . $process->getErrorOutput());
        }

        $output = $process->getOutput();
        $info = json_decode($output, true);

        if (!$info) {
            throw new \Exception('Invalid ffprobe output');
        }

        $videoStream = collect($info['streams'] ?? [])
            ->first(fn($stream) => $stream['codec_type'] === 'video');

        $audioStream = collect($info['streams'] ?? [])
            ->first(fn($stream) => $stream['codec_type'] === 'audio');

        return [
            'duration' => (float) ($info['format']['duration'] ?? 0),
            'bitrate' => (int) ($info['format']['bit_rate'] ?? 0),
            'format_name' => $info['format']['format_name'] ?? null,
            'dimensions' => $videoStream ? [
                'width' => (int) ($videoStream['width'] ?? 0),
                'height' => (int) ($videoStream['height'] ?? 0),
                'aspect_ratio' => $this->calculateAspectRatio(
                    $videoStream['width'] ?? 0, 
                    $videoStream['height'] ?? 0
                ),
            ] : null,
            'video_codec' => $videoStream['codec_name'] ?? null,
            'video_bitrate' => (int) ($videoStream['bit_rate'] ?? 0),
            'frame_rate' => $this->parseFrameRate($videoStream['r_frame_rate'] ?? '0/1'),
            'audio_codec' => $audioStream['codec_name'] ?? null,
            'audio_bitrate' => (int) ($audioStream['bit_rate'] ?? 0),
            'sample_rate' => (int) ($audioStream['sample_rate'] ?? 0),
            'channels' => (int) ($audioStream['channels'] ?? 0),
        ];
    }

    protected function generateThumbnails(Media $media): void
    {
        $thumbnailSizes = $media->type->getThumbnailSizes();
        $duration = $media->getMetadata('duration', 0);
        
        foreach ($thumbnailSizes as $name => $size) {
            $this->generateVideoThumbnail($media, $name, $size, $duration);
        }
    }

    protected function generateVideoThumbnail(Media $media, string $name, array $size, float $duration): void
    {
        [$width, $height] = $size;
        
        $thumbnailPath = $this->getThumbnailPath($media, $name);
        $fullThumbnailPath = Storage::disk($media->disk)->path($thumbnailPath);
        
        $directory = dirname($fullThumbnailPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $timeOffset = $duration > 10 ? '00:00:05.000' : '00:00:01.000';

        $command = [
            'ffmpeg',
            '-i', $media->full_path,
            '-ss', $timeOffset,
            '-vframes', '1',
            '-f', 'image2',
            '-s', $width . 'x' . $height,
            '-q:v', '2',
            '-y',
            $fullThumbnailPath
        ];

        $process = new Process($command);
        $process->setTimeout(60);
        $process->run();

        if ($process->isSuccessful() && file_exists($fullThumbnailPath)) {
            $media->addConversion($name, [
                'file_name' => $thumbnailPath,
                'width' => $width,
                'height' => $height,
                'size' => filesize($fullThumbnailPath),
                'format' => 'jpg',
                'created_at' => now()->toISOString(),
            ]);
        } else {
            Log::warning("Video thumbnail generation failed", [
                'media_id' => $media->id,
                'name' => $name,
                'error' => $process->getErrorOutput()
            ]);
        }
    }

    protected function shouldOptimize(Media $media, array $videoInfo): bool
    {
        $maxFileSize = config('media.video.max_optimized_size', 100 * 1024 * 1024); // 100MB
        $maxBitrate = config('media.video.max_bitrate', 2000000); // 2Mbps
        $maxDimension = config('media.video.max_dimension', 1920);

        if ($media->size > $maxFileSize) {
            return true;
        }

        if (($videoInfo['bitrate'] ?? 0) > $maxBitrate) {
            return true;
        }

        $dimensions = $videoInfo['dimensions'] ?? [];
        if (($dimensions['width'] ?? 0) > $maxDimension || ($dimensions['height'] ?? 0) > $maxDimension) {
            return true;
        }

        return false;
    }

    protected function optimizeVideo(Media $media): void
    {
        $originalPath = $media->full_path;
        $optimizedPath = $this->getOptimizedPath($media);
        $fullOptimizedPath = Storage::disk($media->disk)->path($optimizedPath);

        $directory = dirname($fullOptimizedPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $command = [
            'ffmpeg',
            '-i', $originalPath,
            '-c:v', 'libx264',
            '-preset', 'medium',
            '-crf', '23',
            '-c:a', 'aac',
            '-b:a', '128k',
            '-movflags', '+faststart',
            '-vf', 'scale=1920:1080:force_original_aspect_ratio=decrease',
            '-y',
            $fullOptimizedPath
        ];

        $process = new Process($command);
        $process->setTimeout(600); // 10 minutes
        $process->run();

        if ($process->isSuccessful() && file_exists($fullOptimizedPath)) {
            $originalSize = $media->size;
            $optimizedSize = filesize($fullOptimizedPath);
            
            Storage::disk($media->disk)->move($optimizedPath, $media->file_name);
            
            $media->update(['size' => $optimizedSize]);
            
            $compressionRatio = round((1 - $optimizedSize / $originalSize) * 100, 1);
            
            $metadata = $media->getMetadata();
            $metadata['optimization'] = [
                'original_size' => $originalSize,
                'optimized_size' => $optimizedSize,
                'compression_ratio' => $compressionRatio . '%',
                'optimized_at' => now()->toISOString(),
            ];
            $media->updateMetadata($metadata);
            
            Log::info("Video optimized", [
                'media_id' => $media->id,
                'compression_ratio' => $compressionRatio . '%'
            ]);
        } else {
            Log::error("Video optimization failed", [
                'media_id' => $media->id,
                'error' => $process->getErrorOutput()
            ]);
            
            if (file_exists($fullOptimizedPath)) {
                unlink($fullOptimizedPath);
            }
        }
    }

    protected function addWatermark(Media $media): void
    {
        $watermarkPath = config('media.watermark.video_path');
        
        if (!$watermarkPath || !file_exists($watermarkPath)) {
            return;
        }

        $watermarkedPath = $this->getWatermarkedPath($media);
        $fullWatermarkedPath = Storage::disk($media->disk)->path($watermarkedPath);

        $directory = dirname($fullWatermarkedPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $position = config('media.watermark.video_position', 'bottom-right');
        $opacity = config('media.watermark.opacity', 0.5);
        
        $overlayFilter = $this->buildWatermarkFilter($position, $opacity);

        $command = [
            'ffmpeg',
            '-i', $media->full_path,
            '-i', $watermarkPath,
            '-filter_complex', $overlayFilter,
            '-c:a', 'copy',
            '-y',
            $fullWatermarkedPath
        ];

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if ($process->isSuccessful() && file_exists($fullWatermarkedPath)) {
            Storage::disk($media->disk)->move($watermarkedPath, $media->file_name);
            
            $metadata = $media->getMetadata();
            $metadata['watermark'] = [
                'applied' => true,
                'position' => $position,
                'opacity' => $opacity,
                'applied_at' => now()->toISOString(),
            ];
            $media->updateMetadata($metadata);
        } else {
            Log::warning("Video watermark application failed", [
                'media_id' => $media->id,
                'error' => $process->getErrorOutput()
            ]);
            
            if (file_exists($fullWatermarkedPath)) {
                unlink($fullWatermarkedPath);
            }
        }
    }

    public function convertFormat(Media $media, string $newFormat): Media
    {
        if (!in_array($newFormat, $this->supportedFormats)) {
            throw new \InvalidArgumentException('Неподдерживаемый формат: ' . $newFormat);
        }

        $codec = $this->ffmpegFormats[$newFormat] ?? 'libx264';
        
        $convertedPath = $this->getConvertedPath($media, $newFormat);
        $fullConvertedPath = Storage::disk($media->disk)->path($convertedPath);

        $command = [
            'ffmpeg',
            '-i', $media->full_path,
            '-c:v', $codec,
            '-c:a', 'aac',
            '-y',
            $fullConvertedPath
        ];

        $process = new Process($command);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('Video conversion failed: ' . $process->getErrorOutput());
        }

        return $media->copy($convertedPath);
    }

    public function extractClip(Media $media, float $startTime, float $duration): Media
    {
        $clipPath = $this->getClipPath($media, $startTime, $duration);
        $fullClipPath = Storage::disk($media->disk)->path($clipPath);

        $command = [
            'ffmpeg',
            '-i', $media->full_path,
            '-ss', $startTime,
            '-t', $duration,
            '-c', 'copy',
            '-y',
            $fullClipPath
        ];

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('Video clip extraction failed: ' . $process->getErrorOutput());
        }

        $clipMedia = $media->copy($clipPath);
        
        $metadata = $clipMedia->getMetadata();
        $metadata['clip'] = [
            'start_time' => $startTime,
            'duration' => $duration,
            'source_media_id' => $media->id,
            'created_at' => now()->toISOString(),
        ];
        $clipMedia->updateMetadata($metadata);

        return $clipMedia;
    }

    protected function calculateAspectRatio(int $width, int $height): ?string
    {
        if ($width <= 0 || $height <= 0) {
            return null;
        }

        $gcd = $this->gcd($width, $height);
        $aspectWidth = $width / $gcd;
        $aspectHeight = $height / $gcd;

        return $aspectWidth . ':' . $aspectHeight;
    }

    protected function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : $this->gcd($b, $a % $b);
    }

    protected function parseFrameRate(string $frameRate): float
    {
        if (strpos($frameRate, '/') !== false) {
            [$numerator, $denominator] = explode('/', $frameRate);
            return $denominator > 0 ? (float) $numerator / (float) $denominator : 0;
        }
        
        return (float) $frameRate;
    }

    protected function getThumbnailPath(Media $media, string $name): string
    {
        $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
        $directory = dirname($media->file_name);
        
        return $directory . '/thumbnails/' . $baseName . '_' . $name . '.jpg';
    }

    protected function getOptimizedPath(Media $media): string
    {
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
        $directory = dirname($media->file_name);
        
        return $directory . '/' . $baseName . '_optimized.' . $extension;
    }

    protected function getWatermarkedPath(Media $media): string
    {
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
        $directory = dirname($media->file_name);
        
        return $directory . '/' . $baseName . '_watermarked.' . $extension;
    }

    protected function getConvertedPath(Media $media, string $format): string
    {
        $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
        $directory = dirname($media->file_name);
        
        return $directory . '/' . $baseName . '_converted.' . $format;
    }

    protected function getClipPath(Media $media, float $startTime, float $duration): string
    {
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
        $directory = dirname($media->file_name);
        
        $startFormatted = str_replace('.', '_', $startTime);
        $durationFormatted = str_replace('.', '_', $duration);
        
        return $directory . '/clips/' . $baseName . '_clip_' . $startFormatted . '_' . $durationFormatted . '.' . $extension;
    }

    protected function buildWatermarkFilter(string $position, float $opacity): string
    {
        $positions = [
            'top-left' => '10:10',
            'top-right' => 'W-w-10:10',
            'bottom-left' => '10:H-h-10',
            'bottom-right' => 'W-w-10:H-h-10',
            'center' => '(W-w)/2:(H-h)/2',
        ];

        $pos = $positions[$position] ?? $positions['bottom-right'];
        
        return "[1:v]format=rgba,colorchannelmixer=aa={$opacity}[watermark];[0:v][watermark]overlay={$pos}";
    }
}