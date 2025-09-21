# 🎯 Conventions - Стандарты кодирования

> Соглашения по разработке для SPA Platform

## 📋 Содержание

- [Основные принципы](#-основные-принципы)
- [Backend (Laravel)](#-backend-laravel)
- [Frontend (Vue.js)](#-frontend-vuejs)
- [Переиспользование компонентов](#-переиспользование-компонентов)
- [Design System](#-design-system)
- [База данных](#-база-данных)
- [Тестирование](#-тестирование)
- [Безопасность](#-безопасность)
- [Производительность](#-производительность)
- [Чек-лист](#-чек-лист)

---

## 🎯 Основные принципы

### Принципы разработки:
- **KISS** - Keep It Simple, Stupid (делай просто)
- **YAGNI** - You Aren't Gonna Need It (не переусложняй)
- **DRY** - Don't Repeat Yourself (не повторяйся)
- **SOLID** - принципы объектно-ориентированного программирования
- **MVP** - Minimum Viable Product (минимально жизнеспособный продукт)
- **Fail Fast** - быстрая обработка ошибок
- **Итеративная разработка** - пошаговое развитие

### Современные принципы 2024:
- **Performance First** - производительность с первого дня
- **Security by Design** - безопасность в архитектуре
- **Observability** - полная видимость системы
- **Scalability** - готовность к росту нагрузки
- **Maintainability** - простота поддержки и развития
- **Developer Experience** - удобство разработки

---

## 🏗️ Backend (Laravel)

### Контроллеры:
- **Resource Controllers** для CRUD операций
- **Single Responsibility** - одна ответственность
- **Dependency Injection** - внедрение зависимостей
- **Form Requests** для валидации
- **API Resources** для форматирования ответов

```php
// Пример контроллера
class AdController extends Controller
{
    public function __construct(
        private AdService $adService,
        private AdRequest $adRequest
    ) {}

    public function store(AdRequest $request): JsonResponse
    {
        $ad = $this->adService->create($request->validated());
        return response()->json(new AdResource($ad), 201);
    }
}
```

### Модели:
- **Eloquent ORM** для работы с БД
- **Fillable** массив для массового присвоения
- **Casts** для типизации данных
- **Scopes** для переиспользуемых запросов
- **Accessors/Mutators** для обработки данных

```php
// Пример модели
class Ad extends Model
{
    protected $fillable = [
        'title', 'description', 'price', 'status'
    ];

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime'
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
```

### Сервисы:
- **Business Logic** в сервисах
- **Single Responsibility** - одна ответственность
- **Dependency Injection** - внедрение зависимостей
- **Error Handling** - обработка ошибок
- **Logging** - логирование действий

```php
// Пример сервиса
class AdService
{
    public function __construct(
        private Ad $ad,
        private LoggerInterface $logger
    ) {}

    public function create(array $data): Ad
    {
        try {
            $ad = $this->ad->create($data);
            $this->logger->info('Ad created', ['ad_id' => $ad->id]);
            return $ad;
        } catch (Exception $e) {
            $this->logger->error('Failed to create ad', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
```

---

## 🎨 Frontend (Vue.js)

### Компоненты:
- **Composition API** с <script setup>
- **TypeScript** для типизации
- **Props типизация** - все props должны быть типизированы
- **Emit events** - не мутировать props напрямую
- **Single File Components** - один файл = один компонент

```vue
<!-- Пример компонента -->
<template>
  <div class="ad-card">
    <h3>{{ ad.title }}</h3>
    <p>{{ ad.description }}</p>
    <Button @click="$emit('view', ad.id)">Посмотреть</Button>
  </div>
</template>

<script setup lang="ts">
interface Props {
  ad: {
    id: number
    title: string
    description: string
  }
}

defineProps<Props>()
defineEmits<{
  view: [id: number]
}>()
</script>
```

### State Management:
- **Pinia** для управления состоянием
- **Stores** по модулям
- **Actions** для асинхронных операций
- **Getters** для вычисляемых свойств

```typescript
// Пример store
export const useAdStore = defineStore('ads', () => {
  const ads = ref<Ad[]>([])
  const loading = ref(false)

  const fetchAds = async () => {
    loading.value = true
    try {
      ads.value = await adApi.getAds()
    } finally {
      loading.value = false
    }
  }

  return { ads, loading, fetchAds }
})
```

---

## 🔄 Переиспользование компонентов

### Основные принципы:
- **DRY** - Don't Repeat Yourself (не повторяйся)
- **Единообразие** - все элементы должны выглядеть одинаково
- **Переиспользование** - создавай универсальные компоненты
- **Модульность** - компоненты должны быть независимыми

### Структура компонентов:
```
shared/ui/           # Базовые UI элементы
├── Button.vue       # Единая кнопка
├── Input.vue        # Единый input
├── Modal.vue        # Единый модал
├── Card.vue         # Единая карточка
├── Loading.vue      # Единый лоадер
├── ErrorMessage.vue # Единое сообщение об ошибке
└── FormInput.vue    # Единое поле формы

Components/Layout/   # Layout компоненты
├── AppLayout.vue    # Основной лейаут
├── Header.vue       # Шапка сайта
├── Footer.vue       # Подвал сайта
├── Sidebar.vue      # Боковая панель
└── Navigation.vue   # Навигация
```

### Правила создания компонентов:
- **Один компонент = одна ответственность**
- **Все props должны быть типизированы**
- **Компоненты должны быть переиспользуемыми**
- **Следуй единому стилю дизайна**
- **Добавляй документацию и примеры**
- **Пиши тесты для компонентов**

### Примеры переиспользования:

#### **Button компонент:**
```vue
<!-- Использование везде одинаково -->
<Button variant="primary" size="md">Сохранить</Button>
<Button variant="danger" size="sm">Удалить</Button>
<Button variant="success" size="lg" :loading="isLoading">Отправить</Button>
```

#### **Layout система:**
```vue
<!-- Все страницы используют единый лейаут -->
<AppLayout show-sidebar sidebar-type="filters">
  <AdList />
</AppLayout>

<AppLayout>
  <AdDetails />
</AppLayout>

<AppLayout show-sidebar sidebar-type="navigation">
  <ProfileContent />
</AppLayout>
```

---

## 🎨 Design System

### Цветовая палитра:
```css
:root {
  /* Основные цвета */
  --color-primary: #3B82F6;
  --color-primary-dark: #2563EB;
  --color-primary-light: #60A5FA;
  
  /* Вторичные цвета */
  --color-secondary: #6B7280;
  --color-secondary-dark: #4B5563;
  --color-secondary-light: #9CA3AF;
  
  /* Статусные цвета */
  --color-success: #10B981;
  --color-warning: #F59E0B;
  --color-danger: #EF4444;
  --color-info: #3B82F6;
  
  /* Нейтральные цвета */
  --color-gray-50: #F9FAFB;
  --color-gray-100: #F3F4F6;
  --color-gray-200: #E5E7EB;
  --color-gray-300: #D1D5DB;
  --color-gray-400: #9CA3AF;
  --color-gray-500: #6B7280;
  --color-gray-600: #4B5563;
  --color-gray-700: #374151;
  --color-gray-800: #1F2937;
  --color-gray-900: #111827;
}
```

### Типографика:
```css
.text-heading-1 { @apply text-4xl font-bold text-gray-900; }
.text-heading-2 { @apply text-3xl font-semibold text-gray-900; }
.text-heading-3 { @apply text-2xl font-semibold text-gray-900; }
.text-heading-4 { @apply text-xl font-medium text-gray-900; }
.text-body { @apply text-base text-gray-700; }
.text-caption { @apply text-sm text-gray-500; }
.text-small { @apply text-xs text-gray-400; }
```

### Отступы и размеры:
```css
.spacing-xs { @apply p-1; }
.spacing-sm { @apply p-2; }
.spacing-md { @apply p-4; }
.spacing-lg { @apply p-6; }
.spacing-xl { @apply p-8; }

.margin-xs { @apply m-1; }
.margin-sm { @apply m-2; }
.margin-md { @apply m-4; }
.margin-lg { @apply m-6; }
.margin-xl { @apply m-8; }
```

---

## 🗄️ База данных

### Миграции:
- **Одна таблица = одна миграция**
- **Именование** - snake_case с timestamp
- **Индексы** на foreign keys обязательны
- **Soft deletes** для важных данных

```php
// Пример миграции
Schema::create('ads', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description');
    $table->integer('price');
    $table->enum('status', ['draft', 'active', 'inactive', 'archived']);
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['status', 'created_at']);
    $table->index('user_id');
});
```

### Seeders:
- **Тестовые данные** для разработки
- **Реалистичные данные** - как в продакшн
- **Зависимости** между данными

---

## 🧪 Тестирование

### Unit тесты:
- **Покрытие > 80%** для сервисов
- **Тестирование** всех методов
- **Edge cases** - граничные случаи
- **Error scenarios** - сценарии ошибок

```php
// Пример unit теста
class AdServiceTest extends TestCase
{
    public function test_can_create_ad()
    {
        $data = [
            'title' => 'Test Ad',
            'description' => 'Test Description',
            'price' => 1000
        ];
        
        $ad = $this->adService->create($data);
        
        $this->assertInstanceOf(Ad::class, $ad);
        $this->assertEquals('Test Ad', $ad->title);
    }
}
```

### Feature тесты:
- **API endpoints** - все маршруты
- **Integration** - взаимодействие компонентов
- **Authentication** - авторизация
- **Validation** - валидация данных

---

## 🔒 Безопасность

### Валидация:
- **Input validation** на всех уровнях
- **Output encoding** - кодирование вывода
- **SQL injection** - защита через Eloquent
- **XSS protection** - экранирование в Blade

### Авторизация:
- **Laravel Policies** для проверки прав
- **Middleware** для защиты роутов
- **Role-based access** - роли и разрешения
- **API tokens** - безопасные токены

---

## ⚡ Производительность

### Backend:
- **Database queries** - оптимизация запросов
- **Caching** - Redis для кэширования
- **Lazy loading** - ленивая загрузка
- **Indexes** - индексы для быстрого поиска

### Frontend:
- **Code splitting** - разделение кода
- **Lazy loading** - ленивая загрузка компонентов
- **Image optimization** - оптимизация изображений
- **CDN** - глобальное кэширование

---

## ✅ Чек-лист

### Перед коммитом:
- [ ] Код следует conventions.md
- [ ] Все тесты проходят
- [ ] Покрытие кода > 80%
- [ ] Нет дублирования кода
- [ ] Компоненты переиспользуемы
- [ ] Документация обновлена
- [ ] Логирование добавлено
- [ ] Обработка ошибок реализована

### При создании компонента:
- [ ] Проверить, есть ли уже похожий компонент
- [ ] Сделать компонент переиспользуемым
- [ ] Добавить типизацию для всех props
- [ ] Следовать единому стилю дизайна
- [ ] Добавить документацию
- [ ] Написать тесты

---

**Помни**: Каждый код должен быть читаемым, тестируемым и поддерживаемым.
Следуй принципам Clean Code и SOLID.
Всегда думай о переиспользовании компонентов.