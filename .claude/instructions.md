# 📚 Claude Code Instructions for SPA Platform

## 🎯 Контекстная инженерия для большой кодовой базы (37k+ строк)

### Оптимальная структура промптов

#### 1. Начало работы с задачей
```
Ultrathink, вспомни CLAUDE.md и AI_CONTEXT.md
Задача: [описание]
Контекст: работаем с [указать домен/компонент]
КРИТИЧЕСКИ ВАЖНО: [ключевые ограничения]
```

#### 2. Во время длинного диалога (напоминания)
```
Помни:
- Архитектура: Laravel DDD + Vue FSD
- TypeScript обязателен, no any types
- Проверяй полную цепочку данных
- Не ломай существующий функционал
```

#### 3. Перед финальной реализацией
```
Проверь чек-лист:
✅ Тесты написаны?
✅ Обработка ошибок есть?
✅ TypeScript типы строгие?
✅ Обратная совместимость сохранена?
ВАЖНО: [критическое требование] в конце!
```

## 📋 Чек-листы для типовых задач

### Новый Vue компонент
```typescript
// ЧЕК-ЛИСТ создания компонента:
✅ 1. Проверить, нет ли похожего в entities/ или shared/ui/
✅ 2. Создать в правильной FSD папке:
   - shared/ui/ - переиспользуемый UI
   - entities/[domain]/ui/ - доменный компонент
   - features/ - функциональность
✅ 3. Структура компонента:
   ComponentName/
   ├── ComponentName.vue
   ├── ComponentName.types.ts
   ├── composables/useComponentName.ts
   └── components/ (если есть подкомпоненты)
✅ 4. Обязательные элементы:
   - TypeScript props с interface
   - withDefaults для опциональных props
   - Computed для защиты от null
   - Loading/Error/Empty states
   - Skeleton loader
✅ 5. Проверить мобильную версию (Tailwind sm:, md:, lg:)
```

### Новое поле в форме (критически важно!)
```typescript
// ЧЕК-ЛИСТ добавления поля с v-model:
✅ 1. Добавить в reactive данные:
   const formData = reactive({
     existingField: '',
     newField: [] // новое поле
   })

✅ 2. ОБЯЗАТЕЛЬНО создать watcher:
   watch(() => formData.newField, () => {
     saveFormData() // автосохранение!
   }, { deep: true })

✅ 3. Проверить emit данных:
   emit('update:data', JSON.stringify(formData))

✅ 4. Backend - добавить в $fillable:
   protected $fillable = [..., 'new_field'];

✅ 5. Создать миграцию если нужно:
   $table->json('new_field')->nullable();

✅ 6. ТЕСТ: переключить секции - данные должны сохраниться!
```

### Рефакторинг существующего компонента
```
✅ 1. git branch feature/refactor-component-name
✅ 2. Прочитать текущий код и найти использования
✅ 3. Написать тесты для текущего функционала
✅ 4. Рефакторинг по шагам:
   - TypeScript интерфейсы
   - Логика → composables
   - Добавить error handling
   - Оптимизировать рендеринг
✅ 5. Проверить что тесты проходят
✅ 6. Проверить обратную совместимость
✅ 7. Обновить документацию
```

### Новый домен в Laravel
```php
// ЧЕК-ЛИСТ создания домена:
✅ 1. Создать структуру Domain/[Name]/
   Domain/[Name]/
   ├── Models/
   ├── Services/
   ├── Repositories/
   ├── DTOs/
   ├── Actions/
   └── Events/

✅ 2. Модель с relationships и $fillable
✅ 3. Service с бизнес-логикой (НЕ в контроллере!)
✅ 4. Repository для работы с БД
✅ 5. DTO для передачи данных между слоями
✅ 6. События для междоменного взаимодействия
✅ 7. Контроллер ТОЛЬКО делегирует в сервис
```

## 🔍 Работа с большой кодовой базой

