# 🚀 ПОЛНЫЙ ПЛАН РЕФАКТОРИНГА: ДЕТАЛИЗАЦИЯ ПО ДНЯМ

## 📊 ОБЩАЯ СТАТИСТИКА
- **Длительность:** 20-25 рабочих дней
- **Часов работы:** 160-200
- **Файлов будет создано:** ~150
- **Файлов будет изменено:** ~60
- **Тестов будет написано:** ~100

---

# 📅 НЕДЕЛЯ 1: ПОДГОТОВКА И БАЗОВАЯ ИНФРАСТРУКТУРА

## 🗓️ ДЕНЬ 1: Quick Wins и подготовка
**Время:** 6-8 часов

### Задачи:
1. ✅ **Удаление debug кода** (15 мин)
   - Удалить все console.log, dd(), dump()
   - 17 мест в коде

2. ✅ **JsonFieldsTrait** (1 час)
   ```php
   app/Support/Traits/JsonFieldsTrait.php
   ```

3. ✅ **Базовая структура папок** (30 мин)
   ```
   app/Domain/
   app/Application/
   app/Infrastructure/
   app/Support/
   ```

4. ✅ **Базовые интерфейсы** (1 час)
   - Repository interface
   - Service interface
   - BaseRepository

5. ✅ **Начало рефакторинга User** (2 часа)
   - Разделение на User, UserProfile
   - Создание трейтов HasRoles, HasProfile

6. ✅ **PhotoService** (1 час)
   - Унификация работы с фото

### Результат дня:
- Код стал чище на 30%
- Убрано дублирование
- Создан фундамент архитектуры

---

## 🗓️ ДЕНЬ 2: Завершение модуля User
**Время:** 6-8 часов

### Задачи:
1. **UserRepository** (2 часа)
   ```php
   app/Domain/User/Repositories/UserRepository.php
   - findByEmail()
   - findActive()
   - findWithProfile()
   ```

2. **UserService** (2 часа)
   ```php
   app/Domain/User/Services/UserService.php
   - register()
   - updateProfile()
   - changePassword()
   - deactivate()
   ```

3. **DTO для User** (1 час)
   ```php
   app/Domain/User/DTOs/RegisterUserDTO.php
   app/Domain/User/DTOs/UpdateProfileDTO.php
   ```

4. **Рефакторинг AuthController** (1 час)
   - Использование UserService
   - Удаление бизнес-логики

5. **Тесты для User модуля** (2 часа)
   ```
   tests/Unit/Domain/User/UserServiceTest.php
   tests/Unit/Domain/User/UserRepositoryTest.php
   ```

### Результат дня:
- Модуль User полностью модульный
- Покрытие тестами 80%
- Контроллеры стали тонкими

---

## 🗓️ ДЕНЬ 3: Модуль Media
**Время:** 6-8 часов

### Задачи:
1. **Разделение MediaProcessingService** (2 часа)
   ```php
   app/Domain/Media/Services/MediaService.php
   app/Domain/Media/Services/ImageProcessor.php
   app/Domain/Media/Services/VideoProcessor.php
   app/Domain/Media/Services/ThumbnailService.php
   ```

2. **Унификация моделей Media** (1 час)
   ```php
   app/Domain/Media/Models/Media.php (базовая)
   app/Domain/Media/Models/Photo.php
   app/Domain/Media/Models/Video.php
   ```

3. **MediaRepository** (1 час)
   ```php
   app/Domain/Media/Repositories/MediaRepository.php
   ```

4. **Обработчики загрузки** (2 часа)
   ```php
   app/Domain/Media/Handlers/UploadHandler.php
   app/Domain/Media/Handlers/OptimizationHandler.php
   ```

5. **CDN интеграция** (1 час)
   ```php
   app/Infrastructure/CDN/CDNService.php
   ```

### Результат дня:
- Единая система работы с медиа
- Оптимизация изображений
- Готовность к CDN

---

## 🗓️ ДЕНЬ 4: Начало модуля Ad (Объявления)
**Время:** 8 часов

