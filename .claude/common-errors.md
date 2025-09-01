# 🚨 Частые ошибки и решения - SPA Platform

## 📝 Vue / Frontend ошибки

### 1. ❌ Данные не сохраняются при переключении секций
**Симптомы:** Заполненные поля очищаются при навигации между вкладками

**Причина:** Отсутствует watcher для автосохранения

**Решение:**
```typescript
// ОБЯЗАТЕЛЬНО добавить watcher для нового поля
watch(() => formData.newField, () => {
  saveFormData() // или emitData()
}, { deep: true })
```

**Проверка:** Переключить секции - данные должны остаться

---

### 2. ❌ TypeScript error: Type 'any' is not assignable
**Симптомы:** TS2322, TS7006 ошибки при сборке

**Причина:** Использование any или отсутствие типов

**Решение:**
```typescript
// ❌ ПЛОХО
const data: any = response.data

// ✅ ХОРОШО
interface ResponseData {
  id: number
  name: string
  services: Service[]
}
const data: ResponseData = response.data
```

---

### 3. ❌ Cannot read properties of undefined (reading 'id')
**Симптомы:** Белый экран, ошибка в консоли

**Причина:** Обращение к свойству без проверки

**Решение:**
```vue
<!-- ❌ ПЛОХО -->
<div>{{ master.profile.name }}</div>

<!-- ✅ ХОРОШО -->
<div v-if="master?.profile?.name">
  {{ master.profile.name }}
</div>

<!-- ИЛИ с computed -->
<script setup>
const safeName = computed(() => master.value?.profile?.name || 'Без имени')
</script>
```

---

### 4. ❌ Skeleton loader не показывается
**Симптомы:** Пустой экран во время загрузки

**Причина:** Неправильная проверка состояния загрузки

**Решение:**
```vue
<template>
  <!-- ✅ Правильный порядок проверок -->
  <div v-if="isLoading">
    <SkeletonLoader />
  </div>
  <div v-else-if="error">
    <ErrorMessage :error="error" />
  </div>
  <div v-else-if="!data || data.length === 0">
    <EmptyState />
  </div>
  <div v-else>
    <!-- Основной контент -->
  </div>
</template>
```

---

### 5. ❌ Изменения в props мутируют родительский компонент
**Симптомы:** Консоль: "Avoid mutating a prop directly"

**Причина:** Прямое изменение props

**Решение:**
```typescript
// ❌ ПЛОХО
props.value = newValue

// ✅ ХОРОШО - через emit
emit('update:modelValue', newValue)

// ✅ ИЛИ локальная копия
const localValue = ref(props.value)
watch(() => props.value, (newVal) => {
  localValue.value = newVal
})
```

---

## 🔧 Laravel / Backend ошибки

### 6. ❌ Mass assignment exception
**Симптомы:** "Add [field] to fillable property"

**Причина:** Поле отсутствует в $fillable

**Решение:**
```php
// В модели
protected $fillable = [
    'existing_field',
    'new_field', // ← Добавить новое поле
];
```

---

### 7. ❌ Call to undefined method on null
**Симптомы:** "Call to a member function on null"

**Причина:** Отсутствует проверка на null

**Решение:**
```php
// ❌ ПЛОХО
$master->profile->updateStatus();

// ✅ ХОРОШО
if ($master->profile) {
    $master->profile->updateStatus();
}

// ✅ ИЛИ null-safe оператор
$master->profile?->updateStatus();
```

---

### 8. ❌ N+1 проблема с запросами
**Симптомы:** Сотни SQL запросов, медленная загрузка

**Причина:** Отсутствует eager loading

**Решение:**
```php
// ❌ ПЛОХО - N+1 запросов
$masters = Master::all();
foreach ($masters as $master) {
    echo $master->services; // Новый запрос каждый раз
}

// ✅ ХОРОШО - 2 запроса
$masters = Master::with('services')->get();
```

---

### 9. ❌ CSRF token mismatch
**Симптомы:** 419 ошибка при POST запросах

**Причина:** Отсутствует CSRF токен

**Решение:**
```javascript
// В axios настройках
axios.defaults.headers.common['X-CSRF-TOKEN'] = 
    document.querySelector('meta[name="csrf-token"]').content;

// Или в Inertia
import { usePage } from '@inertiajs/vue3'
const token = usePage().props.csrf_token
```

