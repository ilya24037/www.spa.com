<?php

namespace App\Domain\Media\Models;

use App\Domain\Master\Models\MasterProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    use HasFactory;

    protected $table = 'master_videos'; // Для совместимости с существующей БД

    protected $fillable = [
        'master_profile_id',
        'filename',           // intro.mp4
        'poster_filename',    // intro_poster.jpg
        'mime_type',
        'file_size',
        'duration',
        'width',
        'height',
        'is_main',
        'sort_order',
        'is_approved',
        'processing_status'
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'is_approved' => 'boolean',
        'sort_order' => 'integer',
        'file_size' => 'integer',
        'duration' => 'integer',
        'width' => 'integer',
        'height' => 'integer'
    ];

    /**
     * Связь с профилем мастера
     */
    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    /**
     * Получить URL видео
     */
    public function getVideoUrlAttribute(): string
    {
        $folderName = $this->masterProfile->folder_name;
        return route('master.video', ['master' => $folderName, 'filename' => $this->filename]);
    }

    /**
     * Получить URL постера
     */
    public function getPosterUrlAttribute(): string
    {
        $folderName = $this->masterProfile->folder_name;
        return route('master.video.poster', ['master' => $folderName, 'filename' => $this->poster_filename]);
    }

    /**
     * Получить физический путь к видео
     */
    public function getVideoPath(): string
    {
        $folderName = $this->masterProfile->folder_name;
        return "{$folderName}/video/{$this->filename}";
    }

    /**
     * Получить физический путь к постеру
     */
    public function getPosterPath(): string
    {
        $folderName = $this->masterProfile->folder_name;
        return "{$folderName}/video/{$this->poster_filename}";
    }

    /**
     * Получить длительность в человекочитаемом формате
     */
    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->duration;
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

}