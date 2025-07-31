<?php

namespace App\Domain\Booking\Services;

use App\Models\Booking;
use App\Models\User;
use App\DTOs\CreateBookingDTO;
use App\DTOs\UpdateBookingDTO;
use App\DTOs\BookingFilterDTO;
use App\Actions\Booking\CreateBookingAction;
use App\Actions\Booking\ConfirmBookingAction;
use App\Actions\Booking\CancelBookingAction;
use App\Actions\Booking\CompleteBookingAction;
use App\Actions\Booking\RescheduleBookingAction;
use App\Repositories\BookingRepository;
use Carbon\Carbon;

/**
 * Модульный сервис бронирований
 * Orchestrator для Actions - не содержит бизнес-логики
 */
class BookingService
{
    public function __construct(
        private BookingRepository $repository,
        private CreateBookingAction $createAction,
        private ConfirmBookingAction $confirmAction,
        private CancelBookingAction $cancelAction,
        private CompleteBookingAction $completeAction,
        private RescheduleBookingAction $rescheduleAction
    ) {}

    /**
     * Создать бронирование
     */
    public function create(CreateBookingDTO $dto): Booking
    {
        return $this->createAction->execute($dto);
    }

    /**
     * Подтвердить бронирование
     */
    public function confirm(int $bookingId, User $master, array $options = []): Booking
    {
        $booking = $this->repository->findOrFail($bookingId);
        return $this->confirmAction->execute($booking, $master, $options);
    }

    /**
     * Отменить бронирование
     */
    public function cancel(int $bookingId, User $user, string $reason, bool $force = false): Booking
    {
        $booking = $this->repository->findOrFail($bookingId);
        return $this->cancelAction->execute($booking, $user, $reason, $force);
    }

    /**
     * Завершить бронирование
     */
    public function complete(int $bookingId, User $master, array $options = []): Booking
    {
        $booking = $this->repository->findOrFail($bookingId);
        return $this->completeAction->execute($booking, $master, $options);
    }

    /**
     * Перенести бронирование
     */
    public function reschedule(
        int $bookingId, 
        User $user, 
        Carbon $newStartTime, 
        ?int $newDuration = null,
        ?string $reason = null
    ): Booking {
        $booking = $this->repository->findOrFail($bookingId);
        return $this->rescheduleAction->execute($booking, $user, $newStartTime, $newDuration, $reason);
    }

    /**
     * Найти бронирование
     */
    public function find(int $id): ?Booking
    {
        return $this->repository->findById($id);
    }

    /**
     * Найти по номеру
     */
    public function findByNumber(string $number): ?Booking
    {
        return $this->repository->findByNumber($number);
    }

    /**
     * Получить бронирования с фильтрами
     */
    public function list(BookingFilterDTO $filters)
    {
        return $this->repository->paginate(
            $filters->per_page ?? 15,
            $filters->toArray()
        );
    }

    /**
     * Получить бронирования клиента
     */
    public function getClientBookings(int $clientId, BookingFilterDTO $filters = null)
    {
        $filterArray = $filters ? $filters->toArray() : [];
        return $this->repository->getClientBookings($clientId, $filterArray);
    }

    /**
     * Получить бронирования мастера
     */
    public function getMasterBookings(int $masterId, BookingFilterDTO $filters = null)
    {
        $filterArray = $filters ? $filters->toArray() : [];
        return $this->repository->getMasterBookings($masterId, $filterArray);
    }

    /**
     * Получить предстоящие бронирования
     */
    public function getUpcoming(int $hours = 24)
    {
        return $this->repository->getUpcomingBookings($hours);
    }

    /**
     * Получить статистику
     */
    public function getStats(BookingFilterDTO $filters = null)
    {
        $filterArray = $filters ? $filters->toArray() : [];
        return $this->repository->getBookingStats($filterArray);
    }

    /**
     * Поиск бронирований
     */
    public function search(string $query, BookingFilterDTO $filters = null)
    {
        $filterArray = $filters ? $filters->toArray() : [];
        return $this->repository->search($query, $filterArray);
    }

    /**
     * Массовое подтверждение
     */
    public function bulkConfirm(array $bookingIds, User $master, array $options = []): array
    {
        return $this->confirmAction->bulkConfirm($bookingIds, $master, $options);
    }

    /**
     * Массовая отмена
     */
    public function bulkCancel(array $bookingIds, User $user, string $reason): array
    {
        return $this->cancelAction->bulkCancel($bookingIds, $user, $reason);
    }

    /**
     * Массовое завершение
     */
    public function bulkComplete(array $bookingIds, User $master, array $options = []): array
    {
        return $this->completeAction->bulkComplete($bookingIds, $master, $options);
    }

    /**
     * Автозавершение просроченных
     */
    public function autoCompleteOverdue(): array
    {
        return $this->completeAction->autoCompleteOverdue();
    }

    /**
     * Автоподтверждение
     */
    public function autoConfirm(Booking $booking): bool
    {
        return $this->confirmAction->autoConfirm($booking);
    }
}