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

### Современные принципы 2024 (как на Avito/Ozon):
- **Performance First** - производительность с первого дня
- **Security by Design** - безопасность в архитектуре
- **Observability** - полная видимость системы
- **Scalability** - готовность к росту нагрузки
- **Maintainability** - простота поддержки и развития
- **Developer Experience** - удобство разработки

## 🏗️ Архитектурные правила

### Модульная архитектура (как на Avito/Ozon):
- **Один модуль = одна папка** в `resources/js/Components/Features/`
- **Минимум зависимостей** между модулями
- **Четкое разделение** ответственности
- **Переиспользуемые компоненты** в `UI/` и `Common/`
- **Микросервисная готовность** - возможность выделения в отдельные сервисы
- **Event-driven архитектура** - слабая связанность через события
- **CQRS паттерн** - разделение команд и запросов

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

### Backend (Laravel 12):

#### Контроллеры (современные практики):
- **Resource Controllers** для CRUD операций
- **Один контроллер = одна сущность**
- **Максимум 7 методов** в контроллере
- **Используй Form Requests** для валидации
- **Возвращай API Resources** для форматирования ответов
- **Используй Actions** для сложной бизнес-логики
- **Dependency Injection** через конструктор
- **Rate Limiting** для всех endpoints
- **Кэширование** ответов где возможно

```php
// ✅ Правильно (современные практики)
class AdController extends Controller
{
    public function __construct(
        private AdService $adService,
        private CacheManager $cache
    ) {}
    
    public function index(AdIndexRequest $request)
    {
        $cacheKey = 'ads.index.' . md5(serialize($request->validated()));
        
        $ads = $this->cache->remember($cacheKey, 300, function () use ($request) {
            return $this->adService->getList($request->validated());
        });
        
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

#### Модели (современные практики):
- **Всегда добавляй поля в $fillable**
- **Используй $casts для JSON полей** как 'array'
- **Создавай отношения** через методы
- **Используй soft deletes** для важных данных
- **Используй Accessors/Mutators** для трансформации данных
- **Добавляй Scopes** для переиспользуемых запросов
- **Используй Events** для автоматических действий
- **Добавляй индексы** в миграциях
- **Используй Enums** для статусов и типов

```php
// ✅ Правильно (современные практики)
class Ad extends Model
{
    protected $fillable = [
        'title', 'description', 'price', 'location', 'coordinates', 'status'
    ];
    
    protected $casts = [
        'coordinates' => 'array',
        'price' => 'decimal:2',
        'status' => AdStatus::class,
        'created_at' => 'datetime',
    ];
    
    // Scopes для переиспользуемых запросов
    public function scopeActive($query)
    {
        return $query->where('status', AdStatus::ACTIVE);
    }
    
    public function scopeInLocation($query, $location)
    {
        return $query->where('location', 'like', "%{$location}%");
    }
    
    // Accessor для форматирования цены
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ') . ' ₽';
    }
    
    // Mutator для автоматического slug
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }
    
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

## 🏢 Паттерны крупных платформ (Avito/Ozon/Wildberries)

### Архитектурные паттерны:
- **CQRS** - разделение команд и запросов
- **Event Sourcing** - хранение событий вместо состояния
- **Saga Pattern** - управление распределенными транзакциями
- **Circuit Breaker** - защита от каскадных сбоев
- **Bulkhead Pattern** - изоляция ресурсов
- **Retry Pattern** - повторные попытки с экспоненциальной задержкой

### Паттерны производительности:
- **Caching Strategy** - многоуровневое кэширование
- **Database Sharding** - горизонтальное разделение данных
- **Read Replicas** - реплики для чтения
- **CDN Strategy** - глобальное кэширование контента
- **Lazy Loading** - ленивая загрузка данных
- **Pagination** - постраничная загрузка

### Паттерны безопасности:
- **Zero Trust** - недоверие по умолчанию
- **Defense in Depth** - многоуровневая защита
- **Principle of Least Privilege** - минимальные права доступа
- **Fail Secure** - безопасное поведение при сбоях
- **Input Validation** - валидация на всех уровнях
- **Output Encoding** - кодирование вывода

### Паттерны мониторинга:
- **Three Pillars** - метрики, логи, трейсы
- **Golden Signals** - latency, traffic, errors, saturation
- **SLI/SLO/SLA** - индикаторы, цели, соглашения
- **Error Budget** - бюджет на ошибки
- **Chaos Engineering** - тестирование отказоустойчивости

### Паттерны разработки:
- **Feature Flags** - управление функциональностью
- **Blue-Green Deployment** - безостановочный деплой
- **Canary Releases** - постепенный запуск
- **A/B Testing** - тестирование гипотез
- **Dark Launching** - скрытый запуск функций
- **Kill Switch** - экстренное отключение функций

## 🚀 Производительность и оптимизация

### Backend оптимизация:
- **Кэширование** - Redis для сессий и кэша
- **Database оптимизация** - индексы, eager loading, pagination
- **Queue системы** - Laravel Horizon для фоновых задач
- **CDN** - Cloudflare для статических ресурсов
- **Compression** - gzip для ответов API
- **Rate Limiting** - защита от DDoS

