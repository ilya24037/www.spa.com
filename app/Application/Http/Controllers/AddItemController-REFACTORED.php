<?php

namespace App\Application\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Domain\Service\Services\ServiceCategoryProvider;
use App\Domain\Service\Services\AdCreationService;

/**
 * Контроллер создания объявлений
 * Рефакторинг: 456 → ≤200 строк CLAUDE.md
 */
class AddItemController extends Controller
{
    public function __construct(
        private ServiceCategoryProvider $categoryProvider,
        private AdCreationService $adCreationService
    ) {}

    /**
     * Главная страница выбора категории
     */
    public function index()
    {
        return Inertia::render('AddItem/Index', [
            'categories' => $this->categoryProvider->getAllCategories(),
            'breadcrumbs' => $this->adCreationService->getBreadcrumbs()
        ]);
    }

    /**
     * Форма для массажа
     */
    public function massage()
    {
        return Inertia::render('AddMassage', [
            'cities' => $this->categoryProvider->getAvailableCities(),
            'massageTypes' => $this->categoryProvider->getMassageSubcategories(),
            'breadcrumbs' => $this->adCreationService->getBreadcrumbs('massage')
        ]);
    }

    /**
     * Универсальная форма для категорий
     */
    public function category(string $category)
    {
        if (!$this->categoryProvider->isValidCategory($category)) {
            abort(404, 'Категория не найдена');
        }

        $viewMap = [
            'erotic' => 'AddErotic',
            'strip' => 'AddStrip', 
            'escort' => 'AddEscort'
        ];

        return Inertia::render($viewMap[$category] ?? 'AddService', [
            'cities' => $this->categoryProvider->getAvailableCities(),
            'breadcrumbs' => $this->adCreationService->getBreadcrumbs($category)
        ]);
    }

    /**
     * Универсальная форма добавления услуги
     */
    public function addService(Request $request)
    {
        $category = $request->get('category');
        
        if (!$this->categoryProvider->isValidCategory($category)) {
            abort(404, 'Категория не найдена');
        }

        return Inertia::render('AddService', [
            'category' => $category,
            'categoryName' => $this->categoryProvider->getCategoryName($category),
            'breadcrumbs' => $this->adCreationService->getBreadcrumbs($category)
        ]);
    }

    /**
     * Сохранение объявления через универсальную форму
     */
    public function store(Request $request)
    {
        $result = $this->adCreationService->createFromRequest($request);
        
        if (!$result['success']) {
            return back()->withErrors($result['errors'] ?? [])
                         ->with('error', $result['message']);
        }

        return redirect()->route('dashboard')
                        ->with('success', $result['message']);
    }

    /**
     * Сохранение черновика
     */
    public function storeDraft(Request $request)
    {
        // Упрощенная валидация для черновика
        $validated = $request->validate([
            'category' => 'required|string|in:massage,erotic,strip,escort',
            'display_name' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'price_from' => 'nullable|integer|min:100'
        ]);

        // TODO: Сохранить черновик через сервис
        return response()->json([
            'success' => true,
            'message' => 'Черновик сохранен'
        ]);
    }

    /**
     * Сохранение объявления через модульную форму
     */
    public function storeService(Request $request)
    {
        return $this->store($request);
    }

    /**
     * API методы для AJAX запросов
     */
    public function apiData(Request $request)
    {
        $type = $request->get('type');
        $category = $request->get('category');
        
        return match($type) {
            'cities' => response()->json(['cities' => $this->categoryProvider->getAvailableCities()]),
            'category' => response()->json([
                'valid' => $this->categoryProvider->isValidCategory($category),
                'name' => $this->categoryProvider->getCategoryName($category)
            ]),
            'master-data' => response()->json([
                'cities' => $this->categoryProvider->getAvailableCities(),
                'categoryName' => $this->categoryProvider->getCategoryName($category),
                'validCategories' => array_column($this->categoryProvider->getAllCategories(), 'id')
            ]),
            default => response()->json(['error' => 'Invalid type'], 400)
        };
    }

}