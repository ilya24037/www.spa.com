<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Построитель запросов для бронирований
 */
class BookingQueryBuilder
{
    private Builder $query;

    public function __construct()
    {
        $this->query = Booking::query();
    }

    /**
     * Создать новый экземпляр
     */
    public static function create(): self
    {
        return new static();
    }

    /**
     * Только активные бронирования
     */
    public function active(): self
    {
        $this->query->whereIn('status', [
            Booking::STATUS_PENDING,
            Booking::STATUS_CONFIRMED,
            Booking::STATUS_IN_PROGRESS
        ]);

        return $this;
    }

    /**
     * Бронирования на определенную дату
     */
    public function forDate(Carbon $date): self
    {
        $this->query->whereDate('booking_date', $date);
        return $this;
    }

    /**
     * Бронирования в диапазоне дат
     */
    public function betweenDates(Carbon $startDate, Carbon $endDate): self
    {
        $this->query->whereBetween('booking_date', [$startDate, $endDate]);
        return $this;
    }

    /**
     * Предстоящие бронирования
     */
    public function upcoming(): self
    {
        $this->query->where('booking_date', '>=', today())
                    ->orderBy('booking_date')
                    ->orderBy('start_time');
        return $this;
    }

    /**
     * Прошедшие бронирования
     */
    public function past(): self
    {
        $this->query->where('booking_date', '<', today())
                    ->orderBy('booking_date', 'desc')
                    ->orderBy('start_time', 'desc');
        return $this;
    }

    /**
     * Бронирования сегодня
     */
    public function today(): self
    {
        return $this->forDate(today());
    }

    /**
     * Бронирования завтра
     */
    public function tomorrow(): self
    {
        return $this->forDate(today()->addDay());
    }

    /**
     * Бронирования на этой неделе
     */
    public function thisWeek(): self
    {
        return $this->betweenDates(
            now()->startOfWeek(),
            now()->endOfWeek()
        );
    }

    /**
     * Бронирования в этом месяце
     */
    public function thisMonth(): self
    {
        return $this->betweenDates(
            now()->startOfMonth(),
            now()->endOfMonth()
        );
    }

    /**
     * Фильтр по клиенту
     */
    public function forClient(int $clientId): self
    {
        $this->query->where('client_id', $clientId);
        return $this;
    }

    /**
     * Фильтр по мастеру
     */
    public function forMaster(int $masterId): self
    {
        $this->query->where('master_id', $masterId);
        return $this;
    }

    /**
     * Фильтр по услуге
     */
    public function forService(int $serviceId): self
    {
        $this->query->where('service_id', $serviceId);
        return $this;
    }

    /**
     * Фильтр по статусу
     */
    public function withStatus(string $status): self
    {
        $this->query->where('status', $status);
        return $this;
    }

    /**
     * Фильтр по нескольким статусам
     */
    public function withStatuses(array $statuses): self
    {
        $this->query->whereIn('status', $statuses);
        return $this;
    }

    /**
     * Завершенные бронирования
     */
    public function completed(): self
    {
        return $this->withStatus(Booking::STATUS_COMPLETED);
    }

    /**
     * Отмененные бронирования
     */
    public function cancelled(): self
    {
        return $this->withStatuses([
            Booking::STATUS_CANCELLED,
            Booking::STATUS_NO_SHOW
        ]);
    }

    /**
     * Ожидающие подтверждения
     */
    public function pending(): self
    {
        return $this->withStatus(Booking::STATUS_PENDING);
    }

    /**
     * Подтвержденные бронирования
     */
    public function confirmed(): self
    {
        return $this->withStatus(Booking::STATUS_CONFIRMED);
    }

    /**
     * Выполняющиеся бронирования
     */
    public function inProgress(): self
    {
        return $this->withStatus(Booking::STATUS_IN_PROGRESS);
    }

    /**
     * Фильтр по цене (от)
     */
    public function minPrice(float $price): self
    {
        $this->query->where('total_price', '>=', $price);
        return $this;
    }

    /**
     * Фильтр по цене (до)
     */
    public function maxPrice(float $price): self
    {
        $this->query->where('total_price', '<=', $price);
        return $this;
    }

    /**
     * Диапазон цен
     */
    public function priceBetween(float $minPrice, float $maxPrice): self
    {
        $this->query->whereBetween('total_price', [$minPrice, $maxPrice]);
        return $this;
    }

    /**
     * Оплаченные бронирования
     */
    public function paid(): self
    {
        $this->query->where('payment_status', 'paid');
        return $this;
    }

    /**
     * Неоплаченные бронирования
     */
    public function unpaid(): self
    {
        $this->query->where('payment_status', '!=', 'paid')
                    ->orWhereNull('payment_status');
        return $this;
    }

    /**
     * Домашние услуги
     */
    public function homeServices(): self
    {
        $this->query->where('is_home_service', true);
        return $this;
    }

    /**
     * Услуги в салоне
     */
    public function salonServices(): self
    {
        $this->query->where('is_home_service', false);
        return $this;
    }

    /**
     * С напоминанием
     */
    public function withReminder(): self
    {
        $this->query->where('reminder_sent', true);
        return $this;
    }

    /**
     * Без напоминания
     */
    public function withoutReminder(): self
    {
        $this->query->where('reminder_sent', false)
                    ->orWhereNull('reminder_sent');
        return $this;
    }

    /**
     * Поиск по номеру бронирования
     */
    public function byBookingNumber(string $bookingNumber): self
    {
        $this->query->where('booking_number', 'like', "%{$bookingNumber}%");
        return $this;
    }

    /**
     * Поиск по имени клиента
     */
    public function byClientName(string $name): self
    {
        $this->query->where(function ($query) use ($name) {
            $query->where('client_name', 'like', "%{$name}%")
                  ->orWhereHas('client', function ($clientQuery) use ($name) {
                      $clientQuery->where('name', 'like', "%{$name}%")
                               ->orWhere('email', 'like', "%{$name}%")
                               ->orWhere('phone', 'like', "%{$name}%");
                  });
        });
        return $this;
    }

    /**
     * Поиск по имени мастера
     */
    public function byMasterName(string $name): self
    {
        $this->query->whereHas('master', function ($masterQuery) use ($name) {
            $masterQuery->where('name', 'like', "%{$name}%");
        });
        return $this;
    }

    /**
     * Включить связанные модели
     */
    public function withRelations(array $relations = []): self
    {
        $defaultRelations = [
            'client',
            'master',
            'service',
            'payment',
            'masterProfile'
        ];

        $relations = empty($relations) ? $defaultRelations : $relations;
        $this->query->with($relations);

        return $this;
    }

    /**
     * Сортировка по дате (новые первыми)
     */
    public function latest(): self
    {
        $this->query->latest('booking_date')->latest('start_time');
        return $this;
    }

    /**
     * Сортировка по дате (старые первыми)
     */
    public function oldest(): self
    {
        $this->query->oldest('booking_date')->oldest('start_time');
        return $this;
    }

    /**
     * Получить результат
     */
    public function get()
    {
        return $this->query->get();
    }

    /**
     * Получить первый результат
     */
    public function first()
    {
        return $this->query->first();
    }

    /**
     * Пагинация
     */
    public function paginate(int $perPage = 15)
    {
        return $this->query->paginate($perPage);
    }

    /**
     * Подсчет
     */
    public function count(): int
    {
        return $this->query->count();
    }

    /**
     * Существование
     */
    public function exists(): bool
    {
        return $this->query->exists();
    }

    /**
     * Получить Builder для дополнительных операций
     */
    public function toBuilder(): Builder
    {
        return $this->query;
    }
}