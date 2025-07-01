<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MasterPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_profile_id',
        'path',
        'is_main',
        'order',
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Профиль мастера
     */
    public function masterProfile()
    {
        return $this->belongsTo(MasterProfile::class);
    }

    /**
     * URL фотографии
     */
    public function getUrlAttribute()
    {
        return Storage::url($this->path);
    }
}