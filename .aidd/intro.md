# Техническое описание SPA Platform

## 🎯 О проекте

**SPA Platform** - это современная платформа для поиска и бронирования услуг массажа, созданная по принципу маркетплейса (как Avito/Ozon). Проект разрабатывается с использованием методологии AI-Driven Development (AIDD).

### Ключевые документы
- **Бизнес-идея**: @doc/aidd/idea.md
- **Техническое видение**: @doc/aidd/vision.md
- **План разработки**: @doc/aidd/tasklist.md
- **Стандарты кода**: @doc/aidd/conventions.md
- **Процесс работы**: @doc/aidd/workflow.md

## 🏗 Архитектура системы

### Общая схема
```
┌─────────────────────────────────────────────────────┐
│                   Пользователи                       │
│         Клиенты | Мастера | Администраторы          │
└─────────────────────┬───────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────┐
│                  Frontend Layer                      │
│                                                      │
│  ┌──────────────────────────────────────────────┐  │
│  │            Vue 3 + TypeScript                │  │
│  │         Feature-Sliced Design (FSD)          │  │
│  └──────────────────────────────────────────────┘  │
│                       │                             │
│                   Inertia.js                       │
│                       │                             │
└─────────────────────┬───────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────┐
│                  Backend Layer                       │
│                                                      │
│  ┌──────────────────────────────────────────────┐  │
│  │              Laravel 12                      │  │
│  │         Domain-Driven Design (DDD)           │  │
│  └──────────────────────────────────────────────┘  │
│                                                      │
│   Controllers → Services → Models → Database        │
└─────────────────────┬───────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────┐
│                 Data Layer                           │
│                                                      │
│         MySQL 8.0        Redis Cache                │
│                                                      │
│  ┌────────────────────────────────────────────┐    │
│  │ users | masters | ads | bookings | reviews  │    │
│  └────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────┘
```

## 💻 Технологический стек

### Backend
- **Laravel 12** - основной фреймворк
- **PHP 8.2+** - язык программирования
- **MySQL 8.0** - база данных
- **Redis** - кеширование
- **Queue Jobs** - фоновые задачи

### Frontend
- **Vue 3** - JavaScript фреймворк
- **TypeScript** - типизация
- **Inertia.js** - SPA без API
- **Pinia** - управление состоянием
- **Tailwind CSS** - стили
- **Vite** - сборщик

## 📁 Структура проекта

### Backend структура (DDD)
```
app/Domain/
├── User/              # Домен пользователей
│   ├── Models/        # User.php
│   ├── Services/      # UserService.php
│   └── Actions/       # CreateUserAction.php
├── Master/            # Домен мастеров
│   ├── Models/        # MasterProfile.php
│   ├── Services/      # MasterService.php
│   └── DTOs/          # CreateMasterDTO.php
├── Ad/                # Домен объявлений
│   ├── Models/        # Ad.php
│   ├── Services/      # AdService.php
│   └── Events/        # AdPublished.php
├── Booking/           # Домен бронирований
│   ├── Models/        # Booking.php
│   ├── Services/      # BookingService.php
│   └── Actions/       # CreateBookingAction.php
└── Payment/           # Домен платежей
```

### Frontend структура (FSD)
```
resources/js/src/
├── shared/            # Общие компоненты
│   ├── ui/           # Button, Input, Modal
│   └── lib/          # helpers, utils
├── entities/          # Бизнес-сущности
│   ├── master/       # Компоненты мастера
│   ├── booking/      # Компоненты бронирования
│   └── user/         # Компоненты пользователя
├── features/          # Функциональные блоки
│   ├── auth/         # Авторизация
│   ├── search/       # Поиск
│   └── booking/      # Процесс бронирования
├── widgets/           # Составные виджеты
│   ├── header/       # Шапка сайта
│   └── master-card/  # Карточка мастера
└── pages/            # Страницы
    ├── home/         # Главная
    ├── masters/      # Каталог
    └── profile/      # Личный кабинет
```

## 🚀 Быстрый старт

### 1. Клонирование проекта
```bash
git clone https://github.com/spa-platform/spa.git
cd spa
```

### 2. Установка зависимостей
```bash
# Backend
composer install

# Frontend
npm install
```

### 3. Настройка окружения
```bash
# Копируем конфиг
cp .env.example .env

# Генерируем ключ приложения
php artisan key:generate

# Настраиваем БД в .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spa_platform
DB_USERNAME=root
DB_PASSWORD=
```

### 4. База данных
```bash
# Создаем таблицы
php artisan migrate

# Заполняем тестовыми данными
php artisan db:seed
```

### 5. Запуск серверов
```bash
# Terminal 1 - Backend
php artisan serve

# Terminal 2 - Frontend
npm run dev

# Открыть в браузере
http://localhost:8000
```

