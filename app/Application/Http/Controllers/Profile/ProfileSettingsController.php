<?php

namespace App\Application\Http\Controllers\Profile;

use App\Application\Http\Controllers\Controller;
use App\Domain\User\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Контроллер для управления настройками профилей мастеров
 * Отвечает за переключение статусов, публикацию и восстановление
 */
class ProfileSettingsController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    /**
     * Переключение статуса профиля мастера
     */
    public function toggleProfile(Request $request, $masterId): RedirectResponse
    {
        $success = $this->userService->toggleMasterProfile($request->user(), $masterId);
        
        $message = $success ? 'Статус анкеты изменен' : 'Ошибка изменения статуса';
        return back()->with($success ? 'success' : 'error', $message);
    }

    /**
     * Публикация черновика
     */
    public function publishProfile(Request $request, $masterId): RedirectResponse
    {
        $result = $this->userService->publishMasterProfile($request->user(), $masterId);
        
        $status = $result['success'] ? 'success' : 'error';
        $message = $result['message'] ?? $result['error'] ?? 'Неизвестная ошибка';
        
        return back()->with($status, $message);
    }

    /**
     * Восстановление из архива
     */
    public function restoreProfile(Request $request, $masterId): RedirectResponse
    {
        $success = $this->userService->restoreMasterProfile($request->user(), $masterId);
        
        $message = $success ? 'Анкета восстановлена' : 'Ошибка восстановления анкеты';
        return back()->with($success ? 'success' : 'error', $message);
    }

    /**
     * Архивирование профиля
     */
    public function archiveProfile(Request $request, $masterId): RedirectResponse
    {
        $success = $this->userService->archiveMasterProfile($request->user(), $masterId);
        
        $message = $success ? 'Анкета перемещена в архив' : 'Ошибка архивирования анкеты';
        return back()->with($success ? 'success' : 'error', $message);
    }

    /**
     * Удаление профиля
     */
    public function deleteProfile(Request $request, $masterId): RedirectResponse
    {
        $result = $this->userService->deleteMasterProfile($request->user(), $masterId);
        
        $status = $result['success'] ? 'success' : 'error';
        $message = $result['message'] ?? $result['error'] ?? 'Неизвестная ошибка';
        
        return back()->with($status, $message);
    }

}