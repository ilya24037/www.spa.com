# CLAUDE.md

Этот файл предоставляет инструкции для Claude Code (claude.ai/code) при работе с кодом в этом репозитории.

## ВАЖНО: Общайся со мной на русском языке!

## 🔴 ГЛАВНОЕ ПРАВИЛО ПРОЕКТА - НЕ ДЕЛАЙ КОСМОЛЁТЫ!

**Это железное правило №1. Нарушение = неправильный код. ВСЕГДА следуй этим принципам:**

### 1️⃣ YAGNI - You Aren't Gonna Need It (САМЫЙ ВАЖНЫЙ!)
**Не пиши код "на будущее". Только то, что нужно ПРЯМО СЕЙЧАС.**

```php
// ❌ КОСМОЛЁТ - НЕ ДЕЛАЙ ТАК!
interface BookingStrategyInterface { }
class AdvancedBookingStrategy implements BookingStrategyInterface { }
class BookingFactory {
    public function createStrategy($type): BookingStrategyInterface { }
}
class BookingFacade { }
class BookingRepository extends AbstractRepository { }

// ✅ ПРАВИЛЬНО - ПРОСТОЙ КОД
class BookingService {
    public function create($data) {
        return Booking::create($data);
    }
}
```

### 2️⃣ KISS - Keep It Simple, Stupid
**Самое простое решение = лучшее решение. ВСЕГДА.**

```javascript
// ❌ КОСМОЛЁТ - НЕ ДЕЛАЙ ТАК!
const processData = (data) => {
    return data
        .filter(x => x.active)
        .map(x => ({...x, processed: true}))
        .reduce((acc, val) => [...acc, transformItem(val)], [])
        .sort((a, b) => b.priority - a.priority);
}

// ✅ ПРАВИЛЬНО - ПРОСТОЙ КОД
const getActiveItems = (items) => {
    return items.filter(item => item.active);
}
```

**Золотое правило:** Если работает с `if/else` - ОСТАВЬ `if/else`. НЕ надо Strategy Pattern для двух условий!

### 3️⃣ DRY - Don't Repeat Yourself (с умом!)
**Правило трёх:** Копипаст 2 раза = ОК. Абстракция только после 3-го повторения.

```php
// ✅ ДЛЯ MVP ЭТО НОРМАЛЬНО!
if ($user->type === 'master') {
    // логика для мастера
}
if ($user->type === 'client') {
    // логика для клиента
}
// НЕ НУЖНО создавать UserTypeStrategy для 2 условий!
```

### 4️⃣ SOLID - только Single Responsibility!
**Для MVP достаточно одного принципа: один класс = одна задача.**

```php
// ❌ КОСМОЛЁТ - делает всё сразу
public function processBooking($request) {
    // валидация
    // создание бронирования
    // отправка email
    // обновление статистики
    // логирование
}

// ✅ ПРАВИЛЬНО - одна задача
public function createBooking($data) {
    return Booking::create($data);
}
```

### 📋 Чек-лист принятия решений:
1. **Работает?** → Отлично, НЕ ТРОГАЙ
2. **Можно проще?** → ОБЯЗАТЕЛЬНО упрости
3. **Нужно прямо сейчас?** → Нет? УДАЛИ
4. **Используешь паттерн?** → Объясни ЗАЧЕМ. Не можешь? УДАЛИ

### 🚫 ЗАПРЕЩЕНО в MVP:
- ❌ Фабрики, Стратегии, Декораторы, Фасады
- ❌ Абстрактные классы "на будущее"
- ❌ Интерфейсы для одной реализации
- ❌ Преждевременная оптимизация
- ❌ Сложные композиции и наследования

### ✅ РАЗРЕШЕНО и ПООЩРЯЕТСЯ:
- ✅ Простые классы с понятными методами
- ✅ Прямые вызовы без лишних слоёв
- ✅ if/else вместо паттернов
- ✅ 10 строк понятного кода вместо 100 строк "архитектуры"
- ✅ Дублирование кода (до 3 раз)

**ПОМНИ:** Лучше работающий "плохой" код, чем неработающий "правильный"!

## 🚨 Critical Context
- **Project**: Платформа услуг массажа (тип Avito/Ozon)
- **Progress**: Итерация 5/10 - Система бронирования (60%)
- **Codebase**: 37,000+ строк, Laravel 12 + Vue 3
- **Developer**: Начинающий, работает с AI-помощником

## Обзор проекта

