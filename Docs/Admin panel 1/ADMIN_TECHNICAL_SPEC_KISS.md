# Техническая спецификация административной панели (KISS версия)

## 🎯 Философия: Максимальная простота

Эта спецификация основана на уроках проекта и принципе KISS - используем существующее, не создаем лишнее.

---

## 🏗 Архитектура (УПРОЩЕННАЯ)

### НЕ создаем новую архитектуру!
Используем существующую структуру проекта:

```
СУЩЕСТВУЮЩИЕ ФАЙЛЫ (расширяем):
├── app/Http/Controllers/Profile/ProfileController.php (+150 строк)
├── resources/js/Pages/Dashboard.vue (+80 строк)
├── resources/js/src/entities/ad/ui/ItemCard/ItemCard.vue (+50 строк)
└── routes/web.php (+20 строк)

НОВЫЕ ФАЙЛЫ (минимум):
└── app/Http/Middleware/CheckAdminRole.php (20 строк) [опционально]

Итого базовый: 150 строк кода
Итого с 4 функциями: 320 строк кода
```

---

## 🗄 База данных

### НЕ создаем новые таблицы!

Используем существующие:
- `users.role` - уже есть поле для ролей (admin, moderator, master, client)
- `ads.status` - уже есть статусы (waiting_payment = на модерации)
- `ads.rejection_reason` - уже есть поле для причины отклонения

### Если нужны логи действий (опционально):
```sql
-- Простейшая таблица логов (если критично нужна)
CREATE TABLE admin_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    action VARCHAR(50) NOT NULL,  -- 'approve_ad', 'reject_ad'
    target_id BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id, created_at)
);
```

---

## 🔐 Безопасность (ПРОСТАЯ)

### Проверка прав - один метод:
```php
// app/Http/Middleware/CheckAdminRole.php
class CheckAdminRole
{
    public function handle($request, Closure $next)
    {
        $role = auth()->user()?->role;

        if (!in_array($role, ['admin', 'moderator'])) {
            abort(403, 'Доступ запрещен');
        }

        return $next($request);
    }
}
```

### Использование:
```php
Route::middleware(['auth', CheckAdminRole::class])->group(function () {
    // админские маршруты
});
```

---

## 🤖 Функционал администрирования

### 1. Модерация объявлений:
```php
public function moderation()
{
    abort_if(!in_array(auth()->user()->role, ['admin', 'moderator']), 403);

    $ads = Ad::where('status', 'waiting_payment')
        ->with('user')
        ->paginate(20);

    return Inertia::render('Dashboard', [
        'profiles' => $ads,
        'moderationMode' => true
    ]);
}
```

### 2. Управление пользователями:
```php
public function users(Request $request)
{
    abort_if(auth()->user()->role !== 'admin', 403);

    $users = User::query()
        ->when($request->search, fn($q, $s) =>
            $q->where('email', 'like', "%{$s}%")
              ->orWhere('name', 'like', "%{$s}%")
        )
        ->paginate(20);

    return Inertia::render('Dashboard', [
        'profiles' => $users,
        'userManagementMode' => true
    ]);
}

public function toggleUserStatus(User $user)
{
    abort_if(auth()->user()->role !== 'admin', 403);

    $user->update(['is_blocked' => !$user->is_blocked]);
    return back();
}
```

### 3. Система жалоб:
```php
public function complaints()
{
    abort_if(!in_array(auth()->user()->role, ['admin', 'moderator']), 403);

    // Используем поле rejection_reason для хранения жалоб
    $complaints = Ad::whereNotNull('rejection_reason')
        ->where('status', 'reported')
        ->with(['user'])
        ->paginate(20);

    return Inertia::render('Dashboard', [
        'profiles' => $complaints,
        'complaintsMode' => true
    ]);
}
```

### 4. Управление мастерами:
```php
public function masters()
{
    abort_if(auth()->user()->role !== 'admin', 403);

    $masters = app(\App\Domain\Master\Services\MasterService::class)
        ->getAllMasters()
        ->paginate(20);

    return Inertia::render('Dashboard', [
        'profiles' => $masters,
        'mastersMode' => true
    ]);
}
```

### 5. Модерация отзывов:
```php
public function reviews()
{
    abort_if(!in_array(auth()->user()->role, ['admin', 'moderator']), 403);

    $reviews = app(\App\Domain\Review\Services\ReviewModerationService::class)
        ->getPendingReviews()
        ->paginate(20);

    return Inertia::render('Dashboard', [
        'profiles' => $reviews,
        'reviewsMode' => true
    ]);
}
```

### НЕ делаем (пока):
- ❌ AI проверку
- ❌ Автоматическую модерацию
- ❌ Сложные правила
- ❌ ML модели

---

