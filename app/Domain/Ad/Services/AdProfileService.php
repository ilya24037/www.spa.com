<?php

namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use App\Support\Services\BaseService;
use Illuminate\Support\Str;

/**
 * Сервис для работы с объявлениями в профиле пользователя
 */
class AdProfileService extends BaseService
{
    /**
     * Получить объявления пользователя по статусу
     */
    public function getUserAdsByStatus(User $user, string $status, int $limit = 100): array
    {
        $ads = Ad::where('user_id', $user->id)
            ->where('status', $status)
            ->select([
                'id', 'title', 'status', 'price', 'address', 'travel_area', 
                'specialty', 'description', 'phone', 'contact_method', 
                'photos', 'service_location', 'views_count',
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
            'active' => $countsQuery['active'] ?? 0,
            'draft' => $countsQuery['draft'] ?? 0,
            'waiting_payment' => $countsQuery['waiting_payment'] ?? 0,
            'old' => $countsQuery['archived'] ?? 0,
            'archived' => $countsQuery['archived'] ?? 0,
            'bookings' => $user->getBookings()->where('status', 'pending')->count(),
            'favorites' => $user->getFavoritesCount(),
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
            'reviewsCount' => $user->getReceivedReviewsCount(),
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
            
            return [
                'id' => $ad->id,
                'slug' => Str::slug($ad->title),
                'name' => $ad->title,
                'status' => $ad->status,
                'is_active' => $ad->status === 'active',
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
                'availability' => $ad->status === 'active' ? 'Доступен' : 'Недоступен',
                'messages_count' => 0,
                'services_list' => $ad->specialty ?? '',
                'full_address' => $ad->address ?? 'Адрес не указан',
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