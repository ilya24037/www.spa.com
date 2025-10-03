<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Booking\Enums\BookingStatus;
use App\Enums\BookingType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Единый сервис валидации для всех операций бронирования
 * Объединяет логику из BookingValidator, ValidationService, CancellationValidationService,
 * BookingCompletionValidationService и RescheduleValidator
 */
class BookingValidationService
{
    /**
     * Валидация создания нового бронирования
     */
    public function validateCreate(array $data): void
    {
        // Базовая Laravel валидация
        $validator = Validator::make($data, [
            'master_id' => 'required|integer|exists:master_profiles,id',
            'service_id' => 'required|integer|exists:services,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'nullable|date|after:start_time',
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|regex:/^(\+7|8)?[0-9]{10}$/',
            'client_email' => 'nullable|email',
            'client_comment' => 'nullable|string|max:500',
            'total_price' => 'nullable|numeric|min:0',
            'prepayment' => 'nullable|numeric|min:0',
            'type' => 'nullable|string'
        ], [
            'master_id.required' => 'Мастер обязателен',
            'master_id.exists' => 'Выбранный мастер не найден',
            'service_id.required' => 'Услуга обязательна',
            'service_id.exists' => 'Выбранная услуга не найдена',
            'start_time.required' => 'Время начала обязательно',
            'start_time.after' => 'Время начала должно быть в будущем',
            'end_time.after' => 'Время окончания должно быть позже времени начала',
            'client_name.required' => 'Имя клиента обязательно',
            'client_phone.required' => 'Телефон клиента обязателен',
            'client_phone.regex' => 'Неверный формат телефона',
            'client_email.email' => 'Неверный формат email',
            'total_price.min' => 'Цена не может быть отрицательной',
            'prepayment.min' => 'Предоплата не может быть отрицательной'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Дополнительная бизнес-валидация
        $this->validateBusinessRules($data);

        // Валидация типа бронирования, если указан
        if (!empty($data['type'])) {
            $this->validateBookingType($data);
        }
    }

    /**
     * Валидация отмены бронирования
     */
    public function validateCancellation(Booking $booking, User $user): void
    {
        // Проверка прав доступа
        if (!$this->canUserCancel($booking, $user)) {
            throw ValidationException::withMessages([
                'permission' => 'У вас нет прав для отмены этого бронирования'
            ]);
        }

        // Проверка статуса
        if (!in_array($booking->status, [BookingStatus::PENDING->value, BookingStatus::CONFIRMED->value])) {
            throw ValidationException::withMessages([
                'status' => 'Невозможно отменить бронирование в статусе: ' . $booking->status
            ]);
        }

        // Проверка времени до начала (минимум за 2 часа для обычных пользователей)
        if (!$user->isAdmin()) {
            $hoursBeforeStart = Carbon::now()->diffInHours($booking->start_at, false);
            if ($hoursBeforeStart < 2) {
                throw ValidationException::withMessages([
                    'time' => 'Отмена возможна не позднее чем за 2 часа до начала'
                ]);
            }
        }
    }

    /**
     * Валидация завершения бронирования
     */
    public function validateCompletion(Booking $booking): void
    {
        // Проверка статуса
        if ($booking->status !== BookingStatus::CONFIRMED->value) {
            throw ValidationException::withMessages([
                'status' => 'Можно завершить только подтвержденное бронирование'
            ]);
        }

        // Проверка времени
        if (Carbon::parse($booking->end_at)->isFuture()) {
            throw ValidationException::withMessages([
                'time' => 'Бронирование еще не закончилось'
            ]);
        }

        // Проверка оплаты если требуется
        if ($booking->requires_payment && !$booking->payment_id) {
            throw ValidationException::withMessages([
                'payment' => 'Бронирование не оплачено'
            ]);
        }
    }

    /**
     * Валидация переноса бронирования
     */
    public function validateReschedule(Booking $booking, Carbon $newDateTime, User $user): void
    {
        // Проверка прав доступа
        if (!$this->canUserReschedule($booking, $user)) {
            throw ValidationException::withMessages([
                'permission' => 'У вас нет прав для переноса этого бронирования'
            ]);
        }

        // Проверка статуса
        if (!in_array($booking->status, [BookingStatus::PENDING->value, BookingStatus::CONFIRMED->value])) {
            throw ValidationException::withMessages([
                'status' => 'Невозможно перенести бронирование в статусе: ' . $booking->status
            ]);
        }

        // Проверка минимального времени до переноса (за 4 часа)
        $hoursBeforeStart = Carbon::now()->diffInHours($booking->start_at, false);
        if ($hoursBeforeStart < 4 && !$user->isAdmin()) {
            throw ValidationException::withMessages([
                'time' => 'Перенос возможен не позднее чем за 4 часа до начала'
            ]);
        }

        // Проверка что новое время в будущем
        if ($newDateTime->isPast()) {
            throw ValidationException::withMessages([
                'date' => 'Новая дата должна быть в будущем'
            ]);
        }

        // Проверка что новое время отличается от старого
        if ($newDateTime->equalTo($booking->start_at)) {
            throw ValidationException::withMessages([
                'date' => 'Новая дата совпадает с текущей'
            ]);
        }

        // Проверка максимального количества переносов
        if ($booking->reschedule_count >= 2 && !$user->isAdmin()) {
            throw ValidationException::withMessages([
                'limit' => 'Достигнут лимит переносов (максимум 2 раза)'
            ]);
        }
    }

    /**
     * Валидация подтверждения бронирования
     */
    public function validateConfirmation(Booking $booking): void
    {
        // Проверка статуса
        if ($booking->status !== BookingStatus::PENDING->value) {
            throw ValidationException::withMessages([
                'status' => 'Можно подтвердить только бронирование в статусе "ожидание"'
            ]);
        }

        // Проверка времени
        if (Carbon::parse($booking->start_at)->isPast()) {
            throw ValidationException::withMessages([
                'time' => 'Невозможно подтвердить прошедшее бронирование'
            ]);
        }
    }

    /**
     * Дополнительные бизнес-правила
     */
    private function validateBusinessRules(array $data): void
    {
        // Проверка рабочего времени мастера
        $master = MasterProfile::find($data['master_id']);
        $startTime = Carbon::parse($data['start_time']);
        $dayOfWeek = $startTime->dayOfWeek;
        
        $schedule = $master->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->first();
            
        if (!$schedule || !$schedule->is_working) {
            throw ValidationException::withMessages([
                'date' => 'Мастер не работает в выбранный день'
            ]);
        }

        // Проверка времени работы
        $timeOnly = $startTime->format('H:i:s');
        if ($timeOnly < $schedule->start_time || $timeOnly > $schedule->end_time) {
            throw ValidationException::withMessages([
                'time' => sprintf(
                    'Выбранное время вне рабочих часов мастера (%s - %s)',
                    $schedule->start_time,
                    $schedule->end_time
                )
            ]);
        }

        // Проверка минимальной длительности
        if (isset($data['end_time'])) {
            $duration = Carbon::parse($data['start_time'])->diffInMinutes(Carbon::parse($data['end_time']));
            if ($duration < 30) {
                throw ValidationException::withMessages([
                    'duration' => 'Минимальная длительность бронирования - 30 минут'
                ]);
            }
            if ($duration > 480) {
                throw ValidationException::withMessages([
                    'duration' => 'Максимальная длительность бронирования - 8 часов'
                ]);
            }
        }

        // Проверка предоплаты не больше общей суммы
        if (isset($data['prepayment']) && isset($data['total_price'])) {
            if ($data['prepayment'] > $data['total_price']) {
                throw ValidationException::withMessages([
                    'prepayment' => 'Предоплата не может быть больше общей суммы'
                ]);
            }
        }
    }

    /**
     * Валидация типа бронирования
     */
    private function validateBookingType(array $data): void
    {
        try {
            $type = BookingType::from($data['type']);
            
            // Получаем обязательные поля для типа
            $requiredFields = match($type) {
                BookingType::ONLINE => ['meeting_link'],
                BookingType::OUTCALL => ['client_address'],
                BookingType::INCALL => [],
                default => []
            };

            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw ValidationException::withMessages([
                        $field => "Для типа бронирования '{$type->value}' поле '{$field}' обязательно"
                    ]);
                }
            }
        } catch (\ValueError $e) {
            throw ValidationException::withMessages([
                'type' => 'Недопустимый тип бронирования'
            ]);
        }
    }