### Frontend оптимизация:
- **Code Splitting** - ленивая загрузка компонентов
- **Tree Shaking** - удаление неиспользуемого кода
- **Image Optimization** - WebP, lazy loading
- **Bundle Analysis** - анализ размера бандла
- **Service Workers** - кэширование ресурсов
- **Critical CSS** - инлайн критических стилей

### Мониторинг производительности:
- **Laravel Telescope** - отладка и профилирование
- **Laravel Horizon** - мониторинг очередей
- **New Relic/DataDog** - APM мониторинг
- **Google PageSpeed** - метрики производительности
- **Web Vitals** - Core Web Vitals

## 🔒 Современная безопасность

### Backend безопасность:
- **Laravel Sanctum** - API аутентификация
- **Rate Limiting** - защита от брутфорса
- **CSRF Protection** - защита от CSRF атак
- **SQL Injection** - использование Eloquent/Query Builder
- **XSS Protection** - экранирование вывода
- **HTTPS Only** - принудительное использование HTTPS
- **Security Headers** - CSP, HSTS, X-Frame-Options

### Frontend безопасность:
- **Content Security Policy** - защита от XSS
- **Input Validation** - валидация на клиенте
- **Secure Cookies** - httpOnly, secure флаги
- **CORS** - правильная настройка CORS
- **Dependency Scanning** - проверка уязвимостей в пакетах

## 📊 Observability и мониторинг

### Логирование:
- **Structured Logging** - JSON формат логов
- **Log Levels** - ERROR, WARNING, INFO, DEBUG
- **Context Logging** - добавление контекста к логам
- **Log Aggregation** - ELK Stack или аналоги
- **Error Tracking** - Sentry для отслеживания ошибок

### Метрики:
- **Application Metrics** - время ответа, количество запросов
- **Business Metrics** - конверсия, активность пользователей
- **Infrastructure Metrics** - CPU, память, диск
- **Custom Metrics** - специфичные для бизнеса метрики

### Алерты:
- **Error Rate** - уведомления о высокой частоте ошибок
- **Response Time** - медленные запросы
- **Resource Usage** - высокое использование ресурсов
- **Business Alerts** - критические бизнес-метрики

## 🔧 Современные инструменты

### Обязательные (2024):
- **Laravel 12** - последняя версия фреймворка
- **Vue 3.4+** - Composition API, TypeScript
- **TypeScript 5+** - строгая типизация
- **Tailwind CSS 3.4+** - современные утилиты
- **Vite 5+** - быстрая сборка
- **PHP 8.3+** - последняя версия PHP

### Инструменты разработки:
- **Laravel Telescope** - отладка и профилирование
- **Laravel Horizon** - управление очередями
- **PHPStan Level 8** - максимальный статический анализ
- **ESLint + Prettier** - линтинг и форматирование
- **Husky** - git hooks для проверки кода
- **Lint-staged** - проверка только измененных файлов

### Инструменты тестирования:
- **Pest** - современный тестовый фреймворк
- **Laravel Dusk** - E2E тестирование
- **Vitest** - быстрые unit тесты для Vue
- **Playwright** - современное E2E тестирование
- **Coverage** - анализ покрытия кода

### CI/CD инструменты:
- **GitHub Actions** - автоматизация CI/CD
- **Docker** - контейнеризация приложения
- **Laravel Sail** - локальная разработка
- **Deployer** - автоматический деплой
- **Laravel Forge** - управление серверами

### Мониторинг и аналитика:
- **Sentry** - отслеживание ошибок
- **New Relic** - APM мониторинг
- **Google Analytics 4** - веб-аналитика
- **Hotjar** - анализ поведения пользователей
- **Lighthouse CI** - автоматическая проверка производительности

## 📋 Чек-лист перед коммитом (2024)

### Код качество:
- [ ] Код следует принципам KISS, DRY, SOLID
- [ ] Все тесты проходят (unit, feature, e2e)
- [ ] Покрытие кода > 80%
- [ ] PHPStan Level 8 проходит без ошибок
- [ ] ESLint проходит без предупреждений
- [ ] Код отформатирован Prettier
- [ ] Нет дублирования кода
- [ ] Типизация TypeScript добавлена

### Безопасность:
- [ ] Валидация всех входных данных
- [ ] Проверка прав доступа
- [ ] Нет SQL injection уязвимостей
- [ ] XSS защита реализована
- [ ] CSRF токены используются
- [ ] Rate limiting настроен

### Производительность:
- [ ] Кэширование добавлено где нужно
- [ ] Database запросы оптимизированы
- [ ] Eager loading используется
- [ ] Пагинация реализована
- [ ] Images оптимизированы
- [ ] Bundle size проверен

### Мониторинг:
- [ ] Логирование важных операций
- [ ] Error tracking настроен
- [ ] Метрики добавлены
- [ ] Алерты настроены

### Документация:
- [ ] PHPDoc комментарии добавлены
- [ ] README обновлен
- [ ] API документация актуальна
- [ ] Changelog обновлен

---

> **Помни**: Эти правила помогают создавать качественный, поддерживаемый код. 
> При сомнениях - выбирай простое решение.