**SPA Platform** - Продакшн маркетплейс услуг массажа (37,000+ строк кода)
- **Тип**: Платформа услуг массажа в стиле Avito/Ozon
- **Стек**: Laravel 12, Vue 3, TypeScript, MySQL, Tailwind CSS
- **Архитектура**: DDD бэкенд, FSD фронтенд
- **Готовность**: 86% до MVP

## О разработчике
- Уровень: Начинающий разработчик
- Работаю: Один с AI-помощником
- Цель: Создать сайт типа Avito для услуг массажа

## 📏 Principles (Обязательные принципы)

### ⚠️ КРИТИЧЕСКИ ВАЖНЫЕ (нарушение = переделка):
- **YAGNI** - You Aren't Gonna Need It (НЕ пиши на будущее!)
- **KISS** - Keep It Simple, Stupid (простое решение = лучшее!)
- **DRY** - Don't Repeat Yourself (но только после 3-го повторения!)

### Дополнительные:
- **SOLID** - только Single Responsibility для MVP
- **Mobile-first** подход
- **AI-driven** разработка (AIDD)

## ✅ Code Requirements (Требования к коду)
- Clear naming (понятные имена)
- Single responsibility (единая ответственность)
- Comments in English (комментарии на английском)
- Error handling (обработка ошибок)
- Input validation (валидация входных данных)

## ❌ Forbidden (Запрещено)
- Complex nested structures (сложные вложенные структуры)
- Magic numbers (магические числа)
- Global variables (глобальные переменные)
- Code duplication (дублирование кода)

## ⚡ Performance Rules

### 1. Business Logic First
```bash
# При любой бизнес-ошибке СНАЧАЛА:
grep -r "текст ошибки" app/Domain/*/Actions/
# Экономит 80% времени отладки
```

### 2. Data Chain Check
Component → Watcher → Emit → Backend → DB
# ВСЕГДА проверяй watcher при v-model

## 🛠 Critical Commands

### Quick Debug
```bash
php artisan tinker
npm run type-check
```

### Reset & Test
```bash
php artisan migrate:fresh --seed
php artisan test && npm test
```

### Find Patterns
```bash
grep -r "похожий_код" app/
```

### Разработка
```bash
# Запуск серверов разработки
composer dev                 # Запуск Laravel + Queue + Logs + Vite одновременно
npm run dev                 # Только Vite dev сервер
php artisan serve         # Только Laravel сервер

# Проверка типов и линтинг
npm run type-check       # Валидация TypeScript
npm run lint             # Исправить проблемы ESLint
npm run lint:check       # Проверить проблемы ESLint
php artisan pint         # Исправления стиля PHP кода
```

### Тестирование
```bash
# Тесты бэкенда
php artisan test
composer test

# Тесты фронтенда
npm run test             # Запустить тесты
npm run test:unit       # Запустить один раз
npm run test:watch      # Режим наблюдения
npm run test:coverage   # С покрытием
```

### База данных
```bash
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed
php artisan tinker
```

### Сборка и продакшн
```bash
npm run build           # Продакшн сборка
npm run build:fast      # Пропустить проверку типов
php artisan optimize    # Оптимизация Laravel
php artisan filament:upgrade    # Обновить Filament
```

### 🚀 Chrome DevTools MCP Testing (NEW!)
```bash
# Проверить статус MCP
node scripts/chrome-devtools-mcp.js status

# Примеры команд для браузерного тестирования с Claude:
"Check the performance of http://localhost:8000"
"Test booking calendar at http://localhost:8000/masters/1"
"Test search functionality at http://localhost:8000"
"Test mobile view at http://localhost:8000"

# Файлы:
tests/e2e/chrome-devtools-test.js      # Тестовые сценарии
scripts/chrome-devtools-mcp.js         # Helper скрипт
Docs/MCP_CHROME_DEVTOOLS.md            # Полная документация
```

## 📁 Architecture

### Backend (DDD):
```
app/Domain/[User|Master|Ad|Booking|Payment]/
├── Models/       # $fillable, $casts обязательны!
├── Services/     # ВСЯ бизнес-логика здесь
├── Actions/      # Сложные операции
└── DTOs/         # Передача данных
```

### Frontend (FSD):
```
resources/js/src/
├── entities/master/ui/    # Референс для компонентов
├── features/booking/       # Текущий фокус
└── shared/ui/             # UI kit
```

### Полная структура доменов бэкенда (DDD)
```
app/Domain/
├── User/               # Управление пользователями
├── Master/             # Профили мастеров
├── Ad/                 # Объявления об услугах
├── Booking/           # Планирование встреч
├── Payment/           # Платежи и транзакции
├── Review/             # Отзывы и рейтинги
├── Search/             # Поиск и фильтрация
├── Analytics/         # Аналитика и метрики
├── Notification/      # Уведомления
├── Media/              # Загрузка файлов
└── Service/           # Каталог услуг
```

