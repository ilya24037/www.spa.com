<?php

namespace App\Application\Http\Controllers\Ad;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveAdDraftRequest;
use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\Models\Ad;
use Illuminate\Support\Facades\Auth;

/**
 * Контроллер для работы с черновиками объявлений
 * Управляет сохранением и обновлением черновиков
 */
class DraftController extends Controller
{
    private AdService $adService;
    
    public function __construct(AdService $adService)
    {
        $this->adService = $adService;
    }

    /**
     * Сохранить черновик объявления
     */
    public function store(SaveAdDraftRequest $request)
    {
        try {
            $existingAd = $this->findExistingDraft($request);
            
            $ad = $this->adService->saveDraft(
                $request->validated(), 
                Auth::user(), 
                $existingAd
            );
            
            $message = $existingAd ? 'Черновик обновлен!' : 'Черновик сохранен!';
            
            return redirect('/profile/items/draft/all')
                ->with('success', $message);
            
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Ошибка при сохранении черновика: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Найти существующий черновик
     */
    private function findExistingDraft(SaveAdDraftRequest $request): ?Ad
    {
        $requestId = $request->input('id');
        
        if (!$requestId || $requestId === 'null' || $requestId === '') {
            return null;
        }
        
        $adId = is_numeric($requestId) ? (int)$requestId : null;
        
        if (!$adId) {
            return null;
        }
        
        return Ad::where('id', $adId)
            ->where('user_id', Auth::id())
            ->where('status', 'draft')
            ->first();
    }

    /**
     * Удалить черновик
     */
    public function destroy(Ad $ad)
    {
        $this->authorize('delete', $ad);
        
        // Проверяем, что это черновик
        if ($ad->status !== 'draft') {
            return back()->withErrors([
                'error' => 'Можно удалять только черновики'
            ]);
        }
        
        try {
            $ad->delete();
            
            return redirect()->route('profile.items.draft')
                ->with('success', 'Черновик удален');
                
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Ошибка при удалении черновика: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Преобразовать черновик в активное объявление
     */
    public function publish(Ad $ad)
    {
        $this->authorize('update', $ad);
        
        // Проверяем, что это черновик
        if ($ad->status !== 'draft') {
            return back()->withErrors([
                'error' => 'Опубликовать можно только черновик'
            ]);
        }
        
        try {
            // Проверяем готовность к публикации
            if (!$ad->canBePublished()) {
                $missingFields = $ad->getMissingFieldsForPublication();
                return back()->withErrors([
                    'error' => 'Заполните обязательные поля: ' . implode(', ', $missingFields)
                ]);
            }
            
            $this->adService->publish($ad);
            
            return redirect()->route('select-plan', ['ad' => $ad->id])
                ->with('success', 'Черновик готов к публикации');
                
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Ошибка при публикации: ' . $e->getMessage()
            ]);
        }
    }
}