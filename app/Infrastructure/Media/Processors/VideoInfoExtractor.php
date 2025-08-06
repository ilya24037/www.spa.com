<?php

namespace App\Infrastructure\Media\Processors;

use App\Domain\Media\Models\Media;
use Symfony\Component\Process\Process;

/**
 * Извлечение информации о видео
 */
class VideoInfoExtractor
{
    /**
     * Извлечь информацию о видео
     */
    public function extract(Media $media): array
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

    /**
     * Рассчитать соотношение сторон
     */
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

    /**
     * НОД (наибольший общий делитель)
     */
    protected function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : $this->gcd($b, $a % $b);
    }

    /**
     * Парсинг частоты кадров
     */
    protected function parseFrameRate(string $frameRate): float
    {
        if (strpos($frameRate, '/') !== false) {
            [$numerator, $denominator] = explode('/', $frameRate);
            return $denominator > 0 ? (float) $numerator / (float) $denominator : 0;
        }
        
        return (float) $frameRate;
    }
}