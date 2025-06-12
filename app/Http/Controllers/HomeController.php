<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Models\MassageCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Валидация параметров фильтрации
        $validated = $request->validate([
            'category' => 'nullable|exists:massage_categories,slug',
            'district' => 'nullable|string',
            'metro' => 'nullable|string',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
            'rating' => 'nullable|numeric|min:0|max:5',
            'home_service' => 'nullable|boolean',
            'salon_service' => 'nullable|boolean',
            'verified_only' => 'nullable|boolean',
            'with_reviews' => 'nullable|boolean',
            'sort' => 'nullable|in:default,price_asc,price_desc,rating,reviews,distance',
            'page' => 'nullable|integer|min:1',
        ]);

        // Строим запрос
        $query = MasterProfile::query()
            ->with(['user', 'services.category', 'reviews'])
            ->where('status', 'active');

        // Применяем фильтры
        if ($request->filled('district')) {
            $query->where('district', $validated['district']);
        }

        if ($request->filled('metro')) {
            $query->where('metro_station', $validated['metro']);
        }

        if ($request->filled('category')) {
            $query->whereHas('services', function ($q) use ($validated) {
                $q->whereHas('category', function ($q2) use ($validated) {
                    $q2->where('slug', $validated['category']);
                });
            });
        }

        if ($request->filled('price_min') || $request->filled('price_max')) {
            $query->whereHas('services', function ($q) use ($validated) {
                if (isset($validated['price_min'])) {
                    $q->where('price', '>=', $validated['price_min']);
                }
                if (isset($validated['price_max'])) {
                    $q->where('price', '<=', $validated['price_max']);
                }
            });
        }

        if ($request->filled('rating')) {
            $query->where('rating', '>=', $validated['rating']);
        }

        if ($request->boolean('home_service')) {
            $query->where('home_service', true);
        }

        if ($request->boolean('salon_service')) {
            $query->where('salon_service', true);
        }

        if ($request->boolean('verified_only')) {
            $query->where('is_verified', true);
        }

        if ($request->boolean('with_reviews')) {
            $query->where('reviews_count', '>', 0);
        }

        // Сортировка
        switch ($validated['sort'] ?? 'default') {
            case 'price_asc':
                $query->join('services', 'master_profiles.id', '=', 'services.master_profile_id')
                    ->orderBy('services.price', 'asc')
                    ->select('master_profiles.*');
                break;
            case 'price_desc':
                $query->join('services', 'master_profiles.id', '=', 'services.master_profile_id')
                    ->orderBy('services.price', 'desc')
                    ->select('master_profiles.*');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'reviews':
                $query->orderBy('reviews_count', 'desc');
                break;
            default:
                // Сначала премиум, потом по дате обновления
                $query->orderBy('is_premium', 'desc')
                    ->orderBy('updated_at', 'desc');
        }

        // Пагинация
        $masters = $query->paginate(20)->withQueryString();

        // Получаем категории для фильтров
        $categories = MassageCategory::where('is_active', true)
            ->withCount('services')
            ->orderBy('sort_order')
            ->get();

        // Получаем районы для фильтров (уникальные из базы)
        $districts = MasterProfile::where('status', 'active')
            ->whereNotNull('district')
            ->distinct()
            ->pluck('district');

        return Inertia::render('Home', [
            'masters' => $masters->through(fn ($master) => [
                'id' => $master->id,
                'display_name' => $master->display_name,
                'avatar' => $master->avatar,
                'rating' => $master->rating,
                'reviews_count' => $master->reviews_count,
                'is_verified' => $master->is_verified,
                'is_premium' => $master->is_premium,
                'district' => $master->district,
                'metro_station' => $master->metro_station,
                'home_service' => $master->home_service,
                'salon_service' => $master->salon_service,
                'min_price' => $master->services->min('price'),
                'services_count' => $master->services->count(),
                'coordinates' => [
                    'lat' => $master->latitude ?? 55.7558,
                    'lng' => $master->longitude ?? 37.6173,
                ],
                'specializations' => $master->services->pluck('category.name')->unique()->take(3),
            ]),
            'filters' => array_merge([
                'categories' => $categories,
                'districts' => $districts,
                'price_range' => [
                    'min' => 0,
                    'max' => 50000
                ],
            ], $validated),
            'totalMasters' => $masters->total(),
            'currentPage' => $masters->currentPage(),
            'totalPages' => $masters->lastPage(),
            'currentDistrict' => $validated['district'] ?? null,
            'mapCenter' => [
                'lat' => 55.7558,
                'lng' => 37.6173
            ],
        ]);
    }
}