<?php

namespace App\Application\Http\Controllers\Profile;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\ProfileUpdateRequest;
use App\Domain\Ad\Models\Ad;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Контроллер для управления профилем пользователя
 * Отвечает за редактирование и удаление профиля
 */
class ProfileController extends Controller
{
    /**
     * Отображение личного кабинета
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Определяем таб из URL
        $currentPath = $request->path();
        $tab = 'inactive'; // По умолчанию
        
        if (str_contains($currentPath, '/active/')) {
            $tab = 'active';
        } elseif (str_contains($currentPath, '/draft/')) {
            $tab = 'draft';
        } elseif (str_contains($currentPath, '/inactive/')) {
            $tab = 'inactive';
        } elseif (str_contains($currentPath, '/old/')) {
            $tab = 'old';
        } elseif (str_contains($currentPath, '/archive/')) {
            $tab = 'archive';
        } else {
            // Если запрос из параметра
            $tab = $request->get('tab', 'inactive');
        }
        
        // Маппинг табов на статусы
        $statusMap = [
            'waiting' => 'waiting_payment',
            'inactive' => 'waiting_payment',
            'active' => 'active',
            'drafts' => 'draft',
            'draft' => 'draft',
            'archived' => 'archived',
            'archive' => 'archived',
            'old' => 'archived'
        ];
        
        $status = $statusMap[$tab] ?? 'waiting_payment';
        
        // Получаем объявления пользователя
        $ads = Ad::where('user_id', $user->id)
            ->where('status', $status)
            ->select([
                'id', 'title', 'status', 'price', 'prices', 'address', 'travel_area',
                'specialty', 'description', 'phone', 'contact_method',
                'photos', 'service_location', 'views_count',
                'created_at', 'updated_at'
            ])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
        
        // Преобразуем в формат для ItemCard
        $profiles = $ads->map(function ($ad) {
            // Обработка фотографий
            $photos = $ad->photos;
            if (is_string($photos)) {
                $photos = json_decode($photos, true) ?? [];
            }
            $photos = is_array($photos) ? $photos : [];
            
            $mainImage = null;
            $photosCount = count($photos);
            
            if ($photosCount > 0) {
                $firstPhoto = $photos[0];
                if (is_array($firstPhoto)) {
                    $mainImage = $firstPhoto['preview'] ?? $firstPhoto['url'] ?? $firstPhoto['src'] ?? null;
                } elseif (is_string($firstPhoto)) {
                    $mainImage = $firstPhoto;
                }
            }
            
            // Если нет фото, используем демо
            if (!$mainImage) {
                $mainImage = '/images/masters/demo-' . (($ad->id % 4) + 1) . '.jpg';
                $photosCount = rand(1, 4);
            }
            
            // Обработка service_location
            $serviceLocation = $ad->service_location;
            if (is_string($serviceLocation)) {
                $serviceLocation = json_decode($serviceLocation, true) ?? [];
            }
            $serviceLocation = is_array($serviceLocation) ? $serviceLocation : [];
            
            // Берем цену за час из нового поля prices (как с фото - простая логика)
            $prices = $ad->prices;
            if (is_string($prices)) {
                $prices = json_decode($prices, true) ?? [];
            }
            $prices = is_array($prices) ? $prices : [];
            
            // Простая логика: берем цену за час из черновика
            $finalPrice = $prices['apartments_1h'] ?? $prices['outcall_1h'] ?? 0;
            
            return [
                'id' => $ad->id,
                'slug' => Str::slug($ad->title),
                'name' => $ad->title,
                'title' => $ad->title,
                'status' => $ad->status,
                'is_active' => $ad->status === 'active',
                'price' => $finalPrice,
                'price_from' => $finalPrice,
                'prices' => $prices,
                'views_count' => $ad->views_count ?? 0,
                'photos_count' => $photosCount,
                'avatar' => $mainImage,
                'main_image' => $mainImage,
                'photos' => $photos,
                'photo' => $mainImage,
                'city' => 'Москва',
                'address' => $ad->address ?? '',
                'location' => $ad->address ?? '',
                'district' => $ad->travel_area ?? '',
                'home_service' => in_array('client_home', $serviceLocation),
                'availability' => $ad->status === 'active' ? 'Доступен' : 'Недоступен',
                'messages_count' => 0,
                'services_list' => $ad->specialty ?? '',
                'full_address' => $ad->address ?? 'Адрес не указан',
                'rejection_reason' => null,
                'bookings_count' => 0,
                'reviews_count' => 0,
                'description' => $ad->description,
                'phone' => $ad->phone,
                'contact_method' => $ad->contact_method,
                'created_at' => $ad->created_at ? $ad->created_at->format('d.m.Y') : '',
                'updated_at' => $ad->updated_at ? $ad->updated_at->format('d.m.Y') : '',
                'company_name' => 'Массажный салон',
                'expires_at' => now()->addDays(30)->toISOString(),
                'new_messages_count' => 0,
                'subscribers_count' => 0,
                'favorites_count' => 0,
                'waiting_payment' => $ad->status === 'waiting_payment',
                'views' => $ad->views_count ?? 0,
                'favorites' => 0,
                'messages' => 0,
                'calls' => 0
            ];
        })->toArray();
        
        // Получаем счетчики
        $countsQuery = Ad::where('user_id', $user->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        $counts = [
            'active' => $countsQuery['active'] ?? 0,
            'draft' => $countsQuery['draft'] ?? 0,
            'waiting_payment' => $countsQuery['waiting_payment'] ?? 0,
            'old' => $countsQuery['archived'] ?? 0,
            'archive' => $countsQuery['archived'] ?? 0,
            'unreadMessages' => 0
        ];
        
        // Статистика пользователя
        $userStats = [
            'rating' => 4.2,
            'reviews_count' => 5
        ];
        
        return Inertia::render('Dashboard', [
            'profiles' => $profiles,
            'counts' => $counts,
            'userStats' => $userStats,
            'activeTab' => $tab,
            'title' => 'Мои объявления'
        ]);
    }

    /**
     * Отображение формы профиля пользователя
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => [
                'id' => $request->user()->id,
                'email' => $request->user()->email,
                'role' => $request->user()->role,
                'profile' => $request->user()->getProfile(),
                'master_profile' => $request->user()->getMasterProfile(),
                'can_create_master_profile' => $request->user()->canCreateMasterProfile(),
            ]
        ]);
    }

    /**
     * Обновление информации профиля пользователя
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Удаление аккаунта пользователя
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        auth()->logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}