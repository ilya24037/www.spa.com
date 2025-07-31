<?php

namespace App\Domain\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Helpers\ImageHelper;

/**
 * Модель для работы с медиа-контентом мастера
 * Управляет фото и видео профиля
 */
class MasterMedia extends Model
{
    /**
     * Связь с фотографиями
     */
    public function photos(): HasMany
    {
        return $this->hasMany(\App\Models\MasterPhoto::class, 'master_profile_id');
    }

    /**
     * Связь с видео
     */
    public function videos(): HasMany
    {
        return $this->hasMany(\App\Models\MasterVideo::class, 'master_profile_id');
    }

    /**
     * Главное фото
     */
    public function mainPhoto(): HasOne
    {
        return $this->hasOne(\App\Models\MasterPhoto::class, 'master_profile_id')
                    ->where('is_main', true);
    }

    /**
     * Главное видео
     */
    public function mainVideo(): HasOne
    {
        return $this->hasOne(\App\Models\MasterVideo::class, 'master_profile_id')
                    ->where('is_main', true);
    }

    /**
     * Получить URL аватара
     */
    public function getAvatarUrl(?string $avatar): string
    {
        return ImageHelper::getImageUrl(
            $avatar,
            asset('images/no-avatar.jpg')
        );
    }

    /**
     * Получить все фото с форматированием
     */
    public function getAllPhotosFormatted($masterProfileId): array
    {
        return \App\Models\MasterPhoto::where('master_profile_id', $masterProfileId)
            ->orderBy('order')
            ->get()
            ->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'url' => $photo->url,
                    'thumb_url' => $photo->thumb_url,
                    'medium_url' => $photo->medium_url,
                    'is_main' => $photo->is_main,
                    'file_size' => $photo->formatted_size,
                ];
            })
            ->toArray();
    }

    /**
     * Получить все видео с форматированием
     */
    public function getAllVideosFormatted($masterProfileId): array
    {
        return \App\Models\MasterVideo::where('master_profile_id', $masterProfileId)
            ->orderBy('order')
            ->get()
            ->map(function ($video) {
                return [
                    'id' => $video->id,
                    'url' => $video->url,
                    'thumb_url' => $video->thumb_url,
                    'duration' => $video->formatted_duration,
                    'is_main' => $video->is_main,
                    'file_size' => $video->formatted_size,
                ];
            })
            ->toArray();
    }

    /**
     * Получить имя папки для файлов мастера
     */
    public function getFolderName(string $displayName, int $id): string
    {
        // Берем первое слово из имени
        $firstName = explode(' ', trim($displayName))[0];
        
        // Транслитерируем в латиницу
        $transliteration = [
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'E',
            'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M',
            'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch', 'Ъ' => '',
            'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
            'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
            'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',
            'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
        ];
        
        $transliterated = strtr($firstName, $transliteration);
        
        // Убираем все кроме букв и цифр, приводим к нижнему регистру
        $safeName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $transliterated));
        
        // Если имя пустое, используем "master"
        if (empty($safeName)) {
            $safeName = 'master';
        }
        
        return $safeName . '-' . $id;
    }
}