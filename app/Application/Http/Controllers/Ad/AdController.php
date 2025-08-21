<?php

namespace App\Application\Http\Controllers\Ad;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Ad\CreateAdRequest;
use App\Application\Http\Requests\Ad\UpdateAdRequest;
use App\Application\Http\Resources\Ad\AdResource;
use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\Services\DraftService;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\DTOs\CreateAdDTO;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Контроллер для базовых CRUD операций с объявлениями
 * Простой и понятный, следует принципу KISS
 */
class AdController extends Controller
{
    public function __construct(
        private AdService $adService
    ) {}

    /**
     * Список объявлений
     */
    public function index(): Response
    {
        $ads = $this->adService->getActiveAds(
            perPage: 20,
            withRelations: ['photos', 'user']
        );

        return Inertia::render('Ads/Index', [
            'ads' => AdResource::collection($ads)
        ]);
    }

    /**
     * Просмотр объявления
     */
    public function show(Ad $ad): Response
    {
        // Проверяем доступ
        if (!$ad->isActive() && (!auth()->check() || auth()->id() !== $ad->user_id)) {
            abort(404);
        }

        // Увеличиваем просмотры
        $this->adService->incrementViews($ad);

        $ad->load(['user.profile']);

        return Inertia::render('Ads/Show', [
            'ad' => new AdResource($ad),
            'similarAds' => AdResource::collection(
                $this->adService->getSimilarAds($ad, limit: 4)
            )
        ]);
    }

    /**
     * Форма создания
     */
    public function create(): Response
    {
        // Рендерим новую страницу создания объявления
        // которая правильно очищает localStorage
        return Inertia::render('Ad/Create');
    }

    /**
     * Создание объявления
     */
    public function store(CreateAdRequest $request): RedirectResponse
    {
        $dto = CreateAdDTO::fromArray(
            array_merge(
                $request->validated(),
                ['user_id' => Auth::id()]
            )
        );

        $ad = $this->adService->createFromDTO($dto);

        // Если нужно сразу опубликовать
        if ($request->boolean('publish_immediately', true)) {
            $this->adService->publish($ad);
        }

        return redirect()
            ->route('profile.items.active')
            ->with('success', 'Объявление успешно создано');
    }

    /**
     * Форма редактирования
     */
    public function edit(Ad $ad): Response
    {
        // Защита от несуществующих объявлений
        if (!$ad->exists) {
            abort(404, 'Объявление не найдено');
        }
        
        $this->authorize('update', $ad);

        // Для черновиков используем DraftService для правильной подготовки данных
        if ($ad->status === 'draft') {
            $draftService = app(\App\Domain\Ad\Services\DraftService::class);
            $preparedData = $draftService->prepareForDisplay($ad);
            
            // ВАЖНО: Убедимся, что ID всегда присутствует и имеет правильный тип
            $preparedData['id'] = (int) $ad->id;
            
            return Inertia::render('Ad/Edit', [
                'ad' => $preparedData,
                'isActive' => false
            ]);
        }

        // Для активных объявлений используем стандартный AdResource
        return Inertia::render('Ad/Edit', [
            'ad' => new AdResource($ad),
            'isActive' => $ad->isActive()
        ]);
    }

    /**
     * Обновление объявления
     */
    public function update(UpdateAdRequest $request, Ad $ad): RedirectResponse
    {
        $this->authorize('update', $ad);

        $this->adService->update($ad, $request->validated());

        return redirect()
            ->route('ads.show', $ad)
            ->with('success', 'Объявление успешно обновлено');
    }

    /**
     * Удаление объявления
     */
    public function destroy(Ad $ad): RedirectResponse
    {
        $this->authorize('delete', $ad);

        $this->adService->delete($ad);

        return redirect()
            ->route('profile.items')
            ->with('success', 'Объявление успешно удалено');
    }
}