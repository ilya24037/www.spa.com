<?php

namespace App\Application\Http\Controllers;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Services\MasterService;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Service\Models\MassageCategory;
use App\Domain\Master\DTOs\MasterFilterDTO;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    protected MasterService $masterService;
    protected MasterRepository $masterRepository;

    public function __construct(MasterService $masterService, MasterRepository $masterRepository)
    {
        $this->masterService = $masterService;
        $this->masterRepository = $masterRepository;
    }
    public function index(Request $request)
    {
        // Получаем фильтры из запроса
        $filters = $request->only([
            'q', 'category', 'price_min', 'price_max', 
            'rating', 'district', 'service_type', 'sort'
        ]);

        // Создаем DTO для фильтров
        $filters['per_page'] = 12; // Устанавливаем количество элементов на главной
        $filterDTO = MasterFilterDTO::fromArray($filters);

        // Поиск мастеров через сервис
        $masters = $this->masterService->search($filterDTO);

        // Получаем данные для фильтров через репозиторий
        $categories = $this->masterRepository->getActiveCategories();
        $districts = $this->masterRepository->getAvailableDistricts();
        $priceRange = $this->masterRepository->getPriceRange();

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