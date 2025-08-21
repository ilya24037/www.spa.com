# ✅ Чек-лист миграции на модульную архитектуру

## 📋 Подготовка

- [ ] Создать резервную копию базы данных
- [ ] Создать резервную копию кода
- [ ] Убедиться, что все тесты проходят
- [ ] Проверить наличие всех зависимостей
- [ ] Настроить мониторинг и алерты

## 🏗️ Этап 1: Подготовка окружения

- [ ] Выполнить миграции для Feature Flags
  ```bash
  php artisan migrate
  ```

- [ ] Добавить AdapterServiceProvider в config/app.php
  ```php
  App\Providers\AdapterServiceProvider::class,
  ```

- [ ] Опубликовать конфигурацию
  ```bash
  php artisan vendor:publish --tag=adapters-config
  ```

- [ ] Настроить логирование legacy вызовов
  ```bash
  php artisan feature:flag enable log_legacy_calls
  ```

## 🔄 Этап 2: Включение адаптеров

- [ ] Включить использование адаптеров
  ```bash
  php artisan feature:flag enable use_adapters
  ```

- [ ] Проверить, что адаптеры работают
  ```bash
  php artisan app:monitor-migration
  ```

- [ ] Убедиться, что нет ошибок в логах
  ```bash
  tail -f storage/logs/laravel.log
  ```

## 📦 Этап 3: Миграция BookingService

### Фаза 1: 10% пользователей
- [ ] Включить для 10% пользователей
  ```bash
  php artisan feature:flag set use_modern_booking_service --percentage=10
  ```

- [ ] Мониторить 24 часа
- [ ] Проверить метрики:
  - [ ] Error rate < 1%
  - [ ] Performance не ухудшилась
  - [ ] Adoption rate > 90%

### Фаза 2: 50% пользователей
- [ ] Увеличить до 50%
  ```bash
  php artisan feature:flag set use_modern_booking_service --percentage=50
  ```

- [ ] Мониторить 48 часов
- [ ] Проверить обратную связь от пользователей
- [ ] Исправить найденные проблемы

### Фаза 3: 100% пользователей
- [ ] Включить для всех
  ```bash
  php artisan feature:flag set use_modern_booking_service --percentage=100
  ```

- [ ] Мониторить 1 неделю
- [ ] Убедиться в стабильности

## 🔍 Этап 4: Миграция SearchEngine

- [ ] Переиндексировать данные
  ```bash
  php artisan search:reindex --force
  ```

- [ ] Включить новый поиск
  ```bash
  php artisan feature:flag enable use_modern_search
  ```

- [ ] Проверить функциональность поиска
- [ ] Сравнить результаты со старым поиском

## 👤 Этап 5: Миграция MasterService

- [ ] Обновить импорты в контроллерах
- [ ] Проверить все endpoints
- [ ] Убедиться, что API совместим

## 🧹 Этап 6: Очистка

- [ ] Отключить legacy логирование
  ```bash
  php artisan feature:flag disable log_legacy_calls
  ```

- [ ] Отключить адаптеры
  ```bash
  php artisan feature:flag disable use_adapters
  ```

- [ ] Архивировать старый код
  ```bash
  php artisan app:migrate-modular --step=6_cleanup_legacy
  ```

## 📊 Проверка результатов

### Производительность
- [ ] Время ответа улучшилось на 20%+
- [ ] Потребление памяти снизилось
- [ ] Cache hit rate > 80%

### Качество кода
- [ ] Все тесты проходят
- [ ] Code coverage > 80%
- [ ] Нет критических замечаний от анализаторов

### Стабильность
- [ ] Error rate < 0.1%
- [ ] Uptime > 99.9%
- [ ] Нет критических инцидентов

## 🚨 Контрольные точки

### После каждого этапа проверить:
- [ ] Логи на наличие ошибок
- [ ] Метрики производительности
- [ ] Feedback от пользователей
- [ ] Состояние базы данных

### Критерии остановки миграции:
- [ ] Error rate > 5%
- [ ] Performance degradation > 50%
- [ ] Критические ошибки в production
- [ ] Массовые жалобы пользователей

## 📞 Контакты для экстренных случаев

- **DevOps дежурный**: +7 (XXX) XXX-XX-XX
- **Tech Lead**: email@example.com
- **Slack канал**: #migration-support

## 📝 Заметки

_Место для заметок о ходе миграции:_

```
Дата: ___________
Этап: ___________
Статус: _________
Проблемы: _______
________________
________________
```

---

*Версия чек-листа: 1.0*
*Дата создания: {{ date }}*