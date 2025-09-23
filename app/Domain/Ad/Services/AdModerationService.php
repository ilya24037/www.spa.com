<?php

namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Enums\AdStatus;
use App\Domain\Ad\Repositories\AdRepository;
use App\Domain\Admin\Traits\LogsAdminActions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Сервис модерации объявлений
 */
class AdModerationService
{
    use LogsAdminActions;
    private AdRepository $adRepository;

    // Стоп-слова для проверки контента
    private array $bannedWords = [
        'наркотики', 'оружие', 'проституция', 'интим', 'секс', 'эскорт',
        // Добавить другие стоп-слова по необходимости
    ];

    // Подозрительные фразы
    private array $suspiciousPatterns = [
        '/\b(только|лишь)\s+(сегодня|завтра)\b/iu',
        '/\b100%\s+(гарантия|результат)\b/iu',
        '/\bбез\s+предоплат[ыи]\b/iu',
        '/\b(звони|пиши)\s+(срочно|быстро)\b/iu',
    ];

    public function __construct(AdRepository $adRepository)
    {
        $this->adRepository = $adRepository;
    }

    /**
     * Отправить объявление на модерацию
     */
    public function submitForModeration(Ad $ad): array
    {
        try {
            // Автоматическая проверка
            $checkResult = $this->performAutomaticCheck($ad);

            if ($checkResult['approved']) {
                // Автоматическое одобрение
                $this->adRepository->updateAd($ad, [
                    'status' => AdStatus::ACTIVE->value
                ]);

                Log::info('Ad auto-approved', ['ad_id' => $ad->id]);

                return [
                    'success' => true,
                    'status' => 'approved',
                    'message' => 'Объявление автоматически одобрено'
                ];
            } else {
                // Отправка на ручную модерацию
                $this->adRepository->updateAd($ad, [
                    'status' => AdStatus::PENDING_MODERATION->value
                ]);

                Log::info('Ad sent for manual moderation', [
                    'ad_id' => $ad->id,
                    'issues' => $checkResult['issues']
                ]);

                return [
                    'success' => true,
                    'status' => 'pending',
                    'message' => 'Объявление отправлено на модерацию',
                    'issues' => $checkResult['issues']
                ];
            }

        } catch (\Exception $e) {
            Log::error('Moderation submission failed', [
                'ad_id' => $ad->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Ошибка при отправке на модерацию'
            ];
        }
    }

    /**
     * Одобрить объявление
     */
    public function approveAd(Ad $ad, ?string $moderatorNote = null): bool
    {
        try {
            $updateData = [
                'status' => AdStatus::ACTIVE->value,
                'is_published' => true,          // Устанавливаем флаг публикации
                'published_at' => now(),
                'moderated_at' => now(),         // Время модерации
                'moderation_reason' => null      // Очищаем причину отклонения
            ];

            // Сохраняем старые значения для логирования
            $oldValues = $ad->only(['status', 'is_published', 'published_at', 'moderated_at', 'moderation_reason']);

            $this->adRepository->updateAd($ad, $updateData);

            // Логируем действие администратора
            $this->logAdminAction(
                'approve',
                $ad,
                $oldValues,
                $updateData,
                "Объявление #{$ad->id} одобрено" . ($moderatorNote ? ": $moderatorNote" : ''),
                ['moderator_note' => $moderatorNote]
            );

            Log::info('Ad approved by moderator', ['ad_id' => $ad->id]);

            // Уведомление пользователя (можно добавить)
            // event(new AdApproved($ad));

            return true;

        } catch (\Exception $e) {
            Log::error('Ad approval failed', [
                'ad_id' => $ad->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Отклонить объявление
     */
    public function rejectAd(Ad $ad, string $reason): bool
    {
        try {
            // Сохраняем старые значения для логирования
            $oldValues = $ad->only(['status', 'moderation_reason']);

            $updateData = [
                'status' => AdStatus::REJECTED->value,
                'moderation_reason' => $reason
            ];

            $this->adRepository->updateAd($ad, $updateData);

            // Логируем действие администратора
            $this->logAdminAction(
                'reject',
                $ad,
                $oldValues,
                $updateData,
                "Объявление #{$ad->id} отклонено: {$reason}",
                ['rejection_reason' => $reason]
            );

            Log::info('Ad rejected by moderator', [
                'ad_id' => $ad->id,
                'reason' => $reason
            ]);

            // Уведомление пользователя (можно добавить)
            // event(new AdRejected($ad, $reason));

            return true;

        } catch (\Exception $e) {
            Log::error('Ad rejection failed', [
                'ad_id' => $ad->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Заблокировать объявление
     */
    public function block(Ad $ad, string $reason): bool
    {
        try {
            // Сохраняем старые значения для логирования
            $oldValues = $ad->only(['status', 'moderation_reason']);

            $updateData = [
                'status' => AdStatus::BLOCKED->value,
                'moderation_reason' => $reason
            ];

            $this->adRepository->updateAd($ad, $updateData);

            // Логируем действие администратора
            $this->logAdminAction(
                'block',
                $ad,
                $oldValues,
                $updateData,
                "Объявление #{$ad->id} заблокировано: {$reason}",
                ['block_reason' => $reason]
            );

            Log::warning('Ad blocked', [
                'ad_id' => $ad->id,
                'reason' => $reason
            ]);

            // Уведомление пользователя
            // event(new AdBlocked($ad, $reason));

            return true;

        } catch (\Exception $e) {
            Log::error('Ad blocking failed', [
                'ad_id' => $ad->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Автоматическая проверка объявления
     */
    private function performAutomaticCheck(Ad $ad): array
    {
        $issues = [];
        $approved = true;

        // Проверка контента на стоп-слова
        $contentIssues = $this->checkContent($ad);
        if (!empty($contentIssues)) {
            $issues = array_merge($issues, $contentIssues);
            $approved = false;
        }

        // Проверка фотографий (базовая)
        $photoIssues = $this->checkPhotos($ad);
        if (!empty($photoIssues)) {
            $issues = array_merge($issues, $photoIssues);
            $approved = false;
        }

        // Проверка цен
        $priceIssues = $this->checkPricing($ad);
        if (!empty($priceIssues)) {
            $issues = array_merge($issues, $priceIssues);
            $approved = false;
        }

        // Проверка контактной информации
        $contactIssues = $this->checkContacts($ad);
        if (!empty($contactIssues)) {
            $issues = array_merge($issues, $contactIssues);
            $approved = false;
        }

        return [
            'approved' => $approved,
            'issues' => $issues
        ];
    }

    /**
     * Проверка контента на нарушения
     */
    public function checkContent(Ad $ad): array
    {
        $issues = [];

        // Проверяем наличие основных полей контента
        if (!$ad->title && !$ad->description) {
            return ['Отсутствует контент объявления'];
        }

        $textToCheck = strtolower($ad->title . ' ' . $ad->description);

        // Проверка стоп-слов
        foreach ($this->bannedWords as $word) {
            if (strpos($textToCheck, strtolower($word)) !== false) {
                $issues[] = 'Обнаружено запрещенное слово: ' . $word;
            }
        }

        // Проверка подозрительных паттернов
        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $textToCheck)) {
                $issues[] = 'Обнаружена подозрительная фраза';
            }
        }

        // Проверка длины описания
        if ($ad->description && mb_strlen($ad->description) < 50) {
            $issues[] = 'Слишком короткое описание';
        }

        // Проверка на спам (много заглавных букв)
        if ($ad->title && $this->hasExcessiveCapitals($ad->title)) {
            $issues[] = 'Слишком много заглавных букв в заголовке';
        }

        return $issues;
    }

    /**
     * Проверка фотографий
     */
    private function checkPhotos(Ad $ad): array
    {
        $issues = [];

        // Проверка наличия фотографий
        $photos = $ad->photos ? json_decode($ad->photos, true) : [];
        if (empty($photos)) {
            $issues[] = 'Отсутствуют фотографии';
        }

        // Дополнительные проверки фото можно добавить здесь
        // Например, проверка на неприличный контент через внешние API

        return $issues;
    }

    /**
     * Проверка цен
     */
    private function checkPricing(Ad $ad): array
    {
        $issues = [];

        // Проверка наличия цен
        $price = $ad->starting_price ?? 0;
        if ($price == 0) {
            return ['Отсутствует информация о ценах'];
        }

        // Проверка на подозрительно низкие цены
        if ($price < 100) {
            $issues[] = 'Подозрительно низкая цена';
        }

        // Проверка на подозрительно высокие цены
        if ($price > 100000) {
            $issues[] = 'Подозрительно высокая цена';
        }

        return $issues;
    }

    /**
     * Проверка контактной информации
     */
    private function checkContacts(Ad $ad): array
    {
        $issues = [];

        // Проверка номера телефона
        if (empty($ad->phone)) {
            $issues[] = 'Отсутствует номер телефона';
        } elseif (!$this->isValidPhone($ad->phone)) {
            $issues[] = 'Некорректный номер телефона';
        }

        // Проверка адреса
        if (empty($ad->address)) {
            $issues[] = 'Отсутствует адрес';
        }

        return $issues;
    }

    /**
     * Проверка на избыточное количество заглавных букв
     */
    private function hasExcessiveCapitals(string $text): bool
    {
        $totalChars = mb_strlen($text);
        $upperChars = mb_strlen(preg_replace('/[^А-ЯЁA-Z]/u', '', $text));
        
        return $totalChars > 10 && ($upperChars / $totalChars) > 0.5;
    }

    /**
     * Валидация номера телефона
     */
    private function isValidPhone(string $phone): bool
    {
        // Простая проверка российского номера
        $cleaned = preg_replace('/[^\d]/', '', $phone);
        
        return preg_match('/^[78]\d{10}$/', $cleaned) || 
               preg_match('/^9\d{9}$/', $cleaned);
    }

    /**
     * Получить статистику модерации
     */
    public function getModerationStats(): array
    {
        return [
            'pending' => $this->adRepository->getPendingModeration()->total(),
            'approved_today' => Ad::where('status', AdStatus::ACTIVE->value)
                ->whereDate('updated_at', today())
                ->count(),
            'rejected_today' => Ad::where('status', AdStatus::REJECTED->value)
                ->whereDate('updated_at', today())
                ->count(),
            'blocked_today' => Ad::where('status', AdStatus::BLOCKED->value)
                ->whereDate('updated_at', today())
                ->count(),
        ];
    }

    /**
     * Получить объявления для модерации
     */
    public function getAdsForModeration(int $limit = 10): Collection
    {
        return Ad::with(['user'])
            ->where(function($query) {
                // Либо статус PENDING_MODERATION
                $query->where('status', AdStatus::PENDING_MODERATION->value)
                      // Либо активные, но не опубликованные (на модерации)
                      ->orWhere(function($q) {
                          $q->where('status', AdStatus::ACTIVE->value)
                            ->where('is_published', false);
                      });
            })
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }
}