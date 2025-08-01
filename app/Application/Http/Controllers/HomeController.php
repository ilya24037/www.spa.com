<?php

namespace App\Application\Http\Controllers;

use App\Domain\Master\Models\MasterProfile;
use App\Models\MassageCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Получаем фильтры из запроса
        $filters = $request->only([
            'q', 'category', 'price_min', 'price_max', 
            'rating', 'district', 'service_type', 'sort'
        ]);

        // Строим запрос с необходимыми полями
        $query = MasterProfile::with(['user', 'services', 'photos'])
            ->select([
                'master_profiles.*',
                DB::raw('(SELECT MIN(price) FROM services WHERE services.master_profile_id = master_profiles.id) as min_price'),
                DB::raw('(SELECT MAX(price) FROM services WHERE services.master_profile_id = master_profiles.id) as max_price')
            ])
            ->active();

        // Поиск по тексту
        if (!empty($filters['q'])) {
            $searchTerm = $filters['q'];
            $query->where(function($q) use ($searchTerm) {
                $q->whereHas('user', function($userQuery) use ($searchTerm) {
                    $userQuery->where('name', 'like', "%{$searchTerm}%");
                })
                ->orWhere('bio', 'like', "%{$searchTerm}%")
                ->orWhere('display_name', 'like', "%{$searchTerm}%")
                ->orWhereHas('services', function($serviceQuery) use ($searchTerm) {
                    $serviceQuery->where('name', 'like', "%{$searchTerm}%");
                });
            });
        }

        // Фильтр по категории
        if (!empty($filters['category'])) {
            $query->whereHas('services', function($q) use ($filters) {
                $q->where('massage_category_id', $filters['category']);
            });
        }

        // Фильтр по цене
        if (!empty($filters['price_min'])) {
            $query->having('min_price', '>=', $filters['price_min']);
        }
        if (!empty($filters['price_max'])) {
            $query->having('max_price', '<=', $filters['price_max']);
        }

        // Фильтр по рейтингу
        if (!empty($filters['rating'])) {
            $query->where('rating', '>=', $filters['rating']);
        }

        // Фильтр по району
        if (!empty($filters['district'])) {
            $query->where('district', $filters['district']);
        }

        // Фильтр по метро
        if (!empty($filters['metro'])) {
            $query->where('metro_station', $filters['metro']);
        }

        // Фильтр по типу услуги
        if (!empty($filters['service_type'])) {
            if ($filters['service_type'] === 'home') {
                $query->where('home_service', true);
            } elseif ($filters['service_type'] === 'salon') {
                $query->where('salon_service', true);
            }
        }

        // Сортировка
        switch ($filters['sort'] ?? 'rating') {
            case 'price_asc':
                $query->orderBy('min_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('max_price', 'desc');
                break;
            case 'reviews':
                $query->orderBy('reviews_count', 'desc');
                break;
            default:
                $query->orderBy('rating', 'desc')
                      ->orderBy('is_premium', 'desc');
        }

        // Пагинация с добавлением дополнительных атрибутов
        $masters = $query->paginate(12)->through(function ($master) {
            return [
                'id' => $master->id,
                'slug' => $master->slug,
                'display_name' => $master->display_name,
                'name' => $master->user->name ?? $master->display_name,
                'avatar' => $master->avatar_url,
                'avatar_url' => $master->avatar_url,
                'bio' => $master->bio,
                'rating' => $master->rating,
                'reviews_count' => $master->reviews_count,
                'price_from' => $master->price_from ?? $master->min_price,
                'price_to' => $master->price_to ?? $master->max_price,
                'min_price' => $master->min_price,
                'max_price' => $master->max_price,
                'city' => $master->city,
                'district' => $master->district,
                'metro_station' => $master->metro_station,
                'home_service' => $master->home_service,
                'salon_service' => $master->salon_service,
                'is_verified' => $master->is_verified,
                'is_premium' => $master->is_premium,
                'is_online' => $master->isAvailableNow(),
                'is_available_now' => $master->isAvailableNow(),
                'services' => $master->services ? $master->services->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'category_id' => $service->massage_category_id,
                        'price' => $service->price,
                    ];
                }) : [],
                'phone' => $master->show_contacts ? $master->phone : null,
                'whatsapp' => $master->whatsapp,
            ];
        });

        // Получаем данные для фильтров
        $categories = MassageCategory::whereNull('parent_id')
            ->with('children')
            ->get();

        $districts = MasterProfile::active()
            ->select('district')
            ->distinct()
            ->whereNotNull('district')
            ->pluck('district');

        $priceRange = DB::table('services')
            ->selectRaw('MIN(price) as min, MAX(price) as max')
            ->first();

        return Inertia::render('Home', [
            'masters' => $masters->withQueryString(),
            'filters' => $filters,
            'categories' => $categories,
            'districts' => $districts,
            'priceRange' => $priceRange,
            'currentCity' => 'Москва' // Или из настроек пользователя
        ]);
    }
}