<?php

namespace App\Domain\Admin\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Enums\AdStatus;
use App\Domain\Ad\Services\AdModerationService;
use App\Domain\Admin\Traits\LogsAdminActions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для административных действий
 * Следует принципам SOLID - Single Responsibility
 * Инкапсулирует всю логику административных операций
 */
class AdminActionsService
{
    use LogsAdminActions;

    private AdModerationService $moderationService;

    public function __construct(AdModerationService $moderationService)
    {
        $this->moderationService = $moderationService;
    }

    /**
     * Выполнить массовое действие над объявлениями
     *
     * @param array $adIds ID объявлений
     * @param string $action Действие (approve, reject, block, archive, delete)
     * @param string|null $reason Причина (для reject/block)
     * @return array Результат выполнения [success => bool, count => int, message => string]
     */
    public function performBulkAction(array $adIds, string $action, ?string $reason = null): array
    {
        try {
            $processedCount = 0;
            $failedCount = 0;
            $errors = [];

            DB::transaction(function () use ($adIds, $action, $reason, &$processedCount, &$failedCount, &$errors) {
                // Используем lockForUpdate для предотвращения race conditions
                $ads = Ad::whereIn('id', $adIds)->lockForUpdate()->get();

                foreach ($ads as $ad) {
                    $result = $this->processSingleAction($ad, $action, $reason);

                    if ($result['success']) {
                        $processedCount++;
                    } else {
                        $failedCount++;
                        $errors[] = "Объявление #{$ad->id}: {$result['error']}";
                    }
                }

                // Логируем массовое действие
                $this->logBulkAction(
                    "bulk_{$action}",
                    $adIds,
                    Ad::class,
                    "Выполнено массовое действие '{$action}' для {$processedCount} из " . count($adIds) . " объявлений"
                );
            });

            // Формируем сообщение результата
            $message = $this->formatResultMessage($action, $processedCount, $failedCount);

            return [
                'success' => $processedCount > 0,
                'processed' => $processedCount,
                'failed' => $failedCount,
                'errors' => $errors,
                'message' => $message
            ];

        } catch (\Exception $e) {
            Log::error('Ошибка при массовой операции', [
                'action' => $action,
                'ids' => $adIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'processed' => 0,
                'failed' => count($adIds),
                'errors' => ['Произошла системная ошибка при выполнении операции'],
                'message' => 'Операция не выполнена из-за системной ошибки'
            ];
        }
    }

    /**
     * Обработать одно действие над объявлением
     *
     * @param Ad $ad Объявление
     * @param string $action Действие
     * @param string|null $reason Причина
     * @return array ['success' => bool, 'error' => string|null]
     */
    private function processSingleAction(Ad $ad, string $action, ?string $reason = null): array
    {
        try {
            switch ($action) {
                case 'approve':
                    $result = $this->moderationService->approveAd($ad);
                    break;

                case 'reject':
                    $result = $this->moderationService->rejectAd(
                        $ad,
                        $reason ?? 'Массовое отклонение администратором'
                    );
                    break;

                case 'block':
                    $result = $this->moderationService->block(
                        $ad,
                        $reason ?? 'Массовая блокировка администратором'
                    );
                    break;

                case 'archive':
                    $ad->update(['status' => AdStatus::ARCHIVED->value]);
                    $result = true;
                    break;

                case 'delete':
                    $ad->delete();
                    $result = true;
                    break;

                default:
                    return [
                        'success' => false,
                        'error' => "Неизвестное действие: {$action}"
                    ];
            }

            return [
                'success' => $result,
                'error' => $result ? null : 'Операция не выполнена'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Обновить объявление от имени администратора
     *
     * @param Ad $ad Объявление
     * @param array $data Данные для обновления
     * @return bool
     */
    public function updateAdAsAdmin(Ad $ad, array $data): bool
    {
        try {
            return DB::transaction(function () use ($ad, $data) {
                // Сохраняем старые значения
                $oldValues = $ad->only(array_keys($data));

                // Обновляем объявление
                $ad->update($data);

                // Логируем изменение
                $this->logModelEdit($ad, $oldValues, $data);

                return true;
            });

        } catch (\Exception $e) {
            Log::error('Ошибка при обновлении объявления администратором', [
                'ad_id' => $ad->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Форматировать сообщение о результате
     *
     * @param string $action Действие
     * @param int $processed Обработано успешно
     * @param int $failed Ошибок
     * @return string
     */
    private function formatResultMessage(string $action, int $processed, int $failed): string
    {
        $actionLabels = [
            'approve' => 'одобрено',
            'reject' => 'отклонено',
            'block' => 'заблокировано',
            'archive' => 'архивировано',
            'delete' => 'удалено'
        ];

        $actionLabel = $actionLabels[$action] ?? 'обработано';

        $message = "Успешно {$actionLabel} {$processed} " . $this->pluralize($processed, 'объявление', 'объявления', 'объявлений');

        if ($failed > 0) {
            $message .= ", ошибок: {$failed}";
        }

        return $message;
    }

    /**
     * Склонение слова в зависимости от числа
     */
    private function pluralize(int $count, string $one, string $two, string $many): string
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return $one;
        }

        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return $two;
        }

        return $many;
    }

    /**
     * Получить статистику административных действий
     *
     * @param \DateTime|null $from С даты
     * @param \DateTime|null $to По дату
     * @return array
     */
    public function getStatistics(?\DateTime $from = null, ?\DateTime $to = null): array
    {
        $from = $from ?? now()->subMonth();
        $to = $to ?? now();

        return $this->getAdminStatistics(null, $from, $to);
    }
}