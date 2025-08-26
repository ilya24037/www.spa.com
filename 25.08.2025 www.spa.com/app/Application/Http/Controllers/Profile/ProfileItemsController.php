<?php

namespace App\Application\Http\Controllers\Profile;

/**
 * ✅ DDD РЕФАКТОРИНГ ПРИМЕНЕН:
 * - Заменены прямые связи на Integration Services
 * - Удалены циклические зависимости между доменами
 * - Применены Events для междоменного взаимодействия
 * 
 * Обновлено автоматически: 2025-08-05T06:11:58.034Z
 */


use App\Application\Http\Controllers\Controller;
use App\Domain\Ad\Services\AdProfileService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Контроллер для управления объявлениями в личном кабинете
 * Отвечает за отображение объявлений по статусам
 */
class ProfileItemsController extends Controller
{
    private AdProfileService $adProfileService;

    public function __construct(AdProfileService $adProfileService)
    {
        $this->adProfileService = $adProfileService;
    }
    /**
     * Отображение личного кабинета - сразу показываем активные объявления
     */
    public function index(Request $request)
    {
        return $this->renderItemsByStatus($request, 'active', 'Мои объявления');
    }

    /**
     * Активные объявления
     */
    public function activeItems(Request $request)
    {
        return $this->renderItemsByStatus($request, 'active', 'Активные');
    }

    /**
     * Черновики
     */
    public function draftItems(Request $request)
    {
        return $this->renderItemsByStatus($request, 'draft', 'Черновики');
    }

    /**
     * Объявления, ждущие действий
     */
    public function inactiveItems(Request $request)
    {
        return $this->renderItemsByStatus($request, 'waiting_payment', 'Ждут действий');
    }

    /**
     * Старые объявления
     */
    public function oldItems(Request $request)
    {
        return $this->renderItemsByStatus($request, 'archived', 'Старые');
    }

    /**
     * Архивные объявления
     */
    public function archiveItems(Request $request)
    {
        return $this->renderItemsByStatus($request, 'archived', 'Архив');
    }

    /**
     * Общий метод для рендеринга объявлений по статусу
     */
    private function renderItemsByStatus($request, $status, $title)
    {
        $user = $request->user();
        
        // Получаем объявления через сервис
        $profiles = $this->adProfileService->getUserAdsByStatus($user, $status);
        
        // Получаем счетчики через сервис
        $counts = $this->adProfileService->getUserAdCounts($user);
        
        // Статистика пользователя через сервис
        $userStats = $this->adProfileService->getUserStats($user);
        
        return Inertia::render('Dashboard', [
            'profiles' => $profiles,
            'counts' => $counts,
            'userStats' => $userStats,
            'activeTab' => $status === 'waiting_payment' ? 'inactive' : $status,
            'title' => $title
        ]);
    }

}