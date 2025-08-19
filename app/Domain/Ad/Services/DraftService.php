<?php

namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Простой сервис для работы с черновиками
 * Следует принципу KISS - максимально простая логика
 */
class DraftService
{
    /**
     * Сохранить или обновить черновик
     * Принимает mixed $adId для совместимости с разными типами данных
     */
    public function saveOrUpdate(array $data, User $user, mixed $adId = null): Ad
    {
        // Подготавливаем данные
        $data = $this->prepareData($data);
        $data['user_id'] = $user->id;
        $data['status'] = 'draft';
        
        // Приводим ID к integer если он передан
        $adId = $adId ? (int) $adId : null;
        
        // Добавим детальное логирование
        \Log::info('DraftService::saveOrUpdate - checking for existing ad', [
            'adId' => $adId,
            'user_id' => $user->id,
            'query' => "SELECT * FROM ads WHERE id = {$adId} AND user_id = {$user->id}"
        ]);
        
        // Если передан ID, ищем существующий черновик этого пользователя
        if ($adId && $adId > 0) {
            $ad = Ad::where('id', $adId)
                ->where('user_id', $user->id)
                ->first();
                
            if ($ad) {
                Log::info('DraftService::saveOrUpdate - updating existing', ['ad_id' => $ad->id]);
                $ad->update($data);
                $ad->wasRecentlyCreated = false; // Явно указываем что это обновление
                return $ad;
            } else {
                Log::info('DraftService::saveOrUpdate - ad not found or wrong user', [
                    'ad_id' => $adId,
                    'user_id' => $user->id
                ]);
            }
        }
        
        // Создаем новый черновик
        Log::info('DraftService::saveOrUpdate - creating new', ['user_id' => $user->id]);
        $ad = Ad::create($data);
        $ad->wasRecentlyCreated = true; // Явно указываем что это создание
        
        Log::info('DraftService::saveOrUpdate - result', [
            'ad_id' => $ad->id,
            'was_updated' => $ad->wasRecentlyCreated ? false : true,
            'recently_created' => $ad->wasRecentlyCreated
        ]);
        
        return $ad;
    }

    /**
     * Подготовить черновик для отображения
     */
    public function prepareForDisplay(Ad $ad): array
    {
        $data = $ad->toArray();
        
        // Декодируем JSON поля
        $jsonFields = ['clients', 'services', 'features', 'photos', 'video', 'geo', 'prices'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = json_decode($data[$field], true) ?: [];
            }
        }
        
        return $data;
    }

    /**
     * Удалить черновик
     */
    public function delete(Ad $ad): bool
    {
        // Только черновики можно удалять
        if ($ad->status !== 'draft') {
            throw new \InvalidArgumentException('Только черновики можно удалять');
        }
        
        return $ad->delete();
    }

    /**
     * Подготовить данные для сохранения
     */
    private function prepareData(array $data): array
    {
        // Кодируем массивы в JSON
        $jsonFields = ['clients', 'services', 'features', 'photos', 'video', 'geo', 'prices'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field]) && !is_string($data[$field])) {
                $data[$field] = json_encode($data[$field]);
            }
        }
        
        // Устанавливаем значение по умолчанию для заголовка
        $data['title'] = $data['title'] ?? 'Черновик';
        
        return $data;
    }
}