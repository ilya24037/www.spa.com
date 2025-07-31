<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\User;
use App\Enums\BookingStatus;
use App\Services\NotificationService;
use App\Services\PaymentGatewayService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для завершения бронирования
 * Инкапсулирует всю логику завершения услуги с оплатой и запросом отзыва
 */
class CompleteBookingAction
{
    public function __construct(
        private NotificationService $notificationService,
        private PaymentGatewayService $paymentService
    ) {}

    /**
     * Выполнить завершение бронирования
     */
    public function execute(
        Booking $booking, 
        User $master, 
        array $options = []
    ): Booking {
        Log::info('Completing booking', [
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'master_id' => $master->id,
            'options' => $options,
        ]);

        // Валидация прав и возможности завершения
        $this->validateCompletion($booking, $master);

        // Выполнение завершения в транзакции
        $result = DB::transaction(function () use ($booking, $master, $options) {
            return $this->performCompletion($booking, $master, $options);
        });

        // Обработка оплаты
        $this->processPayment($booking, $options);

        // Отправка уведомлений и запроса отзыва
        $this->sendCompletionNotifications($booking, $options);

        Log::info('Booking completed successfully', [
            'booking_id' => $booking->id,
            'total_earned' => $booking->total_price,
        ]);

        return $booking->fresh();
    }

    /**
     * Валидация возможности завершения
     */
    protected function validateCompletion(Booking $booking, User $master): void
    {
        // Проверяем права мастера
        $this->validateMasterPermissions($booking, $master);

        // Проверяем статус бронирования
        $this->validateBookingStatus($booking);

        // Проверяем время
        $this->validateCompletionTime($booking);
    }

    /**
     * Проверка прав мастера
     */
    protected function validateMasterPermissions(Booking $booking, User $master): void
    {
        $canComplete = $booking->master_id === $master->id ||
                      ($booking->master_profile_id && $master->masterProfile && 
                       $booking->master_profile_id === $master->masterProfile->id) ||
                      $master->hasRole('admin');

        if (!$canComplete) {
            throw new \Exception('У вас нет прав для завершения этого бронирования');
        }
    }

    /**
     * Проверка статуса бронирования
     */
    protected function validateBookingStatus(Booking $booking): void
    {
        if ($booking->status instanceof BookingStatus) {
            if (!$booking->status->canComplete()) {
                throw new \Exception(
                    "Нельзя завершить бронирование в статусе: {$booking->status->getLabel()}"
                );
            }
        } else {
            // Совместимость со старым кодом
            if (!in_array($booking->status, [
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_IN_PROGRESS
            ])) {
                throw new \Exception('Можно завершить только подтвержденные или выполняющиеся бронирования');
            }
        }
    }

    /**
     * Проверка времени завершения
     */
    protected function validateCompletionTime(Booking $booking): void
    {
        // Можно завершить только после начала или в процессе выполнения
        if ($booking->start_time->isFuture()) {
            // Разрешаем завершение за 15 минут до начала (для подготовки)
            if ($booking->start_time->diffInMinutes(now()) > 15) {
                throw new \Exception('Нельзя завершить услугу до её начала');
            }
        }

        // Не должно быть слишком поздно (более 24 часов после окончания)
        if ($booking->end_time->isPast() && 
            $booking->end_time->diffInHours(now()) > 24) {
            Log::warning('Late completion attempt', [
                'booking_id' => $booking->id,
                'hours_after_end' => $booking->end_time->diffInHours(now()),
            ]);
        }
    }

    /**
     * Выполнение завершения
     */
    protected function performCompletion(Booking $booking, User $master, array $options): array
    {
        $previousStatus = $booking->status;

        // Обновляем статус бронирования
        if ($booking->status instanceof BookingStatus) {
            $booking->status = BookingStatus::COMPLETED;
        } else {
            $booking->status = Booking::STATUS_COMPLETED;
        }

        $booking->completed_at = now();
        
        // Обновляем информацию об услуге
        if (!empty($options['actual_duration'])) {
            $booking->metadata = array_merge($booking->metadata ?? [], [
                'completion' => [
                    'actual_duration_minutes' => $options['actual_duration'],
                    'planned_duration_minutes' => $booking->duration_minutes,
                    'completed_by' => $master->id,
                    'completed_at' => now()->toISOString(),
                    'notes' => $options['completion_notes'] ?? null,
                ],
            ]);
        }

        if (!empty($options['service_notes'])) {
            $existingNotes = $booking->internal_notes ? $booking->internal_notes . "\n\n" : '';
            $booking->internal_notes = $existingNotes . "Завершение услуги: " . $options['service_notes'];
        }

        $booking->save();

        // Завершаем все связанные услуги
        $this->completeBookingServices($booking);

        // Обновляем слоты
        $this->completeBookingSlots($booking);

        // Обновляем статистику мастера
        $this->updateMasterStatistics($master, $booking);

        return [
            'booking' => $booking,
            'previous_status' => $previousStatus,
            'earnings' => $booking->total_price,
        ];
    }

    /**
     * Завершение связанных услуг
     */
    protected function completeBookingServices(Booking $booking): void
    {
        $booking->bookingServices()->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }

    /**
     * Завершение временных слотов
     */
    protected function completeBookingSlots(Booking $booking): void
    {
        $booking->slots()->update([
            'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' - Услуга завершена')"),
        ]);
    }

