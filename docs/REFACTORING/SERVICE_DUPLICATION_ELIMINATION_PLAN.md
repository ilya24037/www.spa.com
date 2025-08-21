# 🚀 ПЛАН УСТРАНЕНИЯ ДУБЛИРОВАНИЯ СЕРВИСОВ

## 📊 АНАЛИЗ ТЕКУЩЕЙ СИТУАЦИИ

### Общая статистика дублирования:
- **Всего сервисов:** 76 файлов
- **После рефакторинга:** 23 файла
- **Сокращение:** 70% (53 файла)
- **Время выполнения:** 3 рабочих дня (24 часа)

### Проблемные домены:

#### 📦 Domain/Booking/Services (26 сервисов)
**Проблемы:**
- 5 валидаторов для одной логики
- 4 сервиса уведомлений
- 4 сервиса для работы со слотами
- Дублирование логики отмены/переноса

#### 💳 Domain/Payment/Services (30 сервисов)
**Проблемы:**
- 5 фильтр-сервисов (можно объединить в 1)
- 4 refund-сервиса
- По 4 файла на каждый платежный шлюз
- Дублирование процессоров

#### 🔎 Domain/Search/Services (20 сервисов)
**Проблемы:**
- 8 мелких handlers в отдельной папке
- 6 сервисов для фильтров
- Избыточная грануляция

---

## 📅 ПЛАН РЕФАКТОРИНГА ПО ДНЯМ

### 🗓️ ДЕНЬ 1: Domain/Booking - Устранение дублирования
**Время:** 8 часов  
**Цель:** 26 → 7 сервисов (-73%)

#### Итоговая структура:
```
Domain/Booking/Services/
├── BookingService.php           # Главный координатор
├── BookingQueryService.php      # Все запросы
├── BookingValidationService.php # Вся валидация
├── BookingNotificationService.php # Все уведомления
├── BookingSlotService.php       # Слоты и доступность
├── BookingPaymentService.php    # Интеграция с платежами
└── BookingStatisticsService.php # Аналитика
```

### 🗓️ ДЕНЬ 2: Domain/Payment - Оптимизация
**Время:** 8 часов  
**Цель:** 30 → 9 сервисов (-70%)

#### Итоговая структура:
```
Domain/Payment/Services/
├── PaymentService.php           # Главный сервис
├── PaymentProcessorService.php  # Обработка
├── PaymentFilterService.php     # Все фильтры
├── RefundService.php           # Все возвраты
├── TransactionService.php      # Транзакции
├── SubscriptionService.php     # Подписки
└── Gateways/
    ├── StripeGateway.php       # Весь Stripe
    ├── YooKassaGateway.php     # Вся YooKassa
    └── SbpGateway.php          # СБП
```

### 🗓️ ДЕНЬ 3: Domain/Search - Упрощение
**Время:** 8 часов  
**Цель:** 20 → 7 сервисов (-65%)

#### Итоговая структура:
```
Domain/Search/Services/
├── SearchService.php            # Координатор
├── ElasticsearchEngine.php     # ES движок
├── DatabaseSearchEngine.php    # БД движок
├── SearchFilterService.php     # Все фильтры
├── SearchResultService.php     # Результаты
├── SearchAnalyticsService.php  # Аналитика
└── RecommendationEngine.php    # Рекомендации
```

---

## 🔧 ДЕТАЛЬНЫЙ ПЛАН: ДЕНЬ 1 - BOOKING

### ⏰ 09:00-10:00 - Подготовка (1 час)

#### 1. Создание бэкапа:
```powershell
# PowerShell команды
$date = Get-Date -Format "yyyyMMdd_HHmm"
$backupPath = "C:\Backup\booking_services_$date"

# Копируем текущие сервисы
Copy-Item -Path "C:\www.spa.com\app\Domain\Booking\Services" -Destination $backupPath -Recurse

# Создаем ветку для рефакторинга
cd C:\www.spa.com
git checkout -b refactor/booking-services-deduplication
git add -A
git commit -m "backup: before Booking services refactoring"
```

#### 2. Анализ зависимостей:
```powershell
# Проверяем использование сервисов
Get-ChildItem -Path "C:\www.spa.com" -Recurse -Filter "*.php" | 
    Select-String -Pattern "BookingValidator|ValidationService|CancellationValidation" | 
    Group-Object Path | 
    Select-Object Name, Count
```

