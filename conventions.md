# SPA Platform - Соглашения по разработке

> Правила для AI-ассистента по генерации кода для проекта SPA Platform
> 
> Ссылка на техническое видение: [vision.md](./vision.md)

## 🎯 Основные принципы

### Принципы разработки (из vision.md):
- **KISS** - Keep It Simple, Stupid (делай просто)
- **YAGNI** - You Aren't Gonna Need It (не переусложняй)
- **DRY** - Don't Repeat Yourself (не повторяйся)
- **SOLID** - принципы объектно-ориентированного программирования
- **MVP** - Minimum Viable Product (минимально жизнеспособный продукт)
- **Fail Fast** - быстрая обработка ошибок
- **Итеративная разработка** - пошаговое развитие

## 🏗️ Архитектурные правила

### Модульная архитектура:
- **Один модуль = одна папка** в `resources/js/Components/Features/`
- **Минимум зависимостей** между модулями
- **Четкое разделение** ответственности
- **Переиспользуемые компоненты** в `UI/` и `Common/`

### Структура файлов:
```
resources/js/Components/
├── UI/              # Базовые UI элементы (кнопки, формы, модалки)
├── Common/          # Общие компоненты (хедер, футер, навигация)
├── Features/        # Функциональные модули
│   ├── Dashboard/   # Личные кабинеты
│   ├── Ads/         # Объявления
│   ├── Chat/        # Чат
│   └── Booking/     # Бронирование
└── Pages/           # Компоненты страниц
```

## 💻 Правила кодирования

### Backend (Laravel):

#### Контроллеры:
- **Resource Controllers** для CRUD операций
- **Один контроллер = одна сущность**
- **Максимум 7 методов** в контроллере
- **Используй Form Requests** для валидации
- **Возвращай API Resources** для форматирования ответов

```php
// ✅ Правильно
class AdController extends Controller
{
    public function index(AdIndexRequest $request)
    {
        $ads = AdService::getList($request->validated());
        return AdResource::collection($ads);
    }
}

// ❌ Неправильно
class AdController extends Controller
{
    public function index(Request $request)
    {
        $ads = Ad::where('status', 'active')->get();
        return response()->json($ads);
    }
}
```

#### Модели:
- **Всегда добавляй поля в $fillable**
- **Используй $casts для JSON полей** как 'array'
- **Создавай отношения** через методы
- **Используй soft deletes** для важных данных

```php
// ✅ Правильно
class Ad extends Model
{
    protected $fillable = [
        'title', 'description', 'price', 'location', 'coordinates'
    ];
    
    protected $casts = [
        'coordinates' => 'array',
        'created_at' => 'datetime',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

#### Сервисы:
- **Создавай Service классы** для бизнес-логики
- **Один сервис = одна область** ответственности
- **Используй Dependency Injection**
- **Обрабатывай ошибки** через исключения

```php
// ✅ Правильно
class AdService
{
    public function create(array $data, User $user): Ad
    {
        try {
            $ad = $user->ads()->create($data);
            $this->notifyModerators($ad);
            return $ad;
        } catch (Exception $e) {
            throw new AdCreationException('Failed to create ad', 0, $e);
        }
    }
}
```

### Frontend (Vue 3):

#### Компоненты:
- **Используй Composition API** с `<script setup>`
- **Типизируй все props** с TypeScript интерфейсами
- **Emit события**, не меняй props напрямую
- **Один компонент = одна ответственность**

```vue
<!-- ✅ Правильно -->
<script setup lang="ts">
interface Props {
  ad: Ad;
  showActions?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showActions: true
});

