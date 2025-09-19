<?php

namespace App\Jobs;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Enums\AdStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job для автоматической модерации объявлений
 * Запускается через 5 минут после создания объявления
 */
class AutoModerateAdJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Количество попыток выполнения
     */
    public int $tries = 3;

    /**
     * Таймаут выполнения в секундах
     */
    public int $timeout = 60;

    /**
     * ID объявления для модерации
     */
    protected int $adId;

    /**
     * Создать новый экземпляр job
     */
    public function __construct(int $adId)
    {
        $this->adId = $adId;
        $this->onQueue('moderation');
    }

    /**
     * Выполнить job
     */
    public function handle(): void
    {
        try {
            // Загружаем объявление
            $ad = Ad::find($this->adId);

            if (!$ad) {
                Log::warning('AutoModerateAdJob: Объявление не найдено', [
                    'ad_id' => $this->adId
                ]);
                return;
            }

            // Проверяем, что объявление все еще ждет модерации
            // is_published должно быть false для объявлений на модерации
            // статус должен быть active (не draft, не archived)
            if ($ad->status !== AdStatus::ACTIVE || $ad->is_published === true) {
                Log::info('AutoModerateAdJob: Объявление уже опубликовано или не активно', [
                    'ad_id' => $this->adId,
                    'status' => $ad->status,
                    'is_published' => $ad->is_published
                ]);
                return;
            }

            // Выполняем автоматическую проверку
            $passesModeration = $this->passesAutoModeration($ad);

            Log::info('AutoModerateAdJob: Результат проверки модерации', [
                'ad_id' => $this->adId,
                'passes_moderation' => $passesModeration,
                'title_length' => mb_strlen($ad->title ?? ''),
                'description_length' => mb_strlen($ad->description ?? '')
            ]);

            if ($passesModeration) {
                // Одобряем объявление - делаем его видимым в поиске
                $ad->update([
                    'is_published' => true,
                    'moderated_at' => now()
                ]);

                Log::info('AutoModerateAdJob: Объявление автоматически одобрено', [
                    'ad_id' => $this->adId,
                    'moderated_at' => now()
                ]);

                // TODO: Отправить уведомление пользователю о прохождении модерации
                // $this->sendApprovalNotification($ad);
            } else {
                // Объявление требует ручной модерации
                Log::warning('AutoModerateAdJob: Объявление требует ручной модерации', [
                    'ad_id' => $this->adId,
                    'reason' => 'Найдены запрещенные слова или подозрительный контент'
                ]);

                // TODO: Создать задачу для модератора
                // $this->createManualModerationTask($ad);
            }
        } catch (\Exception $e) {
            Log::error('AutoModerateAdJob: Ошибка при модерации', [
                'ad_id' => $this->adId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Перебрасываем исключение для повторных попыток
            throw $e;
        }
    }

    /**
     * Проверить, проходит ли объявление автоматическую модерацию
     */
    private function passesAutoModeration(Ad $ad): bool
    {
        // Список запрещенных слов
        $bannedWords = [
            // Спам и мошенничество
            'заработок', 'работа на дому', 'быстрые деньги',
            'кредит', 'займ', 'криптовалюта', 'bitcoin',

            // Запрещенные услуги
            'наркотики', 'оружие', 'документы',

            // Оскорбления и непристойности
            'дурак', 'идиот',

            // Подозрительные фразы
            'без предоплаты', 'гарантия 100%', 'только сегодня'
        ];

        // Собираем весь текст для проверки
        $textToCheck = mb_strtolower(
            ($ad->title ?? '') . ' ' .
            ($ad->description ?? '') . ' ' .
            ($ad->services_additional_info ?? '')
        );

        // Проверяем на наличие запрещенных слов
        foreach ($bannedWords as $word) {
            if (mb_strpos($textToCheck, mb_strtolower($word)) !== false) {
                Log::info('AutoModerateAdJob: Найдено запрещенное слово', [
                    'ad_id' => $this->adId,
                    'banned_word' => $word
                ]);
                return false;
            }
        }

        // Дополнительные проверки

        // 1. Проверка на слишком короткое описание
        $description = $ad->description ?? '';
        if (mb_strlen($description) < 30) {
            Log::info('AutoModerateAdJob: Слишком короткое описание', [
                'ad_id' => $this->adId,
                'description_length' => mb_strlen($description)
            ]);
            return false;
        }

        // 2. Проверка на подозрительные символы или спам
        if (preg_match('/(.)\1{4,}/', $textToCheck)) {
            Log::info('AutoModerateAdJob: Обнаружен спам (повторяющиеся символы)', [
                'ad_id' => $this->adId
            ]);
            return false;
        }

        // 3. Проверка на слишком много заглавных букв (крик)
        $uppercase = preg_match_all('/[А-ЯA-Z]/u', $description, $matches);
        $totalChars = mb_strlen($description);

        if ($totalChars > 0 && ($uppercase / $totalChars) > 0.5) {
            Log::info('AutoModerateAdJob: Слишком много заглавных букв', [
                'ad_id' => $this->adId,
                'uppercase_ratio' => $uppercase / $totalChars
            ]);
            return false;
        }

        // 4. Проверка на минимальную информативность заголовка
        if (mb_strlen($ad->title ?? '') < 10) {
            Log::info('AutoModerateAdJob: Слишком короткий заголовок', [
                'ad_id' => $this->adId,
                'title_length' => mb_strlen($ad->title ?? '')
            ]);
            return false;
        }

        // Все проверки пройдены
        return true;
    }

    /**
     * Обработка неудачного выполнения job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AutoModerateAdJob: Job провалился после всех попыток', [
            'ad_id' => $this->adId,
            'error' => $exception->getMessage()
        ]);

        // TODO: Уведомить администратора о проблеме с модерацией
        // $this->notifyAdminAboutFailure($this->adId, $exception);
    }
}