### ⏰ 10:00-12:00 - Объединение валидаторов (2 часа)

#### Шаг 1: Создаем единый валидатор

```php
<?php
// app/Domain/Booking/Services/BookingValidationService.php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use App\Domain\Master\Models\MasterProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Единый сервис валидации для всех операций бронирования
 */
class BookingValidationService
{
    /**
     * Валидация создания нового бронирования
     */
    public function validateCreate(array $data): void
    {
        $validator = Validator::make($data, [
            'master_id' => 'required|exists:master_profiles,id',
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date|after:today',
            'time' => 'required|date_format:H:i',
            'duration' => 'required|integer|min:30|max:480',
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|regex:/^\+7[0-9]{10}$/',
            'client_comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Дополнительная бизнес-валидация
        $this->validateBusinessRules($data);
    }

    /**
     * Валидация отмены бронирования
     */
    public function validateCancellation(Booking $booking, User $user): void
    {
        // Проверка прав доступа
        if ($booking->user_id !== $user->id && !$user->isAdmin()) {
            throw ValidationException::withMessages([
                'user' => 'У вас нет прав для отмены этого бронирования'
            ]);
        }

        // Проверка времени до начала
        $hoursBeforeStart = Carbon::now()->diffInHours($booking->start_at, false);
        if ($hoursBeforeStart < 2 && !$user->isAdmin()) {
            throw ValidationException::withMessages([
                'time' => 'Отмена возможна не позднее чем за 2 часа до начала'
            ]);
        }

        // Проверка статуса
        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            throw ValidationException::withMessages([
                'status' => 'Невозможно отменить бронирование в статусе: ' . $booking->status
            ]);
        }
    }

    /**
     * Валидация завершения бронирования
     */
    public function validateCompletion(Booking $booking): void
    {
        if ($booking->status !== 'confirmed') {
            throw ValidationException::withMessages([
                'status' => 'Можно завершить только подтвержденное бронирование'
            ]);
        }

        if ($booking->end_at->isFuture()) {
            throw ValidationException::withMessages([
                'time' => 'Бронирование еще не закончилось'
            ]);
        }
    }

    /**
     * Валидация переноса бронирования
     */
    public function validateReschedule(Booking $booking, Carbon $newDateTime): void
    {
        // Проверка минимального времени до переноса
        $hoursBeforeStart = Carbon::now()->diffInHours($booking->start_at, false);
        if ($hoursBeforeStart < 4) {
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
    }

    /**
     * Дополнительные бизнес-правила
     */
    private function validateBusinessRules(array $data): void
    {
        // Проверка рабочего времени мастера
        $master = MasterProfile::find($data['master_id']);
        $dayOfWeek = Carbon::parse($data['date'])->dayOfWeek;
        
        $schedule = $master->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->first();
            
        if (!$schedule || !$schedule->is_working) {
            throw ValidationException::withMessages([
                'date' => 'Мастер не работает в выбранный день'
            ]);
        }

        // Проверка времени работы
        $requestedTime = Carbon::parse($data['time']);
        $startTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);
        
        if ($requestedTime->lt($startTime) || $requestedTime->gt($endTime)) {
            throw ValidationException::withMessages([
                'time' => 'Выбранное время вне рабочих часов мастера'
            ]);
        }
    }
}
```

#### Шаг 2: Обновляем BookingService

```powershell
# Открываем файл для редактирования
notepad C:\www.spa.com\app\Domain\Booking\Services\BookingService.php
```

Заменяем старые вызовы:
```php
// БЫЛО:
$this->bookingValidator->validate($data);
$this->validationService->validateBookingData($data);
$this->cancellationValidation->validate($booking);

// СТАЛО:
$this->validationService->validateCreate($data);
$this->validationService->validateCancellation($booking, $user);
```

### ⏰ 12:00-14:00 - Обеденный перерыв

### ⏰ 14:00-16:00 - Объединение уведомлений (2 часа)

#### Создаем единый сервис уведомлений:

