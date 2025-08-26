<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Transaction;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Models\Subscription;
use App\Domain\Payment\Repositories\TransactionRepository;
use App\Domain\Payment\Enums\TransactionType;
use App\Domain\Payment\Enums\TransactionStatus;
use App\Domain\Payment\Enums\TransactionDirection;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\DTOs\CreateTransactionDTO;
use App\Domain\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
        ?string $description = null
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
        ?string $reason = null
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
        ?string $description = null
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
    public function processFailedTransaction(Transaction $transaction, ?string $reason = null): void
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

    // ========== ВАЛИДАЦИЯ ТРАНЗАКЦИЙ ==========

    /**
     * Валидировать создание транзакции
     */
    public function validateTransaction(array $data): void
    {
        $validator = Validator::make($data, [
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string',
            'direction' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'sometimes|string|size:3',
            'description' => 'sometimes|string|max:500'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Дополнительная валидация
        $this->validateTransactionLimits($data);
        $this->validateUserStatus($data['user_id']);
    }

    /**
     * Валидировать лимиты транзакций
     */
    private function validateTransactionLimits(array $data): void
    {
        $type = $data['type'] instanceof TransactionType ? $data['type'] : TransactionType::from($data['type']);
        $userId = $data['user_id'];
        $amount = $data['amount'];

        // Дневные лимиты
        $dailyAmount = $this->repository->getUserDailyAmount($userId, $type);
        $dailyLimit = $this->getDailyLimit($type);
        
        if ($dailyAmount + $amount > $dailyLimit) {
            throw new \InvalidArgumentException("Превышен дневной лимит для типа транзакций {$type->value}");
        }

        // Лимиты на одну транзакцию
        $singleLimit = $this->getSingleTransactionLimit($type);
        if ($amount > $singleLimit) {
            throw new \InvalidArgumentException("Превышен лимит на одну транзакцию для типа {$type->value}");
        }
    }

    /**
     * Валидировать статус пользователя
     */
    private function validateUserStatus(int $userId): void
    {
        $user = User::find($userId);
        
        if (!$user) {
            throw new \InvalidArgumentException('Пользователь не найден');
        }

        if ($user->is_blocked ?? false) {
            throw new \InvalidArgumentException('Пользователь заблокирован');
        }

        if (!($user->is_verified ?? true)) {
            throw new \InvalidArgumentException('Пользователь не верифицирован');
        }
    }

    /**
     * Получить дневной лимит для типа транзакции
     */
    private function getDailyLimit(TransactionType $type): float
    {
        return match($type) {
            TransactionType::PAYMENT => 500000,
            TransactionType::REFUND => 100000,
            TransactionType::TRANSFER => 200000,
            TransactionType::COMMISSION => 50000,
            TransactionType::SUBSCRIPTION => 100000,
            default => 10000
        };
    }

    /**
     * Получить лимит на одну транзакцию
     */
    private function getSingleTransactionLimit(TransactionType $type): float
    {
        return match($type) {
            TransactionType::PAYMENT => 100000,
            TransactionType::REFUND => 100000,
            TransactionType::TRANSFER => 50000,
            TransactionType::COMMISSION => 10000,
            TransactionType::SUBSCRIPTION => 50000,
            default => 5000
        };
    }

    // ========== ПОИСК И ФИЛЬТРАЦИЯ ТРАНЗАКЦИЙ ==========

    /**
     * Поиск транзакций с расширенными фильтрами
     */
    public function searchTransactions(array $criteria): Collection
    {
        $query = Transaction::query();

        // Фильтр по пользователю
        if (isset($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }

        // Фильтр по типу
        if (isset($criteria['type'])) {
            $query->where('type', $criteria['type']);
        }

        // Фильтр по статусу
        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        // Фильтр по направлению
        if (isset($criteria['direction'])) {
            $query->where('direction', $criteria['direction']);
        }

        // Фильтр по сумме
        if (isset($criteria['amount_from'])) {
            $query->where('amount', '>=', $criteria['amount_from']);
        }
        if (isset($criteria['amount_to'])) {
            $query->where('amount', '<=', $criteria['amount_to']);
        }

        // Фильтр по датам
        if (isset($criteria['date_from'])) {
            $query->where('created_at', '>=', $criteria['date_from']);
        }
        if (isset($criteria['date_to'])) {
            $query->where('created_at', '<=', $criteria['date_to']);
        }

        // Поиск по описанию
        if (isset($criteria['search'])) {
            $query->where('description', 'like', '%' . $criteria['search'] . '%');
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Получить транзакции за период
     */
    public function getTransactionsForPeriod(Carbon $from, Carbon $to, array $filters = []): Collection
    {
        return $this->searchTransactions(array_merge($filters, [
            'date_from' => $from,
            'date_to' => $to
        ]));
    }

    /**
     * Получить подозрительные транзакции
     */
    public function getSuspiciousTransactions(): Collection
    {
        return Transaction::where(function ($query) {
            $query->where('amount', '>', 100000) // Крупные суммы
                  ->orWhere('created_at', '>', now()->subMinutes(5)->endOfMinute()) // Частые транзакции
                  ->orWhere('status', TransactionStatus::FAILED);
        })->orderBy('created_at', 'desc')->get();
    }

    // ========== ОТЧЁТНОСТЬ И АНАЛИТИКА ==========

    /**
     * Получить детальную статистику транзакций
     */
    public function getDetailedStatistics(int $userId, Carbon $from, Carbon $to): array
    {
        $transactions = $this->getTransactionsForPeriod($from, $to, ['user_id' => $userId]);

        $stats = [
            'total_count' => $transactions->count(),
            'total_amount' => $transactions->sum('amount'),
            'average_amount' => $transactions->avg('amount') ?? 0,
            'by_type' => [],
            'by_status' => [],
            'by_direction' => [],
            'by_currency' => [],
            'daily_amounts' => [],
            'success_rate' => 0
        ];

        // Группировка по типам
        foreach ($transactions->groupBy('type') as $type => $typeTransactions) {
            $stats['by_type'][$type] = [
                'count' => $typeTransactions->count(),
                'amount' => $typeTransactions->sum('amount'),
                'average' => $typeTransactions->avg('amount')
            ];
        }

        // Группировка по статусам
        foreach ($transactions->groupBy('status') as $status => $statusTransactions) {
            $stats['by_status'][$status] = [
                'count' => $statusTransactions->count(),
                'amount' => $statusTransactions->sum('amount')
            ];
        }

        // Группировка по направлениям
        foreach ($transactions->groupBy('direction') as $direction => $directionTransactions) {
            $stats['by_direction'][$direction] = [
                'count' => $directionTransactions->count(),
                'amount' => $directionTransactions->sum('amount')
            ];
        }

        // Успешность транзакций
        $successCount = $transactions->where('status', TransactionStatus::SUCCESS)->count();
        $stats['success_rate'] = $transactions->count() > 0 ? 
            round($successCount / $transactions->count() * 100, 2) : 0;

        return $stats;
    }

    /**
     * Получить топ пользователей по транзакциям
     */
    public function getTopUsersByTransactions(Carbon $from, Carbon $to, int $limit = 10): Collection
    {
        return Transaction::select('user_id', 
            DB::raw('COUNT(*) as transactions_count'),
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('AVG(amount) as average_amount'))
            ->whereBetween('created_at', [$from, $to])
            ->where('status', TransactionStatus::SUCCESS)
            ->groupBy('user_id')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->with('user:id,name,email')
            ->get();
    }

    /**
     * Экспорт транзакций в CSV
     */
    public function exportTransactions(array $criteria): string
    {
        $transactions = $this->searchTransactions($criteria);
        
        $csv = "ID,User ID,Type,Direction,Amount,Currency,Status,Description,Created At\n";
        
        foreach ($transactions as $transaction) {
            $csv .= implode(',', [
                $transaction->id,
                $transaction->user_id,
                $transaction->type->value,
                $transaction->direction->value,
                $transaction->amount,
                $transaction->currency,
                $transaction->status->value,
                '"' . str_replace('"', '""', $transaction->description ?? '') . '"',
                $transaction->created_at->format('Y-m-d H:i:s')
            ]) . "\n";
        }
        
        return $csv;
    }

    // ========== СВЕРКА И АУДИТ ==========

    /**
     * Сверить транзакции с внешними данными
     */
    public function reconcileTransactions(Carbon $date): array
    {
        $transactions = Transaction::whereDate('created_at', $date)
            ->where('status', TransactionStatus::SUCCESS)
            ->get();

        $result = [
            'processed' => 0,
            'matched' => 0,
            'unmatched' => 0,
            'discrepancies' => []
        ];

        foreach ($transactions as $transaction) {
            $result['processed']++;

            // Здесь была бы логика сверки с внешними системами
            // Пока симулируем результат
            if ($this->verifyTransactionWithGateway($transaction)) {
                $result['matched']++;
            } else {
                $result['unmatched']++;
                $result['discrepancies'][] = [
                    'transaction_id' => $transaction->id,
                    'issue' => 'Gateway data mismatch'
                ];
            }
        }

        return $result;
    }

    /**
     * Проверить транзакцию с платёжным шлюзом
     */
    private function verifyTransactionWithGateway(Transaction $transaction): bool
    {
        // Симуляция проверки - в реальности здесь был бы вызов API шлюза
        return rand(0, 100) > 5; // 95% успешных проверок
    }

    /**
     * Создать аудит лог для транзакции
     */
    public function auditTransaction(Transaction $transaction, string $action, array $data = []): void
    {
        Log::channel('audit')->info('Transaction audit', [
            'transaction_id' => $transaction->id,
            'action' => $action,
            'user_id' => $transaction->user_id,
            'amount' => $transaction->amount,
            'status' => $transaction->status->value,
            'additional_data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }

    // ========== ИНТЕГРАЦИЯ С ДРУГИМИ СЕРВИСАМИ ==========

    /**
     * Создать транзакцию с полной интеграцией
     */
    public function createTransactionWithIntegration(array $data): Transaction
    {
        // Валидация
        $this->validateTransaction($data);

        // Создание транзакции
        $transaction = $this->createTransaction($data);

        // Аудит
        $this->auditTransaction($transaction, 'created', $data);

        // Уведомления (если требуется)
        if ($transaction->amount > 50000) {
            $this->notifyHighValueTransaction($transaction);
        }

        return $transaction;
    }

    /**
     * Уведомить о крупной транзакции
     */
    private function notifyHighValueTransaction(Transaction $transaction): void
    {
        Log::warning('High value transaction created', [
            'transaction_id' => $transaction->id,
            'amount' => $transaction->amount,
            'user_id' => $transaction->user_id
        ]);
    }

    /**
     * Получить баланс с детализацией
     */
    public function getDetailedBalance(int $userId, string $currency = 'RUB'): array
    {
        $balance = $this->getUserBalance($userId, $currency);
        
        $pendingIn = Transaction::where('user_id', $userId)
            ->where('currency', $currency)
            ->where('direction', TransactionDirection::IN)
            ->where('status', TransactionStatus::PENDING)
            ->sum('amount');

        $pendingOut = Transaction::where('user_id', $userId)
            ->where('currency', $currency)
            ->where('direction', TransactionDirection::OUT)
            ->where('status', TransactionStatus::PENDING)
            ->sum('amount');

        return [
            'available_balance' => $balance,
            'pending_incoming' => $pendingIn,
            'pending_outgoing' => $pendingOut,
            'effective_balance' => $balance + $pendingIn - $pendingOut,
            'currency' => $currency,
            'last_updated' => now()
        ];
    }
}