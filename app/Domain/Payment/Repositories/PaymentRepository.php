<?php

namespace App\Domain\Payment\Repositories;

use App\Domain\Payment\Models\Payment;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Domain\Common\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Репозиторий для работы с платежами
 * 
 * @extends BaseRepository<Payment>
 */
class PaymentRepository extends BaseRepository
{
    /**
     * Получить класс модели
     */
    protected function getModelClass(): string
    {
        return Payment::class;
    }

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Найти платеж по ID
     */
    public function find(int $id): ?Payment
    {
        return $this->model->find($id);
    }

    /**
     * Найти платеж по номеру
     */
    public function findByNumber(string $paymentNumber): ?Payment
    {
        return $this->model->where('payment_number', $paymentNumber)->first();
    }

    /**
     * Найти платеж по внешнему ID
     */
    public function findByExternalId(string $externalId, ?string $gateway = null): ?Payment
    {
        $query = $this->model->where('external_id', $externalId);
        
        if ($gateway) {
            $query->where('gateway', $gateway);
        }
        
        return $query->first();
    }

    /**
     * Создать новый платеж
     */
    public function create(array $data): Payment
    {
        return $this->model->create($data);
    }

    /**
     * Обновить платеж
     * Переопределяем базовый метод для совместимости
     */
    public function update(int $id, array $data): bool
    {
        $payment = $this->findOrFail($id);
        return $payment->update($data);
    }
    
    /**
     * Обновить платеж (старая сигнатура для обратной совместимости)
     */
    public function updatePayment(Payment $payment, array $data): bool
    {
        return $payment->update($data);
    }

    /**
     * Удалить платеж
     * Переопределяем базовый метод для совместимости
     */
    public function delete(int $id): bool
    {
        $payment = $this->findOrFail($id);
        return $payment->delete();
    }
    
    /**
     * Удалить платеж (старая сигнатура для обратной совместимости)
     */
    public function deletePayment(Payment $payment): bool
    {
        return $payment->delete();
    }

    /**
     * Получить платежи пользователя
     */
    public function getUserPayments(
        int $userId, 
        array $filters = [], 
        int $perPage = 20
    ): LengthAwarePaginator {
        
        $query = $this->model->where('user_id', $userId)
            ->with(['payable', 'refunds'])
            ->latest();
        
        $this->applyFilters($query, $filters);
        
        return $query->paginate($perPage);
    }

