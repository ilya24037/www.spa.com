<?php

namespace App\Domain\Booking\Handlers;

use App\Domain\Booking\Models\Booking;
use App\Enums\BookingStatus;
use App\Enums\BookingType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Обработчик поиска и фильтрации бронирований
 */
class BookingSearchHandler
{
    public function __construct(
        protected Booking $model
    ) {}

    /**
     * Поиск бронирований с фильтрами и пагинацией
     */
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        $query = $this->applyFilters($query, $filters);

        $sortBy = $filters['sort_by'] ?? 'start_time';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        if ($sortBy === 'date') {
            $query->orderBy('start_time', $sortOrder);
        } elseif ($sortBy === 'status') {
            // Сортировка по приоритету статуса
            $query->orderByRaw("
                CASE 
                    WHEN status = 'in_progress' THEN 1
                    WHEN status = 'confirmed' THEN 2
                    WHEN status = 'pending' THEN 3
                    WHEN status = 'rescheduled' THEN 4
                    WHEN status = 'completed' THEN 5
                    ELSE 6
                END " . $sortOrder);
        } elseif ($sortBy === 'price') {
            $query->orderBy('total_price', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->with(['client', 'master', 'service'])->paginate($perPage);
    }

    /**
     * Найти бронирования для клиента
     */
    public function findForClient(int $clientId, array $filters = []): Collection
    {
        $query = $this->model->forClient($clientId);
        
        return $this->applyFilters($query, $filters)->get();
    }

    /**
     * Найти бронирования для мастера
     */
    public function findForMaster(int $masterId, array $filters = []): Collection
    {
        $query = $this->model->forMaster($masterId);
        
        return $this->applyFilters($query, $filters)->get();
    }

    /**
     * Получить бронирования пользователя с пагинацией
     */
    public function getBookingsForUser($user, int $perPage = 10)
    {
        return Booking::with(['masterProfile.user', 'service', 'client'])
            ->where(function($query) use ($user) {
                // Показываем бронирования где пользователь - клиент
                $query->where('client_id', $user->id);
                
                // Или где пользователь - мастер
                if ($user->masterProfile) {
                    $query->orWhere('master_profile_id', $user->masterProfile->id);
                }
            })
            ->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate($perPage);
    }

    /**
     * Найти пересекающиеся бронирования
     */
    public function findOverlapping(\Carbon\Carbon $startTime, \Carbon\Carbon $endTime, int $masterId, ?int $excludeId = null): Collection
    {
        $query = $this->model->where('master_id', $masterId)
                            ->active()
                            ->where(function ($q) use ($startTime, $endTime) {
                                $q->whereBetween('start_time', [$startTime, $endTime])
                                  ->orWhereBetween('end_time', [$startTime, $endTime])
                                  ->orWhere(function ($q2) use ($startTime, $endTime) {
                                      $q2->where('start_time', '<=', $startTime)
                                         ->where('end_time', '>=', $endTime);
                                  });
                            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get();
    }

    /**
     * Применить фильтры к запросу
     */
    public function applyFilters($query, array $filters = [])
    {
        if (isset($filters['status'])) {
            if ($filters['status'] instanceof BookingStatus) {
                $query->byStatus($filters['status']);
            } elseif (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (isset($filters['type'])) {
            if ($filters['type'] instanceof BookingType) {
                $query->byType($filters['type']);
            } else {
                $query->where('type', $filters['type']);
            }
        }

        if (isset($filters['client_id'])) {
            $query->forClient($filters['client_id']);
        }

        if (isset($filters['master_id'])) {
            $query->forMaster($filters['master_id']);
        }

        if (isset($filters['service_id'])) {
            $query->where('service_id', $filters['service_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('start_time', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('start_time', '<=', $filters['date_to']);
        }

        if (isset($filters['date'])) {
            $query->whereDate('start_time', $filters['date']);
        }

        if (isset($filters['price_min'])) {
            $query->where('total_price', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $query->where('total_price', '<=', $filters['price_max']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%")
                  ->orWhere('client_phone', 'like', "%{$search}%")
                  ->orWhere('client_email', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (isset($filters['active']) && $filters['active']) {
            $query->active();
        }

        if (isset($filters['upcoming']) && $filters['upcoming']) {
            $query->upcoming();
        }

        if (isset($filters['past']) && $filters['past']) {
            $query->past();
        }

        return $query;
    }

    /**
     * Получить истекающие скоро бронирования
     */
    public function getExpiringSoon(int $hours = 24): Collection
    {
        return $this->model->where('status', 'pending')
                          ->where('start_time', '>', now())
                          ->where('start_time', '<=', now()->addHours($hours))
                          ->get();
    }

    /**
     * Получить бронирования, требующие напоминания
     */
    public function getNeedsReminder(): Collection
    {
        return $this->model->needsReminder()->get();
    }

    /**
     * Получить просроченные бронирования
     */
    public function getOverdue(): Collection
    {
        return $this->model->overdue()->get();
    }

    /**
     * Оптимизированный запрос для списков
     */
    public function optimizeQuery()
    {
        return $this->model->select([
            'id', 'booking_number', 'client_id', 'master_id', 'service_id',
            'type', 'status', 'start_time', 'end_time', 'total_price',
            'client_name', 'client_phone', 'created_at'
        ]);
    }
}