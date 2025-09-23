# ADR-001: Domain-Driven Design для backend

## Метаданные

- **Дата:** 2024-08-15
- **Статус:** Accepted
- **Deciders:** Техлид, Backend team
- **Теги:** #architecture #backend #ddd #laravel

## Контекст и проблема

Проект SPA Platform растет, количество бизнес-логики увеличивается. Стандартная MVC структура Laravel приводит к толстым контроллерам и моделям. Нужна архитектура, которая позволит масштабировать проект без потери качества кода.

### Требования
- [x] Разделение бизнес-логики и инфраструктуры
- [x] Легкое тестирование бизнес-логики
- [x] Понятная структура для новых разработчиков
- [x] Возможность работы нескольких команд параллельно

### Ограничения
- Использование Laravel как основного фреймворка
- Команда знакома с Laravel, но не с DDD
- Нужно сохранить работающий код

## Рассмотренные варианты

### Вариант 1: Классический Laravel MVC
**Описание:** Продолжить использовать стандартную структуру Laravel

**Плюсы:**
- Команда уже знает подход
- Много документации
- Быстрый старт

**Минусы:**
- Fat controllers при росте логики
- Сложное тестирование
- Смешивание бизнес-логики и инфраструктуры

### Вариант 2: Domain-Driven Design (DDD)
**Описание:** Разделить код по доменам с явным выделением бизнес-логики

**Плюсы:**
- Четкое разделение ответственности
- Легкое тестирование
- Масштабируемость
- Параллельная работа команд

**Минусы:**
- Кривая обучения для команды
- Больше файлов и папок
- Начальный оверхед

### Вариант 3: Модульная архитектура (Modules)
**Описание:** Разделить приложение на независимые модули

**Плюсы:**
- Независимые модули
- Возможность переиспользования

**Минусы:**
- Сложность интеграции модулей
- Дублирование кода между модулями
- Нет стандарта в Laravel

## Решение

Выбран **Вариант 2: Domain-Driven Design**

### Обоснование
DDD дает четкую структуру для организации растущего кода, разделяет бизнес-логику от инфраструктуры и позволяет легко тестировать. Хотя есть кривая обучения, долгосрочные преимущества перевешивают начальные сложности.

### Детали реализации
```
app/Domain/
├── User/
│   ├── Models/
│   │   └── User.php
│   ├── Services/
│   │   └── UserService.php
│   ├── Actions/
│   │   ├── CreateUserAction.php
│   │   └── UpdateProfileAction.php
│   ├── DTOs/
│   │   └── CreateUserDTO.php
│   └── Events/
│       └── UserCreated.php
├── Master/
│   ├── Models/
│   ├── Services/
│   └── Actions/
├── Booking/
└── Ad/
```

## Последствия

### Положительные
- ✅ Четкая организация кода по доменам
- ✅ Бизнес-логика изолирована в Services и Actions
- ✅ Легкое unit-тестирование бизнес-логики
- ✅ Новые разработчики быстрее понимают структуру
- ✅ Команды могут работать над разными доменами параллельно

### Отрицательные
- ⚠️ Больше файлов и директорий
- ⚠️ Необходимость обучения команды DDD принципам
- ⚠️ Начальный оверхед при создании новых фич

### Риски
- 🔴 Неправильное определение границ доменов → Регулярное ревью архитектуры
- 🔴 Смешивание слоев → Code review и линтеры

## Примеры кода

### До изменения
```php
// app/Http/Controllers/MasterController.php
class MasterController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([...]);

        // Бизнес-логика в контроллере
        if ($data['experience'] < 3) {
            $data['level'] = 'junior';
        } else {
            $data['level'] = 'senior';
        }

        $master = Master::create($data);

        // Еще бизнес-логика
        if ($master->level === 'senior') {
            $master->priority = true;
            $master->save();
        }

        return response()->json($master);
    }
}
```

### После изменения
```php
// app/Http/Controllers/MasterController.php
class MasterController extends Controller
{
    public function __construct(
        private MasterService $masterService
    ) {}

    public function store(StoreMasterRequest $request)
    {
        $dto = CreateMasterDTO::fromRequest($request);
        $master = $this->masterService->create($dto);

        return new MasterResource($master);
    }
}

// app/Domain/Master/Services/MasterService.php
class MasterService
{
    public function create(CreateMasterDTO $dto): Master
    {
        return DB::transaction(function () use ($dto) {
            $master = Master::create($dto->toArray());

            $this->assignLevel($master);
            $this->setPriority($master);

            event(new MasterCreated($master));

            return $master;
        });
    }

    private function assignLevel(Master $master): void
    {
        // Бизнес-логика инкапсулирована
        $master->level = $master->experience < 3 ? 'junior' : 'senior';
        $master->save();
    }
}
```

## Связанные решения

- [ADR-002](./002-fsd-frontend.md) - Аналогичный подход для frontend
- [ADR-005](./005-repository-pattern.md) - Паттерн репозиторий для доменов

## Ссылки

- [Domain-Driven Design by Eric Evans](https://www.amazon.com/Domain-Driven-Design-Tackling-Complexity-Software/dp/0321125215)
- [DDD in Laravel](https://stitcher.io/blog/laravel-beyond-crud-01-domain-oriented-laravel)
- [Our DDD Guidelines](./../CONVENTIONS.md#backend-ddd)

## Заметки

Миграция существующего кода происходит постепенно, по мере рефакторинга. Новый функционал сразу пишется в DDD стиле.

---

*Последнее обновление: 2025-01-22*