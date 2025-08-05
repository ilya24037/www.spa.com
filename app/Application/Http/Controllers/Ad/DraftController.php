<?php

namespace App\Application\Http\Controllers\Ad;

use App\Application\Http\Controllers\Controller;
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
            $requestId = $request->input('id');
            $adId = is_numeric($requestId) ? (int)$requestId : null;
            
            $existingAd = $this->adService->findExistingDraft($request->user()->id, $adId);
            
            $ad = $this->adService->saveDraft(
                $request->validated(), 
                $request->user(), 
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
     * Удалить черновик
     */
    public function destroy(Ad $ad)
    {
        $this->authorize('delete', $ad);
        
        try {
            $this->adService->deleteDraft($ad);
            
            return redirect()->route('profile.items.draft')
                ->with('success', 'Черновик удален');
                
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
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
        
        try {
            // Проверяем готовность к публикации через сервис
            $validation = $this->adService->validateDraftForPublication($ad);
            
            if (!$validation['ready']) {
                return back()->withErrors([
                    'error' => 'Заполните обязательные поля: ' . implode(', ', $validation['missing_fields'])
                ]);
            }
            
            $this->adService->publish($ad);
            
            return redirect()->route('select-plan', ['ad' => $ad->id])
                ->with('success', 'Черновик готов к публикации');
                
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Ошибка при публикации: ' . $e->getMessage()
            ]);
        }
    }
}