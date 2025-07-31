<?php

namespace App\Http\Controllers;

use App\Application\Http\Controllers\Ad\AdController as BaseAdController;
use App\Application\Http\Controllers\Ad\DraftController;
use App\Application\Http\Controllers\Ad\AdMediaController;
use App\Models\Ad;
use Illuminate\Http\Request;

/**
 * Legacy AdController для обратной совместимости
 * Делегирует вызовы в новые контроллеры
 */
class AdController extends BaseAdController
{
    private DraftController $draftController;
    private AdMediaController $mediaController;
    
    public function __construct($adService)
    {
        parent::__construct($adService);
        $this->draftController = app(DraftController::class);
        $this->mediaController = app(AdMediaController::class);
    }

    /**
     * Сохранить черновик объявления
     */
    public function storeDraft($request)
    {
        return $this->draftController->store($request);
    }

    /**
     * Получить данные объявления для API
     */
    public function getData(Ad $ad)
    {
        // Проверяем права доступа
        if (auth()->id() !== $ad->user_id) {
            return response()->json(['error' => 'Нет доступа'], 403);
        }

        $adData = $this->prepareAdData($ad);
        
        // Дополнительная обработка для API
        if (isset($adData['price'])) {
            $adData['price'] = (string) $adData['price'];
        }
        
        // Обработка payment_methods для обратной совместимости
        if (!empty($adData['payment_methods'])) {
            $adData['payment_methods'] = $this->processPaymentMethods($adData['payment_methods']);
        } else {
            $adData['payment_methods'] = [];
        }

        return response()->json($adData);
    }

    /**
     * Переключить статус объявления
     */
    public function toggleStatus(Request $request, Ad $ad)
    {
        $this->authorize('update', $ad);

        $request->validate([
            'status' => 'required|in:draft,active,paused,archived,inactive'
        ]);

        try {
            $ad->update(['status' => $request->status]);
            
            return response()->json([
                'success' => true,
                'message' => 'Статус объявления изменен',
                'status' => $request->status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка при изменении статуса: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Показать объявление
     */
    public function show(Ad $ad)
    {
        $this->authorize('view', $ad);

        $ad->load(['user']);

        if ($ad->status === 'draft') {
            return redirect()->route('ads.draft.show', $ad);
        }

        return \Inertia\Inertia::render('Ads/Show', [
            'ad' => $ad,
            'isOwner' => true
        ]);
    }

    /**
     * Показать черновик
     */
    public function showDraft(Ad $ad)
    {
        $this->authorize('view', $ad);

        if ($ad->status !== 'draft') {
            return redirect()->route('ads.show', $ad);
        }

        $ad->load(['user']);
        $adData = $this->prepareAdData($ad);
        
        // Дополнительная обработка для черновика
        if (isset($adData['price'])) {
            $adData['price'] = (string) $adData['price'];
        }
        
        if (!empty($adData['payment_methods'])) {
            $adData['payment_methods'] = $this->processPaymentMethods($adData['payment_methods']);
        } else {
            $adData['payment_methods'] = [];
        }

        return \Inertia\Inertia::render('Draft/Show', [
            'ad' => $adData,
            'isOwner' => true
        ]);
    }

    /**
     * Удалить черновик
     */
    public function deleteDraft(Ad $ad)
    {
        return $this->draftController->destroy($ad);
    }

    /**
     * Загрузить видео
     */
    public function uploadVideo(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,webm,avi,mov|max:102400',
        ]);

        try {
            $video = $request->file('video');
            $filename = 'video_' . time() . '_' . \Str::random(10) . '.' . $video->getClientOriginalExtension();
            
            $path = $video->storeAs('public/videos', $filename);
            $videoUrl = asset('storage/videos/' . $filename);
            
            return response()->json([
                'success' => true,
                'video' => [
                    'id' => 'video_' . time() . '_' . \Str::random(5),
                    'filename' => $filename,
                    'path' => $path,
                    'url' => $videoUrl,
                    'size' => $video->getSize(),
                    'type' => $video->getMimeType(),
                    'name' => $video->getClientOriginalName()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка загрузки видео: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обработка payment_methods для обратной совместимости
     */
    private function processPaymentMethods($paymentMethods): array
    {
        if (!is_array($paymentMethods)) {
            return [];
        }

        // Проверка старого формата {cash: true, transfer: false}
        if (isset($paymentMethods['cash']) || isset($paymentMethods['transfer'])) {
            $methods = [];
            if (!empty($paymentMethods['cash'])) $methods[] = 'cash';
            if (!empty($paymentMethods['transfer'])) $methods[] = 'transfer';
            return count($methods) > 0 ? $methods : ['cash'];
        }

        // Новый формат - массив
        return array_values(array_filter($paymentMethods, function($value) {
            return !empty($value) && in_array($value, ['cash', 'transfer']);
        }));
    }
}