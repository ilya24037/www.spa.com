# Техническое видение SPA Platform

## 1. 🛠 Технологии

### Backend
- **Laravel 12** - PHP фреймворк для бизнес-логики
- **PHP 8.2+** - современный PHP с типизацией
- **MySQL 8.0** - основная база данных
- **Redis** - кеширование и сессии
- **Queue** - обработка фоновых задач

### Frontend
- **Vue 3** - реактивный фреймворк
- **TypeScript** - строгая типизация
- **Inertia.js** - SPA без отдельного API
- **Pinia** - управление состоянием
- **Tailwind CSS** - утилитарные стили
- **Vite** - сборка и HMR

### Инфраструктура
- **Docker** - контейнеризация
- **Nginx** - веб-сервер
- **Git** - версионирование
- **GitHub Actions** - CI/CD

## 2. 📐 Принципы разработки

### KISS (Keep It Simple, Stupid)
Простые решения предпочтительнее сложных. Не усложняем без необходимости.

### YAGNI (You Aren't Gonna Need It)
Не добавляем функционал "на будущее". Решаем только текущие задачи.

### DRY (Don't Repeat Yourself)
Избегаем дублирования кода. Выносим общую логику в переиспользуемые компоненты.

### SOLID
- **S**ingle Responsibility - один класс = одна ответственность
- **O**pen/Closed - открыт для расширения, закрыт для изменения
- **L**iskov Substitution - наследники должны заменять родителей
- **I**nterface Segregation - много специализированных интерфейсов
- **D**ependency Inversion - зависим от абстракций, не от конкретики

### MVP First
Сначала минимально работающий продукт, затем улучшения.

### Итеративная разработка
Короткие циклы разработки с постоянным тестированием.

## 3. 📁 Структура проекта

### Backend (Domain-Driven Design)
```
app/
├── Domain/                 # Бизнес-логика
│   ├── User/              # Домен пользователей
│   │   ├── Models/        # Eloquent модели
│   │   ├── Services/      # Бизнес-логика
│   │   ├── Actions/       # Сложные операции
│   │   ├── DTOs/          # Data Transfer Objects
│   │   └── Events/        # События домена
│   ├── Master/            # Домен мастеров
│   ├── Ad/                # Домен объявлений
│   ├── Booking/           # Домен бронирований
│   └── Payment/           # Домен платежей
├── Application/           # Слой приложения
│   ├── Http/
│   │   ├── Controllers/   # HTTP контроллеры
│   │   ├── Requests/      # Валидация запросов
│   │   └── Resources/     # API ресурсы
│   └── Console/           # CLI команды
└── Infrastructure/        # Инфраструктура
    ├── Repositories/      # Репозитории
    └── Services/          # Внешние сервисы
```

### Frontend (Feature-Sliced Design)
```
resources/js/src/
├── shared/                # Переиспользуемый код
│   ├── ui/               # UI компоненты
│   ├── lib/              # Утилиты
│   └── api/              # API клиент
├── entities/             # Бизнес-сущности
│   ├── master/
│   ├── booking/
│   └── user/
├── features/             # Функциональность
│   ├── auth/
│   ├── booking/
│   └── search/
├── widgets/              # Композитные блоки
│   ├── header/
│   └── master-card/
└── pages/               # Страницы
    ├── home/
    ├── masters/
    └── profile/
```

## 4. 🏗 Архитектура проекта

### Общая архитектура
```
┌─────────────────────────────────────────────┐
│                  Frontend                    │
│            Vue 3 + TypeScript                │
└────────────────┬────────────────────────────┘
                 │ Inertia.js
┌────────────────▼────────────────────────────┐
│                  Backend                     │
│              Laravel 12 (PHP)                │
├──────────────────────────────────────────────┤
│   Controllers  →  Services  →  Models        │
└────────────────┬────────────────────────────┘
                 │
┌────────────────▼────────────────────────────┐
│                Database                      │
│                 MySQL 8                      │
└──────────────────────────────────────────────┘
```

### Поток данных
1. **Пользователь** → взаимодействует с Vue компонентами
2. **Vue** → отправляет запросы через Inertia
3. **Inertia** → обрабатывает как SPA переходы
4. **Controller** → валидирует запрос
5. **Service** → выполняет бизнес-логику
6. **Model** → работает с БД
7. **Response** → возвращается через Inertia
8. **Vue** → обновляет интерфейс

## 5. 🗄 Модель данных

