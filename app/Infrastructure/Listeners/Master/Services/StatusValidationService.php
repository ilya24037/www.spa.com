<?php

namespace App\Infrastructure\Listeners\Master\Services;

use Exception;

/**
 * Сервис валидации изменения статуса мастера
 */
class StatusValidationService
{
    /**
     * Валидировать смену статуса
     */
    public function validateStatusChange($masterProfile, string $oldStatus, string $newStatus): void
    {
        // Проверяем, что статус действительно изменился
        if ($masterProfile->status === $newStatus) {
            throw new Exception("Статус уже установлен в {$newStatus}");
        }

        // Проверяем допустимые переходы статуса
        $allowedTransitions = $this->getAllowedStatusTransitions();
        
        if (!isset($allowedTransitions[$oldStatus]) || 
            !in_array($newStatus, $allowedTransitions[$oldStatus])) {
            throw new Exception("Недопустимый переход статуса с {$oldStatus} на {$newStatus}");
        }
    }

    /**
     * Получить допустимые переходы статуса
     */
    public function getAllowedStatusTransitions(): array
    {
        return [
            'draft' => ['pending_moderation', 'archived'],
            'pending_moderation' => ['active', 'rejected', 'draft'],
            'active' => ['inactive', 'suspended', 'pending_moderation', 'archived'],
            'inactive' => ['active', 'archived'],
            'suspended' => ['active', 'banned'],
            'rejected' => ['draft', 'archived'],
            'banned' => ['suspended'], // Только админы могут разбанить
            'archived' => ['draft'], // Можно восстановить из архива
        ];
    }

    /**
     * Рассчитать дату автоматической разблокировки
     */
    public function calculateAutoUnblockDate(?string $reason): ?\DateTime
    {
        if (!$reason) {
            return null;
        }

        // Временные блокировки
        $tempReasons = [
            'documents_check' => 7, // дней
            'quality_issues' => 14,
            'client_complaints' => 30,
        ];

        foreach ($tempReasons as $reasonKey => $days) {
            if (str_contains(strtolower($reason), $reasonKey)) {
                return (new \DateTime())->modify("+{$days} days");
            }
        }

        return null; // Постоянная блокировка
    }

    /**
     * Проверить, является ли бан постоянным
     */
    public function isPermanentBan(?string $reason): bool
    {
        $permanentReasons = ['fraud', 'illegal_activities', 'severe_violations'];
        
        if (!$reason) {
            return false;
        }

        foreach ($permanentReasons as $permanentReason) {
            if (str_contains(strtolower($reason), $permanentReason)) {
                return true;
            }
        }

        return false;
    }
}