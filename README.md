# 🎯 SPA Platform - Платформа услуг массажа

> Современная платформа для поиска и бронирования услуг массажа, построенная на Laravel 12 + Vue 3

## 🚀 Технический стек

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Vue 3 + Inertia.js + TypeScript
- **State Management**: Pinia
- **Styling**: Tailwind CSS
- **Database**: MySQL
- **Architecture**: Domain-Driven Design + Feature-Sliced Design

## 📁 Структура проекта

```
SPA Platform/
├── app/                    # Laravel backend
│   ├── Domain/            # Домены (User, Master, Ad, Service, etc.)
│   ├── Application/       # Сервисы и контроллеры
│   └── Infrastructure/    # Репозитории и внешние сервисы
├── resources/js/          # Vue.js frontend
│   ├── src/               # Исходный код
│   │   ├── features/      # Функциональные модули
│   │   ├── entities/      # Бизнес-сущности
│   │   ├── shared/        # Общие компоненты
│   │   └── widgets/       # Переиспользуемые виджеты
│   └── stores/            # Pinia stores
└── database/              # Миграции и seeders
```

## 🎵 Вайб-кодинг

Мы используем принципы "вайб-кодинга" для качественной разработки с помощью ИИ:

📖 **[Подробный гайд по вайб-кодингу](docs/VIBE_CODING_GUIDE.md)**
📋 **[Быстрый чек-лист](docs/VIBE_CODING_CHECKLIST.md)**

### Ключевые принципы:
- ✅ Четкие инструкции и контекст
- ✅ Итеративный подход
- ✅ Код-ревью и рефакторинг
- ✅ Тестирование и валидация

## 📚 Документация

- **[Основная документация](docs/README.md)**
- **[Гайд по вайб-кодингу](docs/VIBE_CODING_GUIDE.md)**
- **[Быстрый чек-лист](docs/VIBE_CODING_CHECKLIST.md)**
- **[Анализ проблем](docs/PROBLEMS/README.md)**
- **[AI Team руководство](AI-TEAM-GUIDE.md)**

## 🏗️ Архитектура

### Backend (Laravel)
- **Domain-Driven Design** - каждый домен в отдельной папке
- **Resource Controllers** - для CRUD операций
- **Form Requests** - для валидации данных
- **Service Layer** - для бизнес-логики
- **API Resources** - для форматирования ответов

### Frontend (Vue)
- **Composition API** с `<script setup>`
- **TypeScript** для типизации
- **Feature-Sliced Design** - компоненты по функциональности
- **Pinia** для управления состоянием
- **Tailwind CSS** для стилизации

## 🚀 Быстрый старт

### Требования
- PHP 8.2+
- Node.js 18+
- MySQL 8.0+
- Composer
- npm/yarn

### Установка

```bash
# Клонирование репозитория
git clone [repository-url]
cd spa-platform

# Backend зависимости
composer install

# Frontend зависимости
npm install

# Настройка окружения
cp .env.example .env
php artisan key:generate

# Миграции и seeders
php artisan migrate --seed

# Запуск
php artisan serve
npm run dev
```

## 🎯 Основные функции

### ✅ Реализовано (86%)
- 🔐 Аутентификация и авторизация
- 👤 Профили пользователей и мастеров
- 📝 Система объявлений (ads)
- 🖼️ Медиа галерея (фото/видео)
- ⭐ Система отзывов и рейтингов
- ❤️ Избранное и сравнение
- 🔍 Поиск и фильтрация (UI готов)
- 📍 Выбор города
- 📋 Каталог услуг и категорий
- 📄 Загрузка документов для верификации

### 🔴 В разработке (14%)
- 📅 **Система бронирования** (критично)
- 💳 **Обработка платежей**
- 🔍 **Backend логика поиска**
- 📱 **Система уведомлений**
- 📊 **Аналитика и статистика**

## 🧪 Тестирование

```bash
# Backend тесты
php artisan test

# Frontend тесты
npm run test

# E2E тесты
npm run test:e2e
```

## 📝 Стандарты кода

- **PHP**: PSR-12 + Laravel Pint
- **JavaScript**: ESLint + Prettier
- **Vue**: Vue Style Guide
- **CSS**: Tailwind CSS conventions

## 🤝 Вклад в проект

1. Форкните репозиторий
2. Создайте feature branch
3. Следуйте стандартам кода
4. Добавьте тесты
5. Создайте Pull Request

## 📄 Лицензия

MIT License - см. [LICENSE](LICENSE) файл

## 🆘 Поддержка

- 📧 Email: [support@spa-platform.com]
- 💬 Issues: [GitHub Issues]
- 📖 Документация: [docs/](docs/)

---

**Разработано с ❤️ для сообщества массажистов**

*Обновлено: 2025-01-27*