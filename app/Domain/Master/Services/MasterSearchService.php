<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Repositories\MasterRepository;
use Illuminate\Http\Request;

/**
 * Сервис для поиска мастеров
 */
class MasterSearchService
{
    private MasterRepository $masterRepository;

    public function __construct(MasterRepository $masterRepository)
    {
        $this->masterRepository = $masterRepository;
    }

    /**
     * Поиск мастеров по фильтрам
     */
    public function searchMasters(Request $request): array
    {
        $filters = $this->prepareFilters($request);
        
        $query = MasterProfile::query()
            ->with(['user', 'services.category'])
            ->where('status', 'active');

        // Применяем фильтры через методы репозитория
        if (!empty($filters['search'])) {
            $this->applyTextSearch($query, $filters['search']);
        }

        if (!empty($filters['category_id'])) {
            $this->applyCategoryFilter($query, $filters['category_id']);
        }

        if (!empty($filters['district'])) {
            $query->where('district', $filters['district']);
        }

        if (!empty($filters['metro'])) {
            $query->where('metro_station', $filters['metro']);
        }

        if (!empty($filters['min_price'])) {
            $this->applyPriceFilter($query, $filters['min_price'], $filters['max_price']);
        }

        if (!empty($filters['age_min'])) {
            $this->applyAgeFilter($query, $filters['age_min'], $filters['age_max']);
        }

        if (!empty($filters['services'])) {
            $this->applyServicesFilter($query, $filters['services']);
        }

        if (!empty($filters['sort'])) {
            $this->applySorting($query, $filters['sort']);
        }

        $masters = $query->paginate(12);

        return [
            'masters' => $masters->items(),
            'pagination' => [
                'current_page' => $masters->currentPage(),
                'last_page' => $masters->lastPage(),
                'per_page' => $masters->perPage(),
                'total' => $masters->total(),
            ],
            'filters' => $filters
        ];
    }

    /**
     * Получить доступные категории для фильтрации
     */
    public function getAvailableCategories(): array
    {
        return \App\Domain\Service\Models\MassageCategory::orderBy('name')->get()->toArray();
    }

    /**
     * Получить подсказки для автодополнения
     */
    public function getSuggestions(string $query): array
    {
        $masters = $this->masterRepository->searchByName($query, 5);

        $categories = \App\Domain\Service\Models\MassageCategory::where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->limit(5)
            ->pluck('name');

        return [
            'masters' => $masters,
            'categories' => $categories
        ];
    }

    /**
     * Подготовить фильтры из запроса
     */
    private function prepareFilters(Request $request): array
    {
        return [
            'search' => $request->get('q'),
            'category_id' => $request->get('category_id'),
            'district' => $request->get('district'),
            'metro' => $request->get('metro'),
            'min_price' => $request->get('min_price'),
            'max_price' => $request->get('max_price'),
            'age_min' => $request->get('age_min'),
            'age_max' => $request->get('age_max'),
            'services' => $request->get('services', []),
            'sort' => $request->get('sort', 'rating')
        ];
    }

    /**
     * Применить текстовый поиск
     */
    private function applyTextSearch($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('display_name', 'like', "%{$search}%")
              ->orWhere('bio', 'like', "%{$search}%")
              ->orWhereHas('services', function ($sq) use ($search) {
                  $sq->where('name', 'like', "%{$search}%")
                     ->orWhere('description', 'like', "%{$search}%");
              });
        });
    }

    /**
     * Применить фильтр по категории
     */
    private function applyCategoryFilter($query, int $categoryId): void
    {
        $query->whereHas('services', function ($q) use ($categoryId) {
            $q->where('massage_category_id', $categoryId);
        });
    }

    /**
     * Применить фильтр по цене
     */
    private function applyPriceFilter($query, int $minPrice, ?int $maxPrice = null): void
    {
        $query->whereHas('services', function ($q) use ($minPrice, $maxPrice) {
            $q->where('price', '>=', $minPrice);
            if ($maxPrice) {
                $q->where('price', '<=', $maxPrice);
            }
        });
    }

    /**
     * Применить фильтр по возрасту
     */
    private function applyAgeFilter($query, int $ageMin, ?int $ageMax = null): void
    {
        $query->where('age', '>=', $ageMin);
        if ($ageMax) {
            $query->where('age', '<=', $ageMax);
        }
    }

    /**
     * Применить фильтр по услугам
     */
    private function applyServicesFilter($query, array $services): void
    {
        $query->whereHas('services', function ($q) use ($services) {
            $q->whereIn('id', $services);
        });
    }

    /**
     * Применить сортировку
     */
    private function applySorting($query, string $sort): void
    {
        switch ($sort) {
            case 'price_asc':
                $query->leftJoin('master_services', 'master_profiles.id', '=', 'master_services.master_profile_id')
                      ->orderBy('master_services.price', 'asc');
                break;
            case 'price_desc':
                $query->leftJoin('master_services', 'master_profiles.id', '=', 'master_services.master_profile_id')
                      ->orderBy('master_services.price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'rating':
            default:
                $query->orderBy('rating_overall', 'desc');
                break;
        }
    }
}