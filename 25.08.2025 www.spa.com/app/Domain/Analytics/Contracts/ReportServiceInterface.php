<?php

namespace App\Domain\Analytics\Contracts;

use Carbon\Carbon;

/**
 * Интерфейс сервиса отчетов
 */
interface ReportServiceInterface
{
    /**
     * Сгенерировать ежедневный отчет
     */
    public function generateDailyReport(?Carbon $date = null): array;

    /**
     * Сгенерировать еженедельный отчет
     */
    public function generateWeeklyReport(?Carbon $startOfWeek = null): array;

    /**
     * Сгенерировать ежемесячный отчет
     */
    public function generateMonthlyReport(?Carbon $month = null): array;

    /**
     * Отчет по конкретному мастеру
     */
    public function generateMasterReport(int $masterProfileId, Carbon $from, Carbon $to): array;

    /**
     * Отчет по рекламным кампаниям (UTM метки)
     */
    public function generateCampaignReport(Carbon $from, Carbon $to): array;

    /**
     * Отчет по поведению пользователей
     */
    public function generateUserBehaviorReport(Carbon $from, Carbon $to): array;
}