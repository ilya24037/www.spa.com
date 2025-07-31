# 🗂️ КАРТА РЕФАКТОРИНГА ПАПКИ APP
## Что и куда переносится из текущей структуры

### 📁 ТЕКУЩАЯ СТРУКТУРА (что у вас сейчас)
```
app/
├── Console/Commands/           # Команды артизан
├── Exceptions/                 # Обработчики исключений  
├── Helpers/                    # Вспомогательные функции
├── Http/
│   ├── Controllers/           # 15+ контроллеров
│   ├── Middleware/            # Middleware
│   └── Requests/              # Form Requests
├── Models/                    # 10+ моделей
├── Providers/                 # Service Providers
├── Services/                  # Сервисы (MediaProcessingService, BookingService)
└── Traits/                    # Трейты

Всего: ~50-60 файлов
```

---

## 🔄 ПЛАН ПЕРЕНОСА ФАЙЛОВ

### 1. МОДЕЛИ (app/Models/* → app/Domain/*/Models/)

#### User.php (500+ строк) разделяется на:
```
app/Models/User.php → 
├── app/Domain/User/Models/User.php (100 строк - только auth)
├── app/Domain/User/Models/UserProfile.php (50 строк)
├── app/Domain/User/Models/UserSettings.php (30 строк)
├── app/Domain/User/Traits/HasRoles.php
├── app/Domain/User/Traits/HasBookings.php
└── app/Domain/User/Traits/HasMasterProfile.php
```

#### MasterProfile.php (400+ строк) разделяется на:
```
app/Models/MasterProfile.php →
├── app/Domain/Master/Models/MasterProfile.php (150 строк)
├── app/Domain/Master/Models/MasterMedia.php
├── app/Domain/Master/Models/MasterSchedule.php
├── app/Domain/Master/Traits/HasSlug.php
└── app/Domain/Master/Traits/GeneratesMetaTags.php
```

#### Ad.php переносится в:
```
app/Models/Ad.php →
├── app/Domain/Ad/Models/Ad.php
├── app/Domain/Ad/Models/AdMedia.php
├── app/Domain/Ad/Models/AdPricing.php
└── app/Domain/Ad/Models/AdLocation.php
```

#### Остальные модели:
```
app/Models/Booking.php → app/Domain/Booking/Models/Booking.php
app/Models/Review.php → app/Domain/Review/Models/Review.php
app/Models/MasterPhoto.php → app/Domain/Media/Models/Photo.php
app/Models/MasterVideo.php → app/Domain/Media/Models/Video.php
app/Models/Service.php → app/Domain/Service/Models/Service.php
app/Models/Schedule.php → app/Domain/Master/Models/Schedule.php
```

---

### 2. КОНТРОЛЛЕРЫ (app/Http/Controllers/* → app/Application/Http/Controllers/*)

#### Разделение больших контроллеров:
```
app/Http/Controllers/ProfileController.php (300+ строк) →
├── app/Application/Http/Controllers/Profile/ProfileController.php
├── app/Application/Http/Controllers/Profile/ProfileItemsController.php
└── app/Application/Http/Controllers/Profile/ProfileSettingsController.php

app/Http/Controllers/AdController.php (400+ строк) →
├── app/Application/Http/Controllers/Ad/AdController.php
├── app/Application/Http/Controllers/Ad/AdMediaController.php
└── app/Application/Http/Controllers/Ad/DraftController.php

app/Http/Controllers/BookingController.php →
├── app/Application/Http/Controllers/Booking/BookingController.php
└── app/Application/Http/Controllers/Booking/BookingSlotController.php
```

#### Простой перенос:
```
app/Http/Controllers/HomeController.php → app/Application/Http/Controllers/HomeController.php
app/Http/Controllers/SearchController.php → app/Application/Http/Controllers/SearchController.php
app/Http/Controllers/FavoriteController.php → app/Application/Http/Controllers/FavoriteController.php
```

---

### 3. СЕРВИСЫ (app/Services/* → app/Domain/*/Services/)

