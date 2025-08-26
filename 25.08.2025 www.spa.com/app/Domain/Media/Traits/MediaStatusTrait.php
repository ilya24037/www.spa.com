<?php

namespace App\Domain\Media\Traits;

use App\Enums\MediaStatus;

/**
 * Трейт для работы со статусами медиафайлов
 */
trait MediaStatusTrait
{
    /**
     * Может ли файл быть удален
     */
    public function canBeDeleted(): bool
    {
        return $this->status->canBeDeleted();
    }

    /**
     * Может ли файл быть восстановлен
     */
    public function canBeRestored(): bool
    {
        return $this->status->canBeRestored();
    }

    /**
     * Обработан ли файл
     */
    public function isProcessed(): bool
    {
        return $this->status === MediaStatus::PROCESSED;
    }

    /**
     * Ожидает ли файл обработки
     */
    public function isPending(): bool
    {
        return $this->status === MediaStatus::PENDING;
    }

    /**
     * Обрабатывается ли файл
     */
    public function isProcessing(): bool
    {
        return $this->status->isProcessing();
    }

    /**
     * Не удалось ли обработать файл
     */
    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }

    /**
     * Пометить как обрабатывающийся
     */
    public function markAsProcessing(): self
    {
        $this->status = MediaStatus::PROCESSING;
        $this->save();
        return $this;
    }

    /**
     * Пометить как обработанный
     */
    public function markAsProcessed(): self
    {
        $this->status = MediaStatus::PROCESSED;
        $this->save();
        return $this;
    }

    /**
     * Пометить как неудачный
     */
    public function markAsFailed(string $errorMessage = null): self
    {
        $this->status = MediaStatus::FAILED;
        
        if ($errorMessage) {
            $metadata = $this->metadata ?? [];
            $metadata['error'] = $errorMessage;
            $this->metadata = $metadata;
        }
        
        $this->save();
        return $this;
    }
}