# План доработки админ-панели - Просмотр всех объявлений
## Дата: 2025-01-09

### Анализ текущего состояния

#### ✅ Что УЖЕ реализовано:
1. **Backend (ProfileController):**
   - ✅ Метод `moderation()` - для просмотра объявлений на модерации
   - ✅ Метод `approve()` - одобрение объявлений (исправлен сегодня с is_published = true)
   - ✅ Метод `reject()` - отклонение объявлений
   - ✅ Метод `users()` - управление пользователями
   - ✅ Метод `masters()` - управление мастерами
   - ✅ Метод `complaints()` - система жалоб
   - ✅ Проверка `isStaff()` и добавление `isAdmin = true` в данные

2. **Frontend (Dashboard.vue):**
   - ✅ Поддержка `moderationMode`
   - ✅ Поддержка `isAdmin`
   - ✅ Отображение статистики модерации

3. **Сервисы:**
   - ✅ AdModerationService с методами модерации

#### ❌ Что НЕ реализовано:
1. **Главная проблема - нет метода для просмотра ВСЕХ объявлений:**
   - ❌ Метод `allAds()` для показа всех объявлений всех пользователей
   - ❌ Фильтрация по статусам (активные, черновики, архив и т.д.)
   - ❌ Поиск по объявлениям всех пользователей

### 📊 Итог анализа:
План из `Docs/Admin panel 1/` выполнен на **40%**. Отсутствует главная функция - просмотр ВСЕХ объявлений.

---

## План доработки (KISS подход)

### 1. Создание бекапов
```bash
# Бекап контроллера
cp app/Application/Http/Controllers/Profile/ProfileController.php _backup/ProfileController_$(date +%Y%m%d).php

# Бекап роутов
cp routes/web.php _backup/web_$(date +%Y%m%d).php

# Бекап Dashboard
cp resources/js/Pages/Dashboard.vue _backup/Dashboard_$(date +%Y%m%d).vue
```

### 2. Добавление функционала

## Новый метод в ProfileController

**Файл:** `app/Application/Http/Controllers/Profile/ProfileController.php`
**Добавить после метода moderation() (строка ~312):**

```php
/**
 * Просмотр всех объявлений для администратора
 */
public function allAds(Request $request)
{
    // Проверка прав
    abort_if(!auth()->user()->isStaff(), 403);

    $tab = $request->get('tab', 'all');
    $search = $request->get('search');

    // Базовый запрос
    $query = Ad::with(['user']);

    // Фильтрация по вкладкам
    switch ($tab) {
        case 'active':
            $query->where('status', 'active')->where('is_published', true);
            break;
        case 'moderation':
            $query->where('status', 'active')->where('is_published', false)
                  ->orWhere('status', 'pending_moderation');
            break;
        case 'draft':
            $query->where('status', 'draft');
            break;
        case 'rejected':
            $query->where('status', 'rejected');
            break;
        case 'expired':
            $query->where('status', 'expired');
            break;
        case 'archived':
            $query->where('status', 'archived');
            break;
        case 'blocked':
            $query->where('status', 'blocked');
            break;
    }

    // Поиск
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('id', $search)
              ->orWhereHas('user', function($q) use ($search) {
                  $q->where('email', 'like', "%{$search}%");
              });
        });
    }

    // Получаем объявления с пагинацией
    $ads = $query->orderBy('created_at', 'desc')->paginate(20);

    // Преобразуем для отображения (используем существующий формат)
    $profiles = $ads->map(function($ad) {
        // Используем тот же формат что и в index()
        $photos = is_string($ad->photos) ? json_decode($ad->photos, true) : $ad->photos;
        $mainImage = is_array($photos) && count($photos) > 0 ? $photos[0]['url'] ?? '' : '';

        return [
            'id' => $ad->id,
            'title' => $ad->title,
            'status' => $ad->status,
            'is_published' => $ad->is_published,
            'moderation_reason' => $ad->moderation_reason,
            'price_from' => $ad->starting_price ?? 0,
            'photo' => $mainImage,
            'photos_count' => is_array($photos) ? count($photos) : 0,
            'address' => $ad->address ?? '',
            'phone' => $ad->phone,
            'views' => $ad->views_count ?? 0,
            // Добавляем информацию о пользователе
            'user' => [
                'id' => $ad->user->id,
                'email' => $ad->user->email,
                'role' => $ad->user->role
            ]
        ];
    });

    // Статистика по статусам
    $stats = [
        'all' => Ad::count(),
        'active' => Ad::where('status', 'active')->where('is_published', true)->count(),
        'moderation' => Ad::where(function($q) {
            $q->where('status', 'active')->where('is_published', false)
              ->orWhere('status', 'pending_moderation');
        })->count(),
        'draft' => Ad::where('status', 'draft')->count(),
        'rejected' => Ad::where('status', 'rejected')->count(),
        'expired' => Ad::where('status', 'expired')->count(),
        'archived' => Ad::where('status', 'archived')->count(),
        'blocked' => Ad::where('status', 'blocked')->count(),
    ];

    return Inertia::render('Dashboard', [
        'profiles' => $profiles,
        'adminMode' => true,  // Новый флаг для админ-режима
        'activeTab' => $tab,
        'stats' => $stats,
        'title' => 'Управление объявлениями',
        'counts' => $stats,  // Для совместимости с Dashboard
        'pagination' => [
            'total' => $ads->total(),
            'current' => $ads->currentPage(),
            'per_page' => $ads->perPage()
        ]
    ]);
}
```

