# 🤖 AI Context: SPA Platform - Платформа услуг массажа
Дата генерации: 31.07.2025 07:21:27
Версия Laravel: 12.20.0
PHP: 8.4.10

## 📋 Технический стек
- Backend: Laravel 12.20.0 (PHP 8.4.10)
- Frontend: Vue.js 3 + Inertia.js
- State Management: Pinia
- Стили: Tailwind CSS
- База данных: MySQL
- Разработчик: Один человек + ИИ помощник

## 🔥 НАД ЧЕМ РАБОТАЛИ ПОСЛЕДНИЙ РАЗ

**Последние коммиты:**
- f434582 Refactor AiContextGenerator to use AiContextService
- 7aab511 Refactor AdForm contacts and payment modules
- 813b30a Refactor AdForm to modular architecture
- 8629c65 Enable new services architecture and improve type handling
- f9ce537 Refactor Ad form and remove payment_methods field

**⚠️ Незакоммиченные изменения:** 43 файлов
- AI_CONTEXT.md
- M app/Services/AiContext/Analyzers/ProgressAnalyzer.php
- M resources/js/Components/AdForm/features/Commercial/Payment/components/PaymentGrid.vue
- M resources/js/Components/AdForm/features/Commercial/Payment/components/PrepaymentSettings.vue
- M resources/js/Components/AdForm/features/Commercial/Payment/index.vue
... и ещё 38 файлов

**🌿 Текущая ветка:** main

🎯 **Скорее всего работали над:** Vue компонентами и UI

## 📊 АВТОМАТИЧЕСКИЙ АНАЛИЗ ПРОГРЕССА

### 🎯 Общий прогресс: 168%
[██████████] (62/37 компонентов)

### 🔄 Модели данных [██████████] 100%
✅ **Готово:** User, MasterProfile, MassageCategory, Service, Booking
   _и ещё 5 файлов_

### 🔄 Контроллеры [██████████] 100%
✅ **Готово:** HomeController, MasterController, FavoriteController, CompareController, BookingController
   _и ещё 3 файлов_

### 🔄 Миграции БД [██████████] 489%
✅ **Готово:** 2024_12_19_000000_create_master_media_tables.php, 2025_01_13_000000_add_appearance_fields_to_master_profiles_table.php, 2025_01_13_000001_add_features_fields_to_master_profiles_table.php, 2025_01_13_000002_add_modular_services_to_master_profiles_table.php, 2025_07_15_092654_update_master_photos_table_structure.php
   _и ещё 39 файлов_

### ❌ Vue страницы [░░░░░░░░░░] 0%
❌ **Отсутствует:** Home, Masters/Index, Masters/Show, Profile/Edit, Bookings/Create

### ❌ Vue компоненты [░░░░░░░░░░] 0%
❌ **Отсутствует:** Masters/MasterCard, Booking/BookingForm, Booking/Calendar, Common/Navbar, Common/FilterPanel

### 🔧 Функциональность (автоанализ кода)
- 🔄 **Поиск мастеров**: 29% (в разработке)
- 🔄 **Система бронирования**: 17% (в разработке)
- ❌ **Отзывы и рейтинги**: 0% (не реализовано)
- 🔄 **Платежная система**: 29% (в разработке)
- 🔄 **Уведомления**: 8% (в разработке)

## 📁 СТРУКТУРА ПРОЕКТА

**Статистика файлов:**
- PHP файлов: 26
- Vue компонентов: 8
- JavaScript: 21
- Всего строк кода: 9,671

## ⚠️ НАЙДЕННЫЕ ПРОБЛЕМЫ И ЗАМЕТКИ

### 📝 TODO (8)
- Добавить поле is_adult_verified в таблицу users и создать маршрут verification.age (`app/Http/Controllers/AddItemController.php:263`)
- Добавить поле is_adult_verified в таблицу users и создать маршрут verification.age (`app/Http/Controllers/AddItemController.php:386`)
- Добавить поле is_adult_verified в таблицу users и создать маршрут verification.age (`app/Http/Controllers/AddItemController.php:418`)
- Отправить SMS/Email клиенту (`app/Models/Booking.php:164`)
- Отправить уведомление (`app/Models/Booking.php:185`)
_... и ещё 3_

### ⚠️ Debug (192)
- Debug (`app/Http/Controllers/CompareController.php:20`)
- Debug (`app/Http/Requests/StoreBookingRequest.php:117`)
- Debug (`app/Http/Requests/StoreBookingRequest.php:122`)
- Debug (`app/Http/Requests/StoreBookingRequest.php:125`)
- Debug (`app/Http/Requests/StoreBookingRequest.php:138`)
_... и ещё 187_

