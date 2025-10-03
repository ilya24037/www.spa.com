<?php

namespace App\Application\Http\Controllers;

use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\Services\AdTransformService;
use App\Domain\Service\Services\CategoryService;
use App\Application\Http\Resources\Ad\AdResource;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            // Get active ads for home page
            $ads = $this->adService->getActiveAdsForHome(12);

            // Transform ads to "masters" format for compatibility with existing components
            $masters = $this->transformer->transformForHomePage($ads)
                ->map(fn($dto) => $dto->toArray());
        } catch (\Exception $e) {
            $ads = collect([]);
            $masters = collect([]);
        }

        return Inertia::render('Home', [
            // Return transformed data as "masters" for existing components
            'masters' => [
                'data' => $masters,
                'meta' => [
                    'total' => $masters->count(),
                    'per_page' => 12,
                    'current_page' => 1
                ]
            ],
            // Keep original ads for compatibility
            'ads' => AdResource::collection($ads),
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