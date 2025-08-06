<?php

namespace App\Domain\Payment\Repositories;

use App\Domain\Payment\Models\Payment;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Репозиторий для поиска и фильтрации платежей
 */
class PaymentQueryRepository
{
    private Payment $model;

    public function __construct()
    {
        $this->model = new Payment();
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
     * Применить фильтры к запросу
     */
    private function applyFilters($query, array $filters = []): void
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
}