## ⚡ Оптимизация

### Простое кеширование:
```php
// Кешируем только счетчик (1 строка!)
$pendingCount = Cache::remember('pending_ads', 60, fn() =>
    Ad::where('status', 'waiting_payment')->count()
);
```

### НЕ делаем:
- ❌ WebSocket real-time
- ❌ Сложные очереди
- ❌ Background jobs
- ❌ Микросервисы

---

## 📊 API (РАСШИРЕННЫЙ)

### Базовые endpoint'ы:
```
GET  /profile/moderation               - список объявлений для модерации
POST /profile/moderation/{ad}/approve  - одобрить объявление
POST /profile/moderation/{ad}/reject   - отклонить объявление
```

### Дополнительные endpoint'ы:
```
# Управление пользователями
GET  /profile/users                    - список пользователей
POST /profile/users/{user}/toggle      - блокировать/разблокировать

# Система жалоб
GET  /profile/complaints               - список жалоб
POST /profile/complaints/{ad}/resolve  - обработать жалобу

# Управление мастерами
GET  /profile/masters                  - список мастеров
POST /profile/masters/{master}/verify  - верифицировать мастера

# Модерация отзывов
GET  /profile/reviews                  - список отзывов
POST /profile/reviews/{review}/moderate - модерировать отзыв
```

### НЕ создаем:
- ❌ REST API
- ❌ GraphQL
- ❌ Версионирование API
- ❌ Сложную документацию

---

## 🎨 UI спецификация

### Используем существующие стили:
```css
/* НЕ создаем новые стили! */
/* Используем Tailwind классы из проекта */

.bg-green-600  /* для кнопки одобрить */
.bg-red-600    /* для кнопки отклонить */
.bg-gray-100   /* для фона */
```

### Расширенное админское меню:
```vue
<!-- Админское меню в Dashboard.vue -->
<div v-if="$page.props.auth.user?.role === 'admin' ||
         $page.props.auth.user?.role === 'moderator'"
     class="border-t mt-4 pt-4">
    <div class="px-4 mb-2">
        <span class="text-xs font-semibold text-gray-500 uppercase">
            🛡️ Администрирование
        </span>
    </div>

    <Link href="/profile/moderation" class="menu-item">
        📝 Модерация объявлений
        <span v-if="pendingCount" class="badge">{{ pendingCount }}</span>
    </Link>

    <Link href="/profile/reviews" class="menu-item">
        ⭐ Модерация отзывов
    </Link>

    <Link href="/profile/complaints" class="menu-item">
        ⚠️ Жалобы
    </Link>

    <Link v-if="$page.props.auth.user?.role === 'admin'"
          href="/profile/users" class="menu-item">
        👥 Пользователи
    </Link>

    <Link v-if="$page.props.auth.user?.role === 'admin'"
          href="/profile/masters" class="menu-item">
        💆 Мастера
    </Link>
</div>

<!-- Условные кнопки в ItemCard.vue для разных режимов -->
<div v-if="$page.props.moderationMode" class="flex gap-2 mt-4">
    <button @click="approve" class="btn-green">✅ Одобрить</button>
    <button @click="reject" class="btn-red">❌ Отклонить</button>
</div>

<div v-if="$page.props.userManagementMode" class="flex gap-2 mt-4">
    <button @click="toggleBlock" class="btn-yellow">
        {{ item.is_blocked ? 'Разблокировать' : 'Заблокировать' }}
    </button>
</div>

<div v-if="$page.props.reviewsMode" class="flex gap-2 mt-4">
    <button @click="approveReview" class="btn-green">Одобрить</button>
    <button @click="deleteReview" class="btn-red">Удалить</button>
</div>
```

---

## 🧪 Тестирование (РУЧНОЕ)

### Минимальный чек-лист:
1. Войти админом - видно меню ✓
2. Перейти в модерацию - видно объявления ✓
3. Одобрить - статус меняется ✓
4. Отклонить - статус меняется ✓

### НЕ пишем (пока):
- ❌ Unit тесты
- ❌ E2E тесты
- ❌ Integration тесты
- ❌ Load тесты

---

## 📦 Зависимости

### Используем существующие:
```json
{
  "laravel/framework": "^12.0",  // уже есть
  "inertiajs/inertia": "^1.0",   // уже есть
  "vue": "^3.4",                  // уже есть
  "tailwindcss": "^3.4"          // уже есть
}
```

### НЕ добавляем:
- ❌ Новые пакеты
- ❌ Дополнительные библиотеки
- ❌ Внешние сервисы

---

## 🚀 Deployment

### Простейший деплой:
```bash
# 1. Обновить код
git pull

# 2. Очистить кеш
php artisan cache:clear

# 3. Готово!
```