## 🔄 Основные потоки данных

### Поток бронирования
```
1. Клиент выбирает мастера
   └─> MasterController@show
       └─> MasterService::getMasterWithAds()
           └─> Master::with(['ads', 'reviews'])->find()

2. Клиент выбирает время
   └─> BookingController@availableSlots
       └─> BookingService::getAvailableSlots()
           └─> BookingSlot::where('is_available', true)

3. Клиент подтверждает бронирование
   └─> BookingController@store
       └─> BookingService::create()
           └─> DB::transaction()
               ├─> Booking::create()
               ├─> BookingSlot::markAsBooked()
               └─> event(BookingCreated)

4. Отправка уведомлений
   └─> BookingCreated event
       └─> SendBookingNotification listener
           ├─> Mail::to($client)->send()
           └─> Mail::to($master)->send()
```

## 🧩 Основные компоненты

### Backend компоненты

#### BookingService
```php
class BookingService {
    public function create(CreateBookingDTO $dto): Booking
    public function cancel(int $bookingId): bool
    public function getAvailableSlots(int $masterId, string $date): Collection
}
```

#### MasterService
```php
class MasterService {
    public function create(CreateMasterDTO $dto): MasterProfile
    public function updateProfile(int $masterId, array $data): MasterProfile
    public function search(SearchCriteria $criteria): LengthAwarePaginator
}
```

### Frontend компоненты

#### MasterCard.vue
```vue
<MasterCard
  :master="masterData"
  :show-booking-button="true"
  @book="handleBooking"
/>
```

#### BookingCalendar.vue
```vue
<BookingCalendar
  :master-id="masterId"
  :available-dates="dates"
  @date-selected="handleDateSelection"
/>
```

## 📊 База данных

### Основные таблицы
- **users** - пользователи системы
- **master_profiles** - профили мастеров
- **ads** - объявления о услугах
- **bookings** - бронирования
- **booking_slots** - временные слоты
- **reviews** - отзывы клиентов
- **payments** - платежи

### Пример миграции
```php
Schema::create('bookings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('ad_id')->constrained();
    $table->foreignId('client_id')->constrained('users');
    $table->date('date');
    $table->time('time');
    $table->enum('status', ['pending', 'confirmed', 'cancelled']);
    $table->decimal('amount', 10, 2);
    $table->timestamps();

    $table->index(['ad_id', 'date']);
});
```

## 🧪 Тестирование

### Backend тесты
```bash
# Все тесты
php artisan test

# Конкретный тест
php artisan test --filter=BookingTest

# С покрытием
php artisan test --coverage
```

### Frontend тесты
```bash
# Unit тесты
npm run test:unit

# E2E тесты
npm run test:e2e

# В режиме watch
npm run test:watch
```

## 📈 Метрики и мониторинг

### Что отслеживаем
- **Бизнес-метрики**: количество бронирований, конверсия, средний чек
- **Технические**: время ответа API, ошибки 500, использование памяти
- **Пользовательские**: DAU/MAU, retention, время на сайте

### Инструменты
- **Laravel Telescope** - debug в development
- **Sentry** - ошибки в production
- **Google Analytics** - поведение пользователей

## 🚢 Деплой

### Staging
```bash
git push staging main
ssh staging.spa.com
cd /var/www/spa
./deploy.sh staging
```

### Production
```bash
# Через GitHub Actions
git push origin main
# Автоматический деплой через CI/CD
```

## 🔧 Полезные команды

### Artisan команды
```bash
php artisan tinker              # REPL консоль
php artisan queue:work          # Запуск очередей
php artisan cache:clear         # Очистка кеша
php artisan migrate:rollback    # Откат миграции
php artisan db:seed            # Заполнение тестовыми данными
```

### NPM скрипты
```bash
npm run dev        # Development сервер
npm run build      # Production сборка
npm run lint       # Проверка кода
npm run type-check # TypeScript проверка
```

## 📚 Дополнительные ресурсы

### Документация
- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Guide](https://vuejs.org/guide/)
- [Inertia.js Docs](https://inertiajs.com/)
- [Tailwind CSS](https://tailwindcss.com/docs)

### Внутренние документы
- Все AIDD документы в `doc/aidd/`
- Примеры кода в `.claude/`
- Спецификации в `.claude/specs/`

## 🤝 Команда и поддержка

### Структура команды
- **Backend разработка** - Laravel, PHP, MySQL
- **Frontend разработка** - Vue.js, TypeScript
- **DevOps** - Docker, CI/CD, мониторинг
- **QA** - Тестирование, автотесты

### Связь
- GitHub Issues для багов
- Pull Requests для новых функций
- Telegram для оперативных вопросов

---

*Последнее обновление: 2025-01-22*
*Версия документа: 1.0*