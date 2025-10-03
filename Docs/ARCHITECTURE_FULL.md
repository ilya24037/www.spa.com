# Полная архитектура SPA Platform

Детальная структура проекта, описание доменов и компонентов.

---

## 🏗️ Общая архитектура

**SPA Platform** использует современную многоуровневую архитектуру:

- **Backend**: Domain-Driven Design (DDD) на Laravel 12
- **Frontend**: Feature-Sliced Design (FSD) на Vue 3 + TypeScript
- **Database**: MySQL с миграциями и сидами
- **Admin Panel**: Filament v4 для администрирования
- **API**: RESTful API с Inertia.js для SPA

---

## 📦 Backend: Domain-Driven Design (DDD)

### Структура доменов

```
app/Domain/
├── User/               # Управление пользователями
├── Master/             # Профили мастеров
├── Ad/                 # Объявления об услугах
├── Booking/            # Система бронирования
├── Payment/            # Платежи и транзакции
├── Review/             # Отзывы и рейтинги
├── Search/             # Поиск и фильтрация
├── Analytics/          # Аналитика и метрики
├── Notification/       # Уведомления (Email, SMS, Push)
├── Media/              # Загрузка и управление файлами
└── Service/            # Каталог услуг
```

### Структура каждого домена

```
app/Domain/{DomainName}/
├── Models/             # Eloquent модели
│   └── {Entity}.php    # Основная модель (User, Master, Ad, etc.)
│
├── Services/           # Бизнес-логика (Service Layer)
│   ├── {Entity}Service.php
│   └── {Feature}Service.php
│
├── Actions/            # Сложные операции (Command pattern)
│   ├── Create{Entity}Action.php
│   ├── Update{Entity}Action.php
│   └── Delete{Entity}Action.php
│
├── DTOs/               # Data Transfer Objects
│   ├── Create{Entity}DTO.php
│   └── Update{Entity}DTO.php
│
├── Events/             # События домена
│   ├── {Entity}Created.php
│   └── {Entity}Updated.php
│
├── Enums/              # Перечисления
│   ├── {Entity}Status.php
│   └── {Entity}Type.php
│
└── Observers/          # Eloquent observers
    └── {Entity}Observer.php
```

---

## 📋 Детальное описание доменов

### 1. **User** - Управление пользователями

**Модели**:
- `User.php` - Основная модель пользователя

**Сервисы**:
- `UserService.php` - Создание, обновление, удаление
- `AuthService.php` - Аутентификация, регистрация

**Энумы**:
- `UserRole.php` - Роли (client, master, admin)
- `UserStatus.php` - Статусы (active, blocked, pending)

**Связи**:
- `hasOne(Master)` - Один пользователь может быть мастером
- `hasMany(Booking)` - Пользователь может иметь много бронирований
- `hasMany(Review)` - Пользователь может оставлять отзывы

---

### 2. **Master** - Профили мастеров

**Модели**:
- `Master.php` - Профиль мастера

**Сервисы**:
- `MasterService.php` - Управление профилем
- `MasterVerificationService.php` - Верификация мастеров

**Энумы**:
- `MasterStatus.php` - Статусы (pending, verified, rejected)
- `ExperienceLevel.php` - Уровень опыта (beginner, intermediate, expert)

**Связи**:
- `belongsTo(User)` - Привязан к пользователю
- `hasMany(Ad)` - Может иметь много объявлений
- `hasMany(Booking)` - Получает бронирования
- `hasMany(Review)` - Получает отзывы

**Кастомные поля**:
- `experience_years` - Количество лет опыта
- `specializations` - JSON массив специализаций
- `certificates` - JSON массив сертификатов
- `working_hours` - JSON расписание работы

---

### 3. **Ad** - Объявления об услугах

**Модели**:
- `Ad.php` - Объявление о услуге

**Сервисы**:
- `AdService.php` - Управление объявлениями
- `AdModerationService.php` - Модерация объявлений
- `DraftService.php` - Работа с черновиками

**Энумы**:
- `AdStatus.php` - Статусы (draft, pending, published, rejected, archived)
- `ModerationType.php` - Типы модерации

**Связи**:
- `belongsTo(Master)` - Принадлежит мастеру
- `hasMany(Booking)` - Может получать бронирования
- `hasMany(Review)` - Может получать отзывы

**Кастомные поля**:
- `photos` - JSON массив фотографий
- `services` - JSON массив услуг
- `prices` - JSON структура цен
- `address` - JSON адрес (Yandex Maps)

---

### 4. **Booking** - Система бронирования

**Модели**:
- `Booking.php` - Бронирование услуги

**Сервисы**:
- `BookingService.php` - Создание, управление бронированиями
- `TimeSlotService.php` - Управление временными слотами
- `CalendarService.php` - Календарь доступности

**Энумы**:
- `BookingStatus.php` - Статусы (pending, confirmed, cancelled, completed)