## Добавить роут

**Файл:** `routes/web.php`
**После существующих роутов модерации (строка ~306):**

```php
// Админ-панель: все объявления
Route::get('/profile/admin/ads', [ProfileController::class, 'allAds'])
    ->name('profile.admin.ads')
    ->middleware(['auth']);
```

## Изменения в Dashboard.vue

**Файл:** `resources/js/Pages/Dashboard.vue`

### 1. Добавить в props интерфейс (строка ~370):
```typescript
adminMode?: boolean
stats?: Record<string, number>
```

### 2. Добавить пункт меню для админов:
Найти блок "🛡️ Администрирование" и после Link для модерации добавить:

```vue
<Link
  href="/profile/admin/ads"
  class="flex items-center justify-between px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md"
  :class="{ 'bg-gray-100': $page.url.includes('/admin/ads') }"
>
  <span>📋 Все объявления</span>
  <span v-if="stats?.all" class="bg-gray-100 text-gray-700 text-xs px-2 py-0.5 rounded-full">
    {{ stats.all }}
  </span>
</Link>
```

### 3. Добавить табы для админ-режима:
В секции computed, после существующих tabs, добавить:

```javascript
// Табы для админ-режима
const adminTabs = computed(() => {
  if (!props.adminMode) return null

  return [
    { id: 'all', label: 'Все', count: props.stats?.all || 0 },
    { id: 'active', label: 'Активные', count: props.stats?.active || 0 },
    { id: 'moderation', label: 'На модерации', count: props.stats?.moderation || 0 },
    { id: 'draft', label: 'Черновики', count: props.stats?.draft || 0 },
    { id: 'rejected', label: 'Отклоненные', count: props.stats?.rejected || 0 },
    { id: 'expired', label: 'Истекшие', count: props.stats?.expired || 0 },
    { id: 'archived', label: 'Архив', count: props.stats?.archived || 0 },
    { id: 'blocked', label: 'Заблокированные', count: props.stats?.blocked || 0 }
  ]
})

// Изменить существующий tabs чтобы использовать adminTabs в админ-режиме:
const tabs = computed(() => {
  if (props.adminMode) return adminTabs.value
  // существующая логика для обычных табов...
})
```

### 4. Показывать информацию о пользователе в ItemCard:
**Файл:** `resources/js/src/entities/ad/ui/ItemCard/ItemCard.vue`

После заголовка объявления добавить:

```vue
<!-- Информация о владельце для админов -->
<div v-if="$page.props.adminMode && item.user" class="text-xs text-gray-500 mt-1">
  <span class="font-medium">Пользователь:</span>
  {{ item.user.email }}
  <span class="text-gray-400">({{ item.user.role }})</span>
</div>
```

---

## Результат внедрения:
- ✅ Админ увидит ВСЕ объявления всех пользователей
- ✅ Сможет фильтровать по 8 статусам
- ✅ Сможет искать по заголовку/описанию/email/ID
- ✅ Увидит статистику по всем статусам
- ✅ Увидит владельца каждого объявления
- ✅ Минимальные изменения кода (KISS принцип)
- ✅ Используем существующие компоненты
- ✅ Следуем архитектуре проекта (DDD, FSD)

## Тестирование:
1. Создать бекапы файлов
2. Внести изменения по плану
3. Очистить кеш: `php artisan optimize:clear`
4. Пересобрать фронт: `npm run build`
5. Зайти под админом
6. Перейти в "Все объявления"
7. Проверить:
   - Отображение всех объявлений
   - Работу фильтров по статусам
   - Поиск по тексту и email
   - Отображение информации о владельцах
   - Статистику по статусам

## Время реализации:
- Backend: 30 минут
- Frontend: 30 минут
- Тестирование: 30 минут
- **Итого: 1.5 часа**