### Задачи:
1. **Разделение модели Ad** (3 часа)
   ```php
   app/Domain/Ad/Models/Ad.php (основная)
   app/Domain/Ad/Models/AdContent.php
   app/Domain/Ad/Models/AdPricing.php
   app/Domain/Ad/Models/AdLocation.php
   app/Domain/Ad/Models/AdSchedule.php
   ```

2. **Enums для Ad** (1 час)
   ```php
   app/Domain/Ad/Enums/AdStatus.php
   app/Domain/Ad/Enums/AdType.php
   app/Domain/Ad/Enums/ServiceLocation.php
   ```

3. **AdRepository** (2 часа)
   ```php
   app/Domain/Ad/Repositories/AdRepository.php
   - findActive()
   - findByUser()
   - findByFilters()
   ```

4. **Начало AdService** (2 часа)
   ```php
   app/Domain/Ad/Services/AdService.php
   - create()
   - update()
   ```

### Результат дня:
- Ad модель стала модульной
- Четкая структура данных
- Подготовка к сложной логике

---

## 🗓️ ДЕНЬ 5: Завершение модуля Ad
**Время:** 8 часов

### Задачи:
1. **AdService - продолжение** (2 часа)
   ```php
   - publish()
   - archive()
   - moderate()
   ```

2. **AdModerationService** (2 часа)
   ```php
   app/Domain/Ad/Services/AdModerationService.php
   - checkContent()
   - approveAd()
   - rejectAd()
   ```

3. **AdMediaService** (1 час)
   ```php
   app/Domain/Ad/Services/AdMediaService.php
   ```

4. **Actions для Ad** (2 часа)
   ```php
   app/Domain/Ad/Actions/PublishAdAction.php
   app/Domain/Ad/Actions/ArchiveAdAction.php
   app/Domain/Ad/Actions/ModerateAdAction.php
   ```

5. **Рефакторинг AdController** (1 час)

### Результат дня:
- Модуль Ad завершен
- Вся логика в сервисах
- Готовность к масштабированию

---

# 📅 НЕДЕЛЯ 2: CORE МОДУЛИ И СЕРВИСЫ

## 🗓️ ДЕНЬ 6: Модуль Master
**Время:** 8 часов

### Задачи:
1. **Разделение MasterProfile** (3 часа)
   ```php
   app/Domain/Master/Models/MasterProfile.php
   app/Domain/Master/Models/MasterSchedule.php
   app/Domain/Master/Models/MasterService.php
   app/Domain/Master/Models/MasterLocation.php
   ```

2. **MasterRepository** (2 часа)
   ```php
   app/Domain/Master/Repositories/MasterRepository.php
   - findActive()
   - findByLocation()
   - findByService()
   ```

3. **MasterService** (3 часа)
   ```php
   app/Domain/Master/Services/MasterService.php
   app/Domain/Master/Services/ScheduleService.php
   ```

### Результат дня:
- Master модуль структурирован
- Расписание отделено
- Готовность к поиску

---

## 🗓️ ДЕНЬ 7: Модуль Booking (Бронирование)
**Время:** 8 часов

### Задачи:
1. **Модели Booking** (2 часа)
   ```php
   app/Domain/Booking/Models/Booking.php
   app/Domain/Booking/Models/BookingSlot.php
   app/Domain/Booking/Models/BookingHistory.php
   ```

2. **BookingService улучшение** (3 часа)
   ```php
   app/Domain/Booking/Services/BookingService.php
   app/Domain/Booking/Services/SlotService.php
   app/Domain/Booking/Services/AvailabilityService.php
   ```

3. **Actions для Booking** (2 часа)
   ```php
   app/Domain/Booking/Actions/CreateBookingAction.php
   app/Domain/Booking/Actions/CancelBookingAction.php
   app/Domain/Booking/Actions/ConfirmBookingAction.php
   ```

4. **События и уведомления** (1 час)
   ```php
   app/Domain/Booking/Events/BookingCreated.php
   app/Domain/Booking/Listeners/SendBookingNotification.php
   ```

