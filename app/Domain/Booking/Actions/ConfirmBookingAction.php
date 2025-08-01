<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Enums\BookingStatus;
use Illuminate\Support\Facades\Log;

/**
 * Action для подтверждения бронирования
 */
class ConfirmBookingAction
{
    private BookingRepository $bookingRepository;

    public function __construct(BookingRepository $bookingRepository)
    {
        $this->bookingRepository = $bookingRepository;
    }

    /**
     * Выполнить подтверждение бронирования
     */
    public function execute(
        Booking $booking, 
        User $master, 
        array $options = []
    ): Booking {
        Log::info('Confirming booking', [
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'master_id' => $master->id,
            'options' => $options,
        ]);

        // Валидация прав и возможности подтверждения
        $this->validateConfirmation($booking, $master);

        // Проверка конфликтов в расписании
        $this->checkScheduleConflicts($booking, $master);

        // Выполнение подтверждения в транзакции
        $result = DB::transaction(function () use ($booking, $master, $options) {
            return $this->performConfirmation($booking, $master, $options);
        });

        // Настройка напоминаний
        $this->scheduleReminders($booking);

        // Отправка уведомлений
        $this->sendConfirmationNotifications($booking, $options);

        // Обработка предоплаты если требуется
        $this->handlePrepayment($booking, $options);

        Log::info('Booking confirmed successfully', [
            'booking_id' => $booking->id,
            'confirmed_at' => $booking->confirmed_at,
        ]);

        return $booking->fresh();
    }

    /**
     * Валидация возможности подтверждения
     */
    protected function validateConfirmation(Booking $booking, User $master): void
    {
        // Проверяем права мастера
        $this->validateMasterPermissions($booking, $master);

        // Проверяем статус бронирования
        $this->validateBookingStatus($booking);

        // Проверяем время
        $this->validateConfirmationTime($booking);
    }

    /**
     * Проверка прав мастера
     */
    protected function validateMasterPermissions(Booking $booking, User $master): void
    {
        $canConfirm = $booking->master_id === $master->id ||
                     ($booking->master_profile_id && $master->masterProfile && 
                      $booking->master_profile_id === $master->masterProfile->id) ||
                     $master->hasRole('admin');

        if (!$canConfirm) {
            throw new \Exception('У вас нет прав для подтверждения этого бронирования');
        }
    }

    /**
     * Проверка статуса бронирования
     */
    protected function validateBookingStatus(Booking $booking): void
    {
        if ($booking->status instanceof BookingStatus) {
            if (!$booking->status->canTransitionTo(BookingStatus::CONFIRMED)) {
                throw new \Exception(
                    "Нельзя подтвердить бронирование в статусе: {$booking->status->getLabel()}"
                );
            }
        } else {
            // Совместимость со старым кодом
            if ($booking->status !== Booking::STATUS_PENDING) {
                throw new \Exception('Можно подтвердить только ожидающие бронирования');
            }
        }
    }

    /**
     * Проверка времени подтверждения
     */
    protected function validateConfirmationTime(Booking $booking): void
    {
        // Нельзя подтвердить просроченное бронирование
        if ($booking->start_time->isPast()) {
            throw new \Exception('Нельзя подтвердить просроченное бронирование');
        }

        // Проверяем автоотмену
        $hoursOld = $booking->created_at->diffInHours(now());
        $autoCanelHours = $booking->status instanceof BookingStatus 
            ? $booking->status->getAutoCanelHours() 
            : 24;

        if ($autoCanelHours && $hoursOld > $autoCanelHours) {
            throw new \Exception(
                "Бронирование просрочено для подтверждения (создано {$hoursOld} часов назад, " .
                "лимит: {$autoCanelHours} часов)"
            );
        }
    }

    /**
     * Проверка конфликтов в расписании
     */
    protected function checkScheduleConflicts(Booking $booking, User $master): void
    {
        // Ищем пересекающиеся подтвержденные бронирования
        $conflicts = Booking::where('master_id', $master->id)
            ->where('id', '!=', $booking->id)
            ->where(function ($query) use ($booking) {
                $query->where('status', BookingStatus::CONFIRMED)
                      ->orWhere('status', BookingStatus::IN_PROGRESS);
            })
            ->where(function ($query) use ($booking) {
                $query->whereBetween('start_time', [$booking->start_time, $booking->end_time])
                      ->orWhereBetween('end_time', [$booking->start_time, $booking->end_time])
                      ->orWhere(function ($q) use ($booking) {
                          $q->where('start_time', '<=', $booking->start_time)
                            ->where('end_time', '>=', $booking->end_time);
                      });
            })
            ->exists();

        if ($conflicts) {
            throw new \Exception(
                'Обнаружен конфликт в расписании. У вас уже есть подтвержденное бронирование на это время.'
            );
        }
    }

