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

        // Перенаправляем на страницу успеха с данными объявления
        return redirect()->route('additem.success', ['ad' => $result['master']->id]);
    }

    /**
     * Страница успешной публикации объявления
     */
    public function success($masterId)
    {
        $masterProfile = \App\Domain\Master\Models\MasterProfile::findOrFail($masterId);

        // Проверяем права доступа
        if ($masterProfile->user_id !== auth()->id()) {
            abort(403, 'Доступ запрещен');
        }

        // Загружаем связанные данные
        $masterProfile->load(['masterServices']);

        // Подготавливаем услуги в формате, ожидаемом Success.vue
        $services = $masterProfile->masterServices->map(function ($service) {
            return [
                'name' => $service->name,
                'price' => $service->price,
                'duration' => $service->duration ?? null
            ];
        })->toArray();

        return Inertia::render('AddItem/Success', [
            'ad' => [
                'id' => $masterProfile->id,
                'title' => $masterProfile->display_name, // display_name как title
                'description' => $masterProfile->description,
                'status' => 'active', // MasterProfile всегда active
                'is_published' => $masterProfile->is_published,
                'moderated_at' => $masterProfile->moderated_at,
                'created_at' => $masterProfile->created_at,
                'services' => $services,
                'specialty' => $masterProfile->display_name, // для совместимости
                'location' => null // MasterProfile не имеет geo поля
            ]
        ]);
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