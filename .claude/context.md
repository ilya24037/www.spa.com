# 🎯 SPA Platform - Быстрый контекст проекта

## Что это за проект
**SPA Platform** - маркетплейс услуг массажа и спа-процедур. Мастера размещают объявления, клиенты бронируют услуги.

## Технологический стек
- **Backend:** Laravel 12 (PHP 8.3), Domain-Driven Design
- **Frontend:** Vue 3.5, TypeScript, Inertia.js, Tailwind CSS
- **Архитектура:** DDD (backend), Feature-Sliced Design (frontend)
- **База данных:** MySQL 8, Redis для кеша
- **Поиск:** Elasticsearch (планируется)

## Размер проекта
- **Кодовая база:** 37,000+ строк кода
- **Домены:** 11 (User, Master, Ad, Booking, Payment, etc.)
- **Компоненты:** 50+ Vue компонентов в FSD структуре
- **Миграции:** 74 файла

## Текущие приоритеты (Сентябрь 2025)
1. ✅ Миграция на FSD/DDD архитектуру (90% готово)
2. 🔄 Рефакторинг MasterController (логика в сервисы)
3. 🔄 Оптимизация производительности карты
4. ⏳ Интеграция платежной системы
5. ⏳ Система уведомлений (SMS/Email)

## Критические проблемы
1. **MasterController.php** - бизнес-логика в контроллере (строки 92-161)
2. **MasterCard.vue** - форматирование в компоненте вместо composables
3. **User.php** - слишком много трейтов (15+)
4. **Производительность** - 74 миграции можно объединить

## Ключевые особенности
- **Обязательно:** TypeScript везде, no any types
- **Обязательно:** Skeleton loaders для всех списков
- **Обязательно:** Обработка loading/error/empty состояний
- **Обязательно:** Watchers для автосохранения форм
- **Запрещено:** Бизнес-логика в контроллерах
- **Запрещено:** console.log в production коде

## Структура проекта

### Frontend (FSD)
```
resources/js/src/
├── shared/     # UI-kit, layouts, composables
├── entities/   # master, booking, ad, user
├── features/   # auth, filters, forms
├── widgets/    # catalog, dashboard
└── pages/      # home, masters, profile
```

### Backend (DDD)
```
app/Domain/
├── User/       # Auth, profiles, settings
├── Master/     # Profiles, schedule, media
├── Ad/         # Объявления, модерация
├── Booking/    # Бронирования, слоты
└── Payment/    # Платежи, подписки
```

## Команда и процессы
- **Разработка:** Solo developer + AI assistants
- **Git:** GitHub Desktop (не CLI)
- **Deploy:** Планируется на VPS
- **Тесты:** PHPUnit + Vitest

## Полезные команды
```powershell
# Laravel
php artisan serve          # Запуск сервера
php artisan migrate:fresh  # Обновить БД
php artisan test           # Тесты

# Frontend
npm run dev                # Разработка
npm run build              # Сборка
npm run type-check         # TypeScript проверка
```

## Где искать информацию
- **Архитектура:** CLAUDE.md
- **Текущие задачи:** AI_CONTEXT.md
- **Инструкции:** .claude/instructions.md
- **Шаблоны:** .claude/templates/
- **Качество:** .claude/quality.json

## Контакты и ссылки
- **Репозиторий:** [приватный GitHub]
- **Staging:** [в разработке]
- **Production:** [планируется]

---
*Последнее обновление: Сентябрь 2025*