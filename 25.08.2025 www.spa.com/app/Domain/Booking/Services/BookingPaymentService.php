<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Payment\Services\PaymentGatewayManager;
use App\Domain\User\Models\User;
use App\Domain\Notification\Contracts\NotificationServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Единый сервис для всех платежных операций бронирования
 * Консолидирует: BookingPaymentProcessorService, BookingRefundService, 
 * CancellationFeeService, PricingService
 */
class BookingPaymentService
{
    public function __construct(
        private ?PaymentService $paymentService = null,
        private ?PaymentGatewayManager $gatewayManager = null,
        private ?NotificationServiceInterface $notificationService = null
    ) {}

    /**
     * ==========================================
     * РАСЧЕТ СТОИМОСТИ
     * ==========================================
     */

    /**
     * Рассчитать полную стоимость бронирования
     */
    public function calculateTotalPrice(Booking $booking): float
    {
        $basePrice = $booking->service_price ?? 0;
        $duration = $booking->duration_minutes ?? 60;
        
        // Базовая стоимость с учетом длительности
        $price = $this->calculateDurationPrice($basePrice, $duration);
        
        // Добавляем наценки
        $price += $this->calculateSurcharges($booking);
        
        // Применяем скидки
        $price -= $this->calculateDiscounts($booking, $price);
        
        // Добавляем комиссию платформы
        $price += $this->calculatePlatformFee($price);
        
        return max(0, round($price, 2));
    }

    /**
     * Рассчитать стоимость с учетом длительности
     */
    public function calculateDurationPrice(float $basePrice, int $durationMinutes): float
    {
        $hours = $durationMinutes / 60;
        return $basePrice * $hours;
    }

    /**
     * Рассчитать наценки
     */
    public function calculateSurcharges(Booking $booking): float
    {
        $surcharges = 0;
        
        // Наценка за выезд
        if ($booking->type === 'outcall') {
            $surcharges += $booking->travel_fee ?? 0;
            $surcharges += $booking->delivery_fee ?? 0;
        }
        
        // Наценка за срочность (менее 2 часов до начала)
        $hoursUntilStart = Carbon::now()->diffInHours(
            Carbon::parse($booking->booking_date . ' ' . $booking->start_time), 
            false
        );
        if ($hoursUntilStart < 2 && $hoursUntilStart >= 0) {
            $surcharges += ($booking->service_price ?? 0) * 0.2; // 20% за срочность
        }
        
        // Наценка за ночное время (22:00 - 06:00)
        $startHour = Carbon::parse($booking->start_time)->hour;
        if ($startHour >= 22 || $startHour < 6) {
            $surcharges += ($booking->service_price ?? 0) * 0.15; // 15% за ночное время
        }
        
        return $surcharges;
    }

    /**
     * Рассчитать скидки
     */
    public function calculateDiscounts(Booking $booking, float $baseAmount): float
    {
        $discounts = 0;
        
        // Скидка постоянного клиента
        if ($this->isRegularClient($booking)) {
            $discounts += $baseAmount * 0.1; // 10% для постоянных
        }
        
        // Скидка за предоплату
        if ($booking->prepayment && $booking->prepayment >= $baseAmount * 0.5) {
            $discounts += $baseAmount * 0.05; // 5% за предоплату 50%+
        }
        
        // Промокод или купон
        if ($booking->discount_amount) {
            $discounts += $booking->discount_amount;
        }
        
        return min($discounts, $baseAmount * 0.3); // Максимум 30% скидки
    }

    /**
     * Рассчитать комиссию платформы
     */
    public function calculatePlatformFee(float $amount): float
    {
        return $amount * 0.1; // 10% комиссия платформы
    }

    /**
     * ==========================================
     * ОБРАБОТКА ПЛАТЕЖЕЙ
     * ==========================================
     */

    /**
     * Обработать платеж при завершении бронирования
     */
    public function processPayment(Booking $booking, array $options = []): array
    {
        $remainingAmount = $this->calculateRemainingAmount($booking);
        
        if ($remainingAmount <= 0) {
            $booking->update(['payment_status' => 'paid']);
            return [
                'success' => true,
                'message' => 'Бронирование полностью оплачено',
                'amount' => 0
            ];
        }

        if ($options['auto_charge'] ?? false) {
            return $this->attemptAutoCharge($booking, $remainingAmount);
        } else {
            return $this->sendPaymentLink($booking, $remainingAmount);
        }
    }

    /**
     * Вычислить остаток к доплате
     */
    public function calculateRemainingAmount(Booking $booking): float
    {
        $totalPrice = $booking->total_price ?? 0;
        $paidAmount = $booking->paid_amount ?? 0;
        return max(0, $totalPrice - $paidAmount);
    }

    /**
     * Попытка автоматического списания
     */
    private function attemptAutoCharge(Booking $booking, float $amount): array
    {
        if (!$this->gatewayManager) {
            return [
                'success' => false,
                'message' => 'Автоматическое списание недоступно',
                'amount' => $amount
            ];
        }

        try {
            $result = $this->gatewayManager->charge([
                'user_id' => $booking->user_id,
                'amount' => $amount,
                'description' => "Доплата за бронирование #{$booking->booking_number}",
                'booking_id' => $booking->id
            ]);

            if ($result['success']) {
                $booking->update([
                    'paid_amount' => ($booking->paid_amount ?? 0) + $amount,
                    'payment_status' => 'paid',
                    'payment_id' => $result['payment_id'] ?? null
                ]);

                Log::info('Auto charge successful', [
                    'booking_id' => $booking->id,
                    'amount' => $amount
                ]);

                return [
                    'success' => true,
                    'message' => 'Оплата успешно проведена',
                    'amount' => $amount,
                    'payment_id' => $result['payment_id'] ?? null
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Не удалось провести оплату',
                'amount' => $amount
            ];

        } catch (\Exception $e) {
            Log::error('Auto charge failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при проведении платежа',
                'amount' => $amount
            ];
        }
    }

    /**
     * Отправить ссылку на оплату
     */
    private function sendPaymentLink(Booking $booking, float $amount): array
    {
        if (!$this->gatewayManager) {
            return [
                'success' => false,
                'message' => 'Сервис оплаты недоступен',
                'amount' => $amount
            ];
        }

        try {
            $paymentLink = $this->gatewayManager->createPaymentLink([
                'amount' => $amount,
                'description' => "Доплата за бронирование #{$booking->booking_number}",
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id
            ]);

            if ($this->notificationService) {
                $this->notificationService->send(
                    $booking->user,
                    'payment.link',
                    [
                        'amount' => $amount,
                        'link' => $paymentLink,
                        'booking_number' => $booking->booking_number
                    ]
                );
            }

            return [
                'success' => true,
                'message' => 'Ссылка на оплату отправлена',
                'amount' => $amount,
                'payment_link' => $paymentLink
            ];

        } catch (\Exception $e) {
            Log::error('Failed to create payment link', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Не удалось создать ссылку на оплату',
                'amount' => $amount
            ];
        }
    }

    /**
     * ==========================================
     * ВОЗВРАТЫ И ШТРАФЫ
     * ==========================================
     */

    /**
     * Обработать возврат средств при отмене
     */
    public function processRefund(Booking $booking, User $cancelledBy): array
    {
        $paidAmount = $booking->paid_amount ?? 0;
        
        // Проверяем, была ли оплата
        if ($paidAmount <= 0) {
            return [
                'success' => true,
                'refund_amount' => 0,
                'fee_amount' => 0,
                'message' => 'Возврат не требуется - бронирование не было оплачено'
            ];
        }

        // Рассчитываем штраф за отмену
        $cancellationFee = $this->calculateCancellationFee($booking, $cancelledBy);
        
        // Рассчитываем сумму возврата
        $refundAmount = max(0, $paidAmount - $cancellationFee['fee_amount']);
        
        if ($refundAmount <= 0) {
            return [
                'success' => true,
                'refund_amount' => 0,
                'fee_amount' => $cancellationFee['fee_amount'],
                'message' => 'Возврат не производится из-за штрафа за отмену'
            ];
        }

        // Выполняем возврат
        return $this->executeRefund($booking, $refundAmount, $cancellationFee);
    }

    /**
     * Рассчитать штраф за отмену
     */
    public function calculateCancellationFee(Booking $booking, User $user): array
    {
        $baseAmount = $booking->total_price ?? 0;
        
        if ($baseAmount <= 0) {
            return [
                'fee_amount' => 0,
                'fee_percent' => 0,
                'description' => 'Штраф не взимается - бронирование бесплатное'
            ];
        }

        $startDateTime = Carbon::parse($booking->booking_date . ' ' . $booking->start_time);
        $hoursUntilStart = Carbon::now()->diffInHours($startDateTime, false);
        $isClient = $booking->user_id === $user->id;

        // Расчет процента штрафа
        $feePercent = $this->calculateFeePercent($hoursUntilStart, $isClient, $booking);
        $feeAmount = ($baseAmount * $feePercent) / 100;

        return [
            'fee_amount' => round($feeAmount, 2),
            'fee_percent' => $feePercent,
            'hours_until_start' => $hoursUntilStart,
            'base_amount' => $baseAmount,
            'is_client_cancellation' => $isClient,
            'description' => $this->getFeeDescription($feePercent, $hoursUntilStart, $isClient)
        ];
    }

    /**
     * Рассчитать процент штрафа
     */
    private function calculateFeePercent(float $hoursUntilStart, bool $isClient, Booking $booking): float
    {
        // Без штрафа при отмене более чем за сутки
        if ($hoursUntilStart >= 24) {
            return 0;
        }

        // Штрафы для клиента
        if ($isClient) {
            if ($hoursUntilStart < 0) {
                return 100; // Полный штраф после начала
            } elseif ($hoursUntilStart < 2) {
                return 50; // 50% менее чем за 2 часа
            } elseif ($hoursUntilStart < 6) {
                return 30; // 30% от 2 до 6 часов
            } elseif ($hoursUntilStart < 12) {
                return 20; // 20% от 6 до 12 часов
            } else {
                return 10; // 10% от 12 до 24 часов
            }
        }

        // Штрафы для мастера (более строгие)
        if ($hoursUntilStart < 0) {
            return 100; // Полный штраф после начала
        } elseif ($hoursUntilStart < 6) {
            return 50; // 50% менее чем за 6 часов
        } elseif ($hoursUntilStart < 12) {
            return 30; // 30% от 6 до 12 часов
        } else {
            return 20; // 20% от 12 до 24 часов
        }
    }

    /**
     * Получить описание штрафа
     */
    private function getFeeDescription(float $feePercent, float $hoursUntilStart, bool $isClient): string
    {
        if ($feePercent == 0) {
            return 'Штраф не взимается при отмене более чем за 24 часа';
        }

        if ($hoursUntilStart < 0) {
            return 'Полный штраф - бронирование уже началось';
        }

        $who = $isClient ? 'клиентом' : 'мастером';
        $hours = round($hoursUntilStart);
        
        return "Штраф {$feePercent}% при отмене {$who} за {$hours} час(ов) до начала";
    }

    /**
     * Выполнить возврат средств
     */
    private function executeRefund(Booking $booking, float $refundAmount, array $cancellationFee): array
    {
        if (!$this->paymentService) {
            return [
                'success' => false,
                'refund_amount' => $refundAmount,
                'fee_amount' => $cancellationFee['fee_amount'],
                'message' => 'Автоматический возврат недоступен. Обратитесь в поддержку.'
            ];
        }

        try {
            $result = $this->paymentService->refund([
                'payment_id' => $booking->payment_id,
                'amount' => $refundAmount,
                'reason' => 'Отмена бронирования',
                'booking_id' => $booking->id
            ]);

            if ($result['success']) {
                $booking->update([
                    'refund_amount' => $refundAmount,
                    'cancellation_fee' => $cancellationFee['fee_amount'],
                    'payment_status' => 'refunded'
                ]);

                Log::info('Refund processed', [
                    'booking_id' => $booking->id,
                    'refund_amount' => $refundAmount,
                    'fee_amount' => $cancellationFee['fee_amount']
                ]);

                return [
                    'success' => true,
                    'refund_amount' => $refundAmount,
                    'fee_amount' => $cancellationFee['fee_amount'],
                    'message' => "Возврат {$refundAmount} руб. будет произведен в течение 3-5 рабочих дней"
                ];
            }

            return [
                'success' => false,
                'refund_amount' => $refundAmount,
                'fee_amount' => $cancellationFee['fee_amount'],
                'message' => $result['message'] ?? 'Не удалось выполнить возврат'
            ];

        } catch (\Exception $e) {
            Log::error('Refund failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'refund_amount' => $refundAmount,
                'fee_amount' => $cancellationFee['fee_amount'],
                'message' => 'Ошибка при выполнении возврата. Обратитесь в поддержку.'
            ];
        }
    }

    /**
     * ==========================================
     * ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
     * ==========================================
     */

    /**
     * Проверить, является ли клиент постоянным
     */
    private function isRegularClient(Booking $booking): bool
    {
        if (!$booking->user_id) {
            return false;
        }

        // Считаем завершенные бронирования за последние 3 месяца
        $completedBookings = Booking::where('user_id', $booking->user_id)
            ->where('master_id', $booking->master_id)
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subMonths(3))
            ->count();

        return $completedBookings >= 3; // 3+ бронирования = постоянный клиент
    }

    /**
     * Обработать предоплату
     */
    public function processPrepayment(Booking $booking, float $amount): array
    {
        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Сумма предоплаты должна быть больше 0'
            ];
        }

        $maxPrepayment = $booking->total_price * 0.5; // Максимум 50% предоплата
        $amount = min($amount, $maxPrepayment);

        try {
            if ($this->gatewayManager) {
                $result = $this->gatewayManager->charge([
                    'user_id' => $booking->user_id,
                    'amount' => $amount,
                    'description' => "Предоплата за бронирование #{$booking->booking_number}",
                    'booking_id' => $booking->id
                ]);

                if ($result['success']) {
                    $booking->update([
                        'prepayment' => $amount,
                        'paid_amount' => $amount,
                        'payment_status' => 'partially_paid'
                    ]);

                    return [
                        'success' => true,
                        'amount' => $amount,
                        'message' => "Предоплата {$amount} руб. успешно проведена"
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Не удалось провести предоплату'
            ];

        } catch (\Exception $e) {
            Log::error('Prepayment failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при проведении предоплаты'
            ];
        }
    }
}