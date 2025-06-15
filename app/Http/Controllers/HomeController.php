<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
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

        // Строим запрос
        $query = MasterProfile::with(['user', 'services'])
            ->where('is_active', true);

        // Поиск по тексту
        if (!empty($filters['q'])) {
            $searchTerm = $filters['q'];
            $query->where(function($q) use ($searchTerm) {
                $q->whereHas('user', function($userQuery) use ($searchTerm) {
                    $userQuery->where('name', 'like', "%{$searchTerm}%");
                })
                ->orWhere('bio', 'like', "%{$searchTerm}%")
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
        if (!empty($filters['price_min']) || !empty($filters['price_max'])) {
            $query->whereHas('services', function($q) use ($filters) {
                if (!empty($filters['price_min'])) {
                    $q->where('price', '>=', $filters['price_min']);
                }
                if (!empty($filters['price_max'])) {
                    $q->where('price', '<=', $filters['price_max']);
                }
            });
        }

        // Фильтр по рейтингу
        if (!empty($filters['rating'])) {
            $query->where('rating', '>=', $filters['rating']);
        }

        // Фильтр по району
        if (!empty($filters['district'])) {
            $query->where('district', $filters['district']);
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
                $query->select('master_profiles.*')
                    ->leftJoin('services', 'master_profiles.id', '=', 'services.master_profile_id')
                    ->groupBy('master_profiles.id')
                    ->orderBy(DB::raw('MIN(services.price)'), 'asc');
                break;
            case 'price_desc':
                $query->select('master_profiles.*')
                    ->leftJoin('services', 'master_profiles.id', '=', 'services.master_profile_id')
                    ->groupBy('master_profiles.id')
                    ->orderBy(DB::raw('MIN(services.price)'), 'desc');
                break;
            case 'reviews':
                $query->orderBy('reviews_count', 'desc');
                break;
            default:
                $query->orderBy('rating', 'desc');
        }

        // Получаем данные для фильтров
        $categories = MassageCategory::whereNull('parent_id')
            ->with('children')
            ->get();

        $districts = MasterProfile::where('is_active', true)
            ->select('district')
            ->distinct()
            ->whereNotNull('district')
            ->pluck('district');

        $priceRange = DB::table('services')
            ->selectRaw('MIN(price) as min, MAX(price) as max')
            ->first();

        return Inertia::render('Home', [
            'masters' => $query->paginate(12)->withQueryString(),
            'filters' => $filters,
            'categories' => $categories,
            'districts' => $districts,
            'priceRange' => $priceRange
        ]);
    }
}