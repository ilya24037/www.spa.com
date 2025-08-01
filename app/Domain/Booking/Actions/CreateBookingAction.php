<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\DTOs\BookingData;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\Master\Repositories\MasterRepository;
use App\Enums\BookingStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для создания нового бронирования
 */
class CreateBookingAction
{
    private BookingRepository $bookingRepository;
    private MasterRepository $masterRepository;

    public function __construct(
        BookingRepository $bookingRepository,
        MasterRepository $masterRepository
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->masterRepository = $masterRepository;
    }

    /**
     * Создать новое бронирование
     */
    public function execute(BookingData $bookingData): array
    {
        try {
            return DB::transaction(function () use ($bookingData) {
                // Проверяем существование мастера
                $master = $this->masterRepository->findById($bookingData->masterId);
                
                if (!$master || !$master->isActive()) {
                    return [
                        'success' => false,
                        'message' => 'Мастер не найден или недоступен',
                    ];
                }

                // Проверяем доступность времени
                if (!$this->isTimeSlotAvailable($bookingData)) {
                    return [
                        'success' => false,
                        'message' => 'Выбранное время недоступно',
                    ];
                }

                // Создаем бронирование
                $booking = $this->bookingRepository->create($bookingData->toArray());

                // Связываем услуги
                if (!empty($bookingData->serviceIds)) {
                    $this->bookingRepository->attachServices($booking->id, $bookingData->serviceIds);
                }

                Log::info('Booking created', [
                    'booking_id' => $booking->id,
                    'master_id' => $bookingData->masterId,
                    'client_id' => $bookingData->clientId,
                    'date' => $bookingData->date,
                ]);

                // TODO: Отправить уведомления

                return [
                    'success' => true,
                    'message' => 'Бронирование успешно создано',
                    'booking' => $booking,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to create booking', [
                'data' => $bookingData->toArray(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при создании бронирования',
            ];
        }
    }

    /**
     * Проверить доступность временного слота
     */
    private function isTimeSlotAvailable(BookingData $bookingData): bool
    {
        // Проверяем конфликты с другими бронированиями
        $hasConflict = $this->bookingRepository->hasTimeConflict(
            $bookingData->masterId,
            $bookingData->date,
            $bookingData->timeStart,
            $bookingData->timeEnd
        );

        if ($hasConflict) {
            return false;
        }

        // TODO: Проверить расписание мастера

        return true;
    }
}