    /**
     * Обновление статистики мастера
     */
    protected function updateMasterStatistics(User $master, Booking $booking): void
    {
        if ($master->masterProfile) {
            $master->masterProfile->increment('completed_bookings_count');
            $master->masterProfile->increment('total_earnings', $booking->total_price);
            
            $master->masterProfile->update([
                'last_booking_completed_at' => now(),
                'last_service_date' => now()->toDateString(),
            ]);

            // Обновляем рейтинг мастера (базовый расчет)
            $this->updateMasterRating($master);
        }
    }

    /**
     * Обновление рейтинга мастера
     */
    protected function updateMasterRating(User $master): void
    {
        $completedCount = $master->masterProfile->completed_bookings_count;
        
        // Простая формула рейтинга на основе количества выполненных услуг
        if ($completedCount >= 100) {
            $rating = 5.0;
        } elseif ($completedCount >= 50) {
            $rating = 4.8;
        } elseif ($completedCount >= 20) {
            $rating = 4.5;
        } elseif ($completedCount >= 10) {
            $rating = 4.2;
        } elseif ($completedCount >= 5) {
            $rating = 4.0;
        } else {
            $rating = 3.8;
        }

        $master->masterProfile->update(['rating' => $rating]);
    }

    /**
     * Обработка оплаты
     */
    protected function processPayment(Booking $booking, array $options): void
    {
        // Если еще не оплачено полностью
        $remainingAmount = $booking->total_price - ($booking->paid_amount ?? 0);
        
        if ($remainingAmount <= 0) {
            $booking->update(['payment_status' => 'paid']);
            return;
        }

        // Автоматическое списание оставшейся суммы (если настроено)
        if ($options['auto_charge'] ?? false) {
            try {
                $chargeResult = $this->paymentService->chargeRemaining($booking, $remainingAmount);
                
                if ($chargeResult['success']) {
                    $booking->update([
                        'paid_amount' => $booking->total_price,
                        'payment_status' => 'paid',
                    ]);
                    
                    Log::info('Remaining amount charged successfully', [
                        'booking_id' => $booking->id,
                        'charged_amount' => $remainingAmount,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to charge remaining amount', [
                    'booking_id' => $booking->id,
                    'amount' => $remainingAmount,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            // Отправляем ссылку на доплату
            try {
                $paymentLink = $this->paymentService->createRemainingPaymentLink($booking, $remainingAmount);
                
                if ($paymentLink) {
                    $this->notificationService->sendRemainingPaymentLink($booking, $paymentLink);
                }
            } catch (\Exception $e) {
                Log::error('Failed to create remaining payment link', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Отправка уведомлений о завершении
     */
    protected function sendCompletionNotifications(Booking $booking, array $options): void
    {
        try {
            // Уведомление клиенту о завершении услуги
            $this->notificationService->sendBookingCompleted($booking);

            // Запрос отзыва (с небольшой задержкой)
            if ($options['request_review'] ?? true) {
                dispatch(function () use ($booking) {
                    $this->notificationService->sendReviewRequest($booking);
                })->delay(now()->addHours(2)); // Через 2 часа после завершения
            }

            // SMS с благодарностью и ссылкой на отзыв
            if ($booking->client_phone && ($options['send_sms'] ?? true)) {
                $this->notificationService->sendCompletionSMS($booking);
            }

            // Email чек об оказанной услуге
            if ($booking->client_email && ($options['send_receipt'] ?? true)) {
                $this->notificationService->sendServiceReceipt($booking);
            }

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
     * Массовое завершение бронирований
     */
    public function bulkComplete(array $bookingIds, User $master, array $options = []): array
    {
        $results = [];
        $totalEarnings = 0;

        foreach ($bookingIds as $bookingId) {
            try {
                $booking = Booking::findOrFail($bookingId);
                $completedBooking = $this->execute($booking, $master, $options);
                
                $earnings = $completedBooking->total_price;
                $totalEarnings += $earnings;
                
                $results[] = [
                    'booking_id' => $bookingId,
                    'success' => true,
                    'earnings' => $earnings,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'booking_id' => $bookingId,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Bulk completion completed', [
            'total_bookings' => count($bookingIds),
            'successful' => count(array_filter($results, fn($r) => $r['success'])),
            'total_earnings' => $totalEarnings,
        ]);

        return [
            'results' => $results,
            'total_earnings' => $totalEarnings,
        ];
    }

    /**
     * Автозавершение просроченных бронирований
     */
    public function autoCompleteOverdue(): array
    {
        $overdueBookings = Booking::where('status', BookingStatus::IN_PROGRESS)
            ->where('end_time', '<', now()->subHours(2))
            ->with(['master'])
            ->get();

        $results = [];

        foreach ($overdueBookings as $booking) {
            try {
                if ($booking->master) {
                    $this->execute($booking, $booking->master, [
                        'auto_complete' => true,
                        'completion_notes' => 'Автоматическое завершение просроченного бронирования',
                        'request_review' => false,
                    ]);
                    
                    $results[] = [
                        'booking_id' => $booking->id,
                        'success' => true,
                    ];
                }
            } catch (\Exception $e) {
                $results[] = [
                    'booking_id' => $booking->id,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Auto-completion of overdue bookings completed', [
            'processed' => count($results),
            'successful' => count(array_filter($results, fn($r) => $r['success'])),
        ]);

        return $results;
    }
}