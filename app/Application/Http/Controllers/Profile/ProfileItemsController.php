<?php

namespace App\Application\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\Ad;

/**
 * Контроллер для управления объявлениями в личном кабинете
 * Отвечает за отображение объявлений по статусам
 */
class ProfileItemsController extends Controller
{
    /**
     * Отображение личного кабинета - сразу показываем активные объявления
     */
    public function index(Request $request)
    {
        return $this->renderItemsByStatus($request, 'active', 'Мои объявления');
    }

    /**
     * Активные объявления
     */
    public function activeItems(Request $request)
    {
        return $this->renderItemsByStatus($request, 'active', 'Активные');
    }

    /**
     * Черновики
     */
    public function draftItems(Request $request)
    {
        return $this->renderItemsByStatus($request, 'draft', 'Черновики');
    }

    /**
     * Объявления, ждущие действий
     */
    public function inactiveItems(Request $request)
    {
        return $this->renderItemsByStatus($request, 'waiting_payment', 'Ждут действий');
    }

    /**
     * Старые объявления
     */
    public function oldItems(Request $request)
    {
        return $this->renderItemsByStatus($request, 'archived', 'Старые');
    }

    /**
     * Архивные объявления
     */
    public function archiveItems(Request $request)
    {
        return $this->renderItemsByStatus($request, 'archived', 'Архив');
    }

    /**
     * Общий метод для рендеринга объявлений по статусу
     */
    private function renderItemsByStatus($request, $status, $title)
    {
        $user = $request->user();
        
        // Оптимизируем запрос
        $ads = Ad::where('user_id', $user->id)
            ->where('status', $status)
            ->select([
                'id', 'title', 'status', 'price', 'address', 'travel_area', 
                'specialty', 'description', 'phone', 'contact_method', 
                'photos', 'service_location', 'views_count',
                'created_at', 'updated_at'
            ])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        // Преобразуем объявления для отображения
        $profiles = $this->transformAdsToProfiles($ads);
        
        // Получаем счетчики
        $counts = $this->getCounts($user);
        
        // Статистика пользователя
        $userStats = $this->getUserStats($user);
        
        return Inertia::render('Dashboard', [
            'profiles' => $profiles,
            'counts' => $counts,
            'userStats' => $userStats,
            'activeTab' => $status === 'waiting_payment' ? 'inactive' : $status,
            'title' => $title
        ]);
    }

    /**
     * Преобразование объявлений в формат для карточек
     */
    private function transformAdsToProfiles($ads)
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
        });
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
        if (empty($ad->photos)) {
            return [];
        }

        if (is_string($ad->photos)) {
            $decoded = json_decode($ad->photos, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($ad->photos) ? $ad->photos : [];
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
        $serviceLocation = is_string($ad->service_location) 
            ? json_decode($ad->service_location, true) 
            : $ad->service_location;
            
        return is_array($serviceLocation) && in_array('client_home', $serviceLocation);
    }

    /**
     * Получение счетчиков
     */
    private function getCounts($user): array
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
            'bookings' => $user->bookings()->where('status', 'pending')->count(),
            'favorites' => $user->favorites()->count(),
            'unreadMessages' => 0,
        ];
    }

    /**
     * Получение статистики пользователя
     */
    private function getUserStats($user): array
    {
        return [
            'rating' => 0,
            'reviewsCount' => $user->reviews()->count(),
            'balance' => $user->balance ?? 0,
        ];
    }
}