---

### 10. ❌ Миграция fails: duplicate column
**Симптомы:** "Column already exists"

**Причина:** Попытка добавить существующую колонку

**Решение:**
```php
// Проверка перед добавлением
Schema::table('ads', function (Blueprint $table) {
    if (!Schema::hasColumn('ads', 'new_column')) {
        $table->string('new_column')->nullable();
    }
});
```

---

## 🔄 Inertia.js специфичные ошибки

### 11. ❌ Страница не обновляется после изменений
**Симптомы:** Данные изменились в БД, но не на странице

**Причина:** Inertia кеширует страницы

**Решение:**
```php
// В контроллере после изменений
return redirect()->route('masters.index')
    ->with('success', 'Обновлено'); // Форсирует обновление

// Или в Vue
import { router } from '@inertiajs/vue3'
router.reload({ only: ['masters'] }) // Обновить только часть данных
```

---

### 12. ❌ Shared data не доступны в компоненте
**Симптомы:** undefined при обращении к shared props

**Причина:** Неправильное обращение к shared data

**Решение:**
```vue
<script setup>
import { usePage } from '@inertiajs/vue3'

// ✅ Правильно
const page = usePage()
const user = computed(() => page.props.auth.user)

// ❌ Неправильно
const user = props.auth.user // shared data не в props компонента
</script>
```

---

## 🎨 Tailwind CSS проблемы

### 13. ❌ Стили не применяются в production
**Симптомы:** Классы работают в dev, но не в build

**Причина:** PurgeCSS удалил "неиспользуемые" классы

**Решение:**
```javascript
// tailwind.config.js
module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    './resources/**/*.ts', // ← Добавить все пути
  ],
  // Или safelist для динамических классов
  safelist: [
    'bg-red-500',
    'text-3xl',
    'lg:text-4xl',
  ]
}
```

---

## 🚀 Производительность

### 14. ❌ Большой размер bundle (>1MB)
**Симптомы:** Медленная загрузка, большой app.js

**Причина:** Все компоненты в одном bundle

**Решение:**
```typescript
// Lazy loading компонентов
const HeavyComponent = defineAsyncComponent(() => 
  import('@/entities/heavy/ui/HeavyComponent.vue')
)

// Route-based code splitting
{
  path: '/admin',
  component: () => import('@/pages/admin/AdminDashboard.vue')
}
```

---

### 15. ❌ Memory leak в Vue компонентах
**Симптомы:** Страница тормозит со временем

**Причина:** Event listeners не очищаются

**Решение:**
```typescript
onMounted(() => {
  window.addEventListener('resize', handleResize)
})

// ОБЯЗАТЕЛЬНО очистить
onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
```

---

## 🔐 Безопасность

### 16. ❌ XSS уязвимость через v-html
**Симптомы:** Возможность выполнения скриптов

**Причина:** Использование v-html с пользовательским вводом

**Решение:**
```vue
<!-- ❌ ОПАСНО -->
<div v-html="userContent"></div>

<!-- ✅ БЕЗОПАСНО -->
<div>{{ userContent }}</div>

<!-- ✅ ИЛИ санитизация -->
<div v-html="sanitizeHtml(userContent)"></div>
```

---

## 💡 Полезные команды для отладки

### Очистка кеша (часто помогает)
```bash
# Laravel
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear

# NPM
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Проверка TypeScript
```bash
# Показать все ошибки
npm run type-check

# Следить за изменениями
npx tsc --watch --noEmit
```

### Логирование для отладки
```php
// Laravel
\Log::info('Debug point', [
    'data' => $request->all(),
    'user' => auth()->id()
]);

// Смотреть логи
tail -f storage/logs/laravel.log
```

```typescript
// Vue (только в dev)
if (import.meta.env.DEV) {
  console.log('Debug:', { data, props, computed: computedValue.value })
}
```

---

## 📚 Где искать помощь

1. **Проверить этот файл** - возможно, ошибка уже описана
2. **Поискать в коде** - `grep -r "похожая_ошибка" .`
3. **Проверить логи** - `storage/logs/laravel.log`
4. **Browser DevTools** - Console и Network вкладки
5. **Использовать debug шаблон** - `.claude/templates/debug.yaml`

---

*Документ обновляется по мере обнаружения новых проблем*