Каждый домен содержит:
- `Models/` - Eloquent модели
- `Services/` - Слой бизнес-логики
- `Actions/` - Сложные операции
- `DTOs/` - Объекты передачи данных
- `Events/` - События домена
- `Enums/` - Перечисления

### Полная структура фронтенда (FSD)
```
resources/js/src/
├── shared/             # UI кит, лейауты, утилиты
├── entities/         # Бизнес-сущности (master, ad, booking)
├── features/         # Действия пользователя (поиск, фильтр, авторизация)
├── widgets/           # Сложные UI блоки
└── pages/               # Страницы роутов
```

### Админ-панель Filament v4
```
app/Filament/
├── Resources/       # Админ CRUD ресурсы
├── Widgets/           # Виджеты дашборда
└── Pages/               # Кастомные админ страницы
```

## Правила общения со мной

1. **ВСЕГДА отвечай на русском языке**
2. **Объясняй каждый шаг подробно**, как для новичка
3. **Предупреждай о возможных ошибках** заранее
4. **Давай пошаговые инструкции** с командами
5. **Жди моего подтверждения** перед выполнением

## Рабочий процесс разработки

### 🔍 ОБЯЗАТЕЛЬНО перед созданием ЛЮБОГО нового компонента:
1. **Проверь Docs проекта** - `C:\www.spa.com\Docs\` (возможно, уже делали похожее)
2. **Поищи документацию** - официальные доки (Laravel, Vue, Tailwind)
3. **Найди примеры на GitHub** - как другие это делают
4. **Проверь Stack Overflow** - возможно, уже есть решение
5. **Посмотри в своём проекте** - может, похожее уже есть (`grep -r "похожий_компонент" resources/js/`)
6. **НЕ изобретай велосипед** - используй готовые решения

#### Примеры поиска:
```
"Laravel booking system example GitHub"
"Vue 3 calendar component"
"Tailwind modal dialog"
"TypeScript interface for booking"
site:github.com "booking calendar" vue3
```

**Правило:** Лучше адаптировать готовое решение, чем писать с нуля!

### При создании новой функции:
1. **Покажи план реализации** - что будем делать
2. **Жди моего подтверждения** - не спеши
3. **Создавай по одному файлу** - пошагово
4. **После каждого файла жди проверки** - убедимся что работает
5. **Показывай как тестировать** - проверим вместе

### Формат ответов:
```
🔍 ИССЛЕДОВАНИЕ:
[Что нашёл в документации/GitHub]

🎯 ЧТО ДЕЛАЕМ:
[Описание задачи]

📋 ПЛАН:
1. Шаг 1
2. Шаг 2
3. Шаг 3

💻 КОД:
[Полный код файла]

🖥️ КОМАНДЫ:
[Команды для терминала]

✅ ПРОВЕРКА:
[Как проверить результат]
```

## 💻 Code Standards

### Laravel MUST
```php
// ✅ Service Layer Pattern
class BookingService {
    public function create(CreateBookingDTO $dto): Booking {
        return DB::transaction(function() use ($dto) {
            // логика
        });
    }
}

// ❌ НИКОГДА в контроллере
public function store(Request $request) {
    // НЕ размещай логику здесь!
}
```

### Vue MUST
```vue
<script setup lang="ts">
// ✅ ВСЕГДА защищай от null
const safeMaster = computed(() => props.master || {} as Master)

// ✅ ВСЕГДА watcher для сохранения
watch(() => formData.newField, () => {
    saveFormData()
}, { deep: true })
</script>
```

### Стандарты Vue компонентов
```vue
<script setup lang="ts">
// Всегда используйте TypeScript интерфейсы
interface Props {
    master: Master
    loading?: boolean
}

// Всегда предоставляйте значения по умолчанию
const props = withDefaults(defineProps<Props>(), {
    loading: false
})

