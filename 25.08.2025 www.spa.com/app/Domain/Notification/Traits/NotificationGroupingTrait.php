<?php

namespace App\Domain\Notification\Traits;

use App\Enums\NotificationType;

/**
 * Трейт для группировки уведомлений
 */
trait NotificationGroupingTrait
{
    /**
     * Группировка уведомлений
     */
    public function getGroupKey(): ?string
    {
        return $this->group_key;
    }

    public function setGroupKey(string $key): void
    {
        $this->update(['group_key' => $key]);
    }

    public static function generateGroupKey(int $userId, NotificationType $type, string $identifier = null): string
    {
        $parts = [$userId, $type->value];
        
        if ($identifier) {
            $parts[] = $identifier;
        }
        
        return implode(':', $parts);
    }
}