    /**
     * Выполнение подтверждения
     */
    protected function performConfirmation(Booking $booking, User $master, array $options): array
    {
        // Обновляем статус бронирования
        if ($booking->status instanceof BookingStatus) {
            $booking->status = BookingStatus::CONFIRMED;
        } else {
            $booking->status = Booking::STATUS_CONFIRMED;
        }

        $booking->confirmed_at = now();
        
        // Добавляем дополнительную информацию от мастера
        if (!empty($options['master_notes'])) {
            $booking->internal_notes = $options['master_notes'];
        }

        if (!empty($options['equipment_list'])) {
            $booking->equipment_required = $options['equipment_list'];
        }

        if (!empty($options['master_phone'])) {
            $booking->master_phone = $options['master_phone'];
        }

        // Обновляем адрес мастера если не указан
        if (empty($booking->master_address) && !empty($options['master_address'])) {
            $booking->master_address = $options['master_address'];
        }

        // Добавляем метаданные подтверждения
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

        // Создаем слоты если нужно
        if ($options['create_slots'] ?? false) {
            $this->createConfirmedBookingSlots($booking);
        }

        // Обновляем статистику мастера
        $this->updateMasterStatistics($master);

        return [
            'booking' => $booking,
            'conflicts_resolved' => $options['resolve_conflicts'] ?? false,
        ];
    }

    /**
     * Создание слотов для подтвержденного бронирования
     */
    protected function createConfirmedBookingSlots(Booking $booking): void
    {
        // Удаляем старые слоты если есть
        $booking->slots()->delete();

        // Основной слот услуги
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

        // Буферное время для подготовки (если тип требует)
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

    /**
     * Настройка напоминаний
     */
    protected function scheduleReminders(Booking $booking): void
    {
        if (!$booking->type) {
            return;
        }

        $reminderHours = $booking->type->getReminderHours();
        
        foreach ($reminderHours as $hours) {
            $reminderTime = $booking->start_time->copy()->subHours($hours);
            
            // Не планируем напоминания в прошлом
            if ($reminderTime->isPast()) {
                continue;
            }

            // Создаем задачу напоминания (здесь должна быть интеграция с очередями)
            dispatch(function () use ($booking) {
                $this->notificationService->sendBookingReminder($booking);
            })->delay($reminderTime);
        }
    }

    /**
     * Отправка уведомлений о подтверждении
     */
    protected function sendConfirmationNotifications(Booking $booking, array $options): void
    {
        try {
            // Уведомление клиенту о подтверждении
            $this->notificationService->sendBookingConfirmed($booking);

            // SMS с деталями если указан телефон
            if ($booking->client_phone && ($options['send_sms'] ?? true)) {
                $this->notificationService->sendConfirmationSMS($booking);
            }

            // Email с деталями если указан email
            if ($booking->client_email && ($options['send_email'] ?? true)) {
                $this->notificationService->sendConfirmationEmail($booking);
            }

            // Для онлайн консультации - отправляем ссылку
            if ($booking->type === \App\Enums\BookingType::ONLINE && $booking->meeting_link) {
                $this->notificationService->sendMeetingLink($booking);
            }

            Log::info('Confirmation notifications sent', [
                'booking_id' => $booking->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send confirmation notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обработка предоплаты
     */
    protected function handlePrepayment(Booking $booking, array $options): void
    {
        if (!$booking->type || !$booking->type->supportsPrepayment()) {
            return;
        }

        $depositAmount = $booking->deposit_amount ?? ($booking->total_price * 0.3);

        if ($depositAmount <= 0) {
            return;
        }

        try {
            // Отправляем ссылку на оплату предоплаты
            $paymentLink = $this->paymentService->createDepositPaymentLink($booking, $depositAmount);
            
            if ($paymentLink) {
                $this->notificationService->sendDepositPaymentLink($booking, $paymentLink);
                
                Log::info('Deposit payment link sent', [
                    'booking_id' => $booking->id,
                    'deposit_amount' => $depositAmount,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create deposit payment link', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Автоподтверждение бронирований
     * Для мастеров с включенным автоподтверждением
     */
    public function autoConfirm(Booking $booking): bool
    {
        try {
            $master = User::find($booking->master_id);
            
            if (!$master || !$master->masterProfile) {
                return false;
            }

            // Проверяем настройку автоподтверждения
            $autoConfirm = $master->masterProfile->auto_confirm_bookings ?? false;
            
            if (!$autoConfirm) {
                return false;
            }

            // Выполняем автоподтверждение
            $this->execute($booking, $master, [
                'auto_confirm' => true,
                'method' => 'automatic',
                'create_slots' => true,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Auto-confirmation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Массовое подтверждение бронирований
     */
    public function bulkConfirm(array $bookingIds, User $master, array $options = []): array
    {
        $results = [];

        foreach ($bookingIds as $bookingId) {
            try {
                $booking = Booking::findOrFail($bookingId);
                $this->execute($booking, $master, $options);
                
                $results[] = [
                    'booking_id' => $bookingId,
                    'success' => true,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'booking_id' => $bookingId,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Bulk confirmation completed', [
            'total_bookings' => count($bookingIds),
            'successful' => count(array_filter($results, fn($r) => $r['success'])),
        ]);

        return $results;
    }
}