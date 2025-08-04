<?php

namespace App\Application\Http\Controllers\Media;

use App\Application\Http\Controllers\Controller;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Media\Models\Photo;
use App\Domain\Media\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

/**
 * Контроллер для работы с медиафайлами мастеров
 */
class MasterMediaController extends Controller
{
    /**
     * Показать фото мастера
     */
    public function photo(MasterProfile $master, string $filename)
    {
        $photo = $master->photos()->where('filename', $filename)->first();
        
        if (!$photo) {
            abort(404);
        }
        
        $path = storage_path('app/public/masters/' . $master->id . '/photos/' . $filename);
        
        if (!file_exists($path)) {
            abort(404);
        }
        
        return Response::file($path);
    }
    
    /**
     * Показать видео мастера
     */
    public function video(MasterProfile $master, string $filename)
    {
        $video = $master->videos()->where('filename', $filename)->first();
        
        if (!$video) {
            abort(404);
        }
        
        $path = storage_path('app/public/masters/' . $master->id . '/videos/' . $filename);
        
        if (!file_exists($path)) {
            abort(404);
        }
        
        return Response::file($path);
    }
    
    /**
     * Показать постер видео
     */
    public function videoPoster(MasterProfile $master, string $filename)
    {
        $posterFilename = str_replace('.mp4', '.jpg', $filename);
        $path = storage_path('app/public/masters/' . $master->id . '/videos/posters/' . $posterFilename);
        
        if (!file_exists($path)) {
            // Возвращаем дефолтный постер
            $defaultPath = public_path('images/video-placeholder.jpg');
            if (file_exists($defaultPath)) {
                return Response::file($defaultPath);
            }
            abort(404);
        }
        
        return Response::file($path);
    }
    
    /**
     * Показать аватар мастера
     */
    public function avatar(MasterProfile $master)
    {
        if (!$master->avatar) {
            // Возвращаем дефолтный аватар
            $defaultPath = public_path('images/default-avatar.jpg');
            if (file_exists($defaultPath)) {
                return Response::file($defaultPath);
            }
            abort(404);
        }
        
        $path = storage_path('app/public/' . $master->avatar);
        
        if (!file_exists($path)) {
            abort(404);
        }
        
        return Response::file($path);
    }
    
    /**
     * Показать миниатюру аватара
     */
    public function avatarThumb(MasterProfile $master)
    {
        if (!$master->avatar) {
            $defaultPath = public_path('images/default-avatar-thumb.jpg');
            if (file_exists($defaultPath)) {
                return Response::file($defaultPath);
            }
            abort(404);
        }
        
        // Генерируем путь к миниатюре
        $thumbPath = str_replace('.jpg', '_thumb.jpg', $master->avatar);
        $path = storage_path('app/public/' . $thumbPath);
        
        if (!file_exists($path)) {
            // Возвращаем оригинал если миниатюры нет
            $originalPath = storage_path('app/public/' . $master->avatar);
            if (file_exists($originalPath)) {
                return Response::file($originalPath);
            }
            abort(404);
        }
        
        return Response::file($path);
    }
}