    /**
     * Проверка прав пользователя на отмену
     */
    private function canUserCancel(Booking $booking, User $user): bool
    {
        // Админы могут отменять любые бронирования
        if ($user->isAdmin()) {
            return true;
        }

        // Мастер может отменять свои бронирования
        if ($booking->master->user_id === $user->id) {
            return true;
        }

        // Клиент может отменять свои бронирования
        if ($booking->client_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Проверка прав пользователя на перенос
     */
    private function canUserReschedule(Booking $booking, User $user): bool
    {
        // Админы могут переносить любые бронирования
        if ($user->isAdmin()) {
            return true;
        }

        // Мастер может переносить свои бронирования
        if ($booking->master->user_id === $user->id) {
            return true;
        }

        // Клиент может переносить свои бронирования
        if ($booking->client_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Валидация телефонного номера
     */
    private function validatePhoneNumber(string $phone): bool
    {
        // Удаляем все нецифровые символы
        $phone = preg_replace('/\D/', '', $phone);
        
        // Проверяем длину (10 или 11 цифр для РФ)
        if (strlen($phone) === 10) {
            return true;
        }
        
        if (strlen($phone) === 11 && ($phone[0] === '7' || $phone[0] === '8')) {
            return true;
        }
        
        return false;
    }

    /**
     * Проверить возможность отмены бронирования
     */
    public function canCancelBooking(Booking $booking): bool
    {
        // Нельзя отменить уже отмененное или завершенное
        if (in_array($booking->status, [BookingStatus::CANCELLED, BookingStatus::COMPLETED])) {
            return false;
        }

        // Можно отменить за 2 часа до начала
        $startDateTime = Carbon::parse($booking->booking_date . ' ' . $booking->start_time);
        $hoursBeforeStart = Carbon::now()->diffInHours($startDateTime, false);
        
        return $hoursBeforeStart >= 2;
    }

    /**
     * Валидация временного слота
     */
    public function validateTimeSlot(
        int $masterId,
        string $bookingDate,
        string $bookingTime,
        int $serviceId
    ): void {
        $startDateTime = Carbon::parse($bookingDate . ' ' . $bookingTime);
        
        if ($startDateTime->isPast()) {
            throw new \InvalidArgumentException('Нельзя забронировать на прошедшее время');
        }

        if ($startDateTime->diffInDays(Carbon::now()) > 30) {
            throw new \InvalidArgumentException('Бронирование возможно не более чем на 30 дней вперед');
        }
    }

    /**
     * Валидация доступности временного слота с учетом типа
     */
    public function validateTimeSlotAvailability(array $data, BookingType $type): void
    {
        $bookingDate = $data['booking_date'] ?? Carbon::parse($data['start_time'] ?? 'now')->format('Y-m-d');
        $bookingTime = $data['booking_time'] ?? Carbon::parse($data['start_time'] ?? 'now')->format('H:i');
        $masterId = $data['master_id'] ?? 0;

        $this->validateTimeSlot($masterId, $bookingDate, $bookingTime, $data['service_id'] ?? 0);
        
        // Дополнительная валидация в зависимости от типа
        if ($type === BookingType::OUTCALL) {
            if (empty($data['address'])) {
                throw new \InvalidArgumentException('Для выездного обслуживания требуется адрес');
            }
        }
    }
}