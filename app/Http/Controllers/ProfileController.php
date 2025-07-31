<?php

namespace App\Http\Controllers;

use App\Application\Http\Controllers\Profile\ProfileController as BaseProfileController;
use App\Application\Http\Controllers\Profile\ProfileItemsController;
use App\Application\Http\Controllers\Profile\ProfileSettingsController;

/**
 * Legacy ProfileController для обратной совместимости
 * Делегирует вызовы в новые контроллеры
 */
class ProfileController extends BaseProfileController
{
    private ProfileItemsController $itemsController;
    private ProfileSettingsController $settingsController;
    
    public function __construct()
    {
        $this->itemsController = app(ProfileItemsController::class);
        $this->settingsController = app(ProfileSettingsController::class);
    }
    /**
     * Отображение личного кабинета
     */
    public function index($request)
    {
        return $this->itemsController->index($request);
    }
    public function activeItems($request) {
        return $this->itemsController->activeItems($request);
    }
    
    public function draftItems($request) {
        return $this->itemsController->draftItems($request);
    }
    
    public function inactiveItems($request) {
        return $this->itemsController->inactiveItems($request);
    }
    
    public function oldItems($request) {
        return $this->itemsController->oldItems($request);
    }
    
    public function archiveItems($request) {
        return $this->itemsController->archiveItems($request);
    }



    // Методы edit, update и destroy наследуются от BaseProfileController
    
    /**
     * Переключение статуса профиля мастера
     */
    public function toggleProfile($request, $masterId)
    {
        return $this->settingsController->toggleProfile($request, $masterId);
    }

    /**
     * Публикация черновика
     */
    public function publishProfile($request, $masterId)
    {
        return $this->settingsController->publishProfile($request, $masterId);
    }

    /**
     * Восстановление из архива
     */
    public function restoreProfile($request, $masterId)
    {
        return $this->settingsController->restoreProfile($request, $masterId);
    }


}