**Связи**:
- `belongsTo(User)` - Клиент
- `belongsTo(Master)` - Мастер
- `belongsTo(Ad)` - Объявление
- `hasOne(Payment)` - Платёж

**Кастомные поля**:
- `booking_date` - Дата бронирования
- `time_slot` - Временной слот
- `duration` - Продолжительность (минуты)
- `total_price` - Итоговая стоимость

---

### 5. **Payment** - Платежи и транзакции

**Модели**:
- `Payment.php` - Платёж

**Сервисы**:
- `PaymentService.php` - Обработка платежей
- `RefundService.php` - Возвраты средств

**Энумы**:
- `PaymentStatus.php` - Статусы (pending, completed, failed, refunded)
- `PaymentMethod.php` - Методы оплаты (card, cash, online)

**Связи**:
- `belongsTo(Booking)` - Привязан к бронированию
- `belongsTo(User)` - Плательщик

---

### 6. **Review** - Отзывы и рейтинги

**Модели**:
- `Review.php` - Отзыв о мастере/услуге

**Сервисы**:
- `ReviewService.php` - Управление отзывами
- `RatingService.php` - Подсчёт рейтингов

**Энумы**:
- `ReviewStatus.php` - Статусы (pending, published, rejected)

**Связи**:
- `belongsTo(User)` - Автор отзыва
- `belongsTo(Master)` - Мастер
- `belongsTo(Ad)` - Объявление
- `belongsTo(Booking)` - Бронирование

**Кастомные поля**:
- `rating` - Оценка (1-5)
- `comment` - Текст отзыва
- `photos` - JSON массив фотографий

---

### 7. **Search** - Поиск и фильтрация

**Сервисы**:
- `SearchService.php` - Поиск по мастерам/услугам
- `FilterService.php` - Фильтрация результатов

**Интеграции**:
- Meilisearch через Laravel Scout
- Geo-поиск через Yandex Maps API

---

### 8. **Analytics** - Аналитика и метрики

**Модели**:
- `Metric.php` - Метрики системы

**Сервисы**:
- `AnalyticsService.php` - Сбор и обработка метрик
- `ReportService.php` - Генерация отчётов

---

### 9. **Notification** - Уведомления

**Модели**:
- `Notification.php` - Уведомления

**Сервисы**:
- `NotificationService.php` - Отправка уведомлений
- `EmailService.php` - Email уведомления
- `SmsService.php` - SMS уведомления

**Каналы**:
- Email (Laravel Mail)
- SMS (планируется)
- Push (планируется)

---

### 10. **Media** - Загрузка и управление файлами

**Интеграции**:
- Spatie MediaLibrary для загрузки
- Оптимизация изображений
- Хранение в storage/app/public

---

### 11. **Service** - Каталог услуг

**Модели**:
- `ServiceCategory.php` - Категории услуг
- `Service.php` - Услуги

**Сервисы**:
- `ServiceCatalogService.php` - Управление каталогом

---

## 🎨 Frontend: Feature-Sliced Design (FSD)

### Структура FSD

```
resources/js/src/
├── app/                    # Инициализация приложения
│   ├── providers/          # Vue providers
│   └── styles/             # Глобальные стили
│
├── pages/                  # Страницы приложения
│   ├── Home.vue            # Главная страница
│   ├── Dashboard.vue       # Личный кабинет
│   ├── Ads/                # Страницы объявлений
│   │   ├── Index.vue       # Список объявлений
│   │   └── Show.vue        # Детальная страница
│   └── Masters/            # Страницы мастеров
│       └── Show.vue        # Профиль мастера
│
├── widgets/                # Композитные блоки
│   ├── Header/             # Шапка сайта
│   ├── Footer/             # Подвал сайта
│   └── Sidebar/            # Боковая панель
│
├── features/               # Фичи (действия пользователя)
│   ├── auth/               # Аутентификация
│   │   ├── ui/             # UI компоненты
│   │   ├── model/          # Бизнес-логика
│   │   └── api/            # API запросы
│   │
│   ├── search/             # Поиск и фильтры
│   │   ├── ui/
│   │   │   ├── SearchBar.vue
│   │   │   └── Filters.vue
│   │   └── model/
│   │
│   └── booking/            # Бронирование
│       ├── ui/
│       │   ├── BookingCalendar.vue
│       │   └── TimeSlotPicker.vue
│       └── model/
│
├── entities/               # Бизнес-сущности
│   ├── user/               # Пользователь
│   │   ├── ui/
│   │   ├── model/
│   │   └── api/
│   │
│   ├── master/             # Мастер
│   │   ├── ui/
│   │   │   └── MasterCard/
│   │   │       ├── MasterCard.vue
│   │   │       ├── MasterInfo.vue
│   │   │       └── MasterStats.vue
│   │   ├── model/
│   │   └── api/
│   │
│   ├── ad/                 # Объявление
│   │   ├── ui/
│   │   │   └── ItemCard/
│   │   │       ├── ItemCard.vue
│   │   │       ├── ItemContent.vue
│   │   │       └── ItemStats.vue
│   │   ├── model/
│   │   └── api/
│   │
│   └── booking/            # Бронирование
│       ├── ui/
│       ├── model/
│       └── api/
│
└── shared/                 # Переиспользуемый код
    ├── ui/                 # UI kit
    │   ├── Button/
    │   ├── Input/
    │   ├── Modal/
    │   └── Card/
    │
    ├── api/                # API клиент
    │   └── client.ts
    │
    ├── lib/                # Утилиты
    │   ├── formatters/
    │   └── validators/
    │
    └── config/             # Конфигурация
        └── routes.ts
```

