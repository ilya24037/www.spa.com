<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\Booking\Actions\Services\BookingConfirmationValidator;
use App\Domain\Booking\Actions\Services\BookingConfirmationProcessor;
use App\Domain\Booking\Actions\Services\BookingConfirmationNotifier;
use App\Domain\Booking\Actions\Services\BookingBulkOperations;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для подтверждения бронирования - координатор
 */
class ConfirmBookingAction
{
    private BookingRepository $bookingRepository;
    private BookingConfirmationValidator $validator;
    private BookingConfirmationProcessor $processor;
    private BookingConfirmationNotifier $notifier;
    private BookingBulkOperations $bulkOperations;

    public function __construct(
        BookingRepository $bookingRepository,
        BookingConfirmationValidator $validator,
        BookingConfirmationProcessor $processor,
        BookingConfirmationNotifier $notifier,
        BookingBulkOperations $bulkOperations
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->validator = $validator;
        $this->processor = $processor;
        $this->notifier = $notifier;
        $this->bulkOperations = $bulkOperations;
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
        $this->validator->validateConfirmation($booking, $master);

        // Проверка конфликтов в расписании
        $this->validator->checkScheduleConflicts($booking, $master);

        // Выполнение подтверждения в транзакции
        $result = DB::transaction(function () use ($booking, $master, $options) {
            return $this->processor->performConfirmation($booking, $master, $options);
        });

        // Настройка напоминаний
        $this->notifier->scheduleReminders($booking);

        // Отправка уведомлений
        $this->notifier->sendConfirmationNotifications($booking, $options);

        // Обработка предоплаты если требуется
        $this->notifier->handlePrepayment($booking, $options);

        Log::info('Booking confirmed successfully', [
            'booking_id' => $booking->id,
            'confirmed_at' => $booking->confirmed_at,
        ]);

        return $booking->fresh();
    }

    /**
     * Автоподтверждение бронирований
     */
    public function autoConfirm(Booking $booking): bool
    {
        return $this->bulkOperations->autoConfirm($booking);
    }

    /**
     * Массовое подтверждение бронирований
     */
    public function bulkConfirm(array $bookingIds, User $master, array $options = []): array
    {
        return $this->bulkOperations->bulkConfirm($bookingIds, $master, $options);
    }
}