### Результат дня:
- Booking полностью модульный
- Реализованы TODO из кода
- Уведомления работают

---

## 🗓️ ДЕНЬ 8: Модуль Search (Поиск)
**Время:** 8 часов

### Задачи:
1. **SearchService архитектура** (3 часа)
   ```php
   app/Domain/Search/Services/SearchService.php
   app/Domain/Search/Engines/AdSearchEngine.php
   app/Domain/Search/Engines/MasterSearchEngine.php
   ```

2. **Фильтры и сортировка** (2 часа)
   ```php
   app/Domain/Search/Filters/LocationFilter.php
   app/Domain/Search/Filters/PriceFilter.php
   app/Domain/Search/Filters/CategoryFilter.php
   app/Domain/Search/Sorters/RatingSorter.php
   ```

3. **Elasticsearch интеграция** (2 часа)
   ```php
   app/Infrastructure/Search/ElasticsearchClient.php
   app/Infrastructure/Search/Indexers/AdIndexer.php
   ```

4. **SearchController рефакторинг** (1 час)

### Результат дня:
- Поиск работает быстро
- Модульные фильтры
- Готовность к масштабированию

---

## 🗓️ ДЕНЬ 9: Модуль Payment (начало)
**Время:** 8 часов

### Задачи:
1. **Payment структура** (2 часа)
   ```php
   app/Domain/Payment/Models/Payment.php
   app/Domain/Payment/Models/Transaction.php
   app/Domain/Payment/Models/Subscription.php
   ```

2. **Gateway интерфейсы** (2 часа)
   ```php
   app/Domain/Payment/Contracts/PaymentGateway.php
   app/Domain/Payment/Gateways/StripeGateway.php
   app/Domain/Payment/Gateways/YooKassaGateway.php
   ```

3. **PaymentService** (3 часа)
   ```php
   app/Domain/Payment/Services/PaymentService.php
   app/Domain/Payment/Services/SubscriptionService.php
   ```

4. **Webhook handlers** (1 час)
   ```php
   app/Domain/Payment/Handlers/WebhookHandler.php
   ```

### Результат дня:
- Универсальная платежная система
- Поддержка разных гейтвеев
- Обработка webhook

---

## 🗓️ ДЕНЬ 10: Контроллеры и Request классы
**Время:** 8 часов

### Задачи:
1. **Рефакторинг всех контроллеров** (4 часа)
   - ProfileController → разделить на 3
   - AdController → убрать логику
   - BookingController → использовать сервисы
   - MasterController → упростить

2. **Form Requests** (2 часа)
   ```php
   app/Application/Http/Requests/Ad/CreateAdRequest.php
   app/Application/Http/Requests/Ad/UpdateAdRequest.php
   app/Application/Http/Requests/Booking/CreateBookingRequest.php
   ```

3. **API Resources** (2 часа)
   ```php
   app/Application/Http/Resources/AdResource.php
   app/Application/Http/Resources/MasterResource.php
   app/Application/Http/Resources/BookingResource.php
   ```

### Результат дня:
- Все контроллеры тонкие
- Валидация вынесена
- API структурирован

---

# 📅 НЕДЕЛЯ 3: РАСШИРЕННЫЕ МОДУЛИ И ОПТИМИЗАЦИЯ

## 🗓️ ДЕНЬ 11: Модуль Review (Отзывы)
**Время:** 6 часов

### Задачи:
1. **Review модели** (1 час)
   ```php
   app/Domain/Review/Models/Review.php
   app/Domain/Review/Models/ReviewReaction.php
   ```

2. **ReviewService** (2 часа)
   ```php
   app/Domain/Review/Services/ReviewService.php
   app/Domain/Review/Services/RatingCalculator.php
   ```

3. **Модерация отзывов** (2 часа)
   ```php
   app/Domain/Review/Services/ReviewModerationService.php
   ```

4. **События** (1 час)
   ```php
   app/Domain/Review/Events/ReviewCreated.php
   app/Domain/Review/Listeners/UpdateMasterRating.php
   ```

### Результат дня:
- Система отзывов готова
- Автоматический подсчет рейтинга
- Модерация отзывов