const emit = defineEmits<{
  favorite: [id: number];
  contact: [id: number];
}>();
</script>
```

#### Stores (Pinia):
- **Один store = одна область** данных
- **Используй TypeScript** для типизации
- **Разделяй actions и state**
- **Обрабатывай состояния** загрузки, успеха, ошибки

```typescript
// ✅ Правильно
export const useAdStore = defineStore('ads', () => {
  const ads = ref<Ad[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  
  const fetchAds = async (filters: AdFilters) => {
    loading.value = true;
    error.value = null;
    try {
      ads.value = await AdService.getList(filters);
    } catch (err) {
      error.value = 'Failed to load ads';
    } finally {
      loading.value = false;
    }
  };
  
  return { ads, loading, error, fetchAds };
});
```

## 🎨 Стилизация (Tailwind CSS)

### Принципы:
- **Mobile-first подход** - начинай с мобильных стилей
- **Используй утилитарные классы** вместо кастомного CSS
- **Создавай компоненты** для переиспользуемых стилей
- **Следуй дизайн-системе** как на Ozon

### Структура стилей:
```css
/* ✅ Правильно - компонент кнопки */
.btn-primary {
  @apply bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors;
}

/* ❌ Неправильно - кастомные стили */
.custom-button {
  background-color: #2563eb;
  color: white;
  padding: 8px 16px;
  border-radius: 8px;
}
```

## 🗄️ База данных

### Миграции:
- **Одна таблица = одна миграция**
- **Добавляй индексы** на foreign keys
- **Используй soft deletes** для важных данных
- **Создавай seeders** для тестовых данных

```php
// ✅ Правильно
Schema::create('ads', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('title');
    $table->text('description');
    $table->decimal('price', 10, 2);
    $table->json('coordinates');
    $table->enum('status', ['draft', 'active', 'inactive', 'moderated']);
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['status', 'created_at']);
    $table->index('user_id');
});
```

## 🔒 Безопасность

### Обязательные проверки:
- **Валидация всех входных данных**
- **Проверка прав доступа** на каждом действии
- **Использование CSRF токенов**
- **Экранирование вывода** пользовательских данных

```php
// ✅ Правильно
public function update(AdUpdateRequest $request, Ad $ad)
{
    $this->authorize('update', $ad);
    
    $ad->update($request->validated());
    
    return new AdResource($ad);
}
```

## 🧪 Тестирование

### Обязательные тесты:
- **Unit тесты** для сервисов
- **Feature тесты** для API endpoints
- **Тестируй граничные случаи** и ошибки
- **Покрытие кода** минимум 80%

```php
// ✅ Правильно
public function test_can_create_ad()
{
    $user = User::factory()->create();
    $adData = Ad::factory()->make()->toArray();
    
    $response = $this->actingAs($user)
        ->postJson('/api/ads', $adData);
    
    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'title', 'price']]);
    
    $this->assertDatabaseHas('ads', [
        'user_id' => $user->id,
        'title' => $adData['title']
    ]);
}
```

## 📝 Документация

### Комментарии:
- **Пиши комментарии на английском**
- **Объясняй сложную логику**
- **Документируй API endpoints**
- **Используй PHPDoc** для методов

```php
/**
 * Create a new ad for the authenticated user
 *
 * @param AdCreateRequest $request
 * @return JsonResponse
 * @throws AdCreationException
 */
public function store(AdCreateRequest $request): JsonResponse
{
    // Implementation
}
```

## 🚫 Что запрещено

### Код:
- **Глобальные переменные** - используй DI
- **Магические числа** - создавай константы
- **Сложные вложенные структуры** - разбивай на методы
- **Дублирование кода** - выноси в методы/компоненты
- **Прямые SQL запросы** - используй Eloquent/Query Builder

### Архитектура:
- **Прямые вызовы к БД** из контроллеров
- **Бизнес-логика** в контроллерах
- **Жестко закодированные значения** в коде
- **Отсутствие обработки ошибок**

## ✅ Что обязательно

### Код:
- **Обработка ошибок** в try-catch блоках
- **Валидация входных данных**
- **Типизация** всех переменных и параметров
- **Логирование** важных операций
- **Тестирование** новой функциональности

### Архитектура:
- **Разделение слоев** (Controller -> Service -> Repository)
- **Использование интерфейсов** для зависимостей
- **Единообразные ответы** API
- **Консистентное именование** файлов и классов

## 🎯 Специфика проекта

### Личные кабинеты:
- **Разделяй логику** клиентов и мастеров
- **Используй общие компоненты** где возможно
- **Следуй паттерну Avito** для UI/UX

### Объявления:
- **Модель как на Avito** - платные/бесплатные
- **Система продвижения** в поиске
- **Множественные объявления** у одного мастера

### Административная панель:
- **Система ролей** Spatie Laravel Permission
- **Гранулярные права** доступа
- **Аудит действий** пользователей

### Чат:
- **Real-time общение** через WebSockets
- **История сообщений** с пагинацией
- **Уведомления** о новых сообщениях

## 🔧 Инструменты

### Обязательные:
- **Laravel Telescope** - отладка
- **Laravel Horizon** - очереди
- **PHPStan** - статический анализ
- **ESLint** - линтинг JS/TS
- **Prettier** - форматирование кода

### Рекомендуемые:
- **Laravel Debugbar** - профилирование
- **Laravel IDE Helper** - автодополнение
- **Vue DevTools** - отладка Vue
- **Tailwind CSS IntelliSense** - автодополнение стилей

## 📋 Чек-лист перед коммитом

- [ ] Код следует принципам KISS, DRY, SOLID
- [ ] Все тесты проходят
- [ ] Добавлена обработка ошибок
- [ ] Валидация входных данных
- [ ] Логирование важных операций
- [ ] Обновлена документация
- [ ] Проверен линтер
- [ ] Код отформатирован
- [ ] Нет дублирования
- [ ] Типизация добавлена

---

> **Помни**: Эти правила помогают создавать качественный, поддерживаемый код. 
> При сомнениях - выбирай простое решение.