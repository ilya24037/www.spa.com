<?php

namespace App\Application\Http\Controllers;

use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\Services\AdTransformService;
use App\Domain\Service\Services\CategoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер главной страницы
 */
class HomeController extends Controller
{
    public function __construct(
        private AdService $adService,
        private AdTransformService $transformer,
        private CategoryService $categoryService
    ) {}
    
    public function index(Request $request)
    {
        try {
            $ads = $this->adService->getActiveAdsForHome(12);
            $masters = $this->transformer->transformForHomePage($ads)
                ->map(fn($dto) => $dto->toArray());
        } catch (\Exception $e) {
            Log::error('Error loading advertisements', ['error' => $e->getMessage()]);
            $masters = collect([]);
        }
        
        return Inertia::render('Home', [
            'masters' => [
                'data' => $masters,
                'meta' => [
                    'total' => $masters->count(),
                    'per_page' => 12,
                    'current_page' => 1
                ]
            ],
            'filters' => [
                'price_min' => $request->get('price_min'),
                'price_max' => $request->get('price_max'),
                'services' => $request->get('services', []),
                'districts' => $request->get('districts', [])
            ],
            'categories' => $this->categoryService->getActiveCategories(),
            'districts' => $this->categoryService->getDistricts(),
            'priceRange' => $this->categoryService->getPriceRange(),
            'currentCity' => $request->get('city', 'Москва')
        ]);
    }
}