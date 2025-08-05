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
    // ВРЕМЕННО УБИРАЕМ ВСЕ ЗАВИСИМОСТИ
    public function __construct()
    {
        // Пустой конструктор
    }
    public function index(Request $request)
    {
        // ВРЕМЕННО УПРОЩЕННАЯ ВЕРСИЯ ДЛЯ ДИАГНОСТИКИ
        return Inertia::render('Home', [
            'masters' => [],
            'filters' => [],
            'categories' => [],
            'districts' => [],
            'priceRange' => ['min' => 0, 'max' => 10000],
            'currentCity' => 'Москва'
        ]);
    }
}