<?php

namespace App\Infrastructure\Media;

use Illuminate\Support\Str;
use App\Support\Helpers\Transliterator;
use App\Domain\User\Models\User;

class PathGenerator
{
    /**
     * Генерация пути для фото объявления
     * Структура: users/{userFolder}/ads/{adId}/photos/{variant}/{uuid}.{ext}
     */
    public static function adPhotoPath(int $userId, int $adId, string $extension = 'jpg', string $variant = 'original'): string
    {
        $uuid = (string) Str::uuid();
        $userFolder = self::getUserFolderName($userId);
        
        return sprintf(
            'users/%s/ads/%d/photos/%s/%s.%s',
            $userFolder,                // users/anna-1
            $adId,                      // ads/178
            $variant,                   // photos/original или photos/thumb
            $uuid,                      // уникальное имя
            ltrim($extension, '.')      // jpg
        );
    }
    
    /**
     * Генерация пути для видео объявления
     * Структура: users/{userFolder}/ads/{adId}/videos/{uuid}.{ext}
     */
    public static function adVideoPath(int $userId, int $adId, string $extension = 'mp4'): string
    {
        $uuid = (string) Str::uuid();
        $userFolder = self::getUserFolderName($userId);
        
        return sprintf(
            'users/%s/ads/%d/videos/%s.%s',
            $userFolder,
            $adId,
            $uuid,
            ltrim($extension, '.')
        );
    }
    
    /**
     * Генерация пути для фото профиля пользователя
     * Структура: users/{userFolder}/profile/{uuid}.{ext}
     */
    public static function userProfilePhotoPath(int $userId, string $extension = 'jpg'): string
    {
        $uuid = (string) Str::uuid();
        $userFolder = self::getUserFolderName($userId);
        
        return sprintf(
            'users/%s/profile/%s.%s',
            $userFolder,
            $uuid,
            ltrim($extension, '.')
        );
    }
    
    /**
     * Получить базовый путь для пользователя
     */
    public static function getUserBasePath(int $userId): string
    {
        $userFolder = self::getUserFolderName($userId);
        return sprintf('users/%s', $userFolder);
    }
    
    /**
     * Получить путь к папке объявления
     */
    public static function getAdBasePath(int $userId, int $adId): string
    {
        $userFolder = self::getUserFolderName($userId);
        return sprintf('users/%s/ads/%d', $userFolder, $adId);
    }
    
    /**
     * Парсинг существующего пути для определения варианта
     * Например: users/1/ads/178/photos/thumb/uuid.jpg -> thumb
     */
    public static function getVariantFromPath(string $path): string
    {
        if (preg_match('/photos\/([^\/]+)\//', $path, $matches)) {
            return $matches[1];
        }
        return 'original';
    }
    
    /**
     * Генерация пути для варианта существующего фото
     * Используется при создании thumb/medium версий
     */
    public static function generateVariantPath(string $originalPath, string $variant): string
    {
        // Заменяем /original/ на нужный вариант
        $path = str_replace('/photos/original/', "/photos/{$variant}/", $originalPath);
        
        // Меняем расширение на jpg для оптимизированных версий
        if ($variant !== 'original') {
            $path = preg_replace('/\.\w+$/', '.jpg', $path);
        }
        
        return $path;
    }
    
    /**
     * Проверка, является ли путь путем к медиа объявления
     */
    public static function isAdMediaPath(string $path): bool
    {
        return str_contains($path, '/ads/') && 
               (str_contains($path, '/photos/') || str_contains($path, '/videos/'));
    }
    
    /**
     * Извлечение user_id и ad_id из пути
     * Возвращает ['user_id' => int, 'ad_id' => int] или null
     * Поддерживает как старый формат (users/1/), так и новый (users/anna-1/)
     */
    public static function extractIdsFromPath(string $path): ?array
    {
        // Пробуем новый формат: users/anna-1/ads/178/
        if (preg_match('/users\/[a-z\-]+\-(\d+)\/ads\/(\d+)\//', $path, $matches)) {
            return [
                'user_id' => (int) $matches[1],
                'ad_id' => (int) $matches[2]
            ];
        }
        
        // Пробуем старый формат: users/1/ads/178/
        if (preg_match('/users\/(\d+)\/ads\/(\d+)\//', $path, $matches)) {
            return [
                'user_id' => (int) $matches[1],
                'ad_id' => (int) $matches[2]
            ];
        }
        
        return null;
    }
    
    /**
     * Получить имя папки пользователя в формате name-id
     * Кэшируем результат для производительности
     */
    private static function getUserFolderName(int $userId): string
    {
        static $cache = [];
        
        if (isset($cache[$userId])) {
            return $cache[$userId];
        }
        
        $user = User::find($userId);
        
        if (!$user) {
            $cache[$userId] = (string) $userId; // Fallback к простому ID
            return $cache[$userId];
        }
        
        // Если у пользователя уже есть сохраненное имя папки
        if (!empty($user->folder_name)) {
            $cache[$userId] = $user->folder_name;
            return $cache[$userId];
        }
        
        // Генерируем новое имя папки
        $folderName = Transliterator::generateUserFolderName($user->name, $userId);
        
        // Сохраняем в БД для будущего использования
        $user->update(['folder_name' => $folderName]);
        
        $cache[$userId] = $folderName;
        return $folderName;
    }
}