```php
<?php
// app/Domain/Booking/Services/BookingNotificationService.php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Notification\Services\NotificationService;
use Carbon\Carbon;

/**
 * Единый сервис уведомлений для бронирований
 */
class BookingNotificationService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Уведомление о создании бронирования
     */
    public function sendCreatedNotification(Booking $booking): void
    {
        // Уведомление клиенту
        $this->notificationService->send(
            $booking->user,
            'booking.created',
            [
                'booking_number' => $booking->number,
                'master_name' => $booking->master->name,
                'date' => $booking->start_at->format('d.m.Y'),
                'time' => $booking->start_at->format('H:i'),
                'service' => $booking->service->name,
            ]
        );

        // Уведомление мастеру
        $this->notificationService->send(
            $booking->master->user,
            'booking.new_for_master',
            [
                'client_name' => $booking->user->name,
                'date' => $booking->start_at->format('d.m.Y'),
                'time' => $booking->start_at->format('H:i'),
                'service' => $booking->service->name,
            ]
        );
    }

    /**
     * Напоминание о бронировании
     */
    public function sendReminderNotification(Booking $booking): void
    {
        $hoursBeforeStart = Carbon::now()->diffInHours($booking->start_at, false);
        
        // Напоминание за день
        if ($hoursBeforeStart >= 23 && $hoursBeforeStart <= 25) {
            $this->notificationService->send(
                $booking->user,
                'booking.reminder_day_before',
                [
                    'master_name' => $booking->master->name,
                    'time' => $booking->start_at->format('H:i'),
                ]
            );
        }
        
        // Напоминание за 2 часа
        if ($hoursBeforeStart >= 1.5 && $hoursBeforeStart <= 2.5) {
            $this->notificationService->send(
                $booking->user,
                'booking.reminder_2hours',
                [
                    'master_name' => $booking->master->name,
                    'address' => $booking->master->address,
                ]
            );
        }
    }

    /**
     * Уведомление о переносе
     */
    public function sendRescheduleNotification(Booking $booking, Carbon $oldDateTime): void
    {
        // Клиенту
        $this->notificationService->send(
            $booking->user,
            'booking.rescheduled',
            [
                'old_date' => $oldDateTime->format('d.m.Y H:i'),
                'new_date' => $booking->start_at->format('d.m.Y H:i'),
                'master_name' => $booking->master->name,
            ]
        );

        // Мастеру
        $this->notificationService->send(
            $booking->master->user,
            'booking.rescheduled_for_master',
            [
                'client_name' => $booking->user->name,
                'old_date' => $oldDateTime->format('d.m.Y H:i'),
                'new_date' => $booking->start_at->format('d.m.Y H:i'),
            ]
        );
    }

    /**
     * Уведомление об отмене
     */
    public function sendCancellationNotification(Booking $booking, string $reason = null): void
    {
        // Клиенту
        $this->notificationService->send(
            $booking->user,
            'booking.cancelled',
            [
                'booking_number' => $booking->number,
                'reason' => $reason,
                'refund_amount' => $booking->calculateRefundAmount(),
            ]
        );

        // Мастеру
        $this->notificationService->send(
            $booking->master->user,
            'booking.cancelled_for_master',
            [
                'client_name' => $booking->user->name,
                'date' => $booking->start_at->format('d.m.Y H:i'),
                'reason' => $reason,
            ]
        );
    }

    /**
     * Уведомление о подтверждении
     */
    public function sendConfirmationNotification(Booking $booking): void
    {
        $this->notificationService->send(
            $booking->user,
            'booking.confirmed',
            [
                'booking_number' => $booking->number,
                'master_name' => $booking->master->name,
                'date' => $booking->start_at->format('d.m.Y'),
                'time' => $booking->start_at->format('H:i'),
            ]
        );
    }
}
```

### ⏰ 16:00-18:00 - Объединение слотов (2 часа)

#### Создаем единый сервис для слотов:

