<?php

namespace App\Application\Http\Controllers;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Service\Models\MassageCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterProfile::query()
            ->with(['user', 'services.category'])
            ->where('status', 'active');

        // Поиск по тексту
        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                  ->orWhere('bio', 'like', "%{$search}%")
                  ->orWhereHas('services', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                         ->orWhere('description', 'like', "%{$search}%");
                  });
            });
        }

        // Фильтр по категории
        if ($categoryId = $request->get('category_id')) {
            $query->whereHas('services', function ($q) use ($categoryId) {
                $q->where('massage_category_id', $categoryId);
            });
        }

        // Фильтр по району
        if ($district = $request->get('district')) {
            $query->where('district', $district);
        }

        // Фильтр по метро
        if ($metro = $request->get('metro')) {
            $query->where('metro_station', $metro);
        }

        // Фильтр по цене
        if ($minPrice = $request->get('min_price')) {
            $query->whereHas('services', function ($q) use ($minPrice) {
                $q->where('price', '>=', $minPrice);
            });
        }

        if ($maxPrice = $request->get('max_price')) {
            $query->whereHas('services', function ($q) use ($maxPrice) {
                $q->where('price', '<=', $maxPrice);
            });
        }

        // Фильтр по типу услуги
        if ($serviceType = $request->get('service_type')) {
            if ($serviceType === 'home') {
                $query->where('home_service', true);
            } elseif ($serviceType === 'salon') {
                $query->where('salon_service', true);
            }
        }

        // Только с фото
        if ($request->get('with_photo')) {
            $query->whereNotNull('avatar');
        }

        // Только проверенные
        if ($request->get('verified_only')) {
            $query->where('is_verified', true);
        }

        // Сортировка
        switch ($request->get('sort', 'rating')) {
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
            case 'experience':
                $query->orderBy('experience_years', 'desc');
                break;
            case 'reviews':
                $query->orderBy('reviews_count', 'desc');
                break;
            default: // rating
                $query->orderBy('rating', 'desc')
                      ->orderBy('reviews_count', 'desc');
        }

        // Премиум мастера в начале
        $query->orderBy('is_premium', 'desc');

        $masters = $query->paginate(12)->withQueryString();

        // Получаем данные для фильтров
        $categories = MassageCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $districts = MasterProfile::where('status', 'active')
            ->whereNotNull('district')
            ->distinct()
            ->pluck('district');

        $metroStations = MasterProfile::where('status', 'active')
            ->whereNotNull('metro_station')
            ->distinct()
            ->pluck('metro_station');

        return Inertia::render('Search/Index', [
            'masters' => $masters,
            'categories' => $categories,
            'districts' => $districts,
            'metroStations' => $metroStations,
            'filters' => $request->only(['q', 'category_id', 'district', 'metro', 'min_price', 'max_price', 'service_type', 'with_photo', 'verified_only', 'sort'])
        ]);
    }

    // API для автодополнения
    public function suggestions(Request $request)
    {
        $query = $request->get('q');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $masters = MasterProfile::where('status', 'active')
            ->where('display_name', 'like', "{$query}%")
            ->limit(5)
            ->pluck('display_name');

        $services = \App\Models\Service::where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->limit(5)
            ->pluck('name');

        $categories = MassageCategory::where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->limit(5)
            ->pluck('name');

        return response()->json([
            'masters' => $masters,
            'services' => $services,
            'categories' => $categories
        ]);
    }
}