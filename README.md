# 🎯 SPA Platform - Платформа услуг массажа

Современная платформа для поиска мастеров массажа и бронирования услуг в стиле Avito/Ozon.

## 🚀 Быстрый старт

### Требования
- PHP 8.2+
- Laravel 12
- Node.js 18+
- MySQL 8.0+
- Composer
- npm

### Установка

1. **Клонировать репозиторий**
```bash
git clone [repository-url]
cd spa-platform
```

2. **Установить зависимости**
```bash
composer install
npm install
```

3. **Настройка окружения**
```bash
cp .env.example .env
# Настроить .env файл с вашими данными БД
```

4. **Настройка базы данных**
```bash
php artisan migrate
php artisan db:seed
```

5. **Запуск серверов**
```bash
# Терминал 1: Laravel
php artisan serve

# Терминал 2: Vite (Hot Reload)
npm run dev
```

6. **Открыть в браузере**
```
http://localhost:8000
```

## 🏗️ Архитектура

### Backend (Laravel 12)
- **Resource Controllers** для CRUD операций
- **Form Requests** для валидации данных
- **Service Layer** для бизнес-логики
- **API Resources** для форматирования ответов
- **Repository Pattern** для работы с БД

### Frontend (Vue 3 + Inertia.js)
- **Composition API** с `<script setup>`
- **TypeScript** для типизации
- **Pinia** для управления состоянием
- **Tailwind CSS** для стилизации
- **Mobile-first** подход

### База данных
- **MySQL** как основная БД
- **Атомарные миграции** (одна таблица = одна миграция)
- **Индексы** на foreign keys
- **Soft deletes** для важных данных

## 📁 Структура проекта

```
spa-platform/
├── app/                    # Laravel приложение
│   ├── Http/Controllers/  # Контроллеры
│   ├── Services/          # Сервисы
│   ├── Models/            # Модели
│   └── Domain/            # Доменная логика
├── resources/
│   └── js/
│       ├── Components/    # Vue компоненты
│       │   ├── UI/        # Базовые UI элементы
│       │   ├── Common/    # Общие компоненты
│       │   ├── Features/  # Функциональные модули
│       │   └── Pages/     # Страницы
│       ├── stores/        # Pinia stores
│       └── types/         # TypeScript типы
├── database/
│   ├── migrations/        # Миграции БД
│   └── seeders/          # Сиды данных
├── project/               # Документация проекта
└── .cursor/               # Настройки Cursor
```

## 🎯 Модульная архитектура

### Структура модуля
```
FeatureName/
├── index.vue         # Основной компонент
├── components/       # Подкомпоненты
├── store/           # Pinia store модуля
├── types/           # TypeScript типы
├── api/             # API методы модуля
└── styles/          # Стили модуля
```

### Примеры модулей
- **SearchModule** - поиск мастеров
- **BookingModule** - система бронирования
- **ReviewsModule** - отзывы и рейтинги
- **ProfileModule** - управление профилями

## 🛠️ Разработка

### Команды Laravel
```bash
# Создание файлов
php artisan make:controller ControllerName
php artisan make:service ServiceName
php artisan make:migration create_table_name_table

# Работа с БД
php artisan migrate
php artisan migrate:rollback
php artisan db:seed

# Кеш и конфигурация
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Команды Frontend
```bash
# Разработка
npm run dev          # Hot reload для разработки
npm run build        # Продакшен сборка
npm run lint         # Проверка стиля

# Тестирование
npm test             # Запуск тестов
npm run test:watch   # Тесты в режиме наблюдения
```

### Git workflow
```bash
# Создание ветки
git checkout -b feature/feature-name

# Коммит
git add .
git commit -m "feat(scope): description"

# Пуш
git push origin feature/feature-name
```

## 📋 Статус проекта

### Текущий прогресс: 86% до MVP

#### ✅ Завершено
- [x] Базовая структура проекта
- [x] Система аутентификации
- [x] CRUD для объявлений
- [x] Загрузка изображений
- [x] Профили мастеров
- [x] Базовая валидация
- [x] Модульная архитектура

#### 🚨 Критично (8%)
- [ ] Система бронирования
- [ ] Интеграция с платежами

#### ⚠️ Важно (14%)
- [ ] Поиск мастеров
- [ ] Система отзывов
- [ ] Уведомления

## 🧪 Тестирование

### Запуск тестов
```bash
# Unit тесты
php artisan test

# Feature тесты
php artisan test --testsuite=Feature

# Frontend тесты
npm test
```

### Покрытие тестами
- **Цель**: 90%+ покрытие
- **Backend**: PHPUnit
- **Frontend**: Jest + Vue Test Utils

## 🚀 Деплой

### Подготовка к продакшену
```bash
# Оптимизация
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Проверка
php artisan test
npm test
```

### Требования к серверу
- PHP 8.2+
- MySQL 8.0+
- Node.js 18+
- Nginx/Apache
- SSL сертификат

## 📚 Документация

### Основные документы
- [Идея проекта](project/idea.md)
- [Техническое видение](project/vision.md)
- [Правила кодирования](project/conventions.md)
- [План работы](project/tasklist.md)
- [Процесс разработки](project/workflow.md)

### API документация
- [API endpoints](docs/api.md)
- [Аутентификация](docs/auth.md)
- [Валидация](docs/validation.md)

## 🤝 Вклад в проект

### Правила
1. Следуйте [правилам кодирования](project/conventions.md)
2. Используйте [процесс разработки](project/workflow.md)
3. Пишите тесты для нового функционала
4. Документируйте изменения

### Процесс
1. Создайте issue с описанием задачи
2. Создайте feature ветку
3. Реализуйте функционал
4. Напишите тесты
5. Создайте Pull Request

## 📞 Поддержка

### Контакты
- **Разработчик**: [Ваше имя]
- **Email**: [email]
- **Telegram**: [username]

### Полезные ссылки
- [Laravel документация](https://laravel.com/docs)
- [Vue.js документация](https://vuejs.org/guide/)
- [Tailwind CSS документация](https://tailwindcss.com/docs)
- [Inertia.js документация](https://inertiajs.com/)

## 📄 Лицензия

Этот проект находится под лицензией [MIT License](LICENSE).

---

**SPA Platform** - создано с ❤️ для сообщества массажистов