### НЕ нужно:
- ❌ Сложные миграции
- ❌ Изменения в nginx
- ❌ Новые env переменные
- ❌ Docker настройки

---

## 📊 Метрики успеха

### Метрики базового функционала:
- Админ может модерировать объявления? ДА ✓
- Работает за 3 дня? ДА ✓
- Меньше 200 строк кода? ДА ✓
- Использует существующее? ДА ✓

### Метрики расширенного функционала:
- Управление пользователями? ДА ✓
- Система жалоб? ДА ✓
- Управление мастерами? ДА ✓
- Модерация отзывов? ДА ✓
- Работает за 4 дня? ДА ✓
- Меньше 350 строк кода? ДА ✓

### НЕ измеряем (пока):
- ❌ Скорость модерации
- ❌ Процент автоматизации
- ❌ ROI
- ❌ KPI

---

## 🎯 Сравнение подходов

| Аспект | Старая спецификация | KISS спецификация |
|--------|---------------------|-------------------|
| Новых таблиц БД | 3-5 | 0-1 |
| Новых файлов | 30+ | 0-1 |
| Строк кода (базовый) | 3000+ | <150 |
| Строк кода (+4 функции) | 5000+ | <350 |
| Зависимостей | 5+ новых | 0 новых |
| Время разработки (база) | 20 дней | 3 дня |
| Время разработки (+4 функции) | 30 дней | 4 дня |
| Сложность | Высокая | Минимальная |

---

## ⚠️ Важные уроки из опыта проекта

### ✅ ДЕЛАТЬ:
1. **grep перед созданием** - проверить что уже есть
2. **Расширять существующее** - не создавать новое
3. **Минимум изменений** - меньше кода = меньше багов
4. **Простые решения** - сложное потом, если нужно

### ❌ НЕ ДЕЛАТЬ:
1. **Не создавать AdminController** - используй ProfileController
2. **Не создавать новые компоненты** - используй ItemCard
3. **Не писать сложную логику** - простой if/else достаточно
4. **Не добавлять "на будущее"** - только то, что нужно сейчас

---

## 📝 Полный код для копирования

### Backend (весь код админки с 4 дополнительными функциями):
```php
// app/Http/Controllers/Profile/ProfileController.php

// Добавить в index()
if (in_array(auth()->user()->role, ['admin', 'moderator'])) {
    $data['isAdmin'] = true;
    $data['adminStats'] = Cache::remember('admin_stats', 60, function() {
        return [
            'pending_ads' => Ad::where('status', 'waiting_payment')->count(),
            'pending_reviews' => Review::where('status', 'pending')->count(),
            'reported_ads' => Ad::where('status', 'reported')->count(),
            'total_users' => User::count(),
            'total_masters' => MasterProfile::count(),
        ];
    });
}

// БАЗОВЫЙ ФУНКЦИОНАЛ
public function moderation()
{
    abort_if(!in_array(auth()->user()->role, ['admin', 'moderator']), 403);

    return Inertia::render('Dashboard', [
        'profiles' => Ad::where('status', 'waiting_payment')->paginate(20),
        'moderationMode' => true
    ]);
}

public function approve(Ad $ad)
{
    abort_if(!in_array(auth()->user()->role, ['admin', 'moderator']), 403);
    $ad->update(['status' => 'active']);
    return back();
}

public function reject(Ad $ad, Request $request)
{
    abort_if(!in_array(auth()->user()->role, ['admin', 'moderator']), 403);
    $ad->update(['status' => 'rejected', 'rejection_reason' => $request->reason]);
    return back();
}

// ДОПОЛНИТЕЛЬНЫЙ ФУНКЦИОНАЛ

// 1. Управление пользователями
public function users(Request $request)
{
    abort_if(auth()->user()->role !== 'admin', 403);

    $users = User::query()
        ->when($request->search, fn($q, $s) =>
            $q->where('email', 'like', "%{$s}%")
              ->orWhere('name', 'like', "%{$s}%")
        )
        ->paginate(20);

    return Inertia::render('Dashboard', [
        'profiles' => $users,
        'userManagementMode' => true
    ]);
}

public function toggleUserStatus(User $user)
{
    abort_if(auth()->user()->role !== 'admin', 403);
    $user->update(['is_blocked' => !$user->is_blocked]);
    return back()->with('success', $user->is_blocked ? 'Заблокирован' : 'Разблокирован');
}

// 2. Система жалоб
public function complaints()
{
    abort_if(!in_array(auth()->user()->role, ['admin', 'moderator']), 403);

    $complaints = Ad::whereNotNull('rejection_reason')
        ->where('status', 'reported')
        ->with(['user'])
        ->paginate(20);

    return Inertia::render('Dashboard', [
        'profiles' => $complaints,
        'complaintsMode' => true
    ]);
}

public function resolveComplaint(Ad $ad, Request $request)
{
    abort_if(!in_array(auth()->user()->role, ['admin', 'moderator']), 403);

    $ad->update([
        'status' => $request->action === 'block' ? 'blocked' : 'active',
        'rejection_reason' => null
    ]);

    return back()->with('success', 'Жалоба обработана');
}

// 3. Управление мастерами
public function masters()
{
    abort_if(auth()->user()->role !== 'admin', 403);

    $masters = app(\App\Domain\Master\Services\MasterService::class)
        ->getAllMasters()
        ->paginate(20);

    return Inertia::render('Dashboard', [
        'profiles' => $masters,
        'mastersMode' => true
    ]);
}

public function toggleMasterVerification($masterId)
{
    abort_if(auth()->user()->role !== 'admin', 403);

    $master = MasterProfile::findOrFail($masterId);
    $master->update(['is_verified' => !$master->is_verified]);

    return back()->with('success', 'Статус верификации изменен');
}

// 4. Модерация отзывов
public function reviews()
{
    abort_if(!in_array(auth()->user()->role, ['admin', 'moderator']), 403);

    $reviews = app(\App\Domain\Review\Services\ReviewModerationService::class)
        ->getPendingReviews()
        ->paginate(20);

    return Inertia::render('Dashboard', [
        'profiles' => $reviews,
        'reviewsMode' => true
    ]);
}

public function moderateReview($reviewId, Request $request)
{
    abort_if(!in_array(auth()->user()->role, ['admin', 'moderator']), 403);

    app(\App\Domain\Review\Services\ReviewModerationService::class)
        ->moderate($reviewId, $request->action);

    return back()->with('success', 'Отзыв обработан');
}
```