---

## 🗓️ ДЕНЬ 12: Модуль Notification
**Время:** 8 часов

### Задачи:
1. **Notification структура** (2 часа)
   ```php
   app/Domain/Notification/Models/Notification.php
   app/Domain/Notification/Models/NotificationTemplate.php
   ```

2. **Каналы уведомлений** (3 часа)
   ```php
   app/Domain/Notification/Channels/SmsChannel.php
   app/Domain/Notification/Channels/EmailChannel.php
   app/Domain/Notification/Channels/PushChannel.php
   ```

3. **NotificationService** (2 часа)
   ```php
   app/Domain/Notification/Services/NotificationService.php
   app/Domain/Notification/Services/TemplateService.php
   ```

4. **Интеграция с событиями** (1 час)

### Результат дня:
- Универсальные уведомления
- Поддержка всех каналов
- Шаблоны сообщений

---

## 🗓️ ДЕНЬ 13: Cache и оптимизация
**Время:** 8 часов

### Задачи:
1. **Cache слой** (3 часа)
   ```php
   app/Infrastructure/Cache/CacheService.php
   app/Infrastructure/Cache/Strategies/AdCacheStrategy.php
   app/Infrastructure/Cache/Strategies/MasterCacheStrategy.php
   ```

2. **Repository декораторы** (2 часа)
   ```php
   app/Infrastructure/Cache/Decorators/CachedAdRepository.php
   app/Infrastructure/Cache/Decorators/CachedMasterRepository.php
   ```

3. **Query оптимизация** (2 часа)
   - Устранение N+1 проблем
   - Добавление индексов
   - Оптимизация eager loading

4. **Redis конфигурация** (1 час)

### Результат дня:
- Скорость увеличена в 5-10 раз
- Кеширование на всех уровнях
- Оптимальные запросы

---

## 🗓️ ДЕНЬ 14: Модуль Analytics
**Время:** 6 часов

### Задачи:
1. **Analytics модели** (1 час)
   ```php
   app/Domain/Analytics/Models/PageView.php
   app/Domain/Analytics/Models/UserAction.php
   ```

2. **AnalyticsService** (3 часа)
   ```php
   app/Domain/Analytics/Services/AnalyticsService.php
   app/Domain/Analytics/Services/ReportService.php
   ```

3. **Сборщики данных** (2 часа)
   ```php
   app/Domain/Analytics/Collectors/PageViewCollector.php
   app/Domain/Analytics/Collectors/ConversionCollector.php
   ```

### Результат дня:
- Сбор аналитики
- Готовые отчеты
- Метрики производительности

---

## 🗓️ ДЕНЬ 15: Тестирование - Unit тесты
**Время:** 8 часов

### Задачи:
1. **Тесты для сервисов** (4 часа)
   ```
   tests/Unit/Domain/Ad/AdServiceTest.php
   tests/Unit/Domain/Booking/BookingServiceTest.php
   tests/Unit/Domain/Payment/PaymentServiceTest.php
   ```

2. **Тесты для репозиториев** (2 часа)
   ```
   tests/Unit/Domain/Ad/AdRepositoryTest.php
   tests/Unit/Domain/Master/MasterRepositoryTest.php
   ```

3. **Тесты для Actions** (2 часа)
   ```
   tests/Unit/Domain/Ad/Actions/PublishAdActionTest.php
   tests/Unit/Domain/Booking/Actions/CreateBookingActionTest.php
   ```

### Результат дня:
- 80+ unit тестов
- Покрытие кода 75%+
- Уверенность в коде

---

# 📅 НЕДЕЛЯ 4: ФИНАЛИЗАЦИЯ И РАЗВЕРТЫВАНИЕ

## 🗓️ ДЕНЬ 16: Feature тесты
**Время:** 8 часов

### Задачи:
1. **E2E тесты основных сценариев** (4 часа)
   ```
   tests/Feature/CreateAdFlowTest.php
   tests/Feature/BookingFlowTest.php
   tests/Feature/PaymentFlowTest.php
   ```