    /**
     * Получить платежи по статусу
     */
    public function getByStatus(PaymentStatus $status, ?int $limit = null): Collection
    {
        $query = $this->model->where('status', $status)
            ->with(['user', 'payable'])
            ->latest();
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * Получить платежи по типу
     */
    public function getByType(PaymentType $type, array $filters = []): Collection
    {
        $query = $this->model->where('type', $type)
            ->with(['user', 'payable'])
            ->latest();
        
        $this->applyFilters($query, $filters);
        
        return $query->get();
    }

    /**
     * Получить платежи по методу оплаты
     */
    public function getByMethod(PaymentMethod $method, array $filters = []): Collection
    {
        $query = $this->model->where('method', $method)
            ->with(['user', 'payable'])
            ->latest();
        
        $this->applyFilters($query, $filters);
        
        return $query->get();
    }

    /**
     * Получить успешные платежи
     */
    public function getSuccessful(array $filters = []): Collection
    {
        $query = $this->model->successful()
            ->with(['user', 'payable'])
            ->latest();
        
        $this->applyFilters($query, $filters);
        
        return $query->get();
    }

    /**
     * Получить неудачные платежи
     */
    public function getFailed(array $filters = []): Collection
    {
        $query = $this->model->failed()
            ->with(['user', 'payable'])
            ->latest();
        
        $this->applyFilters($query, $filters);
        
        return $query->get();
    }

    /**
     * Получить ожидающие платежи
     */
    public function getPending(array $filters = []): Collection
    {
        $query = $this->model->pending()
            ->with(['user', 'payable'])
            ->latest();
        
        $this->applyFilters($query, $filters);
        
        return $query->get();
    }

    /**
     * Получить просроченные платежи
     */
    public function getExpired(): Collection
    {
        return $this->model->where('status', PaymentStatus::PENDING)
            ->where('created_at', '<', now()->subMinutes(30))
            ->with(['user', 'payable'])
            ->get();
    }

    /**
     * Получить платежи для возврата
     */
    public function getRefundable(array $filters = []): Collection
    {
        $query = $this->model->where('status', PaymentStatus::COMPLETED)
            ->whereDoesntHave('refunds', function($q) {
                $q->where('status', PaymentStatus::COMPLETED);
            })
            ->with(['user', 'payable'])
            ->latest();
        
        $this->applyFilters($query, $filters);
        
        return $query->get();
    }

    /**
     * Получить статистику платежей
     */
    public function getStatistics(array $filters = []): array
    {
        $query = $this->buildStatsQuery($filters);
        
        return [
            'total_count' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'successful_count' => $query->where('status', PaymentStatus::COMPLETED)->count(),
            'successful_amount' => $query->where('status', PaymentStatus::COMPLETED)->sum('amount'),
            'failed_count' => $query->whereIn('status', [PaymentStatus::FAILED, PaymentStatus::CANCELLED])->count(),
            'pending_count' => $query->whereIn('status', [PaymentStatus::PENDING, PaymentStatus::PROCESSING])->count(),
            'average_amount' => $query->avg('amount'),
            'by_method' => $this->getStatsByMethod($filters),
            'by_type' => $this->getStatsByType($filters),
            'by_status' => $this->getStatsByStatus($filters),
        ];
    }

    /**
     * Получить статистику по методам оплаты
     */
    public function getStatsByMethod(array $filters = []): array
    {
        $query = $this->buildStatsQuery($filters);
        
        return $query->select('method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('method')
            ->orderBy('total', 'desc')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->method => [
                    'count' => $item->count,
                    'total' => $item->total,
                ]];
            })
            ->toArray();
    }

    /**
     * Получить статистику по типам платежей
     */
    public function getStatsByType(array $filters = []): array
    {
        $query = $this->buildStatsQuery($filters);
        
        return $query->select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->orderBy('total', 'desc')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->type => [
                    'count' => $item->count,
                    'total' => $item->total,
                ]];
            })
            ->toArray();
    }

    /**
     * Получить статистику по статусам
     */
    public function getStatsByStatus(array $filters = []): array
    {
        $query = $this->buildStatsQuery($filters);
        
        return $query->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('status')
            ->orderBy('count', 'desc')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->status => [
                    'count' => $item->count,
                    'total' => $item->total,
                ]];
            })
            ->toArray();
    }

    /**
     * Получить ежедневную статистику
     */
    public function getDailyStats(Carbon $from, Carbon $to): Collection
    {
        return $this->model->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(CASE WHEN status = "completed" THEN 1 END) as successful_count'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as successful_total')
            )
            ->whereBetween('created_at', [$from, $to])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
    }

    /**
     * Получить топ пользователей по платежам
     */
    public function getTopUsers(int $limit = 10, array $filters = []): Collection
    {
        $query = $this->model->select(
                'user_id',
                DB::raw('COUNT(*) as payments_count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->with('user:id,name,email')
            ->where('status', PaymentStatus::COMPLETED)
            ->groupBy('user_id')
            ->orderBy('total_amount', 'desc')
            ->limit($limit);
        
        $this->applyFilters($query, $filters);
        
        return $query->get();
    }

    /**
     * Поиск платежей
     */
    public function search(
        string $query, 
        array $filters = [], 
        int $perPage = 20
    ): LengthAwarePaginator {
        
        $builder = $this->model->query()
            ->with(['user', 'payable']);
        
        // Поиск по номеру платежа, external_id, описанию
        $builder->where(function($q) use ($query) {
            $q->where('payment_number', 'like', "%{$query}%")
              ->orWhere('external_id', 'like', "%{$query}%")
              ->orWhere('description', 'like', "%{$query}%")
              ->orWhereHas('user', function($userQuery) use ($query) {
                  $userQuery->where('name', 'like', "%{$query}%")
                           ->orWhere('email', 'like', "%{$query}%");
              });
        });
        
        $this->applyFilters($builder, $filters);
        
        return $builder->latest()->paginate($perPage);
    }

    /**
     * Получить платежи, требующие внимания
     */
    public function getRequiringAttention(): Collection
    {
        return $this->model->whereIn('status', [
                PaymentStatus::HELD,
                PaymentStatus::DISPUTED,
                PaymentStatus::FAILED
            ])
            ->with(['user', 'payable'])
            ->latest()
            ->get();
    }

    /**
     * Пакетное обновление статуса
     */
    public function batchUpdateStatus(
        array $paymentIds, 
        PaymentStatus $status, 
        array $additionalData = []
    ): int {
        
        $updateData = array_merge(['status' => $status], $additionalData);
        
        return $this->model->whereIn('id', $paymentIds)->update($updateData);
    }

    /**
     * Получить сводку по комиссиям
     */
    public function getFeeSummary(array $filters = []): array
    {
        $query = $this->buildStatsQuery($filters);
        
        return [
            'total_fees' => $query->sum('fee'),
            'average_fee' => $query->avg('fee'),
            'fees_by_method' => $query->select('method', DB::raw('SUM(fee) as total_fee'))
                ->groupBy('method')
                ->pluck('total_fee', 'method')
                ->toArray(),
        ];
    }

    /**
     * Получить конверсию платежей
     */
    public function getConversionRate(array $filters = []): float
    {
        $query = $this->buildStatsQuery($filters);
        
        $total = $query->count();
        $successful = $query->where('status', PaymentStatus::COMPLETED)->count();
        
        return $total > 0 ? ($successful / $total) * 100 : 0;
    }

    /**
     * Получить среднее время обработки
     */
    public function getAverageProcessingTime(array $filters = []): ?float
    {
        $query = $this->buildStatsQuery($filters);
        
        return $query->whereNotNull('processed_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, created_at, processed_at)) as avg_time'))
            ->value('avg_time');
    }

    /**
     * Применить фильтры к запросу
     */
    protected function applyFilters($query, array $filters = [])
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['method'])) {
            $query->where('method', $filters['method']);
        }
        
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['gateway'])) {
            $query->where('gateway', $filters['gateway']);
        }
        
        if (!empty($filters['amount_from'])) {
            $query->where('amount', '>=', $filters['amount_from']);
        }
        
        if (!empty($filters['amount_to'])) {
            $query->where('amount', '<=', $filters['amount_to']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        if (!empty($filters['payable_type'])) {
            $query->where('payable_type', $filters['payable_type']);
        }
        
        if (!empty($filters['has_refunds'])) {
            if ($filters['has_refunds']) {
                $query->has('refunds');
            } else {
                $query->doesntHave('refunds');
            }
        }
    }

    /**
     * Построить базовый запрос для статистики
     */
    protected function buildStatsQuery(array $filters = []): Builder
    {
        $query = $this->model->query();
        $this->applyFilters($query, $filters);
        return $query;
    }

    /**
     * Экспорт платежей в массив
     */
    public function export(array $filters = [], int $limit = 1000): Collection
    {
        $query = $this->model->with(['user:id,name,email', 'payable'])
            ->latest()
            ->limit($limit);
        
        $this->applyFilters($query, $filters);
        
        return $query->get();
    }

    /**
     * Получить дублирующиеся платежи
     */
    public function getDuplicates(): Collection
    {
        return $this->model->select('external_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('external_id')
            ->groupBy('external_id')
            ->having('count', '>', 1)
            ->with(['user', 'payable'])
            ->get();
    }

    /**
     * Очистка истекших платежей
     */
    public function cleanupExpired(): int
    {
        return $this->model->where('status', PaymentStatus::PENDING)
            ->where('created_at', '<', now()->subHours(24))
            ->update(['status' => PaymentStatus::EXPIRED]);
    }
}