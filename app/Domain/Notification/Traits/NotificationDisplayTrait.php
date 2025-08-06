<?php

namespace App\Domain\Notification\Traits;

/**
 * Трейт для отображения уведомлений
 */
trait NotificationDisplayTrait
{
    /**
     * Получить данные для отображения
     */
    public function getDisplayTitle(): string
    {
        return $this->title ?: $this->type->getTitle();
    }

    public function getDisplayMessage(): string
    {
        return $this->message ?: $this->type->getDefaultMessage();
    }

    public function getIcon(): string
    {
        return $this->type->getIcon();
    }

    public function getColor(): string
    {
        return $this->type->getColor();
    }

    public function getFormattedCreatedAt(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getTimeAgo(): string
    {
        if ($this->isRead()) {
            return "Прочитано {$this->read_at->diffForHumans()}";
        }
        
        return "Отправлено {$this->created_at->diffForHumans()}";
    }

    /**
     * Получить URL для действия (если есть)
     */
    public function getActionUrl(): ?string
    {
        return $this->data['action_url'] ?? null;
    }

    /**
     * Получить текст кнопки действия
     */
    public function getActionText(): ?string
    {
        return $this->data['action_text'] ?? null;
    }

    /**
     * Получить дополнительные данные
     */
    public function getExtraData(string $key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Форматирование для API
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'display_title' => $this->getDisplayTitle(),
            'display_message' => $this->getDisplayMessage(),
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
            'time_ago' => $this->getTimeAgo(),
            'is_read' => $this->isRead(),
            'is_expired' => $this->isExpired(),
            'action_url' => $this->getActionUrl(),
            'action_text' => $this->getActionText(),
            'group_key' => $this->getGroupKey(),
            'delivery_stats' => $this->getDeliveryStats(),
        ]);
    }
}