// Всегда защищайтесь от null/undefined
const safeMaster = computed(() => props.master || {} as Master)
</script>
```

### Миграции базы данных
- Одна миграция на таблицу
- Всегда добавляйте индексы на foreign keys
- Используйте soft deletes для критичных данных

## Перед ЛЮБЫМ изменением

### 🔎 СНАЧАЛА ищи готовые решения:
- **Docs проекта** - `C:\www.spa.com\Docs\` - опыт ЭТОГО проекта!
- **GitHub** - примеры похожих проектов
- **Документация** - официальные примеры
- **Stack Overflow** - решения проблем
- **Свой проект** - возможно, уже есть похожее
- **НЕ пиши с нуля** то, что уже написано!

### Перед НОВЫМ компонентом Vue:
- TypeScript интерфейсы для пропсов
- Значения по умолчанию для опциональных пропсов
- Состояния загрузки/ошибки/пустоты
- v-if защита от null
- Мобильная адаптивность (sm: md: lg:)

### Перед ЛЮБЫМ изменением бэкенда:
- Сервисный слой, не контроллер
- DTO для передачи данных
- Транзакция для операций с БД
- Валидация в Request классе
- Покрытие тестами

## 🎯 Current Sprint
- [ ] BookingCalendar.vue - создать компонент
- [ ] TimeSlotPicker.vue - интеграция
- [ ] Поиск мастеров - начать базовый функционал

## 🐛 Debug Patterns

| Проблема | Решение |
|----------|---------|
| "Невозможно архивировать" | grep Actions → упростить валидацию |
| "Данные не сохраняются" | Проверь $fillable → watcher → API |
| "Method not found" | grep -r "methodName" app/ |

## ⚠️ Project Traps
1. **$fillable** - ВСЕГДА добавляй новые поля
2. **Watchers** - ОБЯЗАТЕЛЬНЫ для v-model полей
3. **Transactions** - для множественных операций БД

## Текущее состояние проекта
- **Готовность**: 86% до MVP
- **Критично**: Система бронирования (60%)
- **Важно**: Поиск мастеров (0%)
- **База**: Миграции выполнены
- **Админка**: Filament v4 настроена

## Специфичные функции проекта

- **Yandex Maps API** - Интеграция выбора адреса
- **Календарь бронирования** - Управление временными слотами
- **Медиа библиотека** - Spatie MediaLibrary для загрузок
- **Поиск** - Meilisearch через Laravel Scout
- **Админ-панель** - Filament v4 для администрирования
- **Модерация** - AdModerationService, AdminActionsService
- **Уведомления** - Email, SMS, Push

## Соглашения Git
```bash
feat: новая функция
fix: исправление бага
refactor: реструктуризация кода
docs: документация
test: добавление тестов
style: форматирование кода
```

## Важные напоминания
- **НЕ удаляй** существующий код без спроса
- **ВСЕГДА проверяй** существование файла перед изменением
- **СПРОСИ** если что-то непонятно
- **ПРЕДЛОЖИ варианты** решения
- **УЧТИ** что проект уже частично готов
- **ТЕСТИРУЙ** сразу после создания

## 📚 ВАЖНО: База знаний проекта в C:\www.spa.com\Docs

### 🔴 ОБЯЗАТЕЛЬНО проверь перед решением проблем:
```
C:\www.spa.com\Docs\
├── fixes/              # Решения известных проблем
├── troubleshooting/    # Отладка типичных ошибок
├── antipatterns/       # Что НЕ делать
├── PATTERNS/           # Проверенные паттерны
├── REFACTORING/        # История рефакторинга
├── lessons-learned/    # Уроки из ошибок
├── PROBLEMS/           # Известные проблемы
└── Admin panel 1/      # Документация админки
```

**Правило:** Если ошибка/проблема уже была - решение есть в Docs!

### Примеры поиска в документации:
```bash
# Поиск решения проблемы
grep -r "название ошибки" C:/www.spa.com/Docs/

# Поиск антипаттернов
grep -r "не делай" C:/www.spa.com/Docs/antipatterns/

# Поиск готовых решений
grep -r "booking" C:/www.spa.com/Docs/PATTERNS/
```

## Быстрый гайд по отладке

### Найти ошибки бизнес-логики
```bash
grep -r "текст ошибки" app/Domain/*/Actions/
```

### Отладка потока данных
Компонент → Watcher → Emit → Бэкенд → БД

### Проверка логов
```bash
tail -f storage/logs/laravel.log
php artisan pail   # Просмотр логов в реальном времени
```

## 📚 Full Documentation
- **Conventions**: @.claude/conventions.mdc
- **Business**: @.aidd/idea.md
- **Standards**: @.aidd/conventions.md
- **Progress**: @.aidd/tasklist.md

## Важные файлы проекта
- `.env` - Конфигурация окружения
- `FILAMENT_SETUP.md` - Документация админ-панели
- `.aidd/` - AIDD документация проекта
- `.cursor/` - Правила и настройки Cursor
- `quality.json` - Метрики качества кода

## 💬 Communication
- **Язык**: Русский для объяснений, English для кода
- **Уровень**: Объясняй как новичку, пошагово
- **Формат**: План → Код → Команды → Проверка