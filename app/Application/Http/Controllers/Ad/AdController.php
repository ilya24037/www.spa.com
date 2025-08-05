<?php

namespace App\Application\Http\Controllers\Ad;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Ad\CreateAdRequest;
use App\Application\Http\Requests\Ad\UpdateAdRequest;
use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\DTOs\CreateAdDTO;
use App\Domain\Ad\Enums\AdStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

/**
 * Основной контроллер для управления объявлениями
 * Отвечает за создание, редактирование и публикацию
 */
class AdController extends Controller
{
    private AdService $adService;
    
    public function __construct(AdService $adService)
    {
        $this->adService = $adService;
    }
    
    /**
     * Страница создания объявления
     */
    public function addItem()
    {
        return Inertia::render('AddItem');
    }

    /**
     * Сохранить объявление
     */
    public function store(CreateAdRequest $request)
    {
        $result = $this->createAdFromRequest($request);
        
        if ($result['success']) {
            return redirect()->route('dashboard')
                ->with('success', 'Объявление успешно создано и опубликовано!');
        }
        
        if ($result['is_draft']) {
            return redirect()->route('dashboard')
                ->with('warning', 'Объявление сохранено как черновик. ' . $result['message']);
        }
        
        return back()->withErrors(['error' => $result['message']]);
    }

    /**
     * Опубликовать объявление (AJAX)
     */
    public function publish(CreateAdRequest $request)
    {
        $result = $this->createAdFromRequest($request);
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Объявление готово к публикации',
                'id' => $result['ad']->id,
                'redirect' => route('select-plan', ['ad' => $result['ad']->id])
            ]);
        }
        
        if ($result['is_draft']) {
            return response()->json([
                'success' => false,
                'errors' => ['validation' => [$result['message']]],
                'message' => 'Заполните все обязательные поля'
            ], 422);
        }
        
        return response()->json([
            'success' => false,
            'errors' => ['error' => [$result['message']]],
            'message' => 'Ошибка при создании объявления'
        ], 500);
    }

    /**
     * Общая логика создания объявления (DRY принцип)
     */
    private function createAdFromRequest(CreateAdRequest $request): array
    {
        try {
            $validatedData = $request->validated();
            $validatedData['user_id'] = $request->user()->id;
            
            $dto = CreateAdDTO::fromRequest($validatedData);
            $ad = $this->adService->createFromDTO($dto);
            $this->adService->publish($ad);
            
            return ['success' => true, 'ad' => $ad];
            
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false, 
                'is_draft' => true, 
                'message' => $e->getMessage()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false, 
                'is_draft' => false, 
                'message' => 'Ошибка при создании объявления: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Показать форму редактирования
     */
    public function edit(Ad $ad)
    {
        $this->authorize('update', $ad);

        // Проверяем возможность редактирования через сервис
        if (!$this->adService->canEdit($ad)) {
            return redirect()->route('my-ads.index')
                ->with('error', 'Можно редактировать только черновики');
        }

        $adData = $this->adService->prepareAdDataForView($ad);

        return Inertia::render('EditAd', [
            'ad' => $adData
        ]);
    }

    /**
     * Обновить объявление
     */
    public function update(UpdateAdRequest $request, Ad $ad)
    {
        $this->authorize('update', $ad);

        try {
            $ad = $this->adService->update($ad, $request->validated());
            
            return redirect()->route('profile.items.active')
                ->with('success', 'Объявление успешно обновлено!');
                
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ошибка при обновлении: ' . $e->getMessage()]);
        }
    }

    /**
     * Удалить объявление
     */
    public function destroy(Ad $ad)
    {
        $this->authorize('delete', $ad);

        try {
            $this->adService->delete($ad);
            
            return redirect()->route('profile.items.active')
                ->with('success', 'Объявление удалено');
                
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ошибка при удалении: ' . $e->getMessage()]);
        }
    }



}