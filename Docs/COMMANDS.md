# Команды проекта SPA Platform

Полный справочник команд для разработки, тестирования и отладки проекта.

---

## 🚀 Разработка

### Запуск серверов
```bash
# Запуск всех серверов одновременно (Laravel + Queue + Logs + Vite)
composer dev

# Только Vite dev сервер
npm run dev

# Только Laravel сервер
php artisan serve

# Очередь задач (jobs)
php artisan queue:work

# Просмотр логов в реальном времени
php artisan pail
```

---

## ✅ Проверка качества кода

### TypeScript и ESLint
```bash
# Валидация TypeScript типов
npm run type-check

# Исправить проблемы ESLint автоматически
npm run lint

# Только проверить проблемы ESLint (без исправления)
npm run lint:check
```

### PHP стиль кода
```bash
# Исправить стиль PHP кода (Laravel Pint)
php artisan pint

# Только проверить стиль без исправления
php artisan pint --test
```

---

## 🧪 Тестирование

### Backend тесты
```bash
# Запустить все тесты Laravel
php artisan test

# Или через composer
composer test

# Запустить конкретный тест
php artisan test --filter=BookingTest

# С покрытием кода
php artisan test --coverage
```

### Frontend тесты
```bash
# Запустить тесты Vitest
npm run test

# Один раз (CI режим)
npm run test:unit

# Режим наблюдения (watch mode)
npm run test:watch

# С покрытием кода
npm run test:coverage
```

---

## 🗄️ База данных

### Миграции
```bash
# Выполнить миграции
php artisan migrate

# Откатить последнюю миграцию
php artisan migrate:rollback

# Сбросить БД и выполнить все миграции
php artisan migrate:fresh

# Сбросить БД, выполнить миграции и сиды
php artisan migrate:fresh --seed
```

### Сиды (наполнение данными)
```bash
# Выполнить все сиды
php artisan db:seed

# Выполнить конкретный сид
php artisan db:seed --class=AdminSeeder
php artisan db:seed --class=MasterSeeder
```

### Отладка БД
```bash
# Открыть интерактивную консоль Tinker
php artisan tinker

# Примеры использования Tinker:
# >>> $user = User::find(1)
# >>> $masters = Master::with('user')->get()
# >>> Ad::where('status', 'published')->count()
```

---

## 🏗️ Сборка

### Production сборка
```bash
# Полная продакшн сборка фронтенда
npm run build

# Быстрая сборка (пропустить проверку типов)
npm run build:fast

# Оптимизация Laravel для продакшена
php artisan optimize

# Очистка кеша конфигурации
php artisan optimize:clear
```

### Кеш
```bash
# Очистить кеш приложения
php artisan cache:clear

# Очистить кеш конфигурации
php artisan config:clear

# Очистить кеш роутов
php artisan route:clear

# Очистить кеш представлений
php artisan view:clear

# Очистить всё
php artisan optimize:clear
```

---

## 🔍 Отладка

### Быстрая отладка
```bash
# Интерактивная консоль
php artisan tinker

# Проверка типов TypeScript
npm run type-check

# Просмотр логов в реальном времени
tail -f storage/logs/laravel.log
# Или
php artisan pail
```

### Поиск в коде
```bash
# Найти в PHP коде
grep -r "название_метода" app/

# Найти в бизнес-логике
grep -r "текст_ошибки" app/Domain/*/Actions/

# Найти в Vue компонентах
grep -r "компонент" resources/js/

# Найти во всём проекте
grep -r "поисковый_запрос" .
```

### Информация о системе
```bash
# Версия Laravel
php artisan --version

# Список всех команд artisan
php artisan list

# Информация о приложении
php artisan about

# Список роутов
php artisan route:list

# Список очередей
php artisan queue:monitor
```

---

## 🔧 Filament (Админ-панель)

### Команды Filament
```bash
# Обновить Filament
php artisan filament:upgrade

# Создать пользователя админки
php artisan make:filament-user

# Очистить кеш Filament
php artisan filament:clear-cache

# Создать Resource
php artisan make:filament-resource Master

# Создать Widget
php artisan make:filament-widget StatsOverview
```

---

## 🌐 Chrome DevTools MCP (Браузерное тестирование)

```bash
# Проверить статус MCP
node scripts/chrome-devtools-mcp.js status

# Примеры команд для тестирования с Claude:
"Check the performance of http://localhost:8000"
"Test booking calendar at http://localhost:8000/masters/1"
"Test search functionality at http://localhost:8000"
"Test mobile view at http://localhost:8000"
```

**Файлы**:
- `tests/e2e/chrome-devtools-test.js` - Тестовые сценарии
- `scripts/chrome-devtools-mcp.js` - Helper скрипт
- `Docs/MCP_CHROME_DEVTOOLS.md` - Полная документация

---

## 📦 Зависимости

### Установка
```bash
# Установить PHP зависимости
composer install

# Установить JS зависимости
npm install

# Обновить зависимости
composer update
npm update
```

---

## 🔐 Права доступов (для Linux/Mac)

```bash
# Установить права на storage и cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 🚨 Экстренное восстановление

### Полный сброс проекта
```bash
# 1. Очистить все кеши
php artisan optimize:clear

# 2. Переустановить зависимости
composer install
npm install

# 3. Сбросить БД
php artisan migrate:fresh --seed

# 4. Собрать фронтенд
npm run build

# 5. Оптимизировать
php artisan optimize
```

### Типичные проблемы
```bash
# Ошибка прав доступа
chmod -R 775 storage bootstrap/cache

# Ошибка "Class not found"
composer dump-autoload

# Ошибка конфигурации
php artisan config:clear

# Ошибка роутов
php artisan route:clear
```

---

## 📚 Полезные ссылки

- **Laravel Docs**: https://laravel.com/docs
- **Vue 3 Docs**: https://vuejs.org
- **Filament Docs**: https://filamentphp.com/docs
- **Tailwind CSS**: https://tailwindcss.com/docs