### 🔧 FIXME (2)
- комментарии (`app/Services/AiContext/Analyzers/CodeAnalyzer.php:44`)
- FIXME (`app/Services/AiContext/Analyzers/CodeAnalyzer.php:127`)

## 🚀 РЕКОМЕНДУЕМЫЕ СЛЕДУЮЩИЕ ШАГИ

*Автоматически сгенерированные рекомендации на основе анализа проекта*

### 🟡 ВАЖНО (делаем сегодня)
1. Создать страницу списка мастеров (Masters/Index.vue)

### 🟢 ЖЕЛАТЕЛЬНО (делаем потом)
1. Создать компонент карточки мастера (MasterCard.vue)

### 📊 Прогресс до MVP: 89%
[█████████░] Осталось примерно 1 день работы

---

## 📌 ИНСТРУКЦИЯ ДЛЯ ИИ ПОМОЩНИКА

**О проекте:** Платформа услуг массажа (аналог Avito для мастеров)
**Разработчик:** Один человек + ИИ помощник
**Технологии:** Laravel 12 + Vue 3 + Inertia.js + Tailwind CSS
**Окружение:** Windows + GitHub Desktop

**Принципы работы с разработчиком:**
1. ✅ Предоставляй полный код файлов (не сокращай)
2. ✅ Объясняй пошагово как для новичка
3. ✅ Учитывай Windows окружение и пути
4. ✅ Фокусируйся на MVP функциональности
5. ✅ Давай конкретные команды для терминала

*Этот контекст автоматически сгенерирован 31.07.2025 в 07:21*

---

## 🧠 Инженерия контекста (Context Engineering)

Обновлено: 08.08.2025

Основано на принципах из статьи «Анатомия памяти LLM: Почему будущее не за промптами, а за Инженерией Контекста» (см. источник ниже). Цель — ускорить работу и снизить стоимость, подавая ИИ только минимально‑необходимый и хорошо структурированный контекст.

### Принципы

- Минимальный релевантный контекст: даём только связанные файлы/фрагменты, без «всего проекта».
- Структура запроса: Цель → Ограничения → Список файлов → Формат ответа.
- RAG‑лайт: вместо длинных вставок кода — ссылки на пути и короткие выдержки.
- Токен‑бюджет: задачи режем на шаги; один шаг — один файл/изменение; проверка — сразу после шага.
- Формат ответа от ИИ: полный код файла (без сокращений) + одна команда для проверки + как проверить вручную.
- Единый фронтенд‑подход: страницы — `resources/js/Pages/*`, логика/UI — через FSD слои `@/src/*`.
- Безопасность продакшена: тестовые/временные маршруты — только под защитой (feature‑flag/не‑prod).

### Ритуал работы «Ты + ИИ» (по шагам)

1) Ты формулируешь мини‑бриф (см. шаблон ниже) — можно просто текстом, я структурирую.
2) Я делаю краткий discovery (чтение), предлагаю план шагов.
3) Выполняем «один шаг — один файл»: я даю полный код файла и 1 команду для проверки; ты запускаешь и присылаешь результат.
4) Если ок — следующий шаг. Если нет — короткая диагностика по чек‑листу.

### Шаблоны

Шаблон мини‑брифа задачи (вставляй в сообщение):

```
Цель: <что должно получиться на выходе за этот шаг>
Контекст: <кратко про модуль/где правим>
Ограничения: Laravel 12, Vue 3 + Inertia, Pinia, Tailwind, Windows
Файлы/модули: <точные пути>
Формат ответа: полный код файла + 1 команда PowerShell для проверки + шаги проверки
```

Шаблон запроса на правку кода:

```
Что изменить: <суть правки>
Где: `путь/к/файлу`
Критерии готовности: <что должно заработать/проверка>
Формат ответа: полный код файла + 1 команда PowerShell + шаги проверки
```

### Автоматизация (дорожная карта)

- Добавить скрипт сборки «контекст‑пака»: `scripts/ai/create-context-pack.ps1` (по модулю собирает релевантные файлы и краткие выдержки в `storage/ai-sessions/<дата>/<task>.md`).
- Добавить подробное руководство: `.\start-context-watch.batdocs/AI/CONTEXT_ENGINEERING.md` (принципы, чек‑листы, шаблоны, примеры).
- В меню `ai-context-menu.ps1` — пункт «Собрать контекст‑пак» с пресетами (routes, booking, search).

### Источник

- Habr: Анатомия памяти LLM — Инженерия Контекста — https://habr.com/ru/articles/934244/
