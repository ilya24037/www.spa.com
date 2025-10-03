<?php

namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use Illuminate\Support\Str;

/**
 * Сервис для работы с объявлениями в профиле пользователя
 */
class AdProfileService
{
    /**
     * Получить объявления пользователя по статусу
     */
    public function getUserAdsByStatus(User $user, string|array $status, int $limit = 100): array
    {
        $query = Ad::where('user_id', $user->id);

        // Поддержка массива статусов
        if (is_array($status)) {
            $query->whereIn('status', $status);
        } else {
            $query->where('status', $status);
        }

        $ads = $query
            ->with('user.masterProfile') // Загружаем связь с профилем мастера
            ->select([
                'id', 'title', 'status', 'price', 'address', 'travel_area',
                'description', 'phone', 'contact_method',
                'photos', 'service_location', 'views_count',
                'user_id', // Добавляем user_id для связи
                'created_at', 'updated_at'
            ])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $this->transformAdsToProfiles($ads);
    }

    /**
     * Получить счетчики объявлений пользователя
     */
    public function getUserAdCounts(User $user): array
    {
        $countsQuery = Ad::where('user_id', $user->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            // Активные включают и объявления на модерации
            'active' => ($countsQuery['active'] ?? 0) + ($countsQuery['pending_moderation'] ?? 0),
            'draft' => $countsQuery['draft'] ?? 0,
            'waiting_payment' => $countsQuery['waiting_payment'] ?? 0,
            'old' => $countsQuery['archived'] ?? 0,
            'archived' => $countsQuery['archived'] ?? 0,
            'bookings' => 0, // TODO: Добавить подсчет бронирований когда будет готов функционал
            'favorites' => 0, // TODO: Добавить подсчет избранного когда будет готов функционал
            'unreadMessages' => 0,
        ];
    }

    /**
     * Получить статистику пользователя
     */
    public function getUserStats(User $user): array
    {
        return [
            'rating' => 0,
            'reviewsCount' => 0, // TODO: Добавить подсчет отзывов когда будет готов функционал
            'balance' => $user->balance ?? 0,
        ];
    }

    /**
     * Преобразование объявлений в формат для карточек
     */
    private function transformAdsToProfiles($ads): array
    {
        return $ads->map(function ($ad) {
            // Обработка фотографий
            $mainImage = $this->extractMainImage($ad);
            $photosCount = $this->getPhotosCount($ad);
            
            // Получаем данные о профиле мастера
            $masterProfile = $ad->user?->masterProfile;
            // Используем slug из профиля мастера или генерируем из title
            $masterSlug = $masterProfile?->slug ?: ($masterProfile ? Str::slug($ad->title) : null);
            
            return [
                'id' => $ad->id,
                'slug' => Str::slug($ad->title),
                'name' => $ad->title,
                'status' => is_object($ad->status) ? $ad->status->value : $ad->status,
                'waiting_payment' => (is_object($ad->status) ? $ad->status->value : $ad->status) === 'waiting_payment',
                'is_active' => (is_object($ad->status) ? $ad->status->value : $ad->status) === 'active',
                'price_from' => $ad->price ?? 0,
                'views_count' => $ad->views_count ?? 0,
                'photos_count' => $photosCount,
                'avatar' => $mainImage,
                'main_image' => $mainImage,
                'photos' => $this->parsePhotos($ad),
                'city' => 'Москва',
                'address' => $ad->address ?? '',
                'district' => $ad->travel_area ?? '',
                'home_service' => $this->hasHomeService($ad),
                'availability' => (is_object($ad->status) ? $ad->status->value : $ad->status) === 'active' ? 'Доступен' : 'Недоступен',
                'messages_count' => 0,
                'new_messages_count' => 0,
                'services_list' => '', // Специальность больше не используется
                'full_address' => $ad->address ?? 'Адрес не указан',
                // Добавляем данные о мастере для перехода на страницу мастера
                'master_profile_id' => $masterProfile?->id,
                'master_slug' => $masterSlug,
                'has_master_profile' => $masterProfile !== null,
                'rejection_reason' => null,
                'bookings_count' => 0,
                'reviews_count' => 0,
                'description' => $ad->description,
                'formatted_price' => $ad->formatted_price,
                'phone' => $ad->phone,
                'contact_method' => $ad->contact_method,
                'created_at' => $ad->created_at->format('d.m.Y'),
                'updated_at' => $ad->updated_at->format('d.m.Y'),
                'company_name' => 'Массажный салон',
                'expires_at' => now()->addDays(30)->toISOString(),
                'new_messages_count' => 0,
                'subscribers_count' => 0,
                'favorites_count' => 0,
            ];
        })->toArray();
    }

    /**
     * Извлечение главного изображения
     */
    private function extractMainImage($ad): string
    {
        $photos = $this->parsePhotos($ad);
        
        if (!empty($photos) && isset($photos[0])) {
            $firstPhoto = $photos[0];
            
            if (is_array($firstPhoto)) {
                return $firstPhoto['preview'] ?? $firstPhoto['url'] ?? $firstPhoto['src'] ?? $this->getDefaultImage($ad);
            } elseif (is_string($firstPhoto) && !empty($firstPhoto)) {
                return $firstPhoto;
            }
        }
        
        return $this->getDefaultImage($ad);
    }

    /**
     * Парсинг фотографий
     */
    private function parsePhotos($ad): array
    {
        // Используем JsonFieldsTrait для безопасного получения фотографий
        return $ad->getJsonField('photos', []);
    }

    /**
     * Получение количества фотографий
     */
    private function getPhotosCount($ad): int
    {
        $photos = $this->parsePhotos($ad);
        return empty($photos) ? rand(1, 4) : count($photos);
    }

    /**
     * Получение изображения по умолчанию
     */
    private function getDefaultImage($ad): string
    {
        return '/images/masters/demo-' . (($ad->id % 4) + 1) . '.jpg';
    }

    /**
     * Проверка наличия выезда
     */
    private function hasHomeService($ad): bool
    {
        // Используем JsonFieldsTrait для безопасного получения локации услуг
        $serviceLocation = $ad->getJsonField('service_location', []);
        return in_array('client_home', $serviceLocation);
    }
}