# 🏥 SPA Platform

> Платформа услуг массажа - аналог Avito для мастеров массажа

[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue-3.4-green.svg)](https://vuejs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.0-blue.svg)](https://www.typescriptlang.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.4-cyan.svg)](https://tailwindcss.com)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

## 📋 Содержание

- [Описание проекта](#-описание-проекта)
- [Основные функции](#-основные-функции)
- [Технический стек](#-технический-стек)
- [Быстрый старт](#-быстрый-старт)
- [Установка](#-установка)
- [Настройка](#-настройка)
- [Структура проекта](#-структура-проекта)
- [Разработка](#-разработка)
- [Тестирование](#-тестирование)
- [Деплой](#-деплой)
- [Документация](#-документация)
- [Участие в проекте](#-участие-в-проекте)
- [Лицензия](#-лицензия)

---

## 🎯 Описание проекта

**SPA Platform** - это современная платформа для размещения объявлений о услугах массажа. Мастера могут создавать объявления о своих услугах, а клиенты - искать и выбирать подходящих специалистов.

### Основная идея:
- **Как Avito** - платформа объявлений для мастеров
- **Платные объявления** - мастера платят за размещение и продвижение
- **Никаких комиссий** - мастера не платят комиссию с заказов
- **Real-time чат** - прямое общение на странице объявления
- **Минималистичный дизайн** - как у Ozon

### Целевая аудитория:
- **Мастера массажа** - размещают объявления о услугах
- **Клиенты** - ищут и выбирают мастеров
- **Администраторы** - управляют платформой
- **Модераторы** - проверяют контент

---

## ✨ Основные функции

### 🏠 **Публичная часть:**
- **Главная страница** - поиск и фильтрация объявлений
- **Страница объявления** - детальная информация о мастере
- **Поиск и фильтры** - продвинутый поиск по параметрам
- **Сравнение** - сравнение мастеров
- **Избранное** - сохранение понравившихся объявлений

### 👤 **Личный кабинет:**
- **Единый кабинет** с переключением режимов
- **Режим клиента** - заказы, избранное, сообщения
- **Режим мастера** - объявления, статистика, финансы
- **Настройки профиля** - редактирование данных

### 💬 **Система чата:**
- **Real-time общение** - мгновенные сообщения
- **На странице объявления** - как у Avito
- **Файлы и изображения** - обмен медиа
- **Модерация** - контроль сообщений

### 🛠️ **Административная панель:**
- **Управление пользователями** - блокировка, разблокировка
- **Модерация объявлений** - проверка контента
- **Финансы** - управление платежами
- **Аналитика** - статистика и отчеты
- **Система ролей** - разграничение прав доступа

---

## 🛠️ Технический стек

### **Backend:**
- **Laravel 12** - PHP фреймворк
- **PHP 8.3+** - современная версия PHP
- **MySQL 8.0** - основная база данных
- **Redis** - кэширование и очереди
- **Laravel Sanctum** - аутентификация API
- **Spatie Laravel Permission** - роли и права
- **Laravel WebSockets** - real-time чат

### **Frontend:**
- **Vue 3.4+** - современный JavaScript фреймворк
- **TypeScript 5+** - строгая типизация
- **Inertia.js** - SPA без API
- **Tailwind CSS 3.4+** - utility-first CSS
- **Pinia** - state management
- **Vite 5+** - быстрая сборка

### **DevOps:**
- **Docker** - контейнеризация
- **GitHub Actions** - CI/CD
- **Sentry** - мониторинг ошибок
- **New Relic** - APM
- **Cloudflare** - CDN

---

## 🚀 Быстрый старт

### Предварительные требования:
- **Docker** и **Docker Compose**
- **Git** для клонирования репозитория
- **Node.js 18+** (опционально для локальной разработки)

### 1. Клонирование репозитория:
```bash
git clone https://github.com/your-username/spa-platform.git
cd spa-platform
```

### 2. Запуск через Docker:
```bash
# Копируем конфигурацию
cp .env.example .env

# Запускаем контейнеры
docker-compose up -d

# Устанавливаем зависимости
docker-compose exec app composer install
docker-compose exec app npm install

# Настраиваем базу данных
docker-compose exec app php artisan migrate --seed

# Собираем фронтенд
docker-compose exec app npm run build
```

### 3. Открываем в браузере:
```
http://localhost:8000
```

---

## ⚙️ Установка

### Локальная установка (без Docker):

#### 1. Установка зависимостей:
```bash
# PHP зависимости
composer install

# Node.js зависимости
npm install
```

#### 2. Настройка окружения:
```bash
# Копируем конфигурацию
cp .env.example .env

# Генерируем ключ приложения
php artisan key:generate

# Настраиваем базу данных в .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spa_platform
DB_USERNAME=root
DB_PASSWORD=
```

#### 3. Настройка базы данных:
```bash
# Создаем базу данных
php artisan migrate

# Заполняем тестовыми данными
php artisan db:seed
```

#### 4. Запуск сервера:
```bash
# Backend сервер
php artisan serve

# Frontend сборка (в другом терминале)
npm run dev
```

---

## 🔧 Настройка

### Конфигурация .env:

#### **База данных:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spa_platform
DB_USERNAME=root
DB_PASSWORD=
```

#### **Redis:**
```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### **Почта:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

#### **Файлы:**
```env
FILESYSTEM_DISK=local
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
```

### Настройка прав доступа:
```bash
# Устанавливаем права на папки
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 📁 Структура проекта

```
spa-platform/
├── app/                          # Backend приложение
│   ├── Domain/                   # Бизнес-логика
│   │   ├── Ad/                   # Модуль объявлений
│   │   ├── User/                 # Модуль пользователей
│   │   ├── Chat/                 # Модуль чата
│   │   └── Admin/                # Модуль админки
│   ├── Application/              # Прикладной слой
│   │   ├── Http/                 # Контроллеры
│   │   ├── Services/             # Сервисы
│   │   └── DTOs/                 # Data Transfer Objects
│   └── Infrastructure/           # Инфраструктура
│       ├── Database/             # Миграции, модели
│       ├── External/             # Внешние сервисы
│       └── Media/                # Обработка медиа
├── resources/                    # Frontend ресурсы
│   ├── js/                       # JavaScript/TypeScript
│   │   ├── Components/           # Vue компоненты
│   │   │   ├── UI/               # Базовые UI
│   │   │   ├── Features/         # Функциональные
│   │   │   └── Pages/            # Страницы
│   │   ├── Stores/               # Pinia stores
│   │   ├── Types/                # TypeScript типы
│   │   └── Utils/                # Утилиты
│   └── views/                    # Blade шаблоны
├── database/                     # База данных
│   ├── migrations/               # Миграции
│   ├── seeders/                  # Сидеры
│   └── factories/                # Фабрики
├── tests/                        # Тесты
│   ├── Unit/                     # Unit тесты
│   ├── Feature/                  # Feature тесты
│   └── E2E/                      # E2E тесты
├── docs/                         # Документация
│   ├── idea.md                   # Основная идея
│   ├── vision.md                 # Техническое видение
│   ├── conventions.md            # Стандарты кода
│   ├── tasklist.md               # План разработки
│   └── workflow.md               # Процесс работы
└── docker-compose.yml            # Docker конфигурация
```

---

## 💻 Разработка

### Запуск в режиме разработки:

#### **С Docker:**
```bash
# Запуск контейнеров
docker-compose up -d

# Просмотр логов
docker-compose logs -f app

# Выполнение команд
docker-compose exec app php artisan migrate
docker-compose exec app npm run dev
```

#### **Без Docker:**
```bash
# Backend сервер
php artisan serve

# Frontend сборка
npm run dev

# Очереди (в отдельном терминале)
php artisan queue:work

# WebSockets (в отдельном терминале)
php artisan websockets:serve
```

### Полезные команды:

#### **Laravel:**
```bash
# Миграции
php artisan migrate
php artisan migrate:rollback
php artisan migrate:refresh

# Сидеры
php artisan db:seed
php artisan db:seed --class=UserSeeder

# Кэш
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Очереди
php artisan queue:work
php artisan queue:restart
php artisan horizon

# WebSockets
php artisan websockets:serve
php artisan websockets:restart
```

#### **Frontend:**
```bash
# Установка зависимостей
npm install

# Разработка
npm run dev

# Сборка
npm run build

# Тестирование
npm run test
npm run test:coverage

# Линтинг
npm run lint
npm run lint:fix
```

---

## 🧪 Тестирование

### Запуск тестов:

#### **Все тесты:**
```bash
# PHP тесты
./vendor/bin/pest

# Vue тесты
npm run test
```

#### **По типам:**
```bash
# Unit тесты
./vendor/bin/pest --testsuite=Unit

# Feature тесты
./vendor/bin/pest --testsuite=Feature

# E2E тесты
./vendor/bin/pest --testsuite=E2E
```

#### **С покрытием:**
```bash
# PHP покрытие
./vendor/bin/pest --coverage

# Vue покрытие
npm run test:coverage
```

### Требования к тестам:
- **Покрытие кода > 80%**
- **Все тесты проходят**
- **Тесты независимые**
- **Тесты быстрые**
- **Тесты понятные**

---

## 🚀 Деплой

### Подготовка к продакшн:

#### **1. Настройка окружения:**
```bash
# Копируем продакшн конфигурацию
cp .env.example .env.production

# Настраиваем переменные окружения
# DB_*, REDIS_*, MAIL_*, AWS_*, etc.
```

#### **2. Сборка приложения:**
```bash
# Устанавливаем зависимости
composer install --optimize-autoloader --no-dev
npm ci --production

# Собираем фронтенд
npm run build

# Кэшируем конфигурацию
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### **3. Настройка сервера:**
```bash
# Устанавливаем права
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Настраиваем Nginx/Apache
# Настраиваем SSL сертификаты
# Настраиваем мониторинг
```

### Docker деплой:
```bash
# Сборка образа
docker build -t spa-platform .

# Запуск контейнера
docker run -d -p 80:80 spa-platform
```

---

## 📚 Документация

### Основные документы:
- **[idea.md](docs/idea.md)** - основная идея проекта
- **[vision.md](docs/vision.md)** - техническое видение
- **[conventions.md](docs/conventions.md)** - стандарты кода
- **[tasklist.md](docs/tasklist.md)** - план разработки
- **[workflow.md](docs/workflow.md)** - процесс работы

### API документация:
- **Swagger/OpenAPI** - автоматическая документация
- **Postman коллекция** - для тестирования API
- **Примеры запросов** - в документации

### Архитектура:
- **Диаграммы** - структура проекта
- **Схемы БД** - связи между таблицами
- **API схемы** - взаимодействие компонентов

---

## 🤝 Участие в проекте

### Как внести вклад:

#### **1. Форк репозитория:**
```bash
# Создаем форк на GitHub
# Клонируем свой форк
git clone https://github.com/your-username/spa-platform.git
```

#### **2. Создаем ветку:**
```bash
# Создаем feature ветку
git checkout -b feature/your-feature

# Или hotfix ветку
git checkout -b hotfix/bug-fix
```

#### **3. Разрабатываем:**
```bash
# Вносим изменения
# Следуем conventions.md
# Пишем тесты
# Обновляем документацию
```

#### **4. Создаем Pull Request:**
```bash
# Коммитим изменения
git add .
git commit -m "feat: add new feature"

# Пушим в свой форк
git push origin feature/your-feature

# Создаем PR на GitHub
```

### Правила участия:
- **Следуйте conventions.md**
- **Пишите тесты для нового кода**
- **Обновляйте документацию**
- **Используйте понятные сообщения коммитов**
- **Проходите code review**

---

## 📄 Лицензия

Этот проект лицензирован под MIT License - см. файл [LICENSE](LICENSE) для деталей.

---

## 📞 Поддержка

### Связь с командой:
- **GitHub Issues** - для багов и предложений
- **Discord** - для обсуждений
- **Email** - для критических вопросов

### Полезные ссылки:
- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org/guide/)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Inertia.js Documentation](https://inertiajs.com/)

---

## 🎯 Roadmap

### Версия 1.0 (MVP):
- [x] Базовая настройка проекта
- [x] Система аутентификации
- [x] Модели и миграции
- [x] API для объявлений
- [x] Frontend компоненты
- [x] Личные кабинеты
- [ ] Система чата
- [ ] Административная панель
- [ ] Система ролей

### Версия 1.1:
- [ ] Мобильное приложение
- [ ] Продвинутая аналитика
- [ ] Интеграция с платежными системами
- [ ] API для мобильных приложений

### Версия 2.0:
- [ ] Микросервисная архитектура
- [ ] Машинное обучение для рекомендаций
- [ ] Интеграция с социальными сетями
- [ ] Многоязычность

---

**Создано с ❤️ командой SPA Platform**

*Последнее обновление: {{ date('Y-m-d') }}*