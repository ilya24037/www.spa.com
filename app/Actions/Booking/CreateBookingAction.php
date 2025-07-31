<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\User;
use App\Models\Service;
use App\Enums\BookingType;
use App\Enums\BookingStatus;
use App\Repositories\BookingRepository;
use App\Services\NotificationService;
use App\DTOs\CreateBookingDTO;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для создания нового бронирования
 * Инкапсулирует всю логику создания бронирования
 */
class CreateBookingAction
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private NotificationService $notificationService
    ) {}

    /**
     * Выполнить создание бронирования
     */
    public function execute(CreateBookingDTO $dto): Booking
    {
        Log::info('Creating new booking', [
            'client_id' => $dto->client_id,
            'master_id' => $dto->master_id,
            'service_id' => $dto->service_id,
            'type' => $dto->type->value,
        ]);

        // Валидация данных
        $this->validateBookingData($dto);

        // Проверка доступности слота
        $this->validateTimeSlotAvailability($dto);

        // Создание бронирования в транзакции
        $booking = DB::transaction(function () use ($dto) {
            return $this->createBookingRecord($dto);
        });

        // Отправка уведомлений
        $this->sendNotifications($booking);

        Log::info('Booking created successfully', [
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
        ]);

        return $booking->load(['client', 'master', 'service']);
    }

    /**
     * Валидация данных бронирования
     */
    protected function validateBookingData(CreateBookingDTO $dto): void
    {
        // Проверяем существование пользователей
        if (!User::find($dto->client_id)) {
            throw new \InvalidArgumentException('Клиент не найден');
        }

        if (!User::find($dto->master_id)) {
            throw new \InvalidArgumentException('Мастер не найден');
        }

        // Проверяем существование услуги
        if (!Service::find($dto->service_id)) {
            throw new \InvalidArgumentException('Услуга не найдена');
        }

        // Проверяем время
        if ($dto->start_time->isPast()) {
            throw new \InvalidArgumentException('Нельзя создать бронирование в прошлом');
        }

        // Проверяем минимальное время заранее для типа
        $minAdvanceHours = $dto->type->getMinAdvanceHours();
        if ($dto->start_time->lt(now()->addHours($minAdvanceHours))) {
            throw new \InvalidArgumentException(
                "Для типа {$dto->type->getLabel()} необходимо бронировать минимум за {$minAdvanceHours} часов"
            );
        }

        // Проверяем максимальную продолжительность
        $maxDurationHours = $dto->type->getMaxDurationHours();
        if ($dto->duration_minutes > ($maxDurationHours * 60)) {
            throw new \InvalidArgumentException(
                "Максимальная продолжительность для типа {$dto->type->getLabel()}: {$maxDurationHours} часов"
            );
        }

        // Валидация специфичных полей по типу
        $this->validateTypeSpecificFields($dto);
    }

    /**
     * Валидация полей специфичных для типа бронирования
     */
    protected function validateTypeSpecificFields(CreateBookingDTO $dto): void
    {
        switch ($dto->type) {
            case BookingType::OUTCALL:
                if (empty($dto->client_address)) {
                    throw new \InvalidArgumentException('Для выезда необходимо указать адрес клиента');
                }
                if (empty($dto->client_phone)) {
                    throw new \InvalidArgumentException('Для выезда необходимо указать телефон клиента');
                }
                break;

            case BookingType::INCALL:
                if (empty($dto->master_address)) {
                    throw new \InvalidArgumentException('Для приема в салоне необходимо указать адрес мастера');
                }
                break;

            case BookingType::ONLINE:
                if (empty($dto->platform)) {
                    throw new \InvalidArgumentException('Для онлайн консультации необходимо указать платформу');
                }
                break;

            case BookingType::PACKAGE:
                if (empty($dto->services_list)) {
                    throw new \InvalidArgumentException('Для пакета услуг необходимо указать список услуг');
                }
                break;
        }
    }

    /**
     * Проверка доступности временного слота
     */
    protected function validateTimeSlotAvailability(CreateBookingDTO $dto): void
    {
        $endTime = $dto->start_time->copy()->addMinutes($dto->duration_minutes);

        // Проверяем пересечения с существующими бронированиями
        $overlapping = $this->bookingRepository->findOverlapping(
            $dto->start_time,
            $endTime,
            $dto->master_id
        );

        if ($overlapping->isNotEmpty()) {
            $conflictBooking = $overlapping->first();
            throw new \InvalidArgumentException(
                "Время занято другим бронированием #{$conflictBooking->booking_number} " .
                "({$conflictBooking->start_time->format('d.m.Y H:i')} - {$conflictBooking->end_time->format('H:i')})"
            );
        }

        // Проверяем рабочее время мастера (если есть расписание)
        $this->validateMasterWorkingHours($dto);
    }

    /**
     * Проверка рабочего времени мастера
     */
    protected function validateMasterWorkingHours(CreateBookingDTO $dto): void
    {
        $master = User::find($dto->master_id);
        if (!$master || !$master->masterProfile) {
            return; // Если нет профиля мастера, пропускаем проверку
        }

        $dayOfWeek = $dto->start_time->dayOfWeek;
        $schedule = $master->masterProfile->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->first();

        if (!$schedule) {
            throw new \InvalidArgumentException(
                'Мастер не работает в ' . $dto->start_time->locale('ru')->dayName
            );
        }

        $workStart = Carbon::parse($dto->start_time->format('Y-m-d') . ' ' . $schedule->start_time);
        $workEnd = Carbon::parse($dto->start_time->format('Y-m-d') . ' ' . $schedule->end_time);
        $bookingEnd = $dto->start_time->copy()->addMinutes($dto->duration_minutes);

        if ($dto->start_time->lt($workStart) || $bookingEnd->gt($workEnd)) {
            throw new \InvalidArgumentException(
                "Бронирование выходит за рабочее время мастера ({$workStart->format('H:i')} - {$workEnd->format('H:i')})"
            );
        }

        // Проверяем перерыв
        if ($schedule->break_start && $schedule->break_end) {
            $breakStart = Carbon::parse($dto->start_time->format('Y-m-d') . ' ' . $schedule->break_start);
            $breakEnd = Carbon::parse($dto->start_time->format('Y-m-d') . ' ' . $schedule->break_end);

            if (($dto->start_time->gte($breakStart) && $dto->start_time->lt($breakEnd)) ||
                ($bookingEnd->gt($breakStart) && $bookingEnd->lte($breakEnd)) ||
                ($dto->start_time->lt($breakStart) && $bookingEnd->gt($breakEnd))) {
                throw new \InvalidArgumentException(
                    "Бронирование пересекается с перерывом мастера ({$breakStart->format('H:i')} - {$breakEnd->format('H:i')})"
                );
            }
        }
    }

    /**
     * Создание записи бронирования
     */
    protected function createBookingRecord(CreateBookingDTO $dto): Booking
    {
        $service = Service::find($dto->service_id);
        $endTime = $dto->start_time->copy()->addMinutes($dto->duration_minutes);

        // Рассчитываем стоимость
        $pricing = $this->calculatePricing($service, $dto);

        // Создаем основную запись
        $booking = $this->bookingRepository->create([
            'booking_number' => $this->generateBookingNumber(),
            'client_id' => $dto->client_id,
            'master_id' => $dto->master_id,
            'service_id' => $dto->service_id,
            'type' => $dto->type,
            'status' => BookingStatus::default(),
            'start_time' => $dto->start_time,
            'end_time' => $endTime,
            'duration_minutes' => $dto->duration_minutes,
            'base_price' => $service->price,
            'service_price' => $pricing['service_price'],
            'delivery_fee' => $pricing['delivery_fee'],
            'total_price' => $pricing['total_price'],
            'discount_amount' => $pricing['discount_amount'],
            'deposit_amount' => $pricing['deposit_amount'],
            'client_address' => $dto->client_address,
            'master_address' => $dto->master_address,
            'client_name' => $dto->client_name,
            'client_phone' => $dto->client_phone,
            'client_email' => $dto->client_email,
            'master_phone' => $dto->master_phone,
            'notes' => $dto->notes,
            'internal_notes' => $dto->internal_notes,
            'equipment_required' => $dto->equipment_required,
            'platform' => $dto->platform,
            'meeting_link' => $dto->meeting_link,
            'metadata' => $dto->metadata,
            'source' => $dto->source ?? 'api',
        ]);

        // Создаем дополнительные услуги если есть (для пакетов)
        if ($dto->type === BookingType::PACKAGE && !empty($dto->services_list)) {
            $this->createBookingServices($booking, $dto->services_list);
        }

        // Создаем временные слоты если нужно
        if ($dto->create_slots) {
            $this->createBookingSlots($booking, $dto);
        }

        return $booking;
    }

    /**
     * Создание дополнительных услуг в пакете
     */
    protected function createBookingServices(Booking $booking, array $servicesList): void
    {
        $offset = 0;
        $sortOrder = 1;

        foreach ($servicesList as $serviceData) {
            $service = Service::find($serviceData['service_id']);
            if (!$service) continue;

            $booking->bookingServices()->create([
                'service_id' => $service->id,
                'quantity' => $serviceData['quantity'] ?? 1,
                'unit_price' => $serviceData['price'] ?? $service->price,
                'total_price' => ($serviceData['quantity'] ?? 1) * ($serviceData['price'] ?? $service->price),
                'duration_minutes' => $serviceData['duration'] ?? $service->duration_minutes,
                'start_offset_minutes' => $offset,
                'notes' => $serviceData['notes'] ?? null,
                'sort_order' => $sortOrder++,
            ]);

            $offset += $serviceData['duration'] ?? $service->duration_minutes ?? 60;
        }
    }

    /**
     * Создание временных слотов
     */
    protected function createBookingSlots(Booking $booking, CreateBookingDTO $dto): void
    {
        // Основной слот услуги
        $booking->slots()->create([
            'master_id' => $dto->master_id,
            'start_time' => $dto->start_time,
            'end_time' => $dto->start_time->copy()->addMinutes($dto->duration_minutes),
            'duration_minutes' => $dto->duration_minutes,
            'is_blocked' => false,
            'is_break' => false,
            'is_preparation' => false,
            'notes' => 'Основная услуга: ' . Service::find($dto->service_id)->name,
        ]);

        // Слот подготовки (если нужен)
        if ($dto->type->requiresEquipmentConfirmation()) {
            $prepStart = $dto->start_time->copy()->subMinutes(15);
            $booking->slots()->create([
                'master_id' => $dto->master_id,
                'start_time' => $prepStart,
                'end_time' => $dto->start_time,
                'duration_minutes' => 15,
                'is_blocked' => true,
                'is_break' => false,
                'is_preparation' => true,
                'notes' => 'Подготовка оборудования',
            ]);
        }
    }

    /**
     * Расчет стоимости бронирования
     */
    protected function calculatePricing(Service $service, CreateBookingDTO $dto): array
    {
        $servicePrice = $service->price;
        $deliveryFee = 0;
        $depositAmount = 0;

        // Плата за выезд
        if ($dto->type->hasDeliveryFee()) {
            $deliveryFee = $dto->delivery_fee ?? 500;
        }

        // Предоплата
        if ($dto->type->supportsPrepayment()) {
            $depositAmount = $servicePrice * 0.3; // 30% предоплата
        }

        // Расчет скидок
        $discountAmount = $this->calculateDiscount($service, $dto);

        $totalPrice = $servicePrice + $deliveryFee - $discountAmount;

        return [
            'service_price' => $servicePrice,
            'delivery_fee' => $deliveryFee,
            'discount_amount' => $discountAmount,
            'deposit_amount' => $depositAmount,
            'total_price' => max(0, $totalPrice),
        ];
    }

    /**
     * Расчет скидки
     */
    protected function calculateDiscount(Service $service, CreateBookingDTO $dto): float
    {
        $discount = 0;

        // Скидка для новых клиентов
        if ($this->isFirstBooking($dto->client_id)) {
            $discount += $service->price * 0.1; // 10%
        }

        // Скидки по типу
        switch ($dto->type) {
            case BookingType::INCALL:
                $discount += 200; // Фиксированная скидка за посещение салона
                break;
            case BookingType::ONLINE:
                $discount += $service->price * 0.05; // 5% за онлайн
                break;
            case BookingType::PACKAGE:
                $discount += $service->price * 0.15; // 15% за пакет
                break;
        }

        // Скидка в будние дни
        if ($dto->start_time->isWeekday()) {
            $discount += $service->price * 0.05; // 5%
        }

        // Ограничиваем максимальную скидку
        return min($discount, $service->price * 0.4); // Максимум 40%
    }

    /**
     * Проверка первого бронирования клиента
     */
    protected function isFirstBooking(int $clientId): bool
    {
        return !$this->bookingRepository->optimizeQuery()
                    ->where('client_id', $clientId)
                    ->exists();
    }

    /**
     * Генерация уникального номера бронирования
     */
    protected function generateBookingNumber(): string
    {
        do {
            $number = 'BK' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while ($this->bookingRepository->findByNumber($number));

        return $number;
    }

    /**
     * Отправка уведомлений
     */
    protected function sendNotifications(Booking $booking): void
    {
        try {
            // Уведомление мастеру о новом бронировании
            $this->notificationService->sendBookingCreated($booking);

            // Подтверждение клиенту
            $this->notificationService->sendBookingConfirmationToClient($booking);

            Log::info('Booking notifications sent', ['booking_id' => $booking->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send booking notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
            // Не прерываем процесс из-за ошибки уведомлений
        }
    }
}