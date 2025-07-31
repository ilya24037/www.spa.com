<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MasterMediaController extends Controller
{
    /**
     * Отдать фото мастера
     */
    public function photo(string $master, string $filename)
    {
        $path = "{$master}/photos/{$filename}";
        
        if (!Storage::disk('masters_private')->exists($path)) {
            abort(404);
        }

        $file = Storage::disk('masters_private')->get($path);
        $mimeType = Storage::disk('masters_private')->mimeType($path);

        return response($file, 200)->header('Content-Type', $mimeType);
    }

    /**
     * Отдать видео мастера
     */
    public function video(string $master, string $filename)
    {
        $path = "{$master}/video/{$filename}";
        
        if (!Storage::disk('masters_private')->exists($path)) {
            abort(404);
        }

        $file = Storage::disk('masters_private')->get($path);
        $mimeType = Storage::disk('masters_private')->mimeType($path);

        return response($file, 200)->header('Content-Type', $mimeType);
    }

    /**
     * Отдать постер видео
     */
    public function videoPoster(string $master, string $filename)
    {
        $path = "{$master}/video/{$filename}";
        
        if (!Storage::disk('masters_private')->exists($path)) {
            abort(404);
        }

        $file = Storage::disk('masters_private')->get($path);
        $mimeType = Storage::disk('masters_private')->mimeType($path);

        return response($file, 200)->header('Content-Type', $mimeType);
    }

    /**
     * Отдать аватар мастера (публичный)
     */
    public function avatar(string $master)
    {
        $path = "{$master}/avatar.jpg";
        
        if (!Storage::disk('masters_public')->exists($path)) {
            // Возвращаем дефолтный аватар
            return response()->file(public_path('images/default-avatar.jpg'));
        }

        $file = Storage::disk('masters_public')->get($path);
        
        return response($file, 200)->header('Content-Type', 'image/jpeg');
    }

    /**
     * Отдать миниатюру аватара
     */
    public function avatarThumb(string $master)
    {
        $path = "{$master}/avatar_thumb.jpg";
        
        if (!Storage::disk('masters_public')->exists($path)) {
            return response()->file(public_path('images/default-avatar.jpg'));
        }

        $file = Storage::disk('masters_public')->get($path);
        
        return response($file, 200)->header('Content-Type', 'image/jpeg');
    }
} 