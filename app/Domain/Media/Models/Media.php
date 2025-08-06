<?php

namespace App\Domain\Media\Models;

use App\Enums\MediaType;
use App\Enums\MediaStatus;
use App\Support\Traits\JsonFieldsTrait;
use App\Domain\Media\Traits\MediaTrait;
use App\Domain\Media\Traits\MediaAttributesTrait;
use App\Domain\Media\Traits\MediaConversionsTrait;
use App\Domain\Media\Traits\MediaTypesTrait;
use App\Domain\Media\Traits\MediaStatusTrait;
use App\Domain\Media\Traits\MediaOperationsTrait;
use App\Domain\Media\Traits\MediaScopesTrait;
use App\Domain\Media\Services\MediaFactoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use HasFactory, 
        SoftDeletes, 
        JsonFieldsTrait, 
        MediaTrait,
        MediaAttributesTrait,
        MediaConversionsTrait,
        MediaTypesTrait,
        MediaStatusTrait,
        MediaOperationsTrait,
        MediaScopesTrait;

    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'collection_name',
        'name',
        'file_name',
        'mime_type',
        'disk',
        'size',
        'type',
        'status',
        'alt_text',
        'caption',
        'metadata',
        'conversions',
        'sort_order',
        'expires_at',
    ];

    /**
     * JSON поля для использования с JsonFieldsTrait
     */
    protected $jsonFields = [
        'metadata',
        'conversions',
    ];

    protected $casts = [
        'type' => MediaType::class,
        'status' => MediaStatus::class,
        // JSON поля обрабатываются через JsonFieldsTrait
        'size' => 'integer',
        'sort_order' => 'integer',
        'expires_at' => 'datetime',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'expires_at',
    ];

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    // === ФАБРИЧНЫЕ МЕТОДЫ (делегируем в сервис) ===

    /**
     * Создать Photo через сервис
     */
    public static function createPhoto(array $data): \App\Domain\Media\Models\Photo
    {
        return MediaFactoryService::createPhoto($data);
    }

    /**
     * Создать Video через сервис
     */
    public static function createVideo(array $data): \App\Domain\Media\Models\Video
    {
        return MediaFactoryService::createVideo($data);
    }

    /**
     * Получить все медиафайлы для сущности через сервис
     */
    public static function getMediaForEntity(string $entityType, int $entityId): \Illuminate\Support\Collection
    {
        return MediaFactoryService::getMediaForEntity($entityType, $entityId);
    }

}