# Установка и настройка Filament Admin Panel

## Установка Filament

1. **Установить пакет Filament:**
```bash
composer require filament/filament:"^3.0-stable" -W
```

2. **Опубликовать конфигурационные файлы:**
```bash
php artisan vendor:publish --tag=filament-config
php artisan vendor:publish --tag=filament-panels-config
```

3. **Установить Filament панель:**
```bash
php artisan filament:install --panels
```

4. **Создать администратора:**
```bash
php artisan make:filament-user
```
При запросе введите:
- Name: Admin
- Email: admin@spa.com
- Password: ваш_пароль

## Структура созданных файлов

### Основные компоненты:

1. **AdminPanelProvider** (`app/Providers/Filament/AdminPanelProvider.php`)
   - Основная конфигурация админ-панели
   - Настройки цветовой схемы, навигации, middleware

2. **FilamentAdminAccess Middleware** (`app/Http/Middleware/FilamentAdminAccess.php`)
   - Проверка доступа только для ADMIN и MODERATOR ролей

### Ресурсы (Resources):

1. **AdResource** - Управление объявлениями
   - Полный CRUD функционал
   - Модерация (одобрить/отклонить/заблокировать)
   - Массовые действия
   - Фильтры по статусам
   - Интеграция с AdModerationService

2. **UserResource** - Управление пользователями
   - Блокировка/разблокировка
   - Верификация
   - Смена паролей
   - Управление ролями
   - Статистика по объявлениям

3. **ComplaintResource** - Управление жалобами
   - Рассмотрение жалоб
   - Разрешение/отклонение
   - Блокировка объявлений по результатам
   - История решений

4. **MasterProfileResource** - Управление мастерами
   - Модерация профилей
   - Верификация
   - Премиум статусы
   - Статистика и рейтинги

### Виджеты (Widgets) для Dashboard:

1. **StatsOverview** - Общая статистика
2. **RecentAds** - Последние объявления
3. **RecentComplaints** - Последние жалобы
4. **RecentUsers** - Новые пользователи
5. **AdsChart** - График объявлений за 30 дней

## Доступ к админ-панели

После установки админ-панель будет доступна по адресу:
```
http://spa.test/admin
```

## Права доступа

Доступ к админ-панели имеют только пользователи с ролями:
- `ADMIN` - полный доступ
- `MODERATOR` - доступ к модерации

## Команды для разработки

```bash
# Очистить кэш Filament
php artisan filament:cache-clear

# Обновить assets
php artisan filament:assets

# Создать новый ресурс
php artisan make:filament-resource ModelName

# Создать новый виджет
php artisan make:filament-widget WidgetName
```

## Интеграция с существующим проектом

Filament полностью интегрирован с существующей структурой проекта:
- Использует существующие модели из DDD структуры
- Интегрирован с существующими Enums (AdStatus, UserRole, UserStatus, MasterStatus)
- Использует существующие сервисы (AdModerationService, AdminActionsService)
- Соблюдает принципы SOLID и KISS

## Преимущества замены старой админ-панели

1. **Разделение ответственности** - админка отделена от ProfileController
2. **Готовый функционал** - таблицы, фильтры, формы, виджеты
3. **Безопасность** - встроенная авторизация и проверка прав
4. **Масштабируемость** - легко добавлять новые ресурсы
5. **Производительность** - оптимизированные запросы, пагинация
6. **UX** - современный интерфейс, адаптивный дизайн

## Следующие шаги

1. Запустить команды установки Filament
2. Создать администратора
3. Протестировать функционал
4. Настроить дополнительные ресурсы при необходимости (PaymentResource, ReviewResource, etc.)