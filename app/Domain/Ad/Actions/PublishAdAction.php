<?php

namespace App\Domain\Ad\Actions;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Repositories\AdRepository;
use App\Domain\Ad\Enums\AdStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для публикации объявления
 */
class PublishAdAction
{
    private AdRepository $adRepository;

    public function __construct(AdRepository $adRepository)
    {
        $this->adRepository = $adRepository;
    }

    /**
     * Опубликовать объявление
     */
    public function execute(int $adId): array
    {
        try {
            return DB::transaction(function () use ($adId) {
                $ad = $this->adRepository->findOrFail($adId);

                // Проверяем, можно ли опубликовать
                if (!$this->canPublish($ad)) {
                    return [
                        'success' => false,
                        'message' => 'Объявление не может быть опубликовано в текущем статусе',
                    ];
                }

                // Проверяем заполненность обязательных полей
                $validation = $this->validateForPublish($ad);
                if (!$validation['valid']) {
                    return [
                        'success' => false,
                        'message' => 'Заполните все обязательные поля',
                        'errors' => $validation['errors'],
                    ];
                }

                // Обновляем статус через репозиторий
                $this->adRepository->updateAd($ad, [
                    'status' => AdStatus::WAITING_PAYMENT->value,
                    'published_at' => now()
                ]);

                Log::info('Ad marked for payment', [
                    'ad_id' => $ad->id,
                    'user_id' => $ad->user_id,
                ]);

                return [
                    'success' => true,
                    'message' => 'Объявление отправлено на оплату',
                    'ad' => $ad,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to publish ad', [
                'ad_id' => $adId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при публикации объявления',
            ];
        }
    }

    /**
     * Проверить, можно ли опубликовать объявление
     */
    private function canPublish(Ad $ad): bool
    {
        return in_array($ad->status, [
            AdStatus::DRAFT->value,
            AdStatus::ARCHIVED->value,
        ]);
    }

    /**
     * Валидация объявления для публикации
     */
    private function validateForPublish(Ad $ad): array
    {
        $errors = [];

        // Проверяем контент
        if (!$ad->content || empty($ad->content->title)) {
            $errors[] = 'Не указан заголовок объявления';
        }

        if (!$ad->content || empty($ad->content->description)) {
            $errors[] = 'Не указано описание объявления';
        }

        // Проверяем цены
        if (!$ad->pricing || !$ad->pricing->price) {
            $errors[] = 'Не указана цена';
        }

        // Проверяем локацию
        if (!$ad->location || empty($ad->location->address)) {
            $errors[] = 'Не указан адрес';
        }

        // Проверяем медиа
        if (!$ad->media || empty($ad->media->photos)) {
            $errors[] = 'Добавьте хотя бы одну фотографию';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}