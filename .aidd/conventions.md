# Соглашения по разработке SPA Platform

## 📚 Ссылки на основные документы
- **Идея проекта**: @doc/aidd/idea.md
- **Техническое видение**: @doc/aidd/vision.md
- **План работы**: @doc/aidd/tasklist.md
- **Процесс работы**: @doc/aidd/workflow.md

## 🎯 Главные принципы (из @doc/aidd/vision.md)

### KISS - Keep It Simple, Stupid
Простота превыше всего. Не усложняй без необходимости.

### YAGNI - You Aren't Gonna Need It
Не добавляй функционал "на будущее". Решай только текущие задачи.

### DRY - Don't Repeat Yourself
Не повторяйся. Выноси общий код в переиспользуемые компоненты.

### SOLID
Следуй принципам объектно-ориентированного проектирования.

### Итеративная разработка
Маленькие шаги, частое тестирование, быстрая обратная связь.

## ✅ Что ДЕЛАТЬ

### Backend (Laravel)
```php
// ✅ Бизнес-логика в сервисах
class BookingService {
    public function create(CreateBookingDTO $dto): Booking {
        return DB::transaction(function() use ($dto) {
            // логика создания
        });
    }
}

// ✅ Валидация в Request классах
class StoreBookingRequest extends FormRequest {
    public function rules(): array {
        return [
            'date' => 'required|date|after:today',
            'time' => 'required|date_format:H:i'
        ];
    }
}

// ✅ Контроллеры только для маршрутизации
public function store(StoreBookingRequest $request) {
    $booking = $this->bookingService->create(
        CreateBookingDTO::fromRequest($request)
    );
    return new BookingResource($booking);
}
```

### Frontend (Vue 3 + TypeScript)
```typescript
// ✅ Строгая типизация
interface Props {
    master: Master
    loading?: boolean
}

// ✅ Composition API с <script setup>
<script setup lang="ts">
const props = withDefaults(defineProps<Props>(), {
    loading: false
})

// ✅ Защищенные computed
const safeMaster = computed(() => props.master || {} as Master)
</script>

// ✅ Обработка всех состояний
<template>
  <div v-if="loading">Загрузка...</div>
  <div v-else-if="error">{{ error }}</div>
  <div v-else-if="!data">Нет данных</div>
  <div v-else>{{ data }}</div>
</template>
```

### База данных
```php
// ✅ Миграции для всех изменений схемы
Schema::create('bookings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('master_id')->constrained();
    $table->date('date');
    $table->time('time');
    $table->index(['master_id', 'date']); // индексы для частых запросов
    $table->timestamps();
});

// ✅ Транзакции для связанных операций
DB::transaction(function () {
    $booking = Booking::create([...]);
    $payment = Payment::create([...]);
    event(new BookingCreated($booking));
});
```

### Тестирование
```php
// ✅ Тесты для критичной логики
test('booking cannot be created in the past', function () {
    $response = $this->postJson('/api/bookings', [
        'date' => now()->subDay()
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['date']);
});
```

## ❌ Что НЕ ДЕЛАТЬ

### Backend
```php
// ❌ НЕ размещать логику в контроллерах
public function store(Request $request) {
    // НЕ ДЕЛАЙ ТАК!
    if ($request->date < now()) {
        return error('Invalid date');
    }
    $booking = Booking::create($request->all());
    // еще 100 строк логики...
}

// ❌ НЕ использовать прямые запросы в контроллерах
public function index() {
    // НЕ ДЕЛАЙ ТАК!
    $masters = DB::table('masters')
        ->join('ads', 'masters.id', '=', 'ads.master_id')
        ->where('ads.status', 'active')
        ->get();
}

// ❌ НЕ игнорировать исключения
try {
    $booking->save();
} catch (Exception $e) {
    // НЕ ДЕЛАЙ ТАК - пустой catch!
}
```

