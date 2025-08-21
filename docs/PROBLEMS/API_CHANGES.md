# 🔄 Изменения API при миграции

## Обзор

Этот документ описывает изменения в API при переходе на модульную архитектуру. Большинство изменений обратно совместимы благодаря использованию адаптеров.

## BookingService

### ✅ Без изменений (используют адаптер)

```php
// Старый способ - продолжает работать
$bookingService->createBooking($data);
$bookingService->updateBooking($id, $data);
$bookingService->cancelBooking($id, $reason);
$bookingService->getMasterBookings($masterId);
```

### ⚠️ Новые методы (рекомендуется)

```php
// Новый способ с DTO
use App\Domain\Booking\DTOs\CreateBookingDTO;

$dto = CreateBookingDTO::fromArray($data);
$bookingService->create($dto);
```

### 🔄 Изменения в ответах

Старый ответ:
```json
{
    "id": 1,
    "master_id": 123,
    "status": 1,
    "status_text": "pending"
}
```

Новый ответ:
```json
{
    "id": 1,
    "master_id": 123,
    "status": "pending",
    "status_label": "Ожидает подтверждения",
    "can_cancel": true,
    "can_modify": true
}
```

## MasterService

### ✅ Без изменений

```php
// Все методы остались совместимыми
$masterService->createProfile($dto);
$masterService->updateProfile($id, $dto);
$masterService->activateProfile($id);
$masterService->getBySlug($slug);
```

### 🆕 Новые возможности

```php
// Расширенная фильтрация
$filters = MasterFilterDTO::fromArray([
    'city' => 'Moscow',
    'min_rating' => 4.5,
    'services' => [1, 2, 3],
    'has_home_service' => true
]);
$masters = $masterService->search($filters);

// Предустановленные фильтры
$topMasters = $masterService->search(MasterFilterDTO::top());
$newMasters = $masterService->search(MasterFilterDTO::new());
```

## SearchEngine

### ⚠️ Изменения в параметрах

Старый способ:
```php
$searchService->search('массаж', [
    'type' => 'masters',
    'city' => 'Moscow'
]);
```

Новый способ:
```php
$searchEngine->search(new SearchQueryDTO(
    query: 'массаж',
    types: ['masters'],
    filters: ['city' => 'Moscow']
));
```

### 🔄 Изменения в ответах

Старый формат:
```json
{
    "masters": [...],
    "services": [...],
    "total": 42
}
```

Новый формат:
```json
{
    "masters": [
        {
            "id": 1,
            "type": "master",
            "title": "Иван Иванов",
            "description": "Профессиональный массажист",
            "url": "/masters/ivan-ivanov",
            "score": 0.95,
            "highlight": {
                "title": "Иван <em>Иванов</em>",
                "description": "Профессиональный <em>массажист</em>"
            }
        }
    ],
    "services": [...],
    "meta": {
        "total": 42,
        "took": 15,
        "max_score": 0.95
    }
}
```

## HTTP API Endpoints

### ✅ Обратно совместимые

| Endpoint | Метод | Статус |
|----------|-------|--------|
| `/api/bookings` | POST | ✅ Без изменений |
| `/api/bookings/{id}` | PUT | ✅ Без изменений |
| `/api/bookings/{id}/cancel` | POST | ✅ Без изменений |
| `/api/masters` | GET | ✅ Без изменений |
| `/api/masters/{slug}` | GET | ✅ Без изменений |
| `/api/search` | GET | ✅ Без изменений* |

*Поддерживает старый формат параметров

### 🆕 Новые endpoints

| Endpoint | Метод | Описание |
|----------|-------|----------|
| `/api/v2/bookings` | POST | Принимает JSON с типизацией |
| `/api/v2/search` | POST | Расширенный поиск с фильтрами |
| `/api/masters/top` | GET | Топ мастеров |
| `/api/masters/new` | GET | Новые мастера |

## Enums

### 🆕 Новые Enums вместо констант

Старый способ:
```php
BookingService::STATUS_PENDING; // 1
BookingService::STATUS_CONFIRMED; // 2
```

Новый способ:
```php
use App\Enums\BookingStatus;

BookingStatus::PENDING->value; // 'pending'
BookingStatus::PENDING->label(); // 'Ожидает подтверждения'
BookingStatus::PENDING->color(); // 'yellow'
```

## Валидация

### 🔄 Улучшенная валидация

Старая валидация в контроллере:
```php
$request->validate([
    'master_id' => 'required|exists:masters,id',
    'date' => 'required|date',
]);
```

Новая валидация в DTO:
```php
$dto = CreateBookingDTO::fromRequest($request);
$errors = $dto->validate(); // Детальная валидация бизнес-правил
```

## Обработка ошибок

### 🔄 Улучшенные сообщения об ошибках

Старый формат:
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "date": ["The date field is required."]
    }
}
```

Новый формат:
```json
{
    "message": "Validation failed",
    "errors": {
        "date": ["Дата обязательна для заполнения"],
        "time_slot": ["Выбранное время уже занято"]
    },
    "error_code": "VALIDATION_ERROR",
    "status_code": 422
}
```

## Миграция клиентского кода

### JavaScript/TypeScript

```typescript
// Старый код
const booking = await api.post('/api/bookings', {
    master_id: 123,
    date: '2024-01-15',
    time: '10:00'
});

// Новый код (с поддержкой старого)
const booking = await api.post('/api/v2/bookings', {
    masterId: 123,
    date: '2024-01-15',
    startTime: '10:00',
    endTime: '11:00'
});
```

### Проверка версии API

```javascript
// Определение поддерживаемой версии
const apiVersion = await api.get('/api/version');

if (apiVersion.data.version >= '2.0') {
    // Использовать новый API
} else {
    // Использовать старый API
}
```

## Feature Detection

```php
// В контроллере
if (feature('api_v2')) {
    return $this->newApiResponse($data);
} else {
    return $this->legacyApiResponse($data);
}
```

## Рекомендации

1. **Используйте адаптеры** - они обеспечивают совместимость
2. **Постепенно переходите на новые endpoints** - не спешите
3. **Следите за логами** - адаптеры логируют использование legacy методов
4. **Тестируйте** - убедитесь, что ваша интеграция работает с обеими версиями

---

*Документ обновляется по мере развития API*