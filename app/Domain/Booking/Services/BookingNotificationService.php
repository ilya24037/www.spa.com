<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\Notification\Contracts\NotificationServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Единый сервис уведомлений для бронирований
 * Объединяет логику из BookingNotificationService, BookingReminderService,
 * RescheduleNotificationHandler и NotificationService
 */
class BookingNotificationService
{
    public function __construct(
        private NotificationServiceInterface $notificationService,
        private BookingRepository $bookingRepository
    ) {}

    /**
     * Отправить уведомление о создании бронирования
     */
    public function sendCreatedNotification(Booking $booking): void
    {
        try {
            // Уведомление клиенту
            $this->notificationService->send(
                $booking->user,
                'booking.created',
                [
                    'booking_number' => $booking->booking_number,
                    'master_name' => $booking->master->display_name ?? $booking->master->name,
                    'date' => Carbon::parse($booking->booking_date)->format('d.m.Y'),
                    'time' => Carbon::parse($booking->start_time)->format('H:i'),
                    'service' => $booking->service->name ?? 'Услуга',
                    'address' => $booking->master->address ?? '',
                    'price' => $booking->total_price,
                ]
            );

            // Уведомление мастеру
            if ($booking->master->user) {
                $this->notificationService->send(
                    $booking->master->user,
                    'booking.new_for_master',
                    [
                        'client_name' => $booking->client_name ?? $booking->user->name,
                        'client_phone' => $booking->client_phone,
                        'date' => Carbon::parse($booking->booking_date)->format('d.m.Y'),
                        'time' => Carbon::parse($booking->start_time)->format('H:i'),
                        'service' => $booking->service->name ?? 'Услуга',
                        'comment' => $booking->client_comment ?? '',
                    ]
                );
            }

            Log::info('Booking created notifications sent', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send booking created notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомление о подтверждении бронирования
     */
    public function sendConfirmationNotification(Booking $booking): void
    {
        try {
            $this->notificationService->send(
                $booking->user,
                'booking.confirmed',
                [
                    'booking_number' => $booking->booking_number,
                    'master_name' => $booking->master->display_name ?? $booking->master->name,
                    'date' => Carbon::parse($booking->booking_date)->format('d.m.Y'),
                    'time' => Carbon::parse($booking->start_time)->format('H:i'),
                    'payment_status' => $booking->payment_status ?? 'not_required',
                ]
            );

            Log::info('Booking confirmation notification sent', [
                'booking_id' => $booking->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send booking confirmation notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомление об отмене бронирования
     */
    public function sendCancellationNotification(Booking $booking, ?string $reason = null): void
    {
        try {
            // Клиенту
            $this->notificationService->send(
                $booking->user,
                'booking.cancelled',
                [
                    'booking_number' => $booking->booking_number,
                    'master_name' => $booking->master->display_name ?? $booking->master->name,
                    'date' => Carbon::parse($booking->booking_date)->format('d.m.Y'),
                    'time' => Carbon::parse($booking->start_time)->format('H:i'),
                    'reason' => $reason ?? 'Не указана',
                    'refund_amount' => $this->calculateRefundAmount($booking),
                ]
            );

            // Мастеру
            if ($booking->master->user) {
                $this->notificationService->send(
                    $booking->master->user,
                    'booking.cancelled_for_master',
                    [
                        'client_name' => $booking->client_name ?? $booking->user->name,
                        'date' => Carbon::parse($booking->booking_date)->format('d.m.Y'),
                        'time' => Carbon::parse($booking->start_time)->format('H:i'),
                        'reason' => $reason ?? 'Не указана',
                    ]
                );
            }

            Log::info('Booking cancellation notifications sent', [
                'booking_id' => $booking->id,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send booking cancellation notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить напоминание о бронировании
     */
    public function sendReminderNotification(Booking $booking): void
    {
        // Проверяем, не отправлено ли уже напоминание
        if ($booking->reminder_sent) {
            return;
        }

        try {
            $startDateTime = Carbon::parse($booking->booking_date . ' ' . $booking->start_time);
            $hoursBeforeStart = Carbon::now()->diffInHours($startDateTime, false);
            
            // Напоминание за день
            if ($hoursBeforeStart >= 23 && $hoursBeforeStart <= 25 && !$booking->reminder_24h_sent) {
                $this->notificationService->send(
                    $booking->user,
                    'booking.reminder_day_before',
                    [
                        'master_name' => $booking->master->display_name ?? $booking->master->name,
                        'date' => Carbon::parse($booking->booking_date)->format('d.m.Y'),
                        'time' => Carbon::parse($booking->start_time)->format('H:i'),
                        'service' => $booking->service->name ?? 'Услуга',
                    ]
                );
                
                $booking->update(['reminder_24h_sent' => true]);
            }
            
            // Напоминание за 2 часа
            if ($hoursBeforeStart >= 1.5 && $hoursBeforeStart <= 2.5 && !$booking->reminder_2h_sent) {
                $this->notificationService->send(
                    $booking->user,
                    'booking.reminder_2hours',
                    [
                        'master_name' => $booking->master->display_name ?? $booking->master->name,
                        'time' => Carbon::parse($booking->start_time)->format('H:i'),
                        'address' => $booking->master->address ?? '',
                        'phone' => $booking->master->phone ?? '',
                    ]
                );
                
                $booking->update(['reminder_2h_sent' => true, 'reminder_sent' => true]);
            }

            Log::info('Booking reminder sent', [
                'booking_id' => $booking->id,
                'hours_before' => $hoursBeforeStart,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send booking reminder', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомление о переносе бронирования
     */
    public function sendRescheduleNotification(Booking $booking, Carbon $oldDateTime): void
    {
        try {
            // Клиенту
            $this->notificationService->send(
                $booking->user,
                'booking.rescheduled',
                [
                    'booking_number' => $booking->booking_number,
                    'old_date' => $oldDateTime->format('d.m.Y'),
                    'old_time' => $oldDateTime->format('H:i'),
                    'new_date' => Carbon::parse($booking->booking_date)->format('d.m.Y'),
                    'new_time' => Carbon::parse($booking->start_time)->format('H:i'),
                    'master_name' => $booking->master->display_name ?? $booking->master->name,
                ]
            );

            // Мастеру
            if ($booking->master->user) {
                $this->notificationService->send(
                    $booking->master->user,
                    'booking.rescheduled_for_master',
                    [
                        'client_name' => $booking->client_name ?? $booking->user->name,
                        'old_date' => $oldDateTime->format('d.m.Y'),
                        'old_time' => $oldDateTime->format('H:i'),
                        'new_date' => Carbon::parse($booking->booking_date)->format('d.m.Y'),
                        'new_time' => Carbon::parse($booking->start_time)->format('H:i'),
                    ]
                );
            }

            Log::info('Booking reschedule notifications sent', [
                'booking_id' => $booking->id,
                'old_datetime' => $oldDateTime->toDateTimeString(),
                'new_datetime' => $booking->booking_date . ' ' . $booking->start_time,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send booking reschedule notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправка уведомлений о завершении
     */
    public function sendCompletionNotifications(Booking $booking, array $options = []): void
    {
        try {
            // Основное уведомление о завершении
            $this->sendBasicCompletionNotification($booking);
            
            // Запрос на отзыв (если включено)
            if ($options['request_review'] ?? true) {
                $this->scheduleReviewRequest($booking, $options);
            }
            
            // Дополнительные уведомления
            $this->sendOptionalNotifications($booking, $options);

            Log::info('Completion notifications sent', [
                'booking_id' => $booking->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send completion notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Базовое уведомление о завершении
     */
    private function sendBasicCompletionNotification(Booking $booking): void
    {
        $this->notificationService->sendBookingCompleted($booking);
    }

    /**
     * Запланировать запрос отзыва
     */
    private function scheduleReviewRequest(Booking $booking, array $options): void
    {
        if ($options['request_review'] ?? true) {
            dispatch(function () use ($booking) {
                $this->notificationService->sendReviewRequest($booking);
            })->delay(now()->addHours(2)); // Через 2 часа после завершения
        }
    }

    /**
     * Отправить опциональные уведомления
     */
    private function sendOptionalNotifications(Booking $booking, array $options): void
    {
        // SMS с благодарностью
        if ($booking->client_phone && ($options['send_sms'] ?? true)) {
            $this->notificationService->sendCompletionSMS($booking);
        }
    }

    /**
     * Отправить массовые напоминания о предстоящих бронированиях
     * Вызывается из команды artisan schedule
     */
    public function sendUpcomingBookingReminders(): int
    {
        $reminderTime = Carbon::now()->addHours(
            config('booking.reminder_hours_before', 24)
        );

        $bookings = $this->bookingRepository->getUpcomingBookingsForReminder($reminderTime);
        $sentCount = 0;

        foreach ($bookings as $booking) {
            $this->sendReminderNotification($booking);
            $sentCount++;
        }

        Log::info('Sent upcoming booking reminders', [
            'count' => $sentCount,
            'time' => $reminderTime->toDateTimeString(),
        ]);

        return $sentCount;
    }

    /**
     * Отправить напоминания мастерам о завтрашних бронированиях
     */
    public function sendMasterDailySchedule(): int
    {
        $tomorrow = Carbon::tomorrow();
        $masters = $this->bookingRepository->getMastersWithBookingsForDate($tomorrow);
        $sentCount = 0;

        foreach ($masters as $master) {
            if (!$master->user) {
                continue;
            }

            $bookings = $this->bookingRepository->getMasterBookingsForDate($master->id, $tomorrow);
            
            $this->notificationService->send(
                $master->user,
                'master.daily_schedule',
                [
                    'date' => $tomorrow->format('d.m.Y'),
                    'bookings' => $bookings->map(function ($booking) {
                        return [
                            'time' => Carbon::parse($booking->start_time)->format('H:i'),
                            'client' => $booking->client_name,
                            'service' => $booking->service->name ?? 'Услуга',
                            'phone' => $booking->client_phone,
                        ];
                    })->toArray(),
                ]
            );
            
            $sentCount++;
        }

        Log::info('Sent master daily schedules', [
            'count' => $sentCount,
            'date' => $tomorrow->toDateString(),
        ]);

        return $sentCount;
    }

    /**
     * Вычислить сумму возврата при отмене
     */
    private function calculateRefundAmount(Booking $booking): float
    {
        // Если бронирование не оплачено
        if (!$booking->payment_id) {
            return 0;
        }

        $startDateTime = Carbon::parse($booking->booking_date . ' ' . $booking->start_time);
        $hoursBeforeStart = Carbon::now()->diffInHours($startDateTime, false);
        
        // Полный возврат за 24 часа и более
        if ($hoursBeforeStart >= 24) {
            return $booking->prepayment ?? $booking->total_price;
        }
        
        // 50% возврат от 12 до 24 часов
        if ($hoursBeforeStart >= 12) {
            return ($booking->prepayment ?? $booking->total_price) * 0.5;
        }
        
        // Нет возврата менее чем за 12 часов
        return 0;
    }
}