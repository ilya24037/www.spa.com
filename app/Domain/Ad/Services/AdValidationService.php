<?php

namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Сервис для валидации объявлений
 */
class AdValidationService
{
    /**
     * Валидировать данные для создания объявления
     */
    public function validateCreateData(array $data, User $user): array
    {
        $errors = [];

        // Проверка обязательных полей
        if (empty($data['title'])) {
            $errors['title'] = 'Заголовок обязателен';
        }

        if (empty($data['description'])) {
            $errors['description'] = 'Описание обязательно';
        }

        if (empty($data['category_id'])) {
            $errors['category_id'] = 'Категория обязательна';
        }

        // Проверка цен
        if (!empty($data['price_from']) && !empty($data['price_to'])) {
            if ($data['price_from'] > $data['price_to']) {
                $errors['price'] = 'Цена "от" не может быть больше цены "до"';
            }
        }

        // Проверка лимитов пользователя
        if (!$this->canUserCreateAd($user)) {
            $errors['limit'] = 'Достигнут лимит объявлений для вашего тарифа';
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    /**
     * Валидировать данные для обновления объявления
     */
    public function validateUpdateData(Ad $ad, array $data, User $user): array
    {
        $errors = [];

        // Проверка прав на редактирование
        if ($ad->user_id !== $user->id && !$user->isAdmin()) {
            $errors['permission'] = 'Нет прав на редактирование этого объявления';
        }

        // Валидация полей (аналогично create)
        if (isset($data['title']) && empty($data['title'])) {
            $errors['title'] = 'Заголовок не может быть пустым';
        }

        if (isset($data['price_from']) && isset($data['price_to'])) {
            if ($data['price_from'] > $data['price_to']) {
                $errors['price'] = 'Цена "от" не может быть больше цены "до"';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    /**
     * Проверить может ли пользователь создать объявление
     */
    public function canUserCreateAd(User $user): bool
    {
        $activeAdsCount = $user->ads()->active()->count();
        $maxAds = $user->subscription_plan === 'premium' ? 100 : 5;

        return $activeAdsCount < $maxAds;
    }

    /**
     * Валидировать данные для публикации
     */
    public function validateForPublishing(Ad $ad): array
    {
        $errors = [];

        if (empty($ad->title)) {
            $errors['title'] = 'Заголовок обязателен для публикации';
        }

        if (empty($ad->description)) {
            $errors['description'] = 'Описание обязательно для публикации';
        }

        if (empty($ad->category_id)) {
            $errors['category'] = 'Категория обязательна для публикации';
        }

        if (empty($ad->price_from) && empty($ad->price_to) && empty($ad->price_fixed)) {
            $errors['price'] = 'Необходимо указать стоимость услуги';
        }

        if (!$ad->latitude || !$ad->longitude) {
            $errors['location'] = 'Необходимо указать местоположение';
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return [];
    }

    /**
     * Проверить готовность к модерации
     */
    public function validateForModeration(Ad $ad): bool
    {
        try {
            $this->validateForPublishing($ad);
            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }

    /**
     * Валидировать медиа данные
     */
    public function validateMediaData(array $mediaData): array
    {
        $errors = [];

        if (!empty($mediaData['photos'])) {
            if (count($mediaData['photos']) > 20) {
                $errors['photos'] = 'Максимум 20 фотографий';
            }
        }

        if (!empty($mediaData['videos'])) {
            if (count($mediaData['videos']) > 5) {
                $errors['videos'] = 'Максимум 5 видео';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $mediaData;
    }
}