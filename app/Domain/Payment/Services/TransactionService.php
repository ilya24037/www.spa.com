<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Transaction;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Models\Subscription;
use App\Domain\Payment\Repositories\TransactionRepository;
use App\Domain\Payment\Enums\TransactionType;
use App\Domain\Payment\Enums\TransactionStatus;
use App\Domain\Payment\Enums\TransactionDirection;
use App\Domain\Payment\DTOs\CreateTransactionDTO;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с транзакциями
 */
class TransactionService
{
    public function __construct(
        private TransactionRepository $repository
    ) {}

    /**
     * Создать транзакцию платежа
     */
    public function createPaymentTransaction(
        Payment $payment,
        TransactionDirection $direction = TransactionDirection::IN
    ): Transaction {
        return $this->createTransaction([
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'type' => TransactionType::PAYMENT,
            'direction' => $direction,
            'amount' => $payment->amount,
            'currency' => $payment->currency ?? 'RUB',
            'description' => "Платеж #{$payment->payment_number}",
            'gateway' => $payment->gateway,
            'gateway_transaction_id' => $payment->external_id,
            'transactionable_type' => Payment::class,
            'transactionable_id' => $payment->id,
        ]);
    }

    /**
     * Создать транзакцию подписки
     */
    public function createSubscriptionTransaction(
        Subscription $subscription,
        float $amount,
        string $description = null
    ): Transaction {
        return $this->createTransaction([
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'type' => TransactionType::SUBSCRIPTION,
            'direction' => TransactionDirection::IN,
            'amount' => $amount,
            'currency' => $subscription->currency ?? 'RUB',
            'description' => $description ?? "Оплата подписки {$subscription->plan_name}",
            'gateway' => $subscription->gateway,
            'transactionable_type' => Subscription::class,
            'transactionable_id' => $subscription->id,
        ]);
    }

    /**
     * Создать транзакцию возврата
     */
    public function createRefundTransaction(
        Payment $payment,
        float $amount,
        string $reason = null
    ): Transaction {
        return $this->createTransaction([
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'type' => TransactionType::REFUND,
            'direction' => TransactionDirection::OUT,
            'amount' => $amount,
            'currency' => $payment->currency ?? 'RUB',
            'description' => "Возврат платежа #{$payment->payment_number}" . ($reason ? ": $reason" : ''),
            'gateway' => $payment->gateway,
            'transactionable_type' => Payment::class,
            'transactionable_id' => $payment->id,
        ]);
    }

    /**
     * Создать транзакцию комиссии
     */
    public function createCommissionTransaction(
        Transaction $parentTransaction,
        float $commissionAmount,
        string $description = null
    ): Transaction {
        return $this->createTransaction([
            'payment_id' => $parentTransaction->payment_id,
            'user_id' => $parentTransaction->user_id,
            'type' => TransactionType::COMMISSION,
            'direction' => TransactionDirection::OUT,
            'amount' => $commissionAmount,
            'currency' => $parentTransaction->currency,
            'description' => $description ?? "Комиссия за транзакцию #{$parentTransaction->transaction_id}",
            'transactionable_type' => Transaction::class,
            'transactionable_id' => $parentTransaction->id,
        ]);
    }

    /**
     * Создать транзакцию перевода
     */
    public function createTransferTransaction(
        User $fromUser,
        User $toUser,
        float $amount,
        string $description,
        string $currency = 'RUB'
    ): array {
        return DB::transaction(function () use ($fromUser, $toUser, $amount, $description, $currency) {
            // Исходящая транзакция
            $outgoing = $this->createTransaction([
                'user_id' => $fromUser->id,
                'type' => TransactionType::TRANSFER,
                'direction' => TransactionDirection::OUT,
                'amount' => $amount,
                'currency' => $currency,
                'description' => "Перевод пользователю #{$toUser->id}: $description",
                'transactionable_type' => User::class,
                'transactionable_id' => $toUser->id,
            ]);

            // Входящая транзакция
            $incoming = $this->createTransaction([
                'user_id' => $toUser->id,
                'type' => TransactionType::TRANSFER,
                'direction' => TransactionDirection::IN,
                'amount' => $amount,
                'currency' => $currency,
                'description' => "Перевод от пользователя #{$fromUser->id}: $description",
                'transactionable_type' => User::class,
                'transactionable_id' => $fromUser->id,
            ]);

            return [$outgoing, $incoming];
        });
    }