### Перед созданием нового кода
```bash
# 1. Поиск похожего функционала
grep -r "похожий_паттерн" resources/js/src/
find . -name "*похожее_имя*" -type f

# 2. Проверка импортов
grep -r "import.*ComponentName" resources/js/

# 3. Проверка использования
grep -r "использование_метода" app/
```

### Инкрементальный подход
```
Большую задачу разбиваем на этапы:
1. Подготовка (создание структуры)
2. Базовая реализация
3. Добавление валидации
4. Обработка ошибок
5. Оптимизация
6. Тесты
7. Документация

После каждого этапа - коммит и проверка!
```

## 🚀 Оптимизация производительности

### Frontend оптимизация
```vue
<!-- Lazy loading компонентов -->
const HeavyComponent = defineAsyncComponent(() => 
  import('@/entities/heavy/ui/HeavyComponent.vue')
)

<!-- v-memo для тяжелых списков -->
<div v-for="item in list" :key="item.id" v-memo="[item.updated_at]">

<!-- Виртуализация длинных списков -->
import { VirtualList } from '@tanstack/vue-virtual'
```

### Backend оптимизация
```php
// Eager loading для N+1 проблем
$masters = Master::with(['services', 'media', 'reviews'])
    ->withCount('bookings')
    ->get();

// Чанки для больших данных
Master::chunk(100, function ($masters) {
    // обработка
});

// Кеширование
return Cache::remember('masters_list', 3600, function () {
    return Master::active()->get();
});
```

## 🎨 Примеры правильных промптов

### ✅ ХОРОШИЙ промпт
```
Задача: Добавить фильтрацию мастеров по районам
Контекст: компонент MastersFilter в features/masters-filter/
Используй существующий FilterCheckbox из shared/ui/
Данные districts уже есть в props
ВАЖНО: сохрани совместимость с текущими фильтрами!
```

### ❌ ПЛОХОЙ промпт
```
Сделай фильтры для мастеров
```

### ✅ ХОРОШИЙ промпт для отладки
```
Ошибка: данные metro_stations не сохраняются при переключении секций
Компонент: MetroSelector в resources/js/src/shared/ui/molecules/
Проверь:
1. v-model связку
2. Watcher для автосохранения
3. Emit в родительский компонент
4. $fillable в модели Ad
```

## 📊 Метрики и мониторинг

### Что отслеживать
- Bundle size: не более 500kb для главного чанка
- Lighthouse score: минимум 85
- Time to Interactive: < 3 секунды
- Database queries: < 20 на страницу
- Response time: < 200ms для API

### Команды для проверки
```bash
# Размер бандла
npm run build -- --analyze

# TypeScript покрытие
npx typescript-coverage-report

# PHP метрики
php artisan code:analyse

# Производительность
php artisan optimize:clear
php artisan optimize
```

## 🔐 Безопасность

### Обязательные проверки
```php
// Валидация входных данных
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'services' => 'required|array|min:1',
    'services.*' => 'exists:services,id'
]);

// Защита от XSS
{{ e($userInput) }}  // в Blade
v-text="userInput"   // во Vue

// Защита от SQL injection
DB::select('SELECT * FROM users WHERE id = ?', [$id]);
// НЕ делать конкатенацию!
```

## 📝 Финальный чек-лист перед PR

```
✅ Код работает локально
✅ Тесты написаны и проходят
✅ TypeScript ошибок нет
✅ Console.log удалены
✅ Комментарии на русском добавлены
✅ Документация обновлена
✅ Миграции проверены (up/down)
✅ Обратная совместимость сохранена
✅ Performance не ухудшилась
✅ Mobile версия работает
```

## 💡 Полезные алиасы для PowerShell

```powershell
# Добавить в $PROFILE
function spa { cd C:\www.spa.com }
function art { php artisan $args }
function tinker { php artisan tinker }
function fresh { php artisan migrate:fresh --seed }
function test { php artisan test $args }
function npm-clean { 
    Remove-Item -Recurse -Force node_modules
    Remove-Item package-lock.json
    npm install
}
```