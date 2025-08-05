<?php

namespace App\Domain\Payment\Repositories;

use App\Domain\Payment\Models\Transaction;
use App\Domain\Payment\Enums\TransactionType;
use App\Domain\Payment\Enums\TransactionStatus;
use App\Domain\Payment\Enums\TransactionDirection;
use App\Support\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

/**
 * Репозиторий для работы с транзакциями
 */
class TransactionRepository extends BaseRepository
{
    /**
     * Получить модель репозитория
     */
    public function model(): string
    {
        return Transaction::class;
    }

    /**
     * Создать новую транзакцию
     */
    public function createTransaction(array $data): Transaction
    {
        $data['transaction_id'] = $data['transaction_id'] ?? Transaction::generateTransactionId();
        $data['net_amount'] = $data['net_amount'] ?? ($data['amount'] - ($data['fee'] ?? 0));
        
        return $this->create($data);
    }

    /**
     * Найти транзакцию по внешнему ID
     */
    public function findByGatewayId(string $gateway, string $gatewayTransactionId): ?Transaction
    {
        return $this->model
            ->where('gateway', $gateway)
            ->where('gateway_transaction_id', $gatewayTransactionId)
            ->first();
    }

    /**
     * Получить транзакции пользователя
     */
    public function getUserTransactions(
        int $userId, 
        array $filters = [], 
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = $this->model->byUser($userId);
        
        $this->applyFilters($query, $filters);
        
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Получить баланс пользователя
     */
    public function getUserBalance(int $userId, ?string $currency = 'RUB'): float
    {
        $incoming = $this->model
            ->byUser($userId)
            ->incoming()
            ->successful()
            ->where('currency', $currency)
            ->sum('amount');
            
        $outgoing = $this->model
            ->byUser($userId)
            ->outgoing()
            ->successful()
            ->where('currency', $currency)
            ->sum('amount');
            
        return $incoming - $outgoing;
    }

    /**
     * Получить статистику транзакций
     */
    public function getStatistics(int $userId, Carbon $from, Carbon $to): array
    {
        $query = $this->model
            ->byUser($userId)
            ->successful()
            ->inPeriod($from, $to);
            
        return [
            'total_incoming' => (clone $query)->incoming()->sum('amount'),
            'total_outgoing' => (clone $query)->outgoing()->sum('amount'),
            'count_incoming' => (clone $query)->incoming()->count(),
            'count_outgoing' => (clone $query)->outgoing()->count(),
            'by_type' => $this->getStatisticsByType($userId, $from, $to),
            'daily' => $this->getDailyStatistics($userId, $from, $to),
        ];
    }

    /**
     * Получить статистику по типам транзакций
     */
    public function getStatisticsByType(int $userId, Carbon $from, Carbon $to): array
    {
        return $this->model
            ->byUser($userId)
            ->successful()
            ->inPeriod($from, $to)
            ->selectRaw('type, direction, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('type', 'direction')
            ->get()
            ->mapWithKeys(function ($item) {
                $key = $item->type . '_' . $item->direction;
                return [$key => [
                    'count' => $item->count,
                    'total' => $item->total,
                ]];
            })
            ->toArray();
    }

    /**
     * Получить ежедневную статистику
     */
    public function getDailyStatistics(int $userId, Carbon $from, Carbon $to): array
    {
        return $this->model
            ->byUser($userId)
            ->successful()
            ->inPeriod($from, $to)
            ->selectRaw('DATE(created_at) as date, direction, SUM(amount) as total')
            ->groupBy('date', 'direction')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($items, $date) {
                $incoming = $items->where('direction', TransactionDirection::IN)->sum('total');
                $outgoing = $items->where('direction', TransactionDirection::OUT)->sum('total');
                
                return [
                    'date' => $date,
                    'incoming' => $incoming,
                    'outgoing' => $outgoing,
                    'net' => $incoming - $outgoing,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Получить последние транзакции
     */
    public function getRecent(int $userId, int $limit = 10): Collection
    {
        return $this->model
            ->byUser($userId)
            ->with(['payment', 'subscription'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Обновить статус транзакции
     */
    public function updateStatus(int $transactionId, TransactionStatus $status, ?array $additionalData = []): bool
    {
        $data = array_merge(['status' => $status], $additionalData);
        
        if ($status === TransactionStatus::SUCCESS) {
            $data['processed_at'] = now();
        } elseif ($status === TransactionStatus::FAILED) {
            $data['failed_at'] = now();
        }
        
        return $this->update($transactionId, $data);
    }

    /**
     * Проверить лимиты транзакций
     */
    public function checkDailyLimit(int $userId, TransactionType $type, float $amount): bool
    {
        $dailyLimit = config("payment.limits.daily.{$type->value}", PHP_FLOAT_MAX);
        
        $todayTotal = $this->model
            ->byUser($userId)
            ->byType($type->value)
            ->successful()
            ->whereDate('created_at', today())
            ->sum('amount');
            
        return ($todayTotal + $amount) <= $dailyLimit;
    }

    /**
     * Применить фильтры к запросу
     */
    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }
        
        if (!empty($filters['direction'])) {
            $query->byDirection($filters['direction']);
        }
        
        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }
        
        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }
        
        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }
        
        if (!empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }
        
        if (!empty($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }
    }
}