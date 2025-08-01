<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use App\Enums\BookingType;
use App\Enums\BookingStatus;
use Carbon\Carbon;

/**
 * Сервис валидации данных бронирования
 * Отвечает за все проверки входных данных и прав доступа
 */
class ValidationService
{
    /**
     * Валидация данных бронирования
     */
    public function validateBookingData(array $data): void
    {
        $required = ['master_id', 'service_id', 'start_time', 'client_name', 'client_phone'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Поле {$field} обязательно для заполнения");
            }
        }

        // Валидация телефона
        if (!$this->validatePhoneNumber($data['client_phone'])) {
            throw new \InvalidArgumentException("Неверный формат телефона");
        }

        // Валидация email если указан
        if (!empty($data['client_email']) && !filter_var($data['client_email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Неверный формат email");
        }

        // Валидация типа бронирования
        if (isset($data['type'])) {
            $type = BookingType::from($data['type']);
            $typeRequired = $type->getRequiredFields();
            
            foreach ($typeRequired as $field) {
                if (empty($data[$field])) {
                    throw new \InvalidArgumentException("Для типа {$type->getLabel()} поле {$field} обязательно");
                }
            }
        }

        // Валидация времени
        $this->validateDateTime($data['start_time']);
    }

    /**
     * Валидация прав мастера
     */
    public function validateMasterPermission(Booking $booking, User $master): void
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
    public function validateCancellationPermission(Booking $booking, User $user): void
    {
        $canCancel = $booking->client_id === $user->id || 
                    $booking->master_id === $user->id ||
                    ($booking->master_profile_id && $user->masterProfile && 
                     $booking->master_profile_id === $user->masterProfile->id);
        
        if (!$canCancel) {
            throw new \Exception('У вас нет прав для отмены этого бронирования');
        }
    }

    /**
     * Проверка возможности подтверждения
     */
    public function validateConfirmationAbility(Booking $booking): void
    {
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
    }

    /**
     * Проверка возможности завершения
     */
    public function validateCompletionAbility(Booking $booking): void
    {
        if ($booking->status instanceof BookingStatus) {
            if (!$booking->status->canTransitionTo(BookingStatus::COMPLETED)) {
                throw new \Exception('Нельзя завершить бронирование в статусе: ' . $booking->status->getLabel());
            }
        }

        // Проверяем время
        if ($booking->start_time->isFuture()) {
            throw new \Exception('Нельзя завершить услугу до её начала');
        }
    }

    /**
     * Валидация номера телефона
     */
    private function validatePhoneNumber(string $phone): bool
    {
        // Удаляем все символы кроме цифр
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Проверяем длину (10 или 11 цифр для российских номеров)
        if (strlen($phone) < 10 || strlen($phone) > 11) {
            return false;
        }
        
        // Проверяем, что начинается с 7 или 8 (для 11 цифр)
        if (strlen($phone) == 11 && !in_array($phone[0], ['7', '8'])) {
            return false;
        }
        
        return true;
    }

    /**
     * Валидация даты и времени
     */
    private function validateDateTime($datetime): void
    {
        try {
            $dt = Carbon::parse($datetime);
            
            if ($dt->isPast()) {
                throw new \InvalidArgumentException("Нельзя создать бронирование на прошедшее время");
            }
            
            // Проверяем, что время не слишком далеко в будущем (например, не более года)
            if ($dt->diffInDays(now()) > 365) {
                throw new \InvalidArgumentException("Нельзя создать бронирование более чем на год вперед");
            }
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Неверный формат даты/времени");
        }
    }

    /**
     * Валидация длительности услуги
     */
    public function validateDuration(int $duration): void
    {
        if ($duration < 15) {
            throw new \InvalidArgumentException("Минимальная длительность услуги - 15 минут");
        }
        
        if ($duration > 480) { // 8 часов
            throw new \InvalidArgumentException("Максимальная длительность услуги - 8 часов");
        }
        
        if ($duration % 15 !== 0) {
            throw new \InvalidArgumentException("Длительность должна быть кратна 15 минутам");
        }
    }

    /**
     * Валидация адреса для выездных услуг
     */
    public function validateAddress(array $data, BookingType $type): void
    {
        if ($type === BookingType::OUTCALL && empty($data['client_address'])) {
            throw new \InvalidArgumentException("Для выездной услуги необходимо указать адрес");
        }
        
        if ($type === BookingType::INCALL && empty($data['master_address'])) {
            throw new \InvalidArgumentException("Адрес мастера не указан");
        }
    }

    /**
     * Валидация данных для онлайн услуг
     */
    public function validateOnlineData(array $data): void
    {
        if (empty($data['platform'])) {
            throw new \InvalidArgumentException("Для онлайн услуги необходимо указать платформу");
        }
        
        $validPlatforms = ['zoom', 'skype', 'whatsapp', 'telegram'];
        if (!in_array($data['platform'], $validPlatforms)) {
            throw new \InvalidArgumentException("Неподдерживаемая платформа для онлайн услуг");
        }
    }
}