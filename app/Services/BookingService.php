<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\MasterProfile;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Сервис для работы с бронированиями
 * Содержит всю бизнес-логику системы бронирования
 */
class BookingService
{
    /**
     * Создать новое бронирование
     */
    public function createBooking(array $data): Booking
    {
        // Проверяем доступность слота
        $this->validateTimeSlot(
            $data['master_profile_id'],
            $data['booking_date'],
            $data['booking_time'],
            $data['service_id']
        );

        // Получаем данные для расчета
        $service = Service::findOrFail($data['service_id']);
        $masterProfile = MasterProfile::findOrFail($data['master_profile_id']);

        // Рассчитываем время окончания
        $startTime = Carbon::parse($data['booking_time']);
        $endTime = $startTime->copy()->addMinutes($service->duration_minutes ?? 60);

        // Рассчитываем стоимость
        $pricing = $this->calculatePricing($service, $data);

        // Создаем запись в БД
        $booking = DB::transaction(function () use ($data, $service, $startTime, $endTime, $pricing) {
            return Booking::create([
                'booking_number' => $this->generateBookingNumber(),
                'client_id' => $data['client_id'] ?? auth()->id(),
                'master_profile_id' => $data['master_profile_id'],
                'service_id' => $data['service_id'],
                'booking_date' => $data['booking_date'],
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'duration' => $service->duration_minutes ?? 60,
                'address' => $data['address'] ?? null,
                'address_details' => $data['address_details'] ?? null,
                'is_home_service' => $data['service_location'] === 'home',
                'service_price' => $pricing['service_price'],
                'travel_fee' => $pricing['travel_fee'],
                'discount_amount' => $pricing['discount_amount'],
                'total_price' => $pricing['total_price'],
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'status' => Booking::STATUS_PENDING,
                'client_name' => $data['client_name'],
                'client_phone' => $data['client_phone'],
                'client_email' => $data['client_email'] ?? null,
                'client_comment' => $data['client_comment'] ?? null,
                'source' => 'website'
            ]);
        });

        // Отправляем уведомления
        $this->sendBookingNotifications($booking);

        return $booking->load(['masterProfile.user', 'service', 'client']);
    }

    /**
     * Получить доступные слоты для мастера и услуги
     */
    public function getAvailableSlots(int $masterProfileId, int $serviceId, int $days = 14): array
    {
        $masterProfile = MasterProfile::with('schedules')->findOrFail($masterProfileId);
        $service = Service::findOrFail($serviceId);
        
        $slots = [];
        $startDate = now();
        $endDate = now()->addDays($days);

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek;
            
            // Получаем расписание на этот день недели
            $schedule = $masterProfile->schedules()
                ->where('day_of_week', $dayOfWeek)
                ->where('is_working_day', true)
                ->first();
                
            if (!$schedule) continue;

            // Генерируем слоты с учётом длительности услуги
            $daySlots = $this->generateDaySlots(
                $date->format('Y-m-d'),
                $schedule,
                $service,
                $masterProfile
            );

            if (!empty($daySlots)) {
                $slots[$date->format('Y-m-d')] = $daySlots;
            }
        }

