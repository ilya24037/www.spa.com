<?php

namespace App\Application\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Контроллер для управления настройками профилей мастеров
 * Отвечает за переключение статусов, публикацию и восстановление
 */
class ProfileSettingsController extends Controller
{
    /**
     * Переключение статуса профиля мастера
     */
    public function toggleProfile(Request $request, $masterId): RedirectResponse
    {
        $profile = $request->user()->masterProfiles()->findOrFail($masterId);
        $profile->update(['is_active' => !$profile->is_active]);
        
        return back()->with('success', 'Статус анкеты изменен');
    }

    /**
     * Публикация черновика
     */
    public function publishProfile(Request $request, $masterId): RedirectResponse
    {
        $profile = $request->user()->masterProfiles()->findOrFail($masterId);
        
        // Проверяем готовность к публикации
        if (!$this->canPublish($profile)) {
            return back()->with('error', 'Анкета не готова к публикации. Заполните все обязательные поля.');
        }
        
        $profile->update(['status' => 'active']);
        
        return back()->with('success', 'Анкета опубликована');
    }

    /**
     * Восстановление из архива
     */
    public function restoreProfile(Request $request, $masterId): RedirectResponse
    {
        $profile = $request->user()->masterProfiles()->findOrFail($masterId);
        $profile->update(['status' => 'active']);
        
        return back()->with('success', 'Анкета восстановлена');
    }

    /**
     * Архивирование профиля
     */
    public function archiveProfile(Request $request, $masterId): RedirectResponse
    {
        $profile = $request->user()->masterProfiles()->findOrFail($masterId);
        $profile->update(['status' => 'archived']);
        
        return back()->with('success', 'Анкета перемещена в архив');
    }

    /**
     * Удаление профиля
     */
    public function deleteProfile(Request $request, $masterId): RedirectResponse
    {
        $profile = $request->user()->masterProfiles()->findOrFail($masterId);
        
        // Проверяем, можно ли удалить
        if ($profile->hasActiveBookings()) {
            return back()->with('error', 'Невозможно удалить анкету с активными бронированиями');
        }
        
        $profile->delete();
        
        return back()->with('success', 'Анкета удалена');
    }

    /**
     * Проверка готовности профиля к публикации
     */
    private function canPublish($profile): bool
    {
        // Проверяем обязательные поля
        $requiredFields = [
            'display_name',
            'city',
            'phone',
            'bio',
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($profile->$field)) {
                return false;
            }
        }
        
        // Проверяем наличие хотя бы одной услуги
        if ($profile->services()->count() === 0) {
            return false;
        }
        
        // Проверяем наличие фото
        if ($profile->photos()->count() === 0) {
            return false;
        }
        
        return true;
    }
}