### Основные таблицы
```sql
-- Пользователи
users (id, name, email, password, role, created_at)

-- Профили мастеров
master_profiles (id, user_id, bio, experience, specializations, rating)

-- Объявления
ads (id, master_id, title, description, price, status, created_at)

-- Услуги
services (id, name, category, duration, base_price)

-- Бронирования
bookings (id, ad_id, client_id, date, time, status, total_amount)

-- Отзывы
reviews (id, booking_id, rating, comment, created_at)

-- Платежи
payments (id, booking_id, amount, status, method, created_at)
```

### Связи
- User **has one** MasterProfile
- MasterProfile **has many** Ads
- Ad **has many** Bookings
- Booking **has one** Review
- Booking **has one** Payment

## 6. 🔌 Работа с API/LLM

### RESTful API
```
GET    /api/masters          # Список мастеров
GET    /api/masters/{id}     # Детали мастера
POST   /api/bookings         # Создать бронирование
PUT    /api/bookings/{id}    # Обновить бронирование
DELETE /api/bookings/{id}    # Отменить бронирование
```

### Интеграции
- **Яндекс.Карты** - отображение локаций мастеров
- **SMS шлюз** - уведомления о бронированиях
- **Email сервис** - транзакционные письма
- **Платежный шлюз** - обработка оплаты

### Будущие интеграции (LLM)
- Умный поиск мастеров по описанию
- Автоматические ответы на вопросы
- Анализ отзывов для улучшения сервиса

## 7. 📊 Мониторинг

### Логирование
```php
// Уровни логирования
Log::debug('Детальная отладочная информация');
Log::info('Информационные сообщения');
Log::warning('Предупреждения');
Log::error('Ошибки');
Log::critical('Критические ошибки');

// Структурированное логирование
Log::info('Booking created', [
    'booking_id' => $booking->id,
    'user_id' => $user->id,
    'amount' => $booking->amount
]);
```

### Метрики
- **Бизнес-метрики**: количество бронирований, конверсия, средний чек
- **Технические метрики**: время ответа, использование памяти, количество запросов
- **Пользовательские метрики**: активные пользователи, удержание, NPS

### Инструменты
- **Laravel Telescope** - отладка в development
- **Sentry** - отслеживание ошибок в production
- **Google Analytics** - аналитика поведения
- **Custom Dashboard** - бизнес-метрики

## 8. 📋 Сценарии работы

### Сценарий 1: Клиент бронирует массаж
1. Клиент заходит на главную страницу
2. Использует поиск/фильтры для поиска мастера
3. Открывает профиль мастера
4. Просматривает услуги и отзывы
5. Выбирает услугу и время
6. Заполняет форму бронирования
7. Получает подтверждение
8. Мастер получает уведомление

### Сценарий 2: Мастер создает объявление
1. Мастер регистрируется/входит
2. Заполняет профиль
3. Создает объявление с услугами
4. Устанавливает расписание
5. Публикует объявление
6. Получает заявки от клиентов
7. Управляет бронированиями

### Сценарий 3: Администратор модерирует контент
1. Получает уведомление о новом объявлении
2. Проверяет содержание
3. Одобряет или отклоняет
4. Отправляет обратную связь мастеру

## 9. 🚀 Деплой

### Development
```bash
# Локальная разработка
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

### Staging
```bash
# Тестовый сервер
git push staging main
ssh staging.spa.com
cd /var/www/spa
./deploy.sh staging
```

### Production
```yaml
# GitHub Actions CI/CD
name: Deploy to Production
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Deploy to server
        run: |
          ssh ${{ secrets.SERVER }} "cd /var/www/spa && git pull && ./deploy.sh production"
```

### Docker
```dockerfile
FROM php:8.2-fpm
WORKDIR /var/www
COPY . .
RUN composer install --no-dev --optimize-autoloader
RUN npm ci && npm run build
EXPOSE 9000
CMD ["php-fpm"]
```

## 10. ⚙️ Конфигурирование и логирование

### Конфигурация через .env
```env
# Приложение
APP_NAME="SPA Platform"
APP_ENV=production
APP_DEBUG=false

# База данных
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=spa_platform
DB_USERNAME=spa_user
DB_PASSWORD=secure_password

# Кеш и сессии
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Внешние сервисы
YANDEX_MAPS_API_KEY=xxx
SMS_GATEWAY_TOKEN=xxx
MAIL_MAILER=smtp
```

### Логирование
```php
// config/logging.php
'channels' => [
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'level' => 'critical',
    ],
]
```

### Формат логов
```
[2025-01-22 10:15:32] production.INFO: Booking created {"booking_id":123,"user_id":456,"master_id":789,"amount":3500}
[2025-01-22 10:16:45] production.ERROR: Payment failed {"error":"Insufficient funds","user_id":456}
```

---

*Этот документ служит техническим компасом для разработки SPA Platform, обеспечивая единое понимание архитектуры и подходов.*