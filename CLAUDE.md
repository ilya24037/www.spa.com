\# 🏗️ АРХИТЕКТУРА SPA PLATFORM



\## Технический стек

\- Backend: Laravel 12 + Clean Architecture

\- Frontend: Vue 3 + Inertia.js + Tailwind CSS

\- DB: MySQL с 49+ миграциями

\- Архитектура: Domain-Driven Design



\## Структура приложения

app/

├── Application/         # Слой приложения (контроллеры)

├── Domain/             # Бизнес-логика (Booking, User, Payment)

├── Infrastructure/     # Внешние сервисы

├── Models/            # Legacy модели для совместимости

└── Services/          # Вспомогательные сервисы



resources/js/

├── Pages/             # Inertia страницы

├── Components/        # Vue компоненты (модульные)

│   ├── Booking/       # Система бронирования

│   ├── Masters/       # Компоненты мастеров

│   ├── AdForm/        # Форма объявлений (модульная)

│   └── UI/           # Базовые UI компоненты



## 📊 СТАТУС РЕФАКТОРИНГА (Август 2025)

### ✅ ВЫПОЛНЕНО (80-85% от полного плана):

#### 1. **Модели - 100%** ✅
- Все модели перенесены в домены (User, Ad, Booking, Master, Media, Payment, Notification и др.)
- Большие модели разделены (User → 3 части, MasterProfile → 4 части, Ad → 4 части)
- Созданы legacy-адаптеры для обратной совместимости

#### 2. **Контроллеры - 100%** ✅
- 28 контроллеров в app/Application/Http/Controllers/
- Разделены большие контроллеры:
  - ProfileController → ProfileController, ProfileItemsController, ProfileSettingsController
  - AdController → AdController, AdMediaController, DraftController
  - BookingController → BookingController, BookingSlotController
- Организованы по доменам (Ad/, Booking/, Profile/, Auth/)

#### 3. **Сервисы - 90%** ✅
- 25+ доменных сервисов созданы
- MediaProcessingService успешно разделен на 4 части
- Все домены имеют сервисы:
  - Ad: AdService, AdMediaService, AdModerationService, AdSearchService
  - Booking: BookingService, BookingSlotService
  - User: UserService, UserAuthService
  - Search: 9 сервисов включая движки поиска

#### 4. **DTOs - 100%** ✅
- 32 DTO класса созданы во всех доменах
- Сложные DTO с валидацией (BookingData, UpdateProfileDTO)
- Полное покрытие: Ad (4), Booking (5), User (6), Master (5), Payment (4), Review (3)

#### 5. **Repositories - 100%** ✅
- 9 репозиториев для всех доменов
- AdRepository, BookingRepository, UserRepository, MasterRepository и др.
- Методы find, findOrFail, create, update с поддержкой связей

#### 6. **Actions - 100%** ✅
- 18 Action классов с бизнес-логикой
- Сложные actions: CancelBookingAction (с расчетом штрафов), PublishAdAction
- Покрытие: Ad (3), Booking (5), User (3), Master (3), Payment (3), Review (1)

#### 7. **Infrastructure слой** ✅
- Адаптеры для обратной совместимости
- Система уведомлений с 8 каналами
- Media processing (AI, Image, Video processors)
- Cache, Feature flags

#### 8. **Events и Enums** ✅
- 22 Enum класса для типизации
- События: Booking (6), Payment (3), Notification (4)

### 📁 Полная структура доменов:
```
app/Domain/
├── Ad/           (Models, Services, DTOs, Repositories, Actions)
├── Booking/      (Models, Services, DTOs, Repositories, Actions, Events)
├── Master/       (Models, Services, DTOs, Repositories, Actions)
├── Media/        (Models, Services, DTOs, Repositories)
├── Notification/ (Models, Services, DTOs, Repositories)
├── Payment/      (Models, Services, DTOs, Repositories, Actions, Events)
├── Review/       (Models, Services, DTOs, Repositories, Actions)
├── Search/       (Services, Engines, DTOs, Repositories)
├── Service/      (Models)
└── User/         (Models, Services, DTOs, Repositories, Actions)
```

### ❌ Не выполнено:
- Unit и Feature тесты (Days 15-16)
- Документация API (Day 17)
- Performance оптимизация (Day 18)

### 🎯 Итог: Проект имеет профессиональную DDD архитектуру уровня крупных маркетплейсов

\# 💼 БИЗНЕС-ПРАВИЛА



\## Система бронирования

\- Минимальное время до бронирования: 30 минут (отключено для тестов)

\- Длительность услуги: 60 минут по умолчанию

\- Статусы: pending → confirmed → completed/cancelled

\- Проверка пересечений слотов обязательна



\## Типы услуг

\- incall (к мастеру) / outcall (выезд)

\- Разные тарифы для разных типов

\- Скидки для новых клиентов



\## Платежная система

\- Поддержка WebMoney, СБП, банковских карт

\- Webhook обработка через PaymentController

\- Ad plans для монетизации объявлений



\# 🔧 СОГЛАШЕНИЯ ПО КОДУ



\## Vue компоненты

\- Composition API с <script setup>

\- Props типизированы

\- События через defineEmits

\- Модульная структура (как Avito/Ozon)



\## Laravel

\- Clean Architecture обязательна

\- Actions для бизнес-логики

\- DTOs для передачи данных

\- Enums для типизации (BookingStatus, PaymentType)

\- Repository pattern для доменных сущностей



\## Именование

\- Vue компоненты: PascalCase

\- Methods: camelCase

\- Constants: UPPER\_SNAKE\_CASE

\- Database: snake\_case

