<?php

namespace App\Domain\Analytics\Events;

use App\Domain\Analytics\Models\UserAction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие отслеживания конверсии
 */
class ConversionTracked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public UserAction $userAction;
    public float $conversionValue;

    /**
     * Create a new event instance.
     */
    public function __construct(UserAction $userAction)
    {
        $this->userAction = $userAction;
        $this->conversionValue = $userAction->conversion_value;
    }

    /**
     * Получить данные для логирования
     */
    public function toArray(): array
    {
        return [
            'action_id' => $this->userAction->id,
            'user_id' => $this->userAction->user_id,
            'action_type' => $this->userAction->action_type,
            'actionable_type' => $this->userAction->actionable_type,
            'actionable_id' => $this->userAction->actionable_id,
            'conversion_value' => $this->conversionValue,
            'properties' => $this->userAction->properties,
            'performed_at' => $this->userAction->performed_at->toDateTimeString(),
        ];
    }

    /**
     * Получить тип конверсии
     */
    public function getConversionType(): string
    {
        return $this->userAction->action_type;
    }

    /**
     * Проверить, является ли конверсия высокоценной
     */
    public function isHighValue(float $threshold = 1000.0): bool
    {
        return $this->conversionValue >= $threshold;
    }
}