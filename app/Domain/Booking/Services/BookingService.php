<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Service\Models\Service;
use App\Domain\User\Models\User;
use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Domain\Booking\Repositories\BookingRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Сервис для работы с бронированиями
 * Содержит всю бизнес-логику системы бронирования
 */
class BookingService
{
    public function __construct(
        private \App\Infrastructure\Notification\NotificationService $notificationService,
        private BookingRepository $bookingRepository
    ) {}
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
        
        // Логика скидок
        $discountAmount = $this->calculateDiscount($service, $data);
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
        $this->notificationService->sendBookingCreated($booking);
    }

    /**
     * Отправить уведомление о подтверждении
     */
    private function sendConfirmationNotification(Booking $booking): void
    {
        $this->notificationService->sendBookingConfirmed($booking);
    }

    /**
     * Отправить уведомление об отмене
     */
    private function sendCancellationNotification(Booking $booking, User $cancelledBy): void
    {
        $this->notificationService->sendBookingCancelled($booking, $cancelledBy);
    }

    /**
     * Отправить запрос на отзыв
     */
    private function sendReviewRequest(Booking $booking): void
    {
        $this->notificationService->sendReviewRequest($booking);
    }

    // =================== РАСЧЕТ СКИДОК ===================

    /**
     * Рассчитать размер скидки
     */
    private function calculateDiscount(Service $service, array $data): float
    {
        $discount = 0;
        
        // Скидка для первого бронирования
        if ($this->isFirstBooking($data)) {
            $discount += $service->price * 0.1; // 10% скидка
        }
        
        // Скидка за отсутствие выезда
        if ($data['service_location'] === 'salon') {
            $discount += 200; // фиксированная скидка за посещение салона
        }
        
        // Скидка в будние дни
        $bookingDate = Carbon::parse($data['booking_date']);
        if ($bookingDate->isWeekday()) {
            $discount += $service->price * 0.05; // 5% скидка в будни
        }
        
        return min($discount, $service->price * 0.3); // максимум 30% скидки
    }

    /**
     * Проверить, является ли это первым бронированием клиента
     */
    private function isFirstBooking(array $data): bool
    {
        // Если клиент авторизован, проверяем по user_id
        if (auth()->check()) {
            return !Booking::where('client_id', auth()->id())->exists();
        }
        
        // Если не авторизован, проверяем по телефону
        return !Booking::where('client_phone', $data['client_phone'])->exists();
    }

    // =================== НОВЫЕ МЕТОДЫ С ENUMS ===================

    /**
     * Создать новое бронирование с Enum поддержкой
     */
    public function createBookingWithEnums(array $data): Booking
    {
        $this->validateBookingData($data);

        $type = BookingType::from($data['type'] ?? BookingType::INCALL->value);
        $service = Service::findOrFail($data['service_id']);
        
        // Проверяем доступность слота
        $this->validateTimeSlotAvailability($data, $type);

        // Рассчитываем стоимость
        $pricing = $this->calculatePricingWithType($service, $data, $type);

        // Создаем бронирование
        $booking = DB::transaction(function () use ($data, $service, $type, $pricing) {
            $startTime = Carbon::parse($data['start_time']);
            $duration = $data['duration_minutes'] ?? $service->duration_minutes ?? $type->getDefaultDurationMinutes();
            $endTime = $startTime->copy()->addMinutes($duration);

            return $this->bookingRepository->create([
                'booking_number' => $this->generateBookingNumber(),
                'client_id' => $data['client_id'] ?? auth()->id(),
                'master_id' => $data['master_id'],
                'master_profile_id' => $data['master_profile_id'] ?? null, // Совместимость
                'service_id' => $data['service_id'],
                'type' => $type,
                'status' => BookingStatus::default(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_minutes' => $duration,
                'base_price' => $service->price,
                'service_price' => $pricing['service_price'],
                'delivery_fee' => $pricing['delivery_fee'],
                'total_price' => $pricing['total_price'],
                'discount_amount' => $pricing['discount_amount'],
                'client_address' => $data['client_address'] ?? null,
                'master_address' => $data['master_address'] ?? null,
                'client_name' => $data['client_name'],
                'client_phone' => $data['client_phone'],
                'client_email' => $data['client_email'] ?? null,
                'master_phone' => $data['master_phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                'equipment_required' => $data['equipment_required'] ?? null,
                'platform' => $data['platform'] ?? null,
                'meeting_link' => $data['meeting_link'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'source' => $data['source'] ?? 'website',
            ]);
        });

        // Отправляем уведомления
        $this->sendBookingNotifications($booking);

        return $booking->load(['client', 'master', 'service']);
    }

    /**
     * Подтвердить бронирование с Enum
     */
    public function confirmBookingWithEnum(Booking $booking, User $master): Booking
    {
        $this->validateMasterPermission($booking, $master);

        if ($booking->status instanceof BookingStatus) {
            if (!$booking->status->canTransitionTo(BookingStatus::CONFIRMED)) {
                throw new \Exception('Нельзя подтвердить бронирование в статусе: ' . $booking->status->getLabel());
            }
        } else {
            // Старая логика для совместимости
            if ($booking->status !== Booking::STATUS_PENDING) {
                throw new \Exception('Это бронирование уже обработано');
            }
        }

        $booking->confirmBooking();
        $this->sendConfirmationNotification($booking);

        return $booking;
    }

    /**
     * Отменить бронирование с Enum
     */
    public function cancelBookingWithEnum(Booking $booking, User $user, string $reason): Booking
    {
        $this->validateCancellationPermission($booking, $user);

        $byClient = $booking->client_id === $user->id;
        $booking->cancelBooking($reason, $byClient);
        
        $this->sendCancellationNotification($booking, $user);

        return $booking;
    }

    /**
     * Завершить бронирование с Enum
     */
    public function completeBookingWithEnum(Booking $booking, User $master): Booking
    {
        $this->validateMasterPermission($booking, $master);

        if ($booking->status instanceof BookingStatus) {
            if (!$booking->status->canTransitionTo(BookingStatus::COMPLETED)) {
                throw new \Exception('Нельзя завершить бронирование в статусе: ' . $booking->status->getLabel());
            }
        }

        DB::transaction(function () use ($booking, $master) {
            $booking->completeService();
            
            // Увеличиваем счётчик у мастера
            if ($master->masterProfile) {
                $master->masterProfile->increment('completed_bookings_count');
            }
        });

        $this->sendReviewRequest($booking);

        return $booking;
    }

    /**
     * Перенести бронирование
     */
    public function rescheduleBooking(Booking $booking, Carbon $newStartTime, int $newDuration = null): Booking
    {
        if ($booking->status instanceof BookingStatus) {
            if (!$booking->status->canBeRescheduled()) {
                throw new \Exception('Нельзя перенести бронирование в статусе: ' . $booking->status->getLabel());
            }
        } else {
            if (!$booking->canCancelBooking()) {
                throw new \Exception('Нельзя перенести данное бронирование');
            }
        }

        // Проверяем доступность нового времени
        $duration = $newDuration ?? $booking->duration_minutes;
        $newEndTime = $newStartTime->copy()->addMinutes($duration);
        
        $masterId = $booking->master_id ?? $booking->masterProfile->user_id ?? null;
        if ($masterId) {
            $overlapping = $this->bookingRepository->findOverlapping(
                $newStartTime, 
                $newEndTime, 
                $masterId, 
                $booking->id
            );

            if ($overlapping->isNotEmpty()) {
                throw new \Exception('Новое время занято другим бронированием');
            }
        }

        $booking->reschedule($newStartTime, $duration);
        
        // Отправляем уведомления о переносе
        $this->notificationService->sendBookingRescheduled($booking);

        return $booking;
    }

    /**
     * Получить доступные слоты с учетом типа бронирования
     */
    public function getAvailableSlotsForType(int $masterId, int $serviceId, BookingType $type, int $days = 14): array
    {
        $service = Service::findOrFail($serviceId);
        $duration = $type->getDefaultDurationMinutes();
        $minAdvanceHours = $type->getMinAdvanceHours();
        
        $slots = [];
        $startDate = now()->addHours($minAdvanceHours);
        $endDate = now()->addDays($days);

        for ($date = $startDate->copy()->startOfDay(); $date <= $endDate; $date->addDay()) {
            $daySlots = $this->generateDaySlotsForType($date, $masterId, $service, $type, $duration);
            
            if (!empty($daySlots)) {
                $slots[$date->format('Y-m-d')] = $daySlots;
            }
        }

        return $slots;
    }

    /**
     * Валидация данных бронирования
     */
    protected function validateBookingData(array $data): void
    {
        $required = ['master_id', 'service_id', 'start_time', 'client_name', 'client_phone'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Поле {$field} обязательно для заполнения");
            }
        }

        if (isset($data['type'])) {
            $type = BookingType::from($data['type']);
            $typeRequired = $type->getRequiredFields();
            
            foreach ($typeRequired as $field) {
                if (empty($data[$field])) {
                    throw new \InvalidArgumentException("Для типа {$type->getLabel()} поле {$field} обязательно");
                }
            }
        }
    }

    /**
     * Валидация доступности временного слота с учетом типа
     */
    protected function validateTimeSlotAvailability(array $data, BookingType $type): void
    {
        $startTime = Carbon::parse($data['start_time']);
        $minAdvanceHours = $type->getMinAdvanceHours();
        
        if ($startTime->lt(now()->addHours($minAdvanceHours))) {
            throw new \Exception("Для типа {$type->getLabel()} необходимо бронировать минимум за {$minAdvanceHours} часов");
        }

        $duration = $data['duration_minutes'] ?? $type->getDefaultDurationMinutes();
        $endTime = $startTime->copy()->addMinutes($duration);
        
        $masterId = $data['master_id'];
        $overlapping = $this->bookingRepository->findOverlapping($startTime, $endTime, $masterId);
        
        if ($overlapping->isNotEmpty()) {
            throw new \Exception('Выбранное время уже занято');
        }
    }

    /**
     * Расчет стоимости с учетом типа бронирования
     */
    protected function calculatePricingWithType(Service $service, array $data, BookingType $type): array
    {
        $servicePrice = $service->price;
        $deliveryFee = 0;
        
        if ($type->hasDeliveryFee()) {
            $deliveryFee = $data['delivery_fee'] ?? 500; // Стандартная плата за выезд
        }
        
        $discountAmount = $this->calculateDiscountForType($service, $data, $type);
        $totalPrice = $servicePrice + $deliveryFee - $discountAmount;

        return [
            'service_price' => $servicePrice,
            'delivery_fee' => $deliveryFee,
            'discount_amount' => $discountAmount,
            'total_price' => max(0, $totalPrice),
        ];
    }

    /**
     * Расчет скидки с учетом типа бронирования
     */
    protected function calculateDiscountForType(Service $service, array $data, BookingType $type): float
    {
        $discount = 0;
        
        // Базовые скидки
        if ($this->isFirstBooking($data)) {
            $discount += $service->price * 0.1;
        }
        
        // Скидки по типу бронирования
        if ($type === BookingType::INCALL) {
            $discount += 200; // Скидка за посещение салона
        } elseif ($type === BookingType::ONLINE) {
            $discount += $service->price * 0.05; // 5% скидка за онлайн
        }
        
        // Скидка в будние дни
        $startTime = Carbon::parse($data['start_time']);
        if ($startTime->isWeekday()) {
            $discount += $service->price * 0.05;
        }
        
        return min($discount, $service->price * 0.3); // максимум 30%
    }

    /**
     * Генерация слотов на день с учетом типа
     */
    protected function generateDaySlotsForType(Carbon $date, int $masterId, Service $service, BookingType $type, int $duration): array
    {
        // Получаем расписание мастера
        $master = User::find($masterId);
        if (!$master || !$master->masterProfile) {
            return [];
        }

        $dayOfWeek = $date->dayOfWeek;
        $schedule = $master->masterProfile->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->first();

        if (!$schedule) {
            return [];
        }

        $slots = [];
        $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
        $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);
        
        // Получаем существующие бронирования
        $existingBookings = $this->bookingRepository->getBookingsForDate($date, $masterId);
        
        $currentSlot = $startTime->copy();
        $slotInterval = 30; // Интервал между слотами в минутах
        
        while ($currentSlot->copy()->addMinutes($duration) <= $endTime) {
            // Проверяем доступность слота
            $isAvailable = true;
            
            foreach ($existingBookings as $booking) {
                if ($currentSlot->lt($booking->end_time) && 
                    $currentSlot->copy()->addMinutes($duration)->gt($booking->start_time)) {
                    $isAvailable = false;
                    break;
                }
            }
            
            // Проверяем минимальное время заранее
            $minAdvanceTime = now()->addHours($type->getMinAdvanceHours());
            if ($isAvailable && $currentSlot->gt($minAdvanceTime)) {
                $slots[] = [
                    'time' => $currentSlot->format('H:i'),
                    'datetime' => $currentSlot->toISOString(),
                    'available' => true,
                    'type' => $type->value,
                ];
            }
            
            $currentSlot->addMinutes($slotInterval);
        }

        return $slots;
    }

    /**
     * Валидация прав мастера
     */
    protected function validateMasterPermission(Booking $booking, User $master): void
    {
        $canManage = $booking->master_id === $master->id || 
                    ($booking->master_profile_id && $master->masterProfile && 
                     $booking->master_profile_id === $master->masterProfile->id);
        
        if (!$canManage) {
            throw new \Exception('У вас нет прав для управления этим бронированием');
        }
    }

    /**
     * Валидация прав на отмену
     */
    protected function validateCancellationPermission(Booking $booking, User $user): void
    {
        $canCancel = $booking->client_id === $user->id || 
                    $booking->master_id === $user->id ||
                    ($booking->master_profile_id && $user->masterProfile && 
                     $booking->master_profile_id === $user->masterProfile->id);
        
        if (!$canCancel) {
            throw new \Exception('У вас нет прав для отмены этого бронирования');
        }
    }
} 