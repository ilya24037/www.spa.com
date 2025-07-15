<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;

class VideoHelper
{
    /**
     * Создать превью видео
     */
    public static function createThumbnail($file, $fileName)
    {
        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
        ]);
        
        $video = $ffmpeg->open($file->getPathname());
        
        // Создаем превью на 1 секунде
        $frame = $video->frame(TimeCode::fromSeconds(1));
        
        $thumbFileName = 'thumb_' . $fileName . '.jpg';
        $thumbPath = $thumbFileName;
        
        $frame->save(storage_path('app/public/masters/thumbnails/' . $thumbPath));
        
        return $thumbPath;
    }

    /**
     * Получить длительность видео
     */
    public static function getDuration($file)
    {
        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
        ]);
        
        $video = $ffmpeg->open($file->getPathname());
        $stream = $video->getStreams()->first();
        
        return (int) $stream->get('duration');
    }
} 