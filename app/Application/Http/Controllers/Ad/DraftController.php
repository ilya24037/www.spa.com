<?php

namespace App\Application\Http\Controllers\Ad;

use App\Application\Http\Controllers\Controller;
use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\Services\DraftService;
use App\Domain\Ad\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер для работы с черновиками объявлений
 * Простой и понятный, только черновики
 */
class DraftController extends Controller
{
    public function __construct(
        private AdService $adService,
        private DraftService $draftService
    ) {}

    /**
     * Показать черновик
     */
    public function show(Ad $ad): Response
    {
        $this->authorize('view', $ad);

        return Inertia::render('Draft/Show', [
            'ad' => $this->draftService->prepareForDisplay($ad)
        ]);
    }

    /**
     * Создать новый черновик или обновить существующий
     * Как было до рефакторинга - если передан ad_id в данных, обновляем его
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        // Проверяем, передан ли ID черновика в данных формы
        $adId = $request->input('ad_id') ?: $request->input('id');
        
        // Приводим к integer если передан
        $adId = $adId ? (int) $adId : null;
        
        // Временное логирование для отладки
        Log::info('DraftController::store', [
            'ad_id_raw' => $request->input('ad_id'),
            'id_raw' => $request->input('id'),
            'ad_id_parsed' => $adId,
            'user_id' => Auth::id(),
            'title' => $request->input('title')
        ]);
        
        // Создаем или обновляем черновик
        $ad = $this->draftService->saveOrUpdate(
            $request->all(),
            Auth::user(),
            $adId // Передаем ID если он есть
        );
        
        Log::info('DraftController::store result', [
            'result_ad_id' => $ad->id,
            'was_update' => $adId ? 'yes' : 'no'
        ]);

        // Для Inertia запросов
        if ($request->header('X-Inertia')) {
            return redirect()
                ->route('profile.items.draft')
                ->with('success', 'Черновик сохранен');
        }

        // Для AJAX запросов
        return response()->json([
            'success' => true,
            'message' => $adId ? 'Черновик обновлен' : 'Черновик сохранен',
            'ad_id' => $ad->id
        ]);
    }

    /**
     * Обновить черновик
     */
    public function update(Request $request, Ad $ad): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $ad);

        $ad = $this->draftService->saveOrUpdate(
            $request->all(),
            Auth::user(),
            $ad->id
        );

        // Для Inertia запросов
        if ($request->header('X-Inertia')) {
            return redirect()
                ->route('profile.items.draft')
                ->with('success', 'Черновик обновлен');
        }

        // Для AJAX запросов
        return response()->json([
            'success' => true,
            'message' => 'Черновик обновлен'
        ]);
    }

    /**
     * Удалить черновик
     */
    public function destroy(Ad $ad): RedirectResponse
    {
        $this->authorize('delete', $ad);

        $this->draftService->delete($ad);

        return redirect()
            ->route('profile.items.draft')
            ->with('success', 'Черновик удален');
    }
}