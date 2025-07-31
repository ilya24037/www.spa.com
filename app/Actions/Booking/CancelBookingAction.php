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
 * Action для отмены бронирования
 * Инкапсулирует всю логику отмены с возвратами и уведомлениями
 */
class CancelBookingAction
{
    public function __construct(
        private NotificationService $notificationService,
        private PaymentGatewayService $paymentService
    ) {}

    /**
     * Выполнить отмену бронирования
     */
    public function execute(Booking $booking, User $user, string $reason, bool $forceCancel = false): Booking
    {
        Log::info('Cancelling booking', [
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'cancelled_by' => $user->id,
            'reason' => $reason,
            'force' => $forceCancel,
        ]);

        // Валидация прав и возможности отмены
        $this->validateCancellation($booking, $user, $forceCancel);

        // Расчет штрафа
        $cancellationFee = $this->calculateCancellationFee($booking, $user);

        // Выполнение отмены в транзакции
        $result = DB::transaction(function () use ($booking, $user, $reason, $cancellationFee) {
            return $this->performCancellation($booking, $user, $reason, $cancellationFee);
        });

        // Отправка уведомлений
        $this->sendCancellationNotifications($booking, $user, $cancellationFee);

        Log::info('Booking cancelled successfully', [
            'booking_id' => $booking->id,
            'cancellation_fee' => $cancellationFee,
        ]);

        return $booking->fresh();
    }

    /**
     * Валидация возможности отмены
     */
    protected function validateCancellation(Booking $booking, User $user, bool $forceCancel): void
    {
        // Проверяем права пользователя
        $this->validateUserPermissions($booking, $user);

        // Проверяем статус бронирования
        if (!$forceCancel) {
            $this->validateBookingStatus($booking);
            $this->validateCancellationTime($booking, $user);
        }
    }

    /**
     * Проверка прав пользователя
     */
    protected function validateUserPermissions(Booking $booking, User $user): void
    {
        $isClient = $booking->client_id === $user->id;
        $isMaster = $booking->master_id === $user->id || 
                   ($booking->master_profile_id && $user->masterProfile && 
                    $booking->master_profile_id === $user->masterProfile->id);
        $isAdmin = $user->hasRole('admin') || $user->hasRole('moderator');

        if (!$isClient && !$isMaster && !$isAdmin) {
            throw new \Exception('У вас нет прав для отмены этого бронирования');
        }
    }

    /**
     * Проверка статуса бронирования
     */
    protected function validateBookingStatus(Booking $booking): void
    {
        if ($booking->status instanceof BookingStatus) {
            if (!$booking->status->canBeCancelled()) {
                throw new \Exception(
                    "Нельзя отменить бронирование в статусе: {$booking->status->getLabel()}"
                );
            }
        } else {
            // Совместимость со старым кодом
            if (!in_array($booking->status, [
                Booking::STATUS_PENDING, 
                Booking::STATUS_CONFIRMED
            ])) {
                throw new \Exception('Бронирование нельзя отменить в текущем статусе');
            }
        }
    }

    /**
     * Проверка времени отмены
     */
    protected function validateCancellationTime(Booking $booking, User $user): void
    {
        $bookingStart = $booking->start_time;
        $hoursUntilStart = now()->diffInHours($bookingStart, false);

        // Для клиентов - более строгие ограничения
        if ($booking->client_id === $user->id) {
            $minHours = $booking->type ? $booking->type->getMinAdvanceHours() : 2;
            
            if ($hoursUntilStart < $minHours) {
                throw new \Exception(
                    "Отмена возможна не позднее чем за {$minHours} часов до начала услуги"
                );
            }
        }

        // Для мастеров - можно отменить позже, но с увеличенным штрафом
        if ($booking->master_id === $user->id && $hoursUntilStart < 1) {
            throw new \Exception('Мастер не может отменить бронирование менее чем за час до начала');
        }
    }

    /**
     * Расчет штрафа за отмену
     */
    protected function calculateCancellationFee(Booking $booking, User $user): float
    {
        $baseAmount = $booking->total_price ?? 0;
        
        if ($baseAmount <= 0) {
            return 0;
        }

        $hoursUntilStart = now()->diffInHours($booking->start_time, false);
        $isClient = $booking->client_id === $user->id;

        // Без штрафа при отмене за день
        if ($hoursUntilStart >= 24) {
            return 0;
        }

        // Базовый процент штрафа по типу бронирования
        $feePercent = $booking->type ? $booking->type->getCancellationFeePercent() : 20;

        // Увеличиваем штраф при поздней отмене
        if ($hoursUntilStart < 4) {
            $feePercent = min(100, $feePercent * 2); // Удваиваем штраф
        }

        // Для мастеров штраф больше (они подводят клиента)
        if (!$isClient) {
            $feePercent = min(100, $feePercent * 1.5);
        }

        return ($baseAmount * $feePercent) / 100;
    }

