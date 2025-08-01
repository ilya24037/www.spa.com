<?php

namespace App\Domain\Ad\DTOs;

class AdScheduleData
{
    public function __construct(
        public readonly array $workingDays,
        public readonly ?string $workingHoursStart,
        public readonly ?string $workingHoursEnd,
        public readonly ?bool $flexibleSchedule,
        public readonly ?array $breaks,
        public readonly ?array $holidays,
        public readonly ?string $timezone
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            workingDays: $data['working_days'] ?? [],
            workingHoursStart: $data['working_hours_start'] ?? null,
            workingHoursEnd: $data['working_hours_end'] ?? null,
            flexibleSchedule: $data['flexible_schedule'] ?? false,
            breaks: $data['breaks'] ?? null,
            holidays: $data['holidays'] ?? null,
            timezone: $data['timezone'] ?? 'Europe/Moscow'
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'working_days' => $this->workingDays,
            'working_hours_start' => $this->workingHoursStart,
            'working_hours_end' => $this->workingHoursEnd,
            'flexible_schedule' => $this->flexibleSchedule,
            'breaks' => $this->breaks,
            'holidays' => $this->holidays,
            'timezone' => $this->timezone,
        ], fn($value) => $value !== null);
    }
}