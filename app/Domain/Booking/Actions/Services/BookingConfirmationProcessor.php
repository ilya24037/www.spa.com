<?php

namespace App\Domain\Booking\Actions\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Models\BookingHistory;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\User\Models\User;

/**
 * Процессор подтверждения бронирований
 */
class BookingConfirmationProcessor
{
    /**
     * Выполнение подтверждения
     */
    public function performConfirmation(Booking $booking, User $master, array $options): array
    {
        $previousStatus = $booking->status;
        
        $this->updateBookingStatus($booking, $master, $options);
        $this->logStatusChange($booking, $previousStatus, $master);
        $this->createSlotsIfNeeded($booking, $options);
        $this->updateMasterStatistics($master);

        return [
            'booking' => $booking,
            'conflicts_resolved' => $options['resolve_conflicts'] ?? false,
        ];
    }

    /**
     * Обновление статуса и метаданных бронирования
     */
    protected function updateBookingStatus(Booking $booking, User $master, array $options): void
    {
        if ($booking->status instanceof BookingStatus) {
            $booking->status = BookingStatus::CONFIRMED;
        } else {
            $booking->status = Booking::STATUS_CONFIRMED;
        }

        $booking->confirmed_at = now();
        
        if (!empty($options['master_notes'])) {
            $booking->internal_notes = $options['master_notes'];
        }

        if (!empty($options['equipment_list'])) {
            $booking->equipment_required = $options['equipment_list'];
        }

        if (!empty($options['master_phone'])) {
            $booking->master_phone = $options['master_phone'];
        }

        if (empty($booking->master_address) && !empty($options['master_address'])) {
            $booking->master_address = $options['master_address'];
        }

        $metadata = $booking->metadata ?? [];
        $metadata['confirmation'] = [
            'confirmed_by' => $master->id,
            'confirmed_at' => now()->toISOString(),
            'master_name' => $master->name,
            'confirmation_method' => $options['method'] ?? 'manual',
            'auto_confirmed' => $options['auto_confirm'] ?? false,
        ];
        $booking->metadata = $metadata;

        $booking->save();
    }

    /**
     * Логирование изменения статуса
     */
    protected function logStatusChange(Booking $booking, $previousStatus, User $master): void
    {
        BookingHistory::logStatusChange(
            $booking,
            $previousStatus,
            BookingStatus::CONFIRMED->value,
            'Подтверждено мастером',
            $master->id
        );
    }

    /**
     * Создание слотов если необходимо
     */
    protected function createSlotsIfNeeded(Booking $booking, array $options): void
    {
        if ($options['create_slots'] ?? false) {
            $this->createConfirmedBookingSlots($booking);
        }
    }

    /**
     * Создание слотов для подтвержденного бронирования
     */
    protected function createConfirmedBookingSlots(Booking $booking): void
    {
        $booking->slots()->delete();

        $booking->slots()->create([
            'master_id' => $booking->master_id,
            'start_time' => $booking->start_time,
            'end_time' => $booking->end_time,
            'duration_minutes' => $booking->duration_minutes,
            'is_blocked' => false,
            'is_break' => false,
            'is_preparation' => false,
            'notes' => 'Подтвержденное бронирование: ' . ($booking->service->name ?? 'Услуга'),
        ]);

        if ($booking->type && $booking->type->requiresEquipmentConfirmation()) {
            $prepStart = $booking->start_time->copy()->subMinutes(15);
            
            $booking->slots()->create([
                'master_id' => $booking->master_id,
                'start_time' => $prepStart,
                'end_time' => $booking->start_time,
                'duration_minutes' => 15,
                'is_blocked' => true,
                'is_break' => false,
                'is_preparation' => true,
                'notes' => 'Подготовка к услуге',
            ]);
        }
    }

    /**
     * Обновление статистики мастера
     */
    protected function updateMasterStatistics(User $master): void
    {
        if ($master->masterProfile) {
            $master->masterProfile->increment('confirmed_bookings_count');
            $master->masterProfile->update([
                'last_booking_confirmed_at' => now(),
            ]);
        }
    }
}