    /**
     * Выполнение отмены
     */
    protected function performCancellation(
        Booking $booking, 
        User $user, 
        string $reason, 
        float $cancellationFee
    ): array {
        $isClient = $booking->client_id === $user->id;
        
        // Определяем новый статус
        $newStatus = $isClient ? BookingStatus::CANCELLED_BY_CLIENT : BookingStatus::CANCELLED_BY_MASTER;
        
        // Обновляем бронирование
        if ($booking->status instanceof BookingStatus) {
            $booking->status = $newStatus;
        } else {
            $booking->status = Booking::STATUS_CANCELLED;
        }
        
        $booking->cancellation_reason = $reason;
        $booking->cancelled_at = now();
        $booking->cancelled_by = $user->id;
        
        // Добавляем информацию о штрафе в метаданные
        $metadata = $booking->metadata ?? [];
        $metadata['cancellation'] = [
            'fee_amount' => $cancellationFee,
            'fee_percent' => $cancellationFee > 0 ? round(($cancellationFee / $booking->total_price) * 100, 2) : 0,
            'hours_before_start' => now()->diffInHours($booking->start_time, false),
            'cancelled_by_role' => $isClient ? 'client' : 'master',
        ];
        $booking->metadata = $metadata;
        $booking->save();

        // Освобождаем временные слоты
        $this->releaseBookingSlots($booking);

        // Обрабатываем возврат средств
        $refundResult = $this->processRefund($booking, $cancellationFee);

        return [
            'booking' => $booking,
            'cancellation_fee' => $cancellationFee,
            'refund_result' => $refundResult,
        ];
    }

    /**
     * Освобождение временных слотов
     */
    protected function releaseBookingSlots(Booking $booking): void
    {
        // Удаляем связанные слоты
        $booking->slots()->delete();

        // Если есть дополнительные услуги, помечаем их как отмененные
        $booking->bookingServices()->update([
            'is_completed' => false,
            'notes' => 'Отменено в связи с отменой основного бронирования',
        ]);
    }

    /**
     * Обработка возврата средств
     */
    protected function processRefund(Booking $booking, float $cancellationFee): array
    {
        $paidAmount = $booking->paid_amount ?? 0;
        
        if ($paidAmount <= 0) {
            return [
                'type' => 'no_payment',
                'message' => 'Возврат не требуется - оплата не производилась',
            ];
        }

        $refundAmount = max(0, $paidAmount - $cancellationFee);

        if ($refundAmount <= 0) {
            return [
                'type' => 'no_refund',
                'message' => 'Возврат не производится - весь платеж удержан в качестве штрафа',
                'cancellation_fee' => $cancellationFee,
            ];
        }

        try {
            // Попытка автоматического возврата через платежную систему
            $refundResult = $this->paymentService->refund($booking->payment, $refundAmount);
            
            if ($refundResult['success']) {
                return [
                    'type' => 'automatic_refund',
                    'amount' => $refundAmount,
                    'fee' => $cancellationFee,
                    'transaction_id' => $refundResult['transaction_id'],
                    'message' => "Возврат {$refundAmount} руб. будет зачислен в течение 3-5 рабочих дней",
                ];
            } else {
                return [
                    'type' => 'manual_refund_required',
                    'amount' => $refundAmount,
                    'fee' => $cancellationFee,
                    'message' => 'Возврат будет обработан вручную в течение 24 часов',
                    'error' => $refundResult['error'] ?? 'Неизвестная ошибка',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Refund processing failed', [
                'booking_id' => $booking->id,
                'refund_amount' => $refundAmount,
                'error' => $e->getMessage(),
            ]);

            return [
                'type' => 'manual_refund_required',
                'amount' => $refundAmount,
                'fee' => $cancellationFee,
                'message' => 'Возврат будет обработан вручную в течение 24 часов',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Отправка уведомлений об отмене
     */
    protected function sendCancellationNotifications(Booking $booking, User $cancelledBy, float $fee): void
    {
        try {
            $isClient = $booking->client_id === $cancelledBy->id;

            if ($isClient) {
                // Уведомляем мастера об отмене клиентом
                $this->notificationService->sendBookingCancelledByClient($booking, $fee);
            } else {
                // Уведомляем клиента об отмене мастером  
                $this->notificationService->sendBookingCancelledByMaster($booking, $fee);
            }

            // Внутреннее уведомление администрации при высоком штрафе
            if ($fee > 1000) {
                $this->notificationService->sendHighFeeCancellationAlert($booking, $cancelledBy, $fee);
            }

            Log::info('Cancellation notifications sent', [
                'booking_id' => $booking->id,
                'fee' => $fee,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send cancellation notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Массовая отмена бронирований (например, при недоступности мастера)
     */
    public function bulkCancel(array $bookingIds, User $user, string $reason): array
    {
        $results = [];
        $totalFees = 0;

        foreach ($bookingIds as $bookingId) {
            try {
                $booking = Booking::findOrFail($bookingId);
                $result = $this->execute($booking, $user, $reason, true); // Принудительная отмена
                
                $fee = $result->metadata['cancellation']['fee_amount'] ?? 0;
                $totalFees += $fee;
                
                $results[] = [
                    'booking_id' => $bookingId,
                    'success' => true,
                    'fee' => $fee,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'booking_id' => $bookingId,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Bulk cancellation completed', [
            'total_bookings' => count($bookingIds),
            'successful' => count(array_filter($results, fn($r) => $r['success'])),
            'total_fees' => $totalFees,
        ]);

        return [
            'results' => $results,
            'total_fees' => $totalFees,
        ];
    }
}