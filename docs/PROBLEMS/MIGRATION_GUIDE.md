# Руководство по миграции на модульную архитектуру

## 📋 Обзор

Это руководство описывает процесс постепенной миграции с монолитной архитектуры на модульную (Domain Driven Design) для проекта SPA Platform.

## 🎯 Цели миграции

1. **Разделение ответственности** - каждый модуль отвечает за свою бизнес-логику
2. **Улучшение тестируемости** - изолированные модули проще тестировать
3. **Масштабируемость** - легче добавлять новые функции
4. **Поддерживаемость** - понятная структура кода

## 🏗️ Архитектура

### Старая структура (монолит)
```
app/
├── Services/
│   ├── BookingService.php (800+ строк)
│   ├── UserService.php
│   └── SearchService.php
├── Models/
└── Http/Controllers/
```

### Новая структура (модульная)
```
app/
├── Domain/
│   ├── Booking/
│   │   ├── Services/
│   │   ├── Repositories/
│   │   ├── Actions/
│   │   └── DTOs/
│   ├── User/
│   └── Search/
├── Enums/
├── DTOs/
├── Repositories/
└── Services/Adapters/
```

## 🚀 Этапы миграции

### Этап 1: Подготовка (✅ Завершен)
- Создание новой структуры папок
- Реализация модулей по DDD принципам
- Написание юнит-тестов

### Этап 2: Адаптеры (✅ Завершен)
- Создание адаптеров для постепенного перехода
- Настройка Feature Flags
- Подключение мониторинга

### Этап 3: Постепенная миграция (🚧 В процессе)
- Включение новых сервисов для % пользователей
- Мониторинг ошибок и производительности
- Увеличение охвата

### Этап 4: Полный переход
- 100% пользователей на новой архитектуре
- Отключение адаптеров
- Удаление legacy кода

## 🛠️ Инструкция по миграции

### 1. Установка

```bash
# Установить зависимости
composer install

# Выполнить миграции
php artisan migrate

# Опубликовать конфигурацию
php artisan vendor:publish --tag=adapters-config
```

### 2. Запуск миграции

```bash
# Запустить полную миграцию
php artisan app:migrate-modular

# Запустить конкретный шаг
php artisan app:migrate-modular --step=2_enable_adapters

# Проверить перед запуском (dry run)
php artisan app:migrate-modular --dry-run
```

### 3. Мониторинг

```bash
# Показать панель мониторинга
php artisan app:monitor-migration

# Мониторинг в реальном времени
php artisan app:monitor-migration --realtime

# Сгенерировать отчет
php artisan app:monitor-migration --report
```

### 4. Управление Feature Flags

```bash
# Список всех флагов
php artisan feature:flag list

# Включить функцию
php artisan feature:flag enable use_modern_booking_service

# Установить процент пользователей
php artisan feature:flag set use_modern_booking_service --percentage=25

# Показать статистику
php artisan feature:flag stats use_modern_booking_service
```

## 📊 Feature Flags

### Основные флаги

| Флаг | Описание | По умолчанию |
|------|----------|--------------|
| `use_modern_booking_service` | Новый BookingService | false |
| `use_modern_search` | Новый поисковый движок | false |
| `use_adapters` | Использовать адаптеры | true |
| `log_legacy_calls` | Логировать legacy вызовы | true |

### Использование в коде

```php
// В контроллере
if (feature('use_modern_booking_service')) {
    $booking = $this->modernBookingService->create($data);
} else {
    $booking = $this->legacyBookingService->createBooking($data);
}

// С адаптером (рекомендуется)
$booking = $this->bookingAdapter->createBooking($data);
```

## 🔄 Адаптеры

Адаптеры обеспечивают совместимость между старым и новым кодом:

```php
// BookingServiceAdapter автоматически выбирает нужный сервис
$adapter = app(BookingServiceAdapter::class);
$booking = $adapter->createBooking($data);
```

### Доступные адаптеры:
- `BookingServiceAdapter` - для бронирований
- `MasterServiceAdapter` - для мастеров
- `SearchServiceAdapter` - для поиска

## 📈 Метрики успеха

### Что отслеживаем:
1. **Adoption Rate** - % успешных вызовов новых сервисов
2. **Error Rate** - количество ошибок
3. **Performance** - время ответа
4. **Legacy Calls** - количество вызовов старого кода

### Целевые показатели:
- Adoption Rate > 95%
- Error Rate < 0.1%
- Performance improvement > 20%
- Legacy Calls → 0

## ⚠️ Rollback план

В случае критических проблем:

```bash
# 1. Отключить новые сервисы
php artisan feature:flag disable use_modern_booking_service
php artisan feature:flag disable use_modern_search

# 2. Переключиться на legacy
php artisan feature:flag disable use_adapters

# 3. Откатить миграции (если нужно)
php artisan app:migrate-modular --rollback
```

## 🐛 Решение проблем

### Высокий Error Rate
1. Проверить логи: `tail -f storage/logs/laravel.log`
2. Уменьшить % пользователей
3. Исправить ошибки
4. Постепенно увеличивать охват

### Низкая производительность
1. Проверить кеш: `php artisan cache:clear`
2. Оптимизировать запросы: `php artisan app:optimize-performance`
3. Проверить индексы БД

### Legacy вызовы не уменьшаются
1. Проверить импорты в контроллерах
2. Убедиться, что используются адаптеры
3. Проверить конфигурацию DI контейнера

## 📚 Дополнительные ресурсы

- [Domain Driven Design](https://martinfowler.com/tags/domain%20driven%20design.html)
- [Feature Flags Best Practices](https://martinfowler.com/articles/feature-toggles.html)
- [Strangler Fig Pattern](https://martinfowler.com/bliki/StranglerFigApplication.html)

## 🤝 Поддержка

При возникновении вопросов:
1. Проверьте этот документ
2. Посмотрите логи и метрики
3. Обратитесь к команде разработки

---

*Последнее обновление: {{ date }}*