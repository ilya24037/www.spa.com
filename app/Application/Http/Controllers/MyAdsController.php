<?php

namespace App\Application\Http\Controllers;

use App\Models\Ad;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MyAdsController extends Controller
{
    /**
     * Отображение списка объявлений пользователя
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'waiting');
        $user = $request->user();
        
        // Базовый запрос для объявлений пользователя
        $query = Ad::where('user_id', $user->id)
                   ->orderBy('created_at', 'desc');
        
        // Фильтрация по статусу
        switch ($tab) {
            case 'waiting':
                $query->waitingAction();
                break;
            case 'active':
                $query->active();
                break;
            case 'drafts':
                $query->drafts();
                break;
            case 'archived':
                $query->archived();
                break;
        }
        
        $ads = $query->paginate(10);
        
        // Подсчет количества объявлений по статусам
        $counts = [
            'waiting' => Ad::where('user_id', $user->id)->waitingAction()->count(),
            'active' => Ad::where('user_id', $user->id)->active()->count(),
            'drafts' => Ad::where('user_id', $user->id)->drafts()->count(),
            'archived' => Ad::where('user_id', $user->id)->archived()->count(),
        ];
        
        return Inertia::render('Dashboard', [
            'ads' => $ads,
            'isMyAds' => true,
            'counts' => $counts,
            'currentTab' => $tab
        ]);
    }
    
    /**
     * Оплата объявления
     */
    public function pay(Ad $ad)
    {
        $this->authorize('update', $ad);
        
        // Здесь будет логика оплаты
        // Пока просто помечаем как оплаченное
        $ad->update([
            'status' => Ad::STATUS_ACTIVE,
            'is_paid' => true,
            'paid_at' => now(),
            'expires_at' => now()->addDays(30) // На 30 дней
        ]);
        
        return back()->with('success', 'Объявление успешно оплачено');
    }
    
    /**
     * Деактивация объявления (уже не актуально)
     */
    public function deactivate(Ad $ad)
    {
        $this->authorize('update', $ad);
        
        $ad->update([
            'status' => Ad::STATUS_ARCHIVED
        ]);
        
        return back()->with('success', 'Объявление перемещено в архив');
    }
    
    /**
     * Удаление объявления
     */
    public function destroy(Ad $ad)
    {
        $this->authorize('delete', $ad);
        
        // Запоминаем статус для правильного перенаправления
        $wasDraft = $ad->status === Ad::STATUS_DRAFT;
        
        // ВАЖНО: Реально удаляем объявление из базы данных
        $ad->delete();
        
        // Для AJAX запросов (Inertia.js) возвращаем redirect
        if (request()->header('X-Inertia')) {
            if ($wasDraft) {
                return redirect()->route('profile.items.draft')->with('success', 'Черновик удален');
            }
            return back()->with('success', 'Объявление удалено');
        }
        
        // Для обычных запросов
        if ($wasDraft) {
            return redirect()->route('profile.items.draft')->with('success', 'Черновик удален');
        }
        
        return back()->with('success', 'Объявление удалено');
    }
    
    /**
     * Публикация черновика
     */
    public function publish(Ad $ad)
    {
        $this->authorize('update', $ad);
        
        if ($ad->status !== Ad::STATUS_DRAFT) {
            return back()->with('error', 'Только черновики можно опубликовать');
        }
        
        $ad->update([
            'status' => Ad::STATUS_WAITING_PAYMENT
        ]);
        
        return back()->with('success', 'Объявление отправлено на модерацию');
    }
    
    /**
     * Восстановление из архива
     */
    public function restore(Ad $ad)
    {
        $this->authorize('update', $ad);
        
        if ($ad->status !== Ad::STATUS_ARCHIVED) {
            return back()->with('error', 'Только архивные объявления можно восстановить');
        }
        
        $ad->update([
            'status' => Ad::STATUS_DRAFT
        ]);
        
        return back()->with('success', 'Объявление восстановлено в черновики');
    }
}