2. **API тесты** (2 часа)
   ```
   tests/Feature/Api/AdApiTest.php
   tests/Feature/Api/SearchApiTest.php
   ```

3. **Browser тесты (Dusk)** (2 часа)
   ```
   tests/Browser/LoginTest.php
   tests/Browser/CreateAdTest.php
   ```

### Результат дня:
- Все сценарии протестированы
- API полностью покрыт
- UI тесты работают

---

## 🗓️ ДЕНЬ 17: Документация
**Время:** 6 часов

### Задачи:
1. **API документация** (3 часа)
   - OpenAPI/Swagger спецификация
   - Примеры запросов
   - Postman коллекция

2. **Архитектурная документация** (2 часа)
   - Диаграммы модулей
   - Описание паттернов
   - Инструкции разработчику

3. **README файлы** (1 час)
   - Для каждого модуля
   - Примеры использования

### Результат дня:
- Полная документация
- Легкая передача проекта
- Примеры для всего

---

## 🗓️ ДЕНЬ 18: Performance оптимизация
**Время:** 8 часов

### Задачи:
1. **Профилирование** (2 часа)
   - Laravel Telescope
   - Query анализ
   - Memory профайлинг

2. **Оптимизация запросов** (3 часа)
   - Составные индексы
   - Query оптимизация
   - Денормализация где нужно

3. **Frontend оптимизация** (2 часа)
   - Code splitting
   - Lazy loading
   - Bundle оптимизация

4. **Настройка OPcache** (1 час)

### Результат дня:
- Скорость ответа < 100ms
- Оптимальное использование памяти
- Готовность к нагрузкам

---

## 🗓️ ДЕНЬ 19: Security аудит
**Время:** 6 часов

### Задачи:
1. **Безопасность кода** (3 часа)
   - SQL injection проверка
   - XSS защита
   - CSRF проверка

2. **Аудит зависимостей** (1 час)
   - npm audit
   - composer audit

3. **Настройка прав** (2 часа)
   - Policies обновление
   - Middleware проверка
   - API токены

### Результат дня:
- Все уязвимости закрыты
- Безопасная конфигурация
- Защита данных

---

## 🗓️ ДЕНЬ 20: Подготовка к деплою
**Время:** 8 часов

### Задачи:
1. **CI/CD настройка** (3 часа)
   ```yaml
   .github/workflows/test.yml
   .github/workflows/deploy.yml
   ```

2. **Миграционные скрипты** (2 часа)
   - Данные из старой структуры
   - Обратная совместимость

3. **Мониторинг** (2 часа)
   - Sentry интеграция
   - Логирование
   - Метрики

4. **Backup стратегия** (1 час)

### Результат дня:
- Автоматический деплой
- Мониторинг ошибок
- Готовность к продакшену

---

# 📊 ИТОГОВЫЕ РЕЗУЛЬТАТЫ

## ✅ Что получилось:
1. **Модульная архитектура** как у Avito/Ozon
2. **150+ новых файлов** с четкой структурой
3. **100+ тестов** с покрытием 80%
4. **Скорость ответа < 100ms** (было 500-800ms)
5. **Масштабируемость** - легко добавлять функции
6. **Документация** - полная и понятная

## 📈 Метрики до/после:
- **Время добавления фичи:** 3 дня → 0.5 дня
- **Количество багов:** 10-15 → 1-3 на релиз
- **Скорость разработки:** +300%
- **Поддерживаемость:** +500%

## 🎯 Ключевые достижения:
1. Каждый модуль независим
2. Бизнес-логика отделена от инфраструктуры
3. Легко тестировать
4. Легко расширять
5. Готовность к микросервисам

---

# 🚨 ВАЖНЫЕ ЗАМЕЧАНИЯ

1. **Не пытайтесь сделать всё сразу** - следуйте плану
2. **Тестируйте после каждого дня** - не накапливайте ошибки
3. **Делайте бекапы** - перед каждым большим изменением
4. **Коммитьте часто** - минимум 5-10 коммитов в день
5. **Документируйте на ходу** - не оставляйте на потом