### Frontend
```typescript
// ❌ НЕ использовать any
const data: any = response.data  // НЕ ДЕЛАЙ ТАК!

// ❌ НЕ изменять props напрямую
props.master.name = 'Новое имя'  // НЕ ДЕЛАЙ ТАК!

// ❌ НЕ игнорировать ошибки
fetch('/api/masters')
    .then(response => response.json())
    .then(data => this.masters = data)
    // НЕ ДЕЛАЙ ТАК - нет обработки ошибок!
```

### База данных
```php
// ❌ НЕ изменять схему без миграций
DB::statement('ALTER TABLE users ADD COLUMN age INT'); // НЕ ДЕЛАЙ ТАК!

// ❌ НЕ делать N+1 запросов
$masters = Master::all();
foreach ($masters as $master) {
    echo $master->bookings->count(); // НЕ ДЕЛАЙ ТАК - N+1!
}
```

## 📏 Стандарты качества

### Код должен быть:
- **Читаемым** - понятные имена переменных и методов
- **Тестируемым** - легко покрывается тестами
- **Поддерживаемым** - легко изменять и расширять
- **Документированным** - сложная логика имеет комментарии

### Именование
- **Классы**: PascalCase (`BookingService`, `MasterProfile`)
- **Методы**: camelCase (`createBooking`, `getMasterById`)
- **Переменные**: camelCase (`$masterProfile`, `bookingDate`)
- **Константы**: UPPER_SNAKE_CASE (`MAX_BOOKINGS_PER_DAY`)
- **Таблицы БД**: snake_case множественное число (`master_profiles`)
- **Поля БД**: snake_case (`created_at`, `master_id`)

### Структура файлов
- Один класс = один файл
- Имя файла = имя класса
- Логическая группировка по папкам
- Следование структуре DDD/FSD

## 🔍 Проверка качества

### Перед коммитом проверь:
```bash
# PHP/Laravel
php artisan test           # Все тесты проходят
php artisan code:analyse   # Статический анализ
./vendor/bin/phpcs         # Стиль кода

# JavaScript/Vue
npm run type-check         # TypeScript без ошибок
npm run lint              # ESLint без ошибок
npm run test              # Тесты проходят
npm run build             # Сборка успешна
```

### Код-ревью чеклист:
- [ ] Следует принципам KISS, YAGNI, DRY
- [ ] Бизнес-логика в сервисах
- [ ] Есть тесты для новой функциональности
- [ ] Нет any в TypeScript
- [ ] Обработаны все состояния (loading, error, empty)
- [ ] Есть валидация входных данных
- [ ] Используются транзакции где нужно
- [ ] Нет N+1 запросов
- [ ] Понятные имена переменных и методов

## 🎨 Стиль кода

### PHP
Следуем PSR-12 стандарту.

### JavaScript/TypeScript
Следуем конфигурации ESLint проекта.

### Vue
- Composition API с `<script setup>`
- TypeScript для типизации
- Однофайловые компоненты

### CSS
- Tailwind CSS для стилей
- Минимум custom CSS
- Mobile-first подход

## 📝 Комментарии

### Когда писать комментарии:
```php
// ✅ Сложная бизнес-логика
// Расчет комиссии: базовая 10% + 5% за срочность - 3% для постоянных клиентов
$commission = 0.10 + ($isUrgent ? 0.05 : 0) - ($isRegular ? 0.03 : 0);

// ✅ Неочевидные решения
// Используем soft delete чтобы сохранить историю для аналитики
$booking->delete(); // на самом деле устанавливает deleted_at

// ❌ Очевидные комментарии
// Получаем пользователя по ID
$user = User::find($id); // НЕ НУЖЕН такой комментарий
```

## 🚀 Производительность

### Всегда оптимизируй:
- Используй eager loading для связей
- Добавляй индексы для частых запросов
- Кешируй тяжелые вычисления
- Используй пагинацию для больших списков
- Минимизируй количество запросов к БД

```php
// ✅ Eager loading
$masters = Master::with(['profile', 'bookings', 'reviews'])->get();

// ✅ Кеширование
$topMasters = Cache::remember('top-masters', 3600, function () {
    return Master::top()->limit(10)->get();
});
```

---

*Следование этим соглашениям обеспечивает качество и поддерживаемость кода SPA Platform.*