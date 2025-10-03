<?php

namespace App\Domain\Booking\Handlers;

use App\Domain\Booking\Models\Booking;
use Illuminate\Database\Eloquent\Collection;

/**
 * Обработчик CRUD операций для бронирований
 */
class BookingCrudHandler
{
    public function __construct(
        protected Booking $model
    ) {}

    /**
     * Найти бронирование по ID с загрузкой связей
     */
    public function find(int $id): ?Booking
    {
        return $this->model->with(['client', 'master', 'service', 'payment'])->find($id);
    }

    /**
     * Найти бронирование по ID или выбросить исключение
     */
    public function findOrFail(int $id): Booking
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Найти бронирование по номеру
     */
    public function findByNumber(string $bookingNumber): ?Booking
    {
        return $this->model->where('booking_number', $bookingNumber)->first();
    }

    /**
     * Найти бронирование с загруженными связями
     */
    public function findWithRelations(int $bookingId): ?Booking
    {
        return Booking::with(['master', 'service', 'client'])->find($bookingId);
    }

    /**
     * Создать новое бронирование
     */
    public function create(array $data): Booking
    {
        return $this->model->create($data);
    }

    /**
     * Обновить бронирование
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * Удалить бронирование (мягкое удаление)
     */
    public function delete(int $id): bool
    {
        $booking = $this->find($id);
        return $booking ? $booking->delete() : false;
    }

    /**
     * Принудительно удалить бронирование
     */
    public function forceDelete(int $id): bool
    {
        $booking = $this->model->withTrashed()->find($id);
        return $booking ? $booking->forceDelete() : false;
    }

    /**
     * Восстановить удаленное бронирование
     */
    public function restore(int $id): bool
    {
        $booking = $this->model->withTrashed()->find($id);
        return $booking ? $booking->restore() : false;
    }

    /**
     * Валидировать запрос на создание бронирования
     */
    public function validateBookingRequest(int $masterProfileId, int $serviceId): array
    {
        $masterProfile = \App\Domain\Master\Models\MasterProfile::with(['user', 'services', 'schedules'])
            ->findOrFail($masterProfileId);
            
        $service = \App\Domain\Service\Models\Service::findOrFail($serviceId);
        
        // Проверяем, что услуга принадлежит мастеру
        if (!$masterProfile->services->contains($service)) {
            throw new \InvalidArgumentException('Выбранная услуга не доступна у этого мастера');
        }

        return compact('masterProfile', 'service');
    }

    /**
     * Массовое обновление статуса
     */
    public function batchUpdateStatus(array $ids, $status): int
    {
        return $this->model->whereIn('id', $ids)->update(['status' => $status]);
    }

    /**
     * Отметить отправку напоминания
     */
    public function markReminderSent(int $bookingId): bool
    {
        return $this->update($bookingId, [
            'reminder_sent' => true,
            'reminder_sent_at' => now()
        ]);
    }

    /**
     * Создать несколько бронирований
     */
    public function createBatch(array $bookingsData): Collection
    {
        $bookings = collect();
        
        foreach ($bookingsData as $data) {
            $booking = $this->create($data);
            $bookings->push($booking);
        }
        
        return $bookings;
    }

    /**
     * Получить все бронирования с базовыми отношениями
     */
    public function getAll(array $with = ['client', 'master', 'service']): Collection
    {
        return $this->model->with($with)->get();
    }

    /**
     * Получить количество бронирований
     */
    public function count(array $filters = []): int
    {
        $query = $this->model->newQuery();
        
        if (!empty($filters)) {
            $searchHandler = new BookingSearchHandler($this->model);
            $query = $searchHandler->applyFilters($query, $filters);
        }
        
        return $query->count();
    }

    /**
     * Проверить существование бронирования
     */
    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * Получить случайное бронирование
     */
    public function random(): ?Booking
    {
        return $this->model->inRandomOrder()->first();
    }

    /**
     * Дублировать бронирование
     */
    public function duplicate(int $id, array $overrideData = []): ?Booking
    {
        $original = $this->find($id);
        
        if (!$original) {
            return null;
        }
        
        $data = array_merge(
            $original->toArray(),
            $overrideData,
            [
                'id' => null,
                'booking_number' => null, // Будет сгенерирован автоматически
                'status' => 'pending',
                'created_at' => null,
                'updated_at' => null,
            ]
        );
        
        return $this->create($data);
    }

    /**
     * Получить последние бронирования
     */
    public function getLatest(int $limit = 10): Collection
    {
        return $this->model->orderBy('created_at', 'desc')
                           ->limit($limit)
                           ->get();
    }

    /**
     * Получить старые бронирования
     */
    public function getOldest(int $limit = 10): Collection
    {
        return $this->model->orderBy('created_at', 'asc')
                           ->limit($limit)
                           ->get();
    }

    /**
     * Проверить уникальность номера бронирования
     */
    public function isBookingNumberUnique(string $bookingNumber, ?int $excludeId = null): bool
    {
        $query = $this->model->where('booking_number', $bookingNumber);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return !$query->exists();
    }
}