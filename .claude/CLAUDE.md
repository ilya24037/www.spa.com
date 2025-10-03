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
- **Progress**: 86% до MVP, фокус - Система бронирования (60%)
- **Stack**: Laravel 12 + Vue 3 + TypeScript + MySQL + Tailwind
- **Architecture**: DDD бэкенд, FSD фронтенд, Filament v4 админка
- **Developer**: Начинающий разработчик, работает с AI-помощником

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
├── shared/       # UI kit, утилиты
├── entities/     # Бизнес-сущности (master, ad, booking)
├── features/     # Действия (поиск, фильтр, авторизация)
├── widgets/      # Композитные блоки
└── pages/        # Страницы роутов
```

**Детали**: см. `Docs/ARCHITECTURE_FULL.md`

## Правила общения со мной

1. **ВСЕГДА отвечай на русском языке**
2. **Объясняй каждый шаг подробно**, как для новичка
3. **Предупреждай о возможных ошибках** заранее
4. **Жди моего подтверждения** перед выполнением

## Рабочий процесс разработки

### 🔍 ОБЯЗАТЕЛЬНО перед созданием ЛЮБОГО нового компонента:
1. **Проверь Docs проекта** - `C:\www.spa.com\Docs\` (возможно, уже делали похожее)
2. **Поищи документацию** - официальные доки (Laravel, Vue, Tailwind)
3. **Найди примеры на GitHub** - как другие это делают
4. **Проверь Stack Overflow** - возможно, уже есть решение
5. **Посмотри в своём проекте** - может, похожее уже есть (`grep -r "pattern" resources/js/`)
6. **НЕ изобретай велосипед** - используй готовые решения

#### Примеры поиска:
```
"Laravel booking system example GitHub"
"Vue 3 calendar component"
site:github.com "booking calendar" vue3
```

**Правило:** Лучше адаптировать готовое решение, чем писать с нуля!

### При создании новой функции:
1. **Покажи план реализации** - что будем делать
2. **Жди моего подтверждения** - не спеши
3. **Создавай по одному файлу** - пошагово
4. **После каждого файла жди проверки** - убедимся что работает
5. **Показывай как тестировать** - проверим вместе

## 💻 Code Standards

### Laravel
```php
// ✅ Service Layer Pattern - ВСЯ логика в сервисах
class BookingService {
    public function create(CreateBookingDTO $dto): Booking {
        return DB::transaction(function() use ($dto) {
            // логика
        });
    }
}

// ❌ НИКОГДА логику в контроллере
public function store(Request $request) {
    // НЕ размещай логику здесь!
}
```

### Vue + TypeScript
```vue
<script setup lang="ts">
// ✅ ВСЕГДА интерфейсы + значения по умолчанию
interface Props {
    master: Master
    loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    loading: false
})

// ✅ ВСЕГДА защита от null
const safeMaster = computed(() => props.master || {} as Master)

// ✅ ВСЕГДА watcher для v-model полей
watch(() => formData.newField, () => {
    saveFormData()
}, { deep: true })
</script>
```

### Перед НОВЫМ компонентом Vue:
- TypeScript интерфейсы для пропсов
- Значения по умолчанию для опциональных пропсов
- Состояния загрузки/ошибки/пустоты
- v-if защита от null
- Мобильная адаптивность (sm: md: lg:)

### Перед изменением бэкенда:
- Сервисный слой, не контроллер
- DTO для передачи данных
- Транзакция для операций с БД
- Валидация в Request классе
- Проверка $fillable в моделях

## 🛠 Critical Commands

### Quick Debug
```bash
php artisan tinker          # Интерактивная консоль
npm run type-check          # Валидация TypeScript
tail -f storage/logs/laravel.log  # Логи
```

### Reset & Test
```bash
php artisan migrate:fresh --seed   # Сброс БД
php artisan test && npm test       # Тесты
```

### Find Patterns
```bash
grep -r "текст_ошибки" app/Domain/*/Actions/   # Поиск ошибок бизнес-логики
grep -r "похожий_код" app/                     # Поиск в бэкенде
grep -r "компонент" resources/js/              # Поиск в фронтенде
```

**Полный список**: см. `Docs/COMMANDS.md`

## 🐛 Debug Patterns

| Проблема | Решение |
|----------|---------|
| "Невозможно архивировать" | grep Actions → упростить валидацию |
| "Данные не сохраняются" | Проверь $fillable → watcher → API |
| "Method not found" | grep -r "methodName" app/ |

### Поток данных (ВСЕГДА проверяй):
Component → Watcher → Emit → Backend → DB

## ⚠️ Project Traps
1. **$fillable** - ВСЕГДА добавляй новые поля в модели
2. **Watchers** - ОБЯЗАТЕЛЬНЫ для v-model полей
3. **Transactions** - для множественных операций БД

## 📚 База знаний проекта

### 🔴 ОБЯЗАТЕЛЬНО проверь `C:\www.spa.com\Docs\` перед решением проблем:
```
Docs/
├── ARCHITECTURE_FULL.md    # Полная архитектура проекта
├── COMMANDS.md              # Полный список команд
├── fixes/                   # Решения известных проблем
├── troubleshooting/         # Отладка типичных ошибок
├── antipatterns/            # Что НЕ делать
├── PATTERNS/                # Проверенные паттерны
└── lessons-learned/         # Уроки из ошибок
```

**Правило:** Если ошибка/проблема уже была - решение есть в Docs!

### Примеры поиска в документации:
```bash
grep -r "название ошибки" C:/www.spa.com/Docs/
grep -r "не делай" C:/www.spa.com/Docs/antipatterns/
grep -r "booking" C:/www.spa.com/Docs/PATTERNS/
```

## Специфичные функции проекта

- **Yandex Maps API** - Интеграция выбора адреса
- **Календарь бронирования** - Управление временными слотами
- **Медиа библиотека** - Spatie MediaLibrary для загрузок
- **Поиск** - Meilisearch через Laravel Scout
- **Админ-панель** - Filament v4 для администрирования
- **Модерация** - AdModerationService, AdminActionsService

## Соглашения Git
```bash
feat: новая функция
fix: исправление бага
refactor: реструктуризация кода
docs: документация
test: добавление тестов
```

## Важные файлы
- `.env` - Конфигурация окружения
- `FILAMENT_SETUP.md` - Документация админ-панели
- `.aidd/` - AIDD документация проекта
- `quality.json` - Метрики качества кода

## Важные напоминания
- **НЕ удаляй** существующий код без спроса
- **ВСЕГДА проверяй** существование файла перед изменением
- **СПРОСИ** если что-то непонятно
- **ПРЕДЛОЖИ варианты** решения
- **ТЕСТИРУЙ** сразу после создания
