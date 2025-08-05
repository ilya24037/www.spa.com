# 🚀 ПЛАН ИСПОЛНЕНИЯ: Рефакторинг DDD нарушений

## 🎯 ЦЕЛЬ: Устранить циклические зависимости между доменами User ↔ Booking ↔ Master

### 📊 МАСШТАБ ПРОБЛЕМЫ:
- **48 файлов** используют междоменные связи
- **2 критических трейта** нарушают изоляцию доменов
- **3 домена** с циклическими зависимостями

---

## 📋 ЭТАП 1: События для развязки доменов (2-3 часа)

### 🎯 Создать Events для замены прямых вызовов:

#### 1.1 Booking Events
```php
// app/Domain/Booking/Events/
├── BookingRequested.php     // Создание бронирования
├── BookingStatusChanged.php // Изменение статуса  
├── BookingCancelled.php     // Отмена
└── BookingCompleted.php     // Завершение
```

#### 1.2 Master Events  
```php
// app/Domain/Master/Events/
├── MasterProfileCreated.php   // Создание профиля мастера
├── MasterProfileUpdated.php   // Обновление профиля
└── MasterStatusChanged.php    // Изменение статуса мастера
```

#### 1.3 User Events
```php
// app/Domain/User/Events/
├── UserRegistered.php       // Регистрация пользователя
├── UserRoleChanged.php      // Изменение роли
└── UserProfileUpdated.php   // Обновление профиля
```

---

## 📋 ЭТАП 2: Интерфейсы репозиториев (1-2 часа)

### 🎯 Создать контракты для развязки:

#### 2.1 Booking Interfaces
```php
// app/Domain/Booking/Contracts/
├── BookingRepositoryInterface.php
├── BookingServiceInterface.php  
└── BookingQueryInterface.php
```

#### 2.2 Master Interfaces
```php
// app/Domain/Master/Contracts/
├── MasterRepositoryInterface.php
├── MasterServiceInterface.php
└── MasterQueryInterface.php
```

#### 2.3 User Interfaces
```php
// app/Domain/User/Contracts/
├── UserRepositoryInterface.php
├── UserServiceInterface.php
└── UserQueryInterface.php
```

---

## 📋 ЭТАП 3: Integration Services (2-3 часа)

### 🎯 Создать сервисы для взаимодействия между доменами:

#### 3.1 User Integration Services
```php
// app/Application/Services/Integration/
├── UserBookingIntegrationService.php  // User ↔ Booking
├── UserMasterIntegrationService.php   // User ↔ Master  
└── DTOs/
    ├── UserBookingDTO.php
    └── UserMasterDTO.php
```

#### 3.2 Query Services (для чтения данных)
```php
// app/Application/Services/Query/
├── UserBookingQueryService.php
├── UserMasterQueryService.php
└── BookingMasterQueryService.php
```

---

## 📋 ЭТАП 4: Рефакторинг трейтов (1-2 часа)

### 🎯 Заменить прямые связи на Events и Services:

#### 4.1 HasBookings → UserBookingTrait
```php
// БЫЛО (прямая связь):
public function bookings(): HasMany 
{
    return $this->hasMany(Booking::class, 'client_id');
}

// СТАНЕТ (через сервис):
public function getBookings(): Collection
{
    return app(UserBookingIntegrationService::class)
        ->getUserBookings($this->id);
}
```

#### 4.2 HasMasterProfile → UserMasterTrait  
```php
// БЫЛО (прямая связь):
public function masterProfile(): HasOne
{
    return $this->hasOne(MasterProfile::class);
}

// СТАНЕТ (через сервис):
public function getMasterProfile(): ?MasterProfile
{
    return app(UserMasterIntegrationService::class)
        ->getUserMasterProfile($this->id);
}
```

---

## 📋 ЭТАП 5: Обновление контроллеров и сервисов (3-4 часа)

### 🎯 Заменить прямые вызовы на сервисы во всех 48 файлах:

#### 5.1 Приоритетные файлы (первая волна):
```php
// Высокий приоритет - основные контроллеры:
- BookingController.php
- ProfileController.php  
- MasterController.php
- UserService.php
- BookingService.php
```

#### 5.2 Вторая волна:
```php
// Средний приоритет - вспомогательные:
- FavoriteController.php
- CompareController.php
- ProfileItemsController.php
- MasterRepository.php
```

#### 5.3 Третья волна:
```php
// Низкий приоритет - тесты и утилиты:
- Все тестовые файлы
- Seeders
- Helper классы
```

---

## 📋 ЭТАП 6: Тестирование и валидация (1-2 часа)

### 🎯 Убедиться что всё работает:

#### 6.1 Интеграционные тесты
```php
// tests/Integration/
├── UserBookingIntegrationTest.php
├── UserMasterIntegrationTest.php
└── DomainIsolationTest.php
```

#### 6.2 Проверка циклических зависимостей
```bash
# Команда для проверки:
php artisan analyze:dependencies --check-cycles
```

#### 6.3 Валидация архитектуры
```php
// Тесты что домены изолированы:
- User домен не импортирует Booking/Master модели
- Booking домен не импортирует User/Master модели  
- Master домен не импортирует User/Booking модели
```

---

## 🕒 ВРЕМЕННАЯ ОЦЕНКА:

| Этап | Время | Приоритет |
|------|-------|-----------|
| 1. Events | 2-3 часа | 🔥 Критический |
| 2. Interfaces | 1-2 часа | 🔥 Критический |
| 3. Integration Services | 2-3 часа | 🔥 Критический |  
| 4. Рефакторинг трейтов | 1-2 часа | ⚠️ Высокий |
| 5. Контроллеры/сервисы | 3-4 часа | ⚠️ Высокий |
| 6. Тестирование | 1-2 часа | ⚠️ Высокий |

**ИТОГО: 10-16 часов** (2-3 рабочих дня)

---

## 📊 ОЖИДАЕМЫЙ РЕЗУЛЬТАТ:

### ✅ После выполнения:
- ❌ Циклических зависимостей между доменами  
- ✅ Изолированные домены с четкими границами
- ✅ Event-Driven архитектура для взаимодействий
- ✅ Легкое тестирование каждого домена отдельно
- ✅ Возможность независимого развития доменов

### 📈 Оценка качества:
**Текущая:** 6/10 (работает, но нарушает DDD)  
**Ожидаемая:** 9/10 (полное соответствие DDD принципам)

---

## 🚀 ПОРЯДОК ВЫПОЛНЕНИЯ:

1. **СНАЧАЛА** создаем план и получаем подтверждение ✅
2. **ЗАТЕМ** выполняем по одному этапу с проверкой
3. **ПОСЛЕ КАЖДОГО ЭТАПА** критически оцениваем результат
4. **В КОНЦЕ** делаем финальную критическую оценку

**Готов начать выполнение по утверждению плана!** 🎯