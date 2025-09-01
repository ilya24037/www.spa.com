# 📝 Правила кодирования SPA Platform

## 🎯 Основные принципы

### KISS - Keep It Simple
- Делай код простым и понятным
- Избегай сложных вложенных структур
- Один метод = одна задача

### YAGNI - You Aren't Gonna Need It
- Не добавляй функционал "на будущее"
- Решай только текущие задачи
- Рефакторинг по мере необходимости

### DRY - Don't Repeat Yourself
- Выноси повторяющийся код в методы
- Создавай переиспользуемые компоненты
- Используй базовые классы и трейты

## 🏗️ Структура кода

### PHP (Laravel)
```php
// ✅ Хорошо
class AdController extends Controller
{
    private const ALLOWED_FIELDS = ['title', 'description', 'price'];
    
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0'
        ]);
        
        try {
            $ad = $this->adService->create($validated);
            return response()->json(['success' => true, 'data' => $ad]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

// ❌ Плохо
class AdController extends Controller
{
    public function store(Request $request)
    {
        $ad = Ad::create($request->all()); // Нет валидации!
        return $ad;
    }
}
```

### Vue.js
```vue
<!-- ✅ Хорошо -->
<script setup lang="ts">
interface Props {
  title: string
  price: number
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<{
  update: [field: string, value: any]
  save: []
}>()

const handleUpdate = (field: string, value: any) => {
  emit('update', field, value)
}
</script>

<!-- ❌ Плохо -->
<script>
export default {
  props: ['title', 'price'], // Нет типизации
  methods: {
    update(field, value) { // Нет типов
      this.$emit('update', field, value)
    }
  }
}
</script>
```

## 📁 Организация файлов

### Структура модуля
```
FeatureName/
├── index.vue         # Основной компонент
├── components/       # Подкомпоненты
├── store/           # Pinia store
├── types/           # TypeScript типы
├── api/             # API методы
└── styles/          # Стили модуля
```

### Именование
- **Компоненты**: PascalCase (UserProfile.vue)
- **Файлы**: kebab-case (user-profile.vue)
- **Методы**: camelCase (getUserData)
- **Константы**: UPPER_SNAKE_CASE (MAX_PRICE)

## 🚫 Запрещено

### Сложные структуры
```php
// ❌ НЕ ДЕЛАЙ
if ($user && $user->profile && $user->profile->settings && $user->profile->settings->notifications) {
    $user->profile->settings->notifications->email = true;
}

// ✅ ДЕЛАЙ
$user?->profile?->settings?->notifications?->email = true;
// или
$notifications = $user?->profile?->settings?->notifications;
if ($notifications) {
    $notifications->email = true;
}
```

### Магические числа
```php
// ❌ НЕ ДЕЛАЙ
if ($price > 1000) { ... }

// ✅ ДЕЛАЙ
private const MIN_PRICE = 1000;
if ($price > self::MIN_PRICE) { ... }
```

### Глобальные переменные
```php
// ❌ НЕ ДЕЛАЙ
global $config;

// ✅ ДЕЛАЙ
use App\Services\ConfigService;
$config = app(ConfigService::class);
```

## ✅ Обязательно

### Обработка ошибок
```php
try {
    $result = $this->service->process($data);
    return response()->json(['success' => true, 'data' => $result]);
} catch (ValidationException $e) {
    return response()->json(['errors' => $e->errors()], 422);
} catch (Exception $e) {
    Log::error('Process failed', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Internal server error'], 500);
}
```

### Валидация входных данных
```php
$validated = $request->validate([
    'title' => 'required|string|max:255',
    'price' => 'required|numeric|min:0|max:999999',
    'category_id' => 'required|exists:categories,id'
]);
```

### Комментарии на английском
```php
/**
 * Create new advertisement
 * 
 * @param array $data Validated advertisement data
 * @return Ad Created advertisement instance
 * @throws AdCreationException When creation fails
 */
public function create(array $data): Ad
{
    // Implementation...
}
```