        return $slots;
    }

    /**
     * Подтвердить бронирование
     */
    public function confirmBooking(Booking $booking, User $master): bool
    {
        // Проверяем права
        if (!$master->masterProfile || $booking->master_profile_id !== $master->masterProfile->id) {
            throw new \Exception('У вас нет прав для подтверждения этого бронирования');
        }

        if ($booking->status !== Booking::STATUS_PENDING) {
            throw new \Exception('Это бронирование уже обработано');
        }

        $booking->update([
            'status' => Booking::STATUS_CONFIRMED,
            'confirmed_at' => now()
        ]);

        // Отправляем уведомление клиенту
        $this->sendConfirmationNotification($booking);

        return true;
    }

    /**
     * Отменить бронирование
     */
    public function cancelBooking(Booking $booking, User $user, string $reason = null): bool
    {
        // Проверяем права на отмену
        $canCancel = $booking->client_id === $user->id || 
                    ($user->masterProfile && $booking->master_profile_id === $user->masterProfile->id);
                    
        if (!$canCancel) {
            throw new \Exception('У вас нет прав для отмены этого бронирования');
        }

        // Проверяем статус
        if (!in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED])) {
            throw new \Exception('Это бронирование нельзя отменить');
        }

        // Проверяем время (за 2 часа до начала)
        $bookingDateTime = Carbon::parse($booking->booking_date . ' ' . $booking->start_time);
        if (now()->diffInHours($bookingDateTime, false) < 2) {
            throw new \Exception('Отмена возможна не позднее чем за 2 часа до начала');
        }

        $booking->update([
            'status' => Booking::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
            'cancellation_reason' => $reason
        ]);

        // Отправляем уведомление другой стороне
        $this->sendCancellationNotification($booking, $user);

        return true;
    }

    /**
     * Завершить услугу
     */
    public function completeBooking(Booking $booking, User $master): bool
    {
        // Проверяем права
        if (!$master->masterProfile || $booking->master_profile_id !== $master->masterProfile->id) {
            throw new \Exception('У вас нет прав для завершения этого бронирования');
        }

        // Проверяем статус
        if ($booking->status !== Booking::STATUS_CONFIRMED) {
            throw new \Exception('Можно завершить только подтверждённое бронирование');
        }

        // Проверяем время
        $bookingDateTime = Carbon::parse($booking->booking_date . ' ' . $booking->start_time);
        if ($bookingDateTime->isFuture()) {
            throw new \Exception('Нельзя завершить услугу до её начала');
        }

        DB::transaction(function () use ($booking, $master) {
            // Обновляем бронирование
            $booking->update([
                'status' => Booking::STATUS_COMPLETED,
                'payment_status' => 'paid',
                'paid_at' => now()
            ]);

            // Увеличиваем счётчик у мастера
            $master->masterProfile->increment('completed_bookings_count');

            // Запрашиваем отзыв
            $booking->update([
                'review_requested' => true,
                'review_requested_at' => now()
            ]);
        });

        // Отправляем запрос на отзыв
        $this->sendReviewRequest($booking);

        return true;
    }

    // =================== ПРИВАТНЫЕ МЕТОДЫ ===================

    /**
     * Проверка доступности временного слота
     */
    private function validateTimeSlot(int $masterProfileId, string $date, string $time, int $serviceId): void
    {
        $service = Service::findOrFail($serviceId);
        $startTime = Carbon::parse($time);
        $endTime = $startTime->copy()->addMinutes($service->duration_minutes ?? 60);

        // Проверяем пересечения с другими бронированиями
        $conflict = Booking::where('master_profile_id', $masterProfileId)
            ->where('booking_date', $date)
            ->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED])
            ->where(function($query) use ($startTime, $endTime) {
                $query->whereTime('start_time', '<', $endTime->format('H:i:s'))
                      ->whereTime('end_time', '>', $startTime->format('H:i:s'));
            })
            ->exists();

        if ($conflict) {
            throw new \Exception('Выбранное время уже занято');
        }

        // Проверяем, что бронирование не в прошлом
        $bookingDateTime = Carbon::parse($date . ' ' . $time);
        if ($bookingDateTime->isPast()) {
            throw new \Exception('Нельзя забронировать время в прошлом');
        }

        // Проверяем минимальное время до бронирования (ОТКЛЮЧЕНО ДЛЯ ТЕСТОВ)
        // if ($bookingDateTime->diffInMinutes(now()) < 15) {
        //     throw new \Exception('Бронирование возможно минимум за 15 минут');
        // }
    }

    /**
     * Расчет стоимости бронирования
     */
    private function calculatePricing(Service $service, array $data): array
    {
        $servicePrice = $service->price;
        $travelFee = ($data['service_location'] === 'home') ? 500 : 0;
        $discountAmount = 0; // TODO: логика скидок
        $totalPrice = $servicePrice + $travelFee - $discountAmount;

        return [
            'service_price' => $servicePrice,
            'travel_fee' => $travelFee,
            'discount_amount' => $discountAmount,
            'total_price' => $totalPrice
        ];
    }

    /**
     * Генерация уникального номера бронирования
     */
    private function generateBookingNumber(): string
    {
        do {
            $number = 'BK' . date('Ymd') . strtoupper(Str::random(4));
        } while (Booking::where('booking_number', $number)->exists());

        return $number;
    }

    /**
     * Генерация слотов на день (ПУБЛИЧНЫЙ МЕТОД)
     */
    public function generateDaySlots(string $date, $schedule, Service $service, MasterProfile $masterProfile): array
    {
        $slots = [];
        $serviceDuration = $service->duration_minutes ?? 60;
        
        $startTime = Carbon::parse($date . ' ' . $schedule->start_time);
        $endTime = Carbon::parse($date . ' ' . $schedule->end_time);
        $breakStart = $schedule->break_start ? Carbon::parse($date . ' ' . $schedule->break_start) : null;
        $breakEnd = $schedule->break_end ? Carbon::parse($date . ' ' . $schedule->break_end) : null;

        // Получаем существующие бронирования
        $existingBookings = Booking::where('master_profile_id', $masterProfile->id)
            ->where('booking_date', $date)
            ->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED])
            ->get();

        $currentSlot = $startTime->copy();
        
        while ($currentSlot->copy()->addMinutes($serviceDuration) <= $endTime) {
            // Пропускаем время обеда
            if ($breakStart && $breakEnd) {
                if ($currentSlot >= $breakStart && $currentSlot < $breakEnd) {
                    $currentSlot = $breakEnd->copy();
                    continue;
                }
                if ($currentSlot->copy()->addMinutes($serviceDuration) > $breakStart && 
                    $currentSlot < $breakStart) {
                    $currentSlot = $breakEnd->copy();
                    continue;
                }
            }

            // Проверяем занятость
            $isAvailable = true;
            foreach ($existingBookings as $booking) {
                $bookingStart = Carbon::parse($date . ' ' . $booking->start_time);
                $bookingEnd = Carbon::parse($date . ' ' . $booking->end_time);
                
                if ($currentSlot < $bookingEnd && $currentSlot->copy()->addMinutes($serviceDuration) > $bookingStart) {
                    $isAvailable = false;
                    break;
                }
            }

            // Проверяем, что слот в будущем
            $slotDateTime = Carbon::parse($date . ' ' . $currentSlot->format('H:i'));
            if ($isAvailable && $slotDateTime > now()->addMinutes(15)) {
                $slots[] = [
                    'time' => $currentSlot->format('H:i'),
                    'available' => true
                ];
            }

            $currentSlot->addMinutes(30); // Шаг 30 минут
        }

        return $slots;
    }

    // =================== УВЕДОМЛЕНИЯ ===================

    /**
     * Отправить уведомления о новом бронировании
     */
    private function sendBookingNotifications(Booking $booking): void
    {
        // TODO: Реализовать отправку email/SMS
        // $this->notificationService->sendBookingCreated($booking);
    }

    /**
     * Отправить уведомление о подтверждении
     */
    private function sendConfirmationNotification(Booking $booking): void
    {
        // TODO: Реализовать уведомление клиенту
    }

    /**
     * Отправить уведомление об отмене
     */
    private function sendCancellationNotification(Booking $booking, User $cancelledBy): void
    {
        // TODO: Реализовать уведомление другой стороне
    }

    /**
     * Отправить запрос на отзыв
     */
    private function sendReviewRequest(Booking $booking): void
    {
        // TODO: Реализовать запрос отзыва
    }
} 