### Frontend (весь код админки):
```vue
<!-- Dashboard.vue - добавить в меню -->
<div v-if="$page.props.auth.user?.role === 'admin'" class="border-t mt-4 pt-4">
    <Link href="/profile/moderation">
        Модерация ({{ pendingCount }})
    </Link>
</div>

<!-- ItemCard.vue - добавить кнопки -->
<div v-if="$page.props.moderationMode" class="flex gap-2 mt-4">
    <button @click="router.post(`/profile/moderation/${item.id}/approve`)"
            class="bg-green-600 text-white px-4 py-2 rounded">
        ✅ Одобрить
    </button>
    <button @click="router.post(`/profile/moderation/${item.id}/reject`, {reason: 'test'})"
            class="bg-red-600 text-white px-4 py-2 rounded">
        ❌ Отклонить
    </button>
</div>
```

### Routes (все маршруты с дополнительными функциями):
```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    // Базовая модерация объявлений
    Route::get('/profile/moderation', [ProfileController::class, 'moderation']);
    Route::post('/profile/moderation/{ad}/approve', [ProfileController::class, 'approve']);
    Route::post('/profile/moderation/{ad}/reject', [ProfileController::class, 'reject']);

    // Управление пользователями (только admin)
    Route::get('/profile/users', [ProfileController::class, 'users']);
    Route::post('/profile/users/{user}/toggle', [ProfileController::class, 'toggleUserStatus']);

    // Система жалоб
    Route::get('/profile/complaints', [ProfileController::class, 'complaints']);
    Route::post('/profile/complaints/{ad}/resolve', [ProfileController::class, 'resolveComplaint']);

    // Управление мастерами (только admin)
    Route::get('/profile/masters', [ProfileController::class, 'masters']);
    Route::post('/profile/masters/{master}/verify', [ProfileController::class, 'toggleMasterVerification']);

    // Модерация отзывов
    Route::get('/profile/reviews', [ProfileController::class, 'reviews']);
    Route::post('/profile/reviews/{review}/moderate', [ProfileController::class, 'moderateReview']);
});
```

---

## 🎉 Готово!

### Базовая админка:
- **Код:** 150 строк в существующих файлах
- **Время:** 3 дня (12 часов)
- **Результат:** Полностью рабочая модерация объявлений

### Расширенная админка (+4 функции):
- **Код:** 350 строк в существующих файлах
- **Время:** 4 дня (16 часов)
- **Результат:**
  - ✅ Модерация объявлений
  - ✅ Управление пользователями
  - ✅ Система жалоб
  - ✅ Управление мастерами
  - ✅ Модерация отзывов

### Преимущества KISS подхода:
- НЕ создаем новые файлы
- НЕ меняем архитектуру
- НЕ добавляем зависимости
- НЕ усложняем деплой
- Используем то, что УЖЕ работает

---

*Документ создан: 2025-01-22*
*Версия: KISS 1.0*
*Основан на уроках из Docs/LESSONS/*