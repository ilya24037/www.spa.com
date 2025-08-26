<?php

namespace App\Domain\Payment\Repositories;

use App\Domain\Payment\Models\Payment;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Репозиторий для бизнес-логики и специальных операций с платежами
 */
class PaymentBusinessRepository
{
    private Payment $model;

    public function __construct()
    {
        $this->model = new Payment();
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

    /**
     * Получить платежи по списку идентификаторов
     */
    public function getByIds(array $ids): Collection
    {
        return $this->model->whereIn('id', $ids)
            ->with(['user', 'payable', 'refunds'])
            ->get();
    }

    /**
     * Проверить существование платежа с внешним ID
     */
    public function existsByExternalId(string $externalId, ?string $gateway = null): bool
    {
        $query = $this->model->where('external_id', $externalId);
        
        if ($gateway) {
            $query->where('gateway', $gateway);
        }
        
        return $query->exists();
    }

    /**
     * Получить последний платеж пользователя
     */
    public function getLastUserPayment(int $userId): ?Payment
    {
        return $this->model->where('user_id', $userId)
            ->latest()
            ->first();
    }

    /**
     * Найти платежи для автоматической отмены
     */
    public function getForAutoCancellation(int $minutesOld = 15): Collection
    {
        return $this->model->where('status', PaymentStatus::PENDING)
            ->where('created_at', '<', now()->subMinutes($minutesOld))
            ->where('auto_cancel', true)
            ->with(['user', 'payable'])
            ->get();
    }

    /**
     * Получить платежи требующие подтверждения
     */
    public function getPendingConfirmation(): Collection
    {
        return $this->model->where('status', PaymentStatus::PROCESSING)
            ->where('created_at', '<', now()->subMinutes(5))
            ->with(['user', 'payable'])
            ->get();
    }

    /**
     * Применить фильтры к запросу
     */
    private function applyFilters($query, array $filters = []): void
    {
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
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
    }
}