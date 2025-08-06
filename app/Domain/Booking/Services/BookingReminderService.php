<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Repositories\BookingRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Сервис напоминаний о бронированиях
 */
class BookingReminderService
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private NotificationService $notificationService
    ) {}

    /**
     * Отправить напоминание о бронировании
     */
    public function sendBookingReminder(Booking $booking): void
    {
        if ($booking->reminder_sent) {
            return;
        }

        try {
            $this->notificationService->sendBookingReminder($booking);
            
            $booking->update(['reminder_sent' => true]);
            
            Log::info('Booking reminder sent', ['booking_id' => $booking->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send booking reminder', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Отправить напоминания о предстоящих бронированиях
     */
    public function sendUpcomingBookingReminders(): int
    {
        $reminderTime = Carbon::now()->addHours(
            config('booking.reminder_hours_before', 24)
        );

        $bookings = $this->bookingRepository->getBookingsForReminder($reminderTime);
        
        $sentCount = 0;
        
        foreach ($bookings as $booking) {
            try {
                $this->sendBookingReminder($booking);
                $sentCount++;
            } catch (\Exception $e) {
                Log::error('Failed to send reminder for booking', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Booking reminders processed', [
            'total' => $bookings->count(),
            'sent' => $sentCount
        ]);

        return $sentCount;
    }

    /**
     * Отправить напоминание за день до бронирования
     */
    public function sendDayBeforeReminders(): int
    {
        $tomorrow = Carbon::tomorrow();
        $bookings = $this->bookingRepository->getBookingsForDate($tomorrow);
        
        $sentCount = 0;
        
        foreach ($bookings as $booking) {
            if (!$booking->day_before_reminder_sent) {
                $this->notificationService->sendDayBeforeReminder($booking);
                $booking->update(['day_before_reminder_sent' => true]);
                $sentCount++;
            }
        }
        
        return $sentCount;
    }

    /**
     * Отправить напоминание за час до бронирования
     */
    public function sendHourBeforeReminders(): int
    {
        $inOneHour = Carbon::now()->addHour();
        $bookings = $this->bookingRepository->getBookingsStartingBetween(
            Carbon::now()->addMinutes(55),
            Carbon::now()->addMinutes(65)
        );
        
        $sentCount = 0;
        
        foreach ($bookings as $booking) {
            if (!$booking->hour_before_reminder_sent) {
                $this->notificationService->sendHourBeforeReminder($booking);
                $booking->update(['hour_before_reminder_sent' => true]);
                $sentCount++;
            }
        }
        
        return $sentCount;
    }
}