    /**
     * Создать транзакцию
     */
    public function createTransaction(array $data): Transaction
    {
        // Проверить лимиты если указан тип
        if (isset($data['type']) && isset($data['user_id']) && isset($data['amount'])) {
            $type = $data['type'] instanceof TransactionType ? $data['type'] : TransactionType::from($data['type']);
            
            if (!$this->repository->checkDailyLimit($data['user_id'], $type, $data['amount'])) {
                throw new \Exception('Превышен дневной лимит для данного типа транзакций');
            }
        }

        // Рассчитать баланс до транзакции
        if (isset($data['user_id']) && isset($data['currency'])) {
            $data['balance_before'] = $this->repository->getUserBalance(
                $data['user_id'], 
                $data['currency']
            );
        }

        // Создать транзакцию
        $transaction = $this->repository->createTransaction($data);

        // Рассчитать баланс после транзакции
        if ($transaction->status === TransactionStatus::SUCCESS) {
            $balanceAfter = $data['balance_before'] ?? 0;
            
            if ($transaction->direction === TransactionDirection::IN) {
                $balanceAfter += $transaction->amount;
            } else {
                $balanceAfter -= $transaction->amount;
            }
            
            $transaction->update(['balance_after' => $balanceAfter]);
        }

        Log::info('Transaction created', [
            'transaction_id' => $transaction->transaction_id,
            'type' => $transaction->type->value,
            'amount' => $transaction->amount,
            'user_id' => $transaction->user_id,
        ]);

        return $transaction;
    }

    /**
     * Обработать успешную транзакцию
     */
    public function processSuccessfulTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            // Обновить статус
            $this->repository->updateStatus($transaction->id, TransactionStatus::SUCCESS, [
                'balance_after' => $this->calculateBalanceAfter($transaction),
            ]);

            // Обработать связанные действия
            $this->processRelatedActions($transaction);
        });
    }

    /**
     * Обработать неудачную транзакцию
     */
    public function processFailedTransaction(Transaction $transaction, string $reason = null): void
    {
        $this->repository->updateStatus($transaction->id, TransactionStatus::FAILED, [
            'notes' => $reason,
        ]);

        Log::warning('Transaction failed', [
            'transaction_id' => $transaction->transaction_id,
            'reason' => $reason,
        ]);
    }

    /**
     * Отменить транзакцию
     */
    public function cancelTransaction(Transaction $transaction): bool
    {
        if (!in_array($transaction->status, [TransactionStatus::PENDING, TransactionStatus::PROCESSING])) {
            return false;
        }

        return $this->repository->updateStatus($transaction->id, TransactionStatus::CANCELLED);
    }

    /**
     * Получить баланс пользователя
     */
    public function getUserBalance(int $userId, string $currency = 'RUB'): float
    {
        return $this->repository->getUserBalance($userId, $currency);
    }

    /**
     * Получить историю транзакций
     */
    public function getUserTransactionHistory(int $userId, array $filters = [], int $perPage = 20)
    {
        return $this->repository->getUserTransactions($userId, $filters, $perPage);
    }

    /**
     * Получить статистику транзакций
     */
    public function getUserStatistics(int $userId, \Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        return $this->repository->getStatistics($userId, $from, $to);
    }

    /**
     * Рассчитать баланс после транзакции
     */
    protected function calculateBalanceAfter(Transaction $transaction): float
    {
        $balanceBefore = $transaction->balance_before ?? 0;
        
        if ($transaction->direction === TransactionDirection::IN) {
            return $balanceBefore + $transaction->amount;
        }
        
        return $balanceBefore - $transaction->amount;
    }

    /**
     * Обработать связанные действия после успешной транзакции
     */
    protected function processRelatedActions(Transaction $transaction): void
    {
        // Обновить связанный платеж
        if ($transaction->payment_id && $transaction->payment) {
            $transaction->payment->update([
                'status' => \App\Enums\PaymentStatus::COMPLETED,
                'confirmed_at' => now(),
            ]);
        }

        // Обновить связанную подписку
        if ($transaction->subscription_id && $transaction->subscription) {
            $transaction->subscription->update([
                'last_payment_id' => $transaction->payment_id,
                'status' => \App\Domain\Payment\Enums\SubscriptionStatus::ACTIVE,
            ]);
        }

        // Дополнительные действия в зависимости от типа
        match($transaction->type) {
            TransactionType::SUBSCRIPTION => $this->handleSubscriptionPayment($transaction),
            TransactionType::REFUND => $this->handleRefund($transaction),
            default => null,
        };
    }

    /**
     * Обработать оплату подписки
     */
    protected function handleSubscriptionPayment(Transaction $transaction): void
    {
        if (!$transaction->subscription) {
            return;
        }

        $subscription = $transaction->subscription;
        
        // Продлить подписку
        if ($subscription->interval && $subscription->interval_count) {
            app(SubscriptionService::class)->renewSubscription($subscription);
        }
    }

    /**
     * Обработать возврат
     */
    protected function handleRefund(Transaction $transaction): void
    {
        // Логика обработки возврата
        Log::info('Refund processed', [
            'transaction_id' => $transaction->transaction_id,
            'amount' => $transaction->amount,
        ]);
    }
}