```php
<?php
// app/Domain/Booking/Services/BookingSlotService.php

namespace App\Domain\Booking\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Models\BookingSlot;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Единый сервис для работы со слотами и доступностью
 */
class BookingSlotService
{
    /**
     * Проверка доступности слота
     */
    public function isSlotAvailable(int $masterId, Carbon $dateTime, int $duration = 60): bool
    {
        // Проверяем рабочее время мастера
        if (!$this->isMasterWorking($masterId, $dateTime)) {
            return false;
        }

        // Проверяем нет ли пересечений с другими бронированиями
        $endTime = $dateTime->copy()->addMinutes($duration);
        
        $hasConflict = Booking::where('master_id', $masterId)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where(function ($query) use ($dateTime, $endTime) {
                $query->whereBetween('start_at', [$dateTime, $endTime])
                    ->orWhereBetween('end_at', [$dateTime, $endTime])
                    ->orWhere(function ($q) use ($dateTime, $endTime) {
                        $q->where('start_at', '<=', $dateTime)
                          ->where('end_at', '>=', $endTime);
                    });
            })
            ->exists();

        return !$hasConflict;
    }

    /**
     * Получение доступных слотов на день
     */
    public function getAvailableSlots(int $masterId, Carbon $date): Collection
    {
        $master = MasterProfile::findOrFail($masterId);
        $dayOfWeek = $date->dayOfWeek;
        
        // Получаем расписание мастера на этот день
        $schedule = $master->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->first();
            
        if (!$schedule || !$schedule->is_working) {
            return collect();
        }

        // Генерируем слоты с интервалом 30 минут
        $slots = collect();
        $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
        $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);
        
        $period = CarbonPeriod::create($startTime, '30 minutes', $endTime);
        
        foreach ($period as $slotTime) {
            // Пропускаем прошедшее время
            if ($slotTime->isPast()) {
                continue;
            }
            
            // Проверяем доступность
            if ($this->isSlotAvailable($masterId, $slotTime)) {
                $slots->push([
                    'time' => $slotTime->format('H:i'),
                    'datetime' => $slotTime->toIso8601String(),
                    'available' => true,
                ]);
            }
        }

        return $slots;
    }

    /**
     * Резервирование слота
     */
    public function reserveSlot(int $masterId, Carbon $dateTime, int $duration, int $bookingId): BookingSlot
    {
        // Проверяем доступность
        if (!$this->isSlotAvailable($masterId, $dateTime, $duration)) {
            throw new \Exception('Слот недоступен для бронирования');
        }

        // Создаем запись о слоте
        return BookingSlot::create([
            'booking_id' => $bookingId,
            'master_id' => $masterId,
            'start_at' => $dateTime,
            'end_at' => $dateTime->copy()->addMinutes($duration),
            'status' => 'reserved',
        ]);
    }

    /**
     * Освобождение слота
     */
    public function releaseSlot(int $bookingId): void
    {
        BookingSlot::where('booking_id', $bookingId)
            ->update(['status' => 'released']);
    }

    /**
     * Получение занятых слотов
     */
    public function getBookedSlots(int $masterId, Carbon $date): Collection
    {
        return Booking::where('master_id', $masterId)
            ->whereDate('start_at', $date)
            ->whereIn('status', ['confirmed', 'pending'])
            ->get()
            ->map(function ($booking) {
                return [
                    'start' => $booking->start_at->format('H:i'),
                    'end' => $booking->end_at->format('H:i'),
                    'client' => $booking->user->name,
                    'service' => $booking->service->name,
                ];
            });
    }

    /**
     * Проверка работает ли мастер в указанное время
     */
    private function isMasterWorking(int $masterId, Carbon $dateTime): bool
    {
        $master = MasterProfile::find($masterId);
        if (!$master) {
            return false;
        }

        $dayOfWeek = $dateTime->dayOfWeek;
        $time = $dateTime->format('H:i:s');
        
        $schedule = $master->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working', true)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time)
            ->first();
            
        return $schedule !== null;
    }

    /**
     * Получение следующего доступного слота
     */
    public function getNextAvailableSlot(int $masterId, Carbon $fromDate = null): ?array
    {
        $fromDate = $fromDate ?? Carbon::now();
        
        // Ищем в течение следующих 30 дней
        for ($i = 0; $i < 30; $i++) {
            $date = $fromDate->copy()->addDays($i);
            $slots = $this->getAvailableSlots($masterId, $date);
            
            if ($slots->isNotEmpty()) {
                return [
                    'date' => $date->format('Y-m-d'),
                    'time' => $slots->first()['time'],
                    'datetime' => $slots->first()['datetime'],
                ];
            }
        }
        
        return null;
    }
}
```

### ⏰ 18:00-19:00 - Тестирование и очистка (1 час)

#### Тестирование изменений:

```powershell
# Запускаем тесты
cd C:\www.spa.com
C:\Users\user1\.config\herd\bin\php.bat artisan test tests/Unit/Domain/Booking/

# Если тесты проходят, удаляем старые файлы
$oldFiles = @(
    "BookingValidator.php",
    "ValidationService.php",
    "CancellationValidationService.php",
    "BookingCompletionValidationService.php",
    "RescheduleValidator.php",
    "NotificationService.php",
    "BookingReminderService.php",
    "RescheduleNotificationHandler.php",
    "AvailabilityChecker.php",
    "AvailabilityService.php",
    "SlotService.php"
)

foreach ($file in $oldFiles) {
    $path = "C:\www.spa.com\app\Domain\Booking\Services\$file"
    if (Test-Path $path) {
        Remove-Item $path -Force
        Write-Host "Удален: $file" -ForegroundColor Green
    }
}

# Проверяем что осталось
Get-ChildItem C:\www.spa.com\app\Domain\Booking\Services\*.php | Select-Object Name
```

#### Коммит изменений:

```powershell
git add -A
git commit -m "refactor(booking): eliminate service duplication

- Объединены 5 валидаторов в BookingValidationService
- Объединены 4 сервиса уведомлений в BookingNotificationService  
- Объединены 4 сервиса слотов в BookingSlotService
- Сокращение с 26 до 7 сервисов (-73%)

BREAKING CHANGE: Изменены зависимости в BookingService"
```

---

## 🔧 ДЕТАЛЬНЫЙ ПЛАН: ДЕНЬ 2 - PAYMENT

### ⏰ 09:00-12:00 - Объединение фильтров (3 часа)

[Детальный код для Payment домена...]

---

## 🔧 ДЕТАЛЬНЫЙ ПЛАН: ДЕНЬ 3 - SEARCH

### ⏰ 09:00-12:00 - Объединение handlers (3 часа)

[Детальный код для Search домена...]

---

## ✅ ФИНАЛЬНЫЙ ЧЕК-ЛИСТ

### После каждого дня проверяем:

- [ ] Все unit тесты проходят
- [ ] Feature тесты работают
- [ ] Контроллеры используют новые сервисы
- [ ] DI контейнер обновлен в AppServiceProvider
- [ ] Старые файлы удалены
- [ ] Изменения закоммичены
- [ ] Документация обновлена

### Команды для проверки:

```powershell
# Полная проверка после рефакторинга
C:\Users\user1\.config\herd\bin\php.bat artisan test --testsuite=Unit
C:\Users\user1\.config\herd\bin\php.bat artisan test --testsuite=Feature

# Проверка покрытия кода
C:\Users\user1\.config\herd\bin\php.bat artisan test --coverage

# Очистка кеша
C:\Users\user1\.config\herd\bin\php.bat artisan optimize:clear

# Проверка автозагрузки
C:\Users\user1\.config\herd\bin\composer.bat dump-autoload
```

---

## 📊 ОЖИДАЕМЫЕ РЕЗУЛЬТАТЫ

### Метрики до/после:

| Домен | Было | Стало | Сокращение |
|-------|------|-------|------------|
| Booking | 26 | 7 | -73% |
| Payment | 30 | 9 | -70% |
| Search | 20 | 7 | -65% |
| **ИТОГО** | **76** | **23** | **-70%** |

### Преимущества:

1. **Упрощение поддержки** - в 3 раза меньше файлов
2. **Ясность архитектуры** - понятная структура
3. **Быстрая навигация** - легко найти нужный код
4. **Снижение багов** - нет дублирования логики
5. **Ускорение разработки** - +200% к скорости

### Риски и их минимизация:

| Риск | Решение |
|------|---------|
| Поломка функционала | Тестирование после каждого шага |
| Потеря кода | Бэкапы перед началом |
| Конфликты в git | Работа в отдельной ветке |
| Проблемы с DI | Обновление AppServiceProvider |

---

## 🚀 КОМАНДА ДЛЯ СТАРТА

```powershell
# Начинаем рефакторинг!
cd C:\www.spa.com
git checkout -b refactor/service-deduplication
Write-Host "🚀 Рефакторинг начат! Следуйте плану день за днем." -ForegroundColor Green
```

---

*План создан на основе анализа текущего кода и соответствует общему плану рефакторинга из complete-refactoring-plan.md (дни 7-9)*