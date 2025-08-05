<?php

namespace App\Application\Http\Controllers\Ad;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\CreateAdRequest;
use App\Application\Http\Requests\UpdateAdRequest;
use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\DTOs\CreateAdDTO;
use App\Enums\AdStatus;
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
        try {
            // Создаем DTO из валидированных данных запроса
            $validatedData = $request->validated();
            $validatedData['user_id'] = $request->user()->id;
            
            $dto = CreateAdDTO::fromRequest($validatedData);
            $ad = $this->adService->createFromDTO($dto);
            
            // Пытаемся сразу опубликовать
            $this->adService->publish($ad);
            
            return redirect()->route('dashboard')
                ->with('success', 'Объявление успешно создано и опубликовано!');
            
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('dashboard')
                ->with('warning', 'Объявление сохранено как черновик. ' . $e->getMessage());
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ошибка при создании объявления: ' . $e->getMessage()]);
        }
    }



    /**
     * Опубликовать объявление
     */
    public function publish(CreateAdRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $validatedData['user_id'] = $request->user()->id;
            
            $dto = CreateAdDTO::fromRequest($validatedData);
            $ad = $this->adService->createFromDTO($dto);
            $this->adService->publish($ad);

            return response()->json([
                'success' => true,
                'message' => 'Объявление готово к публикации',
                'id' => $ad->id,
                'redirect' => route('select-plan', ['ad' => $ad->id])
            ]);
            
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'errors' => ['validation' => [$e->getMessage()]],
                'message' => 'Заполните все обязательные поля'
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['error' => [$e->getMessage()]],
                'message' => 'Ошибка при создании объявления'
            ], 500);
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