<?php

namespace App\Application\Services\Integration\UserAds;

use Illuminate\Support\Facades\DB;

/**
 * Обработчик валидации данных объявлений и проверки ограничений
 */
class AdValidationHandler
{
    /**
     * Проверить может ли пользователь создать новое объявление
     */
    public function canUserCreateAd(int $userId): bool
    {
        // Проверяем лимит активных объявлений (например, максимум 10)
        $activeAdsCount = $this->getUserActiveAdsCount($userId);
        if ($activeAdsCount >= 10) {
            return false;
        }

        // Проверяем лимит создания в день (например, максимум 3)
        $todayAdsCount = DB::table('ads')
            ->where('user_id', $userId)
            ->whereDate('created_at', today())
            ->count();

        if ($todayAdsCount >= 3) {
            return false;
        }

        // Проверяем статус пользователя
        $userStatus = DB::table('users')
            ->where('id', $userId)
            ->value('status');

        return in_array($userStatus, ['active', 'verified']);
    }

    /**
     * Проверить принадлежит ли объявление пользователю
     */
    public function userOwnsAd(int $userId, int $adId): bool
    {
        return DB::table('ads')
            ->where('id', $adId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Валидировать данные объявления
     */
    public function validateAdData(array $data): ?array
    {
        // Проверяем обязательные поля
        if (empty($data['title']) || strlen($data['title']) < 3) {
            return null;
        }

        $title = trim($data['title']);
        if (strlen($title) > 100) {
            return null;
        }

        // Проверяем описание (опционально, но если есть - валидируем)
        $description = trim($data['description'] ?? '');
        if (strlen($description) > 2000) {
            return null;
        }

        // Проверяем цену
        $price = null;
        if (isset($data['price']) && is_numeric($data['price'])) {
            $price = (float) $data['price'];
            if ($price < 0 || $price > 100000) {
                return null;
            }
        }

        // Проверяем категорию
        $allowedCategories = ['massage', 'spa', 'wellness', 'fitness', 'beauty'];
        $category = $data['category'] ?? 'massage';
        if (!in_array($category, $allowedCategories)) {
            $category = 'massage';
        }

        return [
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'specialty' => trim($data['specialty'] ?? ''),
            'price' => $price,
            'status' => $data['status'] ?? 'draft',
        ];
    }

    /**
     * Проверить готовность объявления к публикации
     */
    public function isAdReadyForPublication(int $adId): array
    {
        $ad = DB::table('ads')->where('id', $adId)->first();
        
        if (!$ad) {
            return [
                'ready' => false,
                'errors' => ['Объявление не найдено']
            ];
        }

        $errors = [];

        // Проверяем статус
        if ($ad->status !== 'draft') {
            $errors[] = 'Объявление должно быть в статусе черновика';
        }

        // Проверяем обязательные поля
        if (empty($ad->title) || strlen($ad->title) < 3) {
            $errors[] = 'Заголовок обязателен (минимум 3 символа)';
        }

        if (empty($ad->description) || strlen($ad->description) < 20) {
            $errors[] = 'Описание обязательно (минимум 20 символов)';
        }

        if (empty($ad->category)) {
            $errors[] = 'Категория обязательна';
        }

        // Проверяем наличие фотографий
        $photosCount = DB::table('ad_media')
            ->where('ad_id', $adId)
            ->where('type', 'photo')
            ->count();

        if ($photosCount === 0) {
            $errors[] = 'Необходимо добавить минимум 1 фотографию';
        }

        return [
            'ready' => empty($errors),
            'errors' => $errors,
            'required_fields_completed' => count($errors) === 0
        ];
    }

    /**
     * Проверить лимиты пользователя по объявлениям
     */
    public function checkUserLimits(int $userId): array
    {
        $limits = [
            'max_active_ads' => 10,
            'max_daily_ads' => 3,
            'max_draft_ads' => 20,
        ];

        $current = [
            'active_ads' => $this->getUserActiveAdsCount($userId),
            'today_ads' => $this->getTodayAdsCount($userId),
            'draft_ads' => $this->getDraftAdsCount($userId),
        ];

        return [
            'limits' => $limits,
            'current' => $current,
            'can_create_ad' => $current['active_ads'] < $limits['max_active_ads'] 
                            && $current['today_ads'] < $limits['max_daily_ads']
                            && $current['draft_ads'] < $limits['max_draft_ads'],
            'limit_reasons' => [
                'active_ads_limit' => $current['active_ads'] >= $limits['max_active_ads'],
                'daily_limit' => $current['today_ads'] >= $limits['max_daily_ads'],
                'draft_limit' => $current['draft_ads'] >= $limits['max_draft_ads'],
            ]
        ];
    }

    /**
     * Получить количество активных объявлений
     */
    private function getUserActiveAdsCount(int $userId): int
    {
        return DB::table('ads')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Получить количество объявлений созданных сегодня
     */
    private function getTodayAdsCount(int $userId): int
    {
        return DB::table('ads')
            ->where('user_id', $userId)
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Получить количество черновиков
     */
    private function getDraftAdsCount(int $userId): int
    {
        return DB::table('ads')
            ->where('user_id', $userId)
            ->where('status', 'draft')
            ->count();
    }

    /**
     * Проверить можно ли редактировать объявление
     */
    public function canEditAd(int $userId, int $adId): array
    {
        if (!$this->userOwnsAd($userId, $adId)) {
            return [
                'can_edit' => false,
                'reason' => 'Объявление не принадлежит пользователю'
            ];
        }

        $ad = DB::table('ads')->where('id', $adId)->first();
        
        if (!$ad) {
            return [
                'can_edit' => false,
                'reason' => 'Объявление не найдено'
            ];
        }

        $editableStatuses = ['draft', 'pending', 'rejected'];
        
        if (!in_array($ad->status, $editableStatuses)) {
            return [
                'can_edit' => false,
                'reason' => 'Объявление в статусе "' . $ad->status . '" нельзя редактировать'
            ];
        }

        return [
            'can_edit' => true,
            'reason' => null
        ];
    }
}