```
app/Services/MediaProcessingService.php →
├── app/Domain/Media/Services/MediaService.php
├── app/Domain/Media/Services/ImageProcessor.php
├── app/Domain/Media/Services/VideoProcessor.php
└── app/Domain/Media/Services/ThumbnailGenerator.php

app/Services/BookingService.php →
├── app/Domain/Booking/Services/BookingService.php
├── app/Domain/Booking/Services/SlotService.php
└── app/Domain/Booking/Services/NotificationService.php

app/Services/AiContext/* → app/Infrastructure/Analysis/AiContext/
(вспомогательный сервис, не бизнес-логика)
```

---

### 4. НОВЫЕ ФАЙЛЫ (которых сейчас нет)

#### Репозитории (для работы с БД):
```
app/Domain/User/Repositories/UserRepository.php (новый)
app/Domain/Ad/Repositories/AdRepository.php (новый)
app/Domain/Booking/Repositories/BookingRepository.php (новый)
app/Domain/Master/Repositories/MasterRepository.php (новый)
```

#### DTO (Data Transfer Objects):
```
app/Domain/User/DTOs/CreateUserDTO.php (новый)
app/Domain/Ad/DTOs/CreateAdDTO.php (новый)
app/Domain/Booking/DTOs/CreateBookingDTO.php (новый)
```

#### Actions (единичные операции):
```
app/Domain/Ad/Actions/PublishAdAction.php (новый)
app/Domain/Booking/Actions/CancelBookingAction.php (новый)
app/Domain/User/Actions/VerifyEmailAction.php (новый)
```

---

### 5. ВСПОМОГАТЕЛЬНЫЕ ФАЙЛЫ

```
app/Helpers/* → app/Support/Helpers/
app/Traits/* → app/Support/Traits/
app/Exceptions/* → app/Application/Exceptions/
app/Http/Middleware/* → app/Application/Http/Middleware/
app/Http/Requests/* → app/Application/Http/Requests/
app/Providers/* → остаются в app/Providers/
```

---

## 📊 СТАТИСТИКА РЕФАКТОРИНГА

### До рефакторинга:
- Файлов в app/: ~60
- Средний размер файла: 200-500 строк
- Уровень вложенности: 2-3
- Связанность: высокая

### После рефакторинга:
- Файлов в app/: ~150-200
- Средний размер файла: 50-150 строк
- Уровень вложенности: 4-5
- Связанность: низкая (модульная)

---

## 🚀 ПОРЯДОК РЕФАКТОРИНГА ФАЙЛОВ

### День 1: Подготовка
1. Создать новую структуру папок
2. Настроить autoload в composer.json
3. Создать базовые интерфейсы

### День 2-3: Модели
1. Начать с простых моделей (Review, Service)
2. Разделить User на части
3. Разделить MasterProfile
4. Перенести Ad и связанные

### День 4-5: Сервисы
1. Разделить MediaProcessingService
2. Улучшить BookingService
3. Создать новые сервисы для Ad

### День 6-7: Контроллеры
1. Разделить большие контроллеры
2. Убрать бизнес-логику в сервисы
3. Оставить только HTTP-логику

### День 8-9: Тестирование
1. Проверить все маршруты
2. Написать тесты для новых сервисов
3. Убедиться что ничего не сломалось

---

## ⚠️ ВАЖНЫЕ ЗАМЕЧАНИЯ

### Что НЕ трогаем сразу:
- routes/web.php (обновляем постепенно)
- config/* (остается как есть)
- database/migrations/* (не меняем)
- Providers (обновляем в конце)

### Обратная совместимость:
- Старые namespace оставляем через aliases
- Постепенно обновляем импорты
- Не ломаем существующий функционал

### Проблемные места:
1. **Циклические зависимости** - User ↔ MasterProfile
2. **JSON поля в моделях** - нужна унификация
3. **Дублирование логики** - в контроллерах
4. **Отсутствие тестов** - писать по ходу