---

## 🔧 Application Layer (Контроллеры и роуты)

### Структура контроллеров

```
app/Application/Http/Controllers/
├── HomeController.php          # Главная страница
├── DashboardController.php     # Личный кабинет
│
├── Ad/
│   ├── AdController.php        # CRUD объявлений
│   └── AdSearchController.php  # Поиск объявлений
│
├── Master/
│   └── MasterController.php    # Профили мастеров
│
├── Booking/
│   └── BookingController.php   # Бронирования
│
├── Profile/
│   └── ProfileController.php   # Профиль пользователя
│
└── Auth/
    ├── LoginController.php
    └── RegisterController.php
```

### Роуты (routes/web.php)

```php
// Публичные страницы
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/masters/{master}', [MasterController::class, 'show'])->name('masters.show');
Route::get('/ads/{ad}', [AdController::class, 'show'])->name('ads.show');

// Авторизованные пользователи
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('bookings', BookingController::class);
});

// Только для мастеров
Route::middleware(['auth', 'role:master'])->group(function () {
    Route::resource('ads', AdController::class);
});
```

---

## 🛡️ Filament Admin Panel

### Структура админ-панели

```
app/Filament/
├── Resources/              # CRUD ресурсы
│   ├── AdResource.php      # Управление объявлениями
│   ├── UserResource.php    # Управление пользователями
│   ├── MasterResource.php  # Управление мастерами
│   └── BookingResource.php # Управление бронированиями
│
├── Widgets/                # Виджеты дашборда
│   ├── StatsOverview.php   # Общая статистика
│   └── RecentBookings.php  # Последние бронирования
│
└── Pages/                  # Кастомные страницы
    └── Settings.php        # Настройки системы
```

### Основные ресурсы

- **AdResource** - модерация объявлений, изменение статусов
- **UserResource** - управление пользователями, блокировка
- **MasterResource** - верификация мастеров
- **BookingResource** - просмотр всех бронирований

---

## 🗄️ Database Schema (Основные таблицы)

### users
```sql
- id
- name
- email
- password
- role (enum: client, master, admin)
- status (enum: active, blocked, pending)
- email_verified_at
- created_at, updated_at
```

### masters
```sql
- id
- user_id (FK)
- bio
- experience_years
- specializations (JSON)
- certificates (JSON)
- working_hours (JSON)
- status (enum: pending, verified, rejected)
- created_at, updated_at
```

### ads
```sql
- id
- master_id (FK)
- title
- description
- photos (JSON)
- services (JSON)
- prices (JSON)
- address (JSON)
- status (enum: draft, pending, published, rejected, archived)
- published_at
- created_at, updated_at, deleted_at
```

### bookings
```sql
- id
- user_id (FK)
- master_id (FK)
- ad_id (FK)
- booking_date
- time_slot
- duration
- total_price
- status (enum: pending, confirmed, cancelled, completed)
- created_at, updated_at
```

### payments
```sql
- id
- booking_id (FK)
- user_id (FK)
- amount
- method (enum: card, cash, online)
- status (enum: pending, completed, failed, refunded)
- transaction_id
- created_at, updated_at
```

### reviews
```sql
- id
- user_id (FK)
- master_id (FK)
- ad_id (FK)
- booking_id (FK)
- rating (1-5)
- comment
- photos (JSON)
- status (enum: pending, published, rejected)
- created_at, updated_at
```

---

## 🔗 Внешние интеграции

### Yandex Maps API
- Автокомплит адресов
- Геокодирование
- Отображение карты

### Meilisearch (поиск)
- Полнотекстовый поиск
- Фасетная фильтрация
- Geo-поиск

### Spatie MediaLibrary
- Загрузка фото
- Оптимизация изображений
- Генерация thumbnails

### Laravel Scout
- Индексация моделей
- Синхронизация с Meilisearch

---

## 📊 Текущий прогресс

- ✅ User домен (100%)
- ✅ Master домен (100%)
- ✅ Ad домен (95%)
- 🔄 Booking домен (60%)
- 🔄 Payment домен (40%)
- ⏳ Review домен (20%)
- ⏳ Search домен (30%)
- ⏳ Analytics домен (0%)
- ⏳ Notification домен (50%)

**Общая готовность проекта**: 86% до MVP
