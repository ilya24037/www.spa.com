<?php

namespace App\Application\Http\Controllers;

use App\Domain\Ad\Services\AdProfileService;
use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\Models\Ad;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MyAdsController extends Controller
{
    private AdProfileService $adProfileService;
    private AdService $adService;

    public function __construct(AdProfileService $adProfileService, AdService $adService)
    {
        $this->adProfileService = $adProfileService;
        $this->adService = $adService;
    }

    /**
     * Отображение списка объявлений пользователя
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'waiting');
        $user = $request->user();

        // Маппинг табов на статусы
        $statusMap = [
            'waiting' => 'waiting_payment',
            'active' => ['active', 'pending_moderation'], // Активные включают и на модерации
            'drafts' => 'draft',
            'archived' => 'archived'
        ];

        $status = $statusMap[$tab] ?? 'waiting_payment';

        // Получаем объявления через сервис
        $profiles = $this->adProfileService->getUserAdsByStatus($user, $status);
        
        // Получаем счетчики через сервис  
        $counts = $this->adProfileService->getUserAdCounts($user);
        
        return Inertia::render('Dashboard', [
            'profiles' => $profiles,
            'isMyAds' => true,
            'counts' => $counts,
            'currentTab' => $tab,
            'activeTab' => $tab
        ]);
    }
    
    /**
     * Оплата объявления  
     */
    public function pay(Ad $ad)
    {
        $this->authorize('update', $ad);
        
        try {
            $this->adService->markAsPaid($ad);
            return back()->with('success', 'Объявление успешно оплачено');
        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка при оплате: ' . $e->getMessage());
        }
    }
    
    /**
     * Деактивация объявления (уже не актуально)
     */
    public function deactivate(Ad $ad)
    {
        $this->authorize('update', $ad);
        
        try {
            $this->adService->archive($ad);
            return back()->with('success', 'Объявление перемещено в архив');
        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка при архивировании: ' . $e->getMessage());
        }
    }
    
    /**
     * Удаление объявления
     */
    public function destroy(Ad $ad)
    {
        // Проверяем, что пользователь владелец объявления
        if ($ad->user_id !== auth()->id()) {
            abort(403, 'У вас нет прав для удаления этого объявления');
        }
        
        try {
            $wasDraft = $ad->status === 'draft' || $ad->status === \App\Domain\Ad\Enums\AdStatus::DRAFT;
            $this->adService->delete($ad);

            $message = $wasDraft ? 'Черновик удален' : 'Объявление удалено';

            // Для черновиков редиректим на страницу черновиков
            if ($wasDraft) {
                return redirect()
                    ->to('/profile/items/draft/all')
                    ->with('success', 'Черновик удален');
            }

            // Возвращаем успешный ответ для Inertia
            if (request()->header('X-Inertia')) {
                return back()->with('success', $message);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            if (request()->header('X-Inertia')) {
                return back()->withErrors(['error' => 'Ошибка при удалении: ' . $e->getMessage()]);
            }
            
            return back()->with('error', 'Ошибка при удалении: ' . $e->getMessage());
        }
    }
    
    /**
     * Публикация черновика
     */
    public function publish(Ad $ad)
    {
        $this->authorize('update', $ad);
        
        try {
            $this->adService->publish($ad);
            return back()->with('success', 'Объявление отправлено на модерацию');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка при публикации: ' . $e->getMessage());
        }
    }
    
    /**
     * Восстановление из архива
     */
    public function restore(Ad $ad)
    {
        $this->authorize('update', $ad);
        
        try {
            $this->adService->restore($ad);
            return back()->with('success', 'Объявление восстановлено в черновики');
        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка при восстановлении: ' . $e->getMessage());
        }
    }
}
