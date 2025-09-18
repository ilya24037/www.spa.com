<?php

namespace App\Jobs;

use App\Domain\Master\Models\MasterProfile;
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
     * ID мастера/объявления для модерации
     */
    protected int $masterId;

    /**
     * Создать новый экземпляр job
     */
    public function __construct(int $masterId)
    {
        $this->masterId = $masterId;
        $this->onQueue('moderation');
    }

    /**
     * Выполнить job
     */
    public function handle(): void
    {
        try {
            // Загружаем объявление
            $master = MasterProfile::find($this->masterId);

            if (!$master) {
                Log::warning('AutoModerateAdJob: Объявление не найдено', [
                    'master_id' => $this->masterId
                ]);
                return;
            }

            // Проверяем, что объявление все еще ждет модерации
            // is_published должно быть false для объявлений на модерации
            if ($master->is_published === true) {
                Log::info('AutoModerateAdJob: Объявление уже опубликовано', [
                    'master_id' => $this->masterId
                ]);
                return;
            }

            // Выполняем автоматическую проверку
            if ($this->passesAutoModeration($master)) {
                // Одобряем объявление - делаем его видимым в поиске
                $master->update([
                    'is_published' => true,
                    'moderated_at' => now()
                ]);

                Log::info('AutoModerateAdJob: Объявление автоматически одобрено', [
                    'master_id' => $this->masterId,
                    'moderated_at' => now()
                ]);

                // TODO: Отправить уведомление пользователю о прохождении модерации
                // $this->sendApprovalNotification($master);
            } else {
                // Объявление требует ручной модерации
                Log::warning('AutoModerateAdJob: Объявление требует ручной модерации', [
                    'master_id' => $this->masterId,
                    'reason' => 'Найдены запрещенные слова или подозрительный контент'
                ]);

                // TODO: Создать задачу для модератора
                // $this->createManualModerationTask($master);
            }
        } catch (\Exception $e) {
            Log::error('AutoModerateAdJob: Ошибка при модерации', [
                'master_id' => $this->masterId,
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
    private function passesAutoModeration(MasterProfile $master): bool
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
            ($master->display_name ?? '') . ' ' .
            ($master->description ?? '') . ' ' .
            ($master->salon_name ?? '')
        );

        // Проверяем на наличие запрещенных слов
        foreach ($bannedWords as $word) {
            if (mb_strpos($textToCheck, mb_strtolower($word)) !== false) {
                Log::info('AutoModerateAdJob: Найдено запрещенное слово', [
                    'master_id' => $this->masterId,
                    'banned_word' => $word
                ]);
                return false;
            }
        }

        // Дополнительные проверки

        // 1. Проверка на слишком короткое описание
        if (mb_strlen($master->description) < 30) {
            Log::info('AutoModerateAdJob: Слишком короткое описание', [
                'master_id' => $this->masterId,
                'description_length' => mb_strlen($master->description)
            ]);
            return false;
        }

        // 2. Проверка на подозрительные символы или спам
        if (preg_match('/(.)\1{4,}/', $textToCheck)) {
            Log::info('AutoModerateAdJob: Обнаружен спам (повторяющиеся символы)', [
                'master_id' => $this->masterId
            ]);
            return false;
        }

        // 3. Проверка на слишком много заглавных букв (крик)
        $uppercase = preg_match_all('/[А-ЯA-Z]/', $master->description, $matches);
        $totalChars = mb_strlen($master->description);
        if ($totalChars > 0 && ($uppercase / $totalChars) > 0.5) {
            Log::info('AutoModerateAdJob: Слишком много заглавных букв', [
                'master_id' => $this->masterId,
                'uppercase_ratio' => $uppercase / $totalChars
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
            'master_id' => $this->masterId,
            'error' => $exception->getMessage()
        ]);

        // TODO: Уведомить администратора о проблеме с модерацией
        // $this->notifyAdminAboutFailure($this->masterId, $exception);
    }
}