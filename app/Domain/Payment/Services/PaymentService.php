<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Services\PaymentProcessorService;
use App\Domain\Payment\Repositories\PaymentRepository;
use App\Domain\Payment\DTOs\CreatePaymentDTO;
use App\Domain\Payment\DTOs\RefundPaymentDTO;
use App\Domain\User\Models\User;
use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Enums\PaymentMethod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Упрощенный сервис платежей - делегирует сложную логику в PaymentProcessorService
 */
class PaymentService
{
    protected PaymentRepository $paymentRepository;
    protected PaymentProcessorService $paymentProcessor;

    public function __construct(
        PaymentRepository $paymentRepository,
        PaymentProcessorService $paymentProcessor
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->paymentProcessor = $paymentProcessor;
    }

    /**
     * Создать платёж
     */
    public function createPayment(CreatePaymentDTO $dto): Payment
    {
        return $this->paymentProcessor->processPayment($dto);
    }

    /**
     * Создать платёж для бронирования
     */
    public function createPaymentForBooking(Booking $booking, PaymentMethod $method): Payment
    {
        $dto = new CreatePaymentDTO([
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'amount' => $booking->total_price,
            'payment_method' => $method,
            'description' => "Оплата за услугу: {$booking->service_name}",
            'metadata' => [
                'booking_id' => $booking->id,
                'master_id' => $booking->master_id
            ]
        ]);

        return $this->createPayment($dto);
    }

    /**
     * Вернуть платёж
     */
    public function refundPayment(Payment $payment, ?float $amount = null, ?string $reason = null): Payment
    {
        $dto = new RefundPaymentDTO([
            'amount' => $amount ?? $payment->amount,
            'reason' => $reason ?? 'Возврат по запросу'
        ]);

        return $this->paymentProcessor->refundPayment($payment, $dto);
    }

    /**
     * Получить платёж по ID
     */
    public function findById(int $id): ?Payment
    {
        return $this->paymentRepository->findById($id);
    }

    /**
     * Получить платежи пользователя
     */
    public function getUserPayments(User $user, int $limit = 50): Collection
    {
        return $this->paymentRepository->findByUser($user->id, $limit);
    }

    /**
     * Получить платежи по бронированию
     */
    public function getBookingPayments(Booking $booking): Collection
    {
        return $this->paymentRepository->findByBooking($booking->id);
    }

    /**
     * Получить успешные платежи
     */
    public function getSuccessfulPayments(int $limit = 100): Collection
    {
        return $this->paymentRepository->findByStatus(PaymentStatus::COMPLETED, $limit);
    }

    /**
     * Получить неудачные платежи
     */
    public function getFailedPayments(int $limit = 100): Collection
    {
        return $this->paymentRepository->findByStatus(PaymentStatus::FAILED, $limit);
    }

    /**
     * Обработать webhook
     */
    public function handleWebhook(PaymentMethod $method, array $data): bool
    {
        return $this->paymentProcessor->handleWebhook($method, $data);
    }

    /**
     * Проверить статус платежа
     */
    public function checkPaymentStatus(Payment $payment): Payment
    {
        return $this->paymentProcessor->checkPaymentStatus($payment);
    }

    /**
     * Получить статистику платежей
     */
    public function getPaymentStats(User $user = null): array
    {
        if ($user) {
            return $this->paymentRepository->getUserStats($user->id);
        }

        return $this->paymentRepository->getGlobalStats();
    }

    /**
     * Получить платежи за период
     */
    public function getPaymentsByPeriod(
        \DateTime $from,
        \DateTime $to,
        PaymentStatus $status = null
    ): Collection {
        return $this->paymentRepository->findByPeriod($from, $to, $status);
    }

    /**
     * Поиск платежей
     */
    public function searchPayments(array $criteria): Collection
    {
        return $this->paymentRepository->search($criteria);
    }

    /**
     * Получить доступные методы платежа
     */
    public function getAvailablePaymentMethods(): array
    {
        return $this->paymentProcessor->getAvailablePaymentMethods();
    }

    /**
     * Проверить доступность метода платежа
     */
    public function isPaymentMethodAvailable(PaymentMethod $method): bool
    {
        return $this->paymentProcessor->isGatewayAvailable($method);
    }

    /**
     * Отменить платёж
     */
    public function cancelPayment(Payment $payment, string $reason = null): Payment
    {
        if (!in_array($payment->status, [PaymentStatus::PENDING, PaymentStatus::PROCESSING])) {
            throw new \InvalidArgumentException('Можно отменить только ожидающие или обрабатываемые платежи');
        }

        $payment = $this->paymentRepository->update($payment, [
            'status' => PaymentStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason
        ]);

        Log::info('Payment cancelled', [
            'payment_id' => $payment->id,
            'reason' => $reason
        ]);

        return $payment;
    }

    /**
     * Повторить неудачный платёж
     */
    public function retryPayment(Payment $payment): Payment
    {
        if ($payment->status !== PaymentStatus::FAILED) {
            throw new \InvalidArgumentException('Можно повторить только неудачные платежи');
        }

        $dto = new CreatePaymentDTO([
            'user_id' => $payment->user_id,
            'booking_id' => $payment->booking_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payment_method' => $payment->payment_method,
            'description' => $payment->description,
            'metadata' => array_merge($payment->metadata ?? [], ['retry_of' => $payment->id])
        ]);

        return $this->createPayment($dto);
    }

    /**
     * Получить общую сумму платежей пользователя
     */
    public function getUserTotalPaid(User $user): float
    {
        return $this->paymentRepository->getUserTotalPaid($user->id);
    }

    /**
     * Получить среднюю сумму платежа
     */
    public function getAveragePaymentAmount(): float
    {
        return $this->paymentRepository->getAverageAmount();
    }

    /**
     * Экспорт платежей
     */
    public function exportPayments(array $criteria = []): Collection
    {
        return $this->paymentRepository->export($criteria);
    }
}