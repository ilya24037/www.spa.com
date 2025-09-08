<?php

namespace App\Application\Http\Controllers\Ad;

use App\Application\Http\Controllers\Controller;
use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Actions\PublishAdAction;
use App\Domain\Ad\Actions\ArchiveAdAction;
use Illuminate\Http\RedirectResponse;

/**
 * Контроллер для управления статусами объявлений
 * Простой и понятный, только изменение статусов
 */
class AdStatusController extends Controller
{
    public function __construct(
        private AdService $adService,
        private PublishAdAction $publishAction,
        private ArchiveAdAction $archiveAction
    ) {}

    /**
     * Активировать объявление
     */
    public function activate(Ad $ad): RedirectResponse
    {
        $this->authorize('update', $ad);

        $this->publishAction->execute($ad);

        return redirect()
            ->route('profile.items.active')
            ->with('success', 'Объявление активировано');
    }

    /**
     * Деактивировать объявление
     */
    public function deactivate(Ad $ad): RedirectResponse
    {
        $this->authorize('update', $ad);

        $this->adService->deactivate($ad);

        return redirect()
            ->route('profile.items.inactive')
            ->with('success', 'Объявление деактивировано');
    }

    /**
     * Архивировать объявление
     */
    public function archive(Ad $ad): RedirectResponse
    {
        $this->authorize('update', $ad);

        $result = $this->archiveAction->execute($ad->id, auth()->id());

        if (!$result['success']) {
            return back()->withErrors([
                'error' => $result['message']
            ]);
        }

        return redirect()
            ->route('profile.items.archive')
            ->with('success', $result['message']);
    }

    /**
     * Восстановить из архива
     */
    public function restore(Ad $ad): RedirectResponse
    {
        $this->authorize('update', $ad);

        $this->adService->restoreFromArchive($ad);

        return redirect()
            ->route('profile.items.active')
            ->with('success', 'Объявление восстановлено');
    }

    /**
     * Опубликовать черновик
     */
    public function publish(Ad $ad): RedirectResponse
    {
        $this->authorize('update', $ad);

        // Проверяем готовность к публикации
        $validation = $this->adService->validateDraftForPublication($ad);
        
        if (!$validation['ready']) {
            return back()->withErrors([
                'error' => 'Заполните обязательные поля: ' . implode(', ', $validation['missing_fields'])
            ]);
        }

        $this->publishAction->execute($ad);

        return redirect()
            ->route('profile.items.active')
            ->with('success', 'Объявление опубликовано');
    }
}