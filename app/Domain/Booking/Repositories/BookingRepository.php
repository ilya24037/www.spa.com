<?php

namespace App\Domain\Booking\Repositories;

use App\Domain\Booking\Models\Booking;
use App\Enums\BookingStatus;
use App\Enums\BookingType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingRepository
{
    public function __construct(
        private Booking $model
    ) {}

    public function find(int $id): ?Booking
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): Booking
    {
        return $this->model->findOrFail($id);
    }

    public function findByNumber(string $bookingNumber): ?Booking
    {
        return $this->model->where('booking_number', $bookingNumber)->first();
    }

    public function create(array $data): Booking
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        $booking = $this->find($id);
        return $booking ? $booking->delete() : false;
    }

    public function forceDelete(int $id): bool
    {
        $booking = $this->model->withTrashed()->find($id);
        return $booking ? $booking->forceDelete() : false;
    }

    public function restore(int $id): bool
    {
        $booking = $this->model->withTrashed()->find($id);
        return $booking ? $booking->restore() : false;
    }

    public function findForClient(int $clientId, array $filters = []): Collection
    {
        $query = $this->model->forClient($clientId);
        
        return $this->applyFilters($query, $filters)->get();
    }

    public function findForMaster(int $masterId, array $filters = []): Collection
    {
        $query = $this->model->forMaster($masterId);
        
        return $this->applyFilters($query, $filters)->get();
    }

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

    protected function applyFilters($query, array $filters)
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

    public function getUpcoming(int $limit = 10): Collection
    {
        return $this->model->upcoming()
                          ->active()
                          ->orderBy('start_time')
                          ->limit($limit)
                          ->get();
    }

    public function getUpcomingForMaster(int $masterId, int $limit = 10): Collection
    {
        return $this->model->forMaster($masterId)
                          ->upcoming()
                          ->active()
                          ->orderBy('start_time')
                          ->limit($limit)
                          ->get();
    }

    public function getUpcomingForClient(int $clientId, int $limit = 10): Collection
    {
        return $this->model->forClient($clientId)
                          ->upcoming()
                          ->active()
                          ->orderBy('start_time')
                          ->limit($limit)
                          ->get();
    }

    public function getTodayBookings(?int $masterId = null): Collection
    {
        $query = $this->model->today()->active();
        
        if ($masterId) {
            $query->forMaster($masterId);
        }
        
        return $query->orderBy('start_time')->get();
    }

    public function getBookingsForDate(Carbon $date, ?int $masterId = null): Collection
    {
        $query = $this->model->whereDate('start_time', $date);
        
        if ($masterId) {
            $query->forMaster($masterId);
        }
        
        return $query->orderBy('start_time')->get();
    }

    public function getBookingsForDateRange(Carbon $startDate, Carbon $endDate, ?int $masterId = null): Collection
    {
        $query = $this->model->whereBetween('start_time', [$startDate, $endDate]);
        
        if ($masterId) {
            $query->forMaster($masterId);
        }
        
        return $query->orderBy('start_time')->get();
    }

    public function findOverlapping(Carbon $startTime, Carbon $endTime, int $masterId, ?int $excludeId = null): Collection
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

    public function getStatistics(array $filters = []): array
    {
        $query = $this->model->newQuery();
        
        if (isset($filters['master_id'])) {
            $query->forMaster($filters['master_id']);
        }
        
        if (isset($filters['date_from'])) {
            $query->where('start_time', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('start_time', '<=', $filters['date_to']);
        }

        $stats = $query->select([
            DB::raw('COUNT(*) as total_bookings'),
            DB::raw('COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_bookings'),
            DB::raw('COUNT(CASE WHEN status IN ("cancelled_by_client", "cancelled_by_master", "no_show") THEN 1 END) as cancelled_bookings'),
            DB::raw('SUM(CASE WHEN status = "completed" THEN total_price ELSE 0 END) as total_revenue'),
            DB::raw('AVG(CASE WHEN status = "completed" THEN total_price ELSE NULL END) as average_booking_value'),
            DB::raw('COUNT(CASE WHEN status IN ("pending", "confirmed", "in_progress") THEN 1 END) as active_bookings'),
        ])->first();

        $conversionRate = $stats->total_bookings > 0 
            ? round(($stats->completed_bookings / $stats->total_bookings) * 100, 2) 
            : 0;

        $cancellationRate = $stats->total_bookings > 0 
            ? round(($stats->cancelled_bookings / $stats->total_bookings) * 100, 2) 
            : 0;

        return [
            'total_bookings' => $stats->total_bookings ?? 0,
            'completed_bookings' => $stats->completed_bookings ?? 0,
            'cancelled_bookings' => $stats->cancelled_bookings ?? 0,
            'active_bookings' => $stats->active_bookings ?? 0,
            'total_revenue' => $stats->total_revenue ?? 0,
            'average_booking_value' => $stats->average_booking_value ?? 0,
            'conversion_rate' => $conversionRate,
            'cancellation_rate' => $cancellationRate,
        ];
    }

    public function getRevenueByMonth(int $year, ?int $masterId = null): Collection
    {
        $query = $this->model->whereYear('start_time', $year)
                            ->where('status', 'completed');
        
        if ($masterId) {
            $query->forMaster($masterId);
        }

        return $query->select([
            DB::raw('MONTH(start_time) as month'),
            DB::raw('SUM(total_price) as revenue'),
            DB::raw('COUNT(*) as bookings_count')
        ])
        ->groupBy(DB::raw('MONTH(start_time)'))
        ->orderBy('month')
        ->get();
    }

    public function getPopularServices(int $limit = 10, ?int $masterId = null): Collection
    {
        $query = $this->model->join('services', 'bookings.service_id', '=', 'services.id')
                            ->where('bookings.status', 'completed');
        
        if ($masterId) {
            $query->where('bookings.master_id', $masterId);
        }

        return $query->select([
            'services.id',
            'services.name',
            DB::raw('COUNT(*) as bookings_count'),
            DB::raw('SUM(bookings.total_price) as total_revenue'),
            DB::raw('AVG(bookings.total_price) as average_price')
        ])
        ->groupBy('services.id', 'services.name')
        ->orderByDesc('bookings_count')
        ->limit($limit)
        ->get();
    }

    public function getNeedsReminder(): Collection
    {
        return $this->model->needsReminder()->get();
    }

    public function getOverdue(): Collection
    {
        return $this->model->overdue()->get();
    }

    public function getExpiringSoon(int $hours = 24): Collection
    {
        return $this->model->where('status', 'pending')
                          ->where('start_time', '>', now())
                          ->where('start_time', '<=', now()->addHours($hours))
                          ->get();
    }

    public function getFrequentClients(int $limit = 10, ?int $masterId = null): Collection
    {
        $query = $this->model->join('users', 'bookings.client_id', '=', 'users.id')
                            ->where('bookings.status', 'completed');
        
        if ($masterId) {
            $query->where('bookings.master_id', $masterId);
        }

        return $query->select([
            'users.id',
            'users.name',
            'users.email',
            DB::raw('COUNT(*) as bookings_count'),
            DB::raw('SUM(bookings.total_price) as total_spent'),
            DB::raw('MAX(bookings.start_time) as last_booking')
        ])
        ->groupBy('users.id', 'users.name', 'users.email')
        ->orderByDesc('bookings_count')
        ->limit($limit)
        ->get();
    }

    public function markReminderSent(int $bookingId): bool
    {
        return $this->update($bookingId, [
            'reminder_sent' => true,
            'reminder_sent_at' => now()
        ]);
    }

    public function batchUpdateStatus(array $ids, $status): int
    {
        return $this->model->whereIn('id', $ids)->update(['status' => $status]);
    }

    public function getBookingCalendar(Carbon $startDate, Carbon $endDate, ?int $masterId = null): array
    {
        $query = $this->model->whereBetween('start_time', [$startDate, $endDate]);
        
        if ($masterId) {
            $query->forMaster($masterId);
        }

        $bookings = $query->with(['client', 'service'])->get();

        $calendar = [];
        
        foreach ($bookings as $booking) {
            $date = $booking->start_time->format('Y-m-d');
            
            if (!isset($calendar[$date])) {
                $calendar[$date] = [];
            }
            
            $calendar[$date][] = [
                'id' => $booking->id,
                'title' => $booking->service->name ?? 'Услуга',
                'client' => $booking->client->name ?? $booking->client_name,
                'start' => $booking->start_time->format('c'),
                'end' => $booking->end_time->format('c'),
                'status' => $booking->status,
                'color' => $booking->status instanceof BookingStatus ? $booking->status->getColor() : '#6B7280',
            ];
        }

        return $calendar;
    }

    public function optimizeQuery()
    {
        return $this->model->select([
            'id', 'booking_number', 'client_id', 'master_id', 'service_id',
            'type', 'status', 'start_time', 'end_time', 'total_price',
            'client_name', 'client_phone', 'created_at'
        ]);
    }
}