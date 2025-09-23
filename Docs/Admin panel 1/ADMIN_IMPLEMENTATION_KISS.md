# 🚀 Быстрая реализация админ-панели (принцип KISS)

## За 3 дня вместо 20!

Это упрощенная инструкция на основе уроков проекта. Делаем ТОЛЬКО необходимое.

---

## 📋 День 1: Backend (4 часа)

### Шаг 1.1: Проверяем что уже есть (30 мин)
```bash
# Выполни эти команды ПЕРЕД любыми изменениями!
grep -r "UserRole::ADMIN" app/
grep -r "AdModerationService" app/
grep -r "role.*admin" app/Http/

# Нашли? Отлично, используем!
```

### Шаг 1.2: Добавляем в ProfileController (30 мин)
```php
// app/Http/Controllers/Profile/ProfileController.php
// ДОБАВИТЬ в метод index() после $data = [...];

if (in_array(auth()->user()->role, ['admin', 'moderator'])) {
    $data['isAdmin'] = true;
    $data['pendingCount'] = Ad::where('status', 'waiting_payment')->count();
}
```

### Шаг 1.3: Один метод для модерации (1 час)
```php
// app/Http/Controllers/Profile/ProfileController.php
// ДОБАВИТЬ новый метод

public function moderation()
{
    abort_if(!in_array(auth()->user()->role, ['admin', 'moderator']), 403);

    $ads = Ad::where('status', 'waiting_payment')
        ->with(['user', 'content'])
        ->paginate(20);

    return Inertia::render('Dashboard', [
        'profiles' => $ads,
        'moderationMode' => true
    ]);
}
```

### Шаг 1.4: Методы approve/reject (1 час)
```php
// app/Http/Controllers/Profile/ProfileController.php
// ДОБАВИТЬ методы

public function approve(Ad $ad)
{
    abort_if(!in_array(auth()->user()->role, ['admin', 'moderator']), 403);

    $ad->update(['status' => 'active']);

    return back()->with('success', 'Одобрено');
}

public function reject(Ad $ad, Request $request)
{
    abort_if(!in_array(auth()->user()->role, ['admin', 'moderator']), 403);

    $ad->update([
        'status' => 'rejected',
        'rejection_reason' => $request->reason
    ]);

    return back()->with('success', 'Отклонено');
}
```

### Шаг 1.5: Маршруты (30 мин)
```php
// routes/web.php
// ДОБАВИТЬ в группу middleware(['auth'])

Route::get('/profile/moderation', [ProfileController::class, 'moderation']);
Route::post('/profile/moderation/{ad}/approve', [ProfileController::class, 'approve']);
Route::post('/profile/moderation/{ad}/reject', [ProfileController::class, 'reject']);
```

### Шаг 1.6: Проверка backend (30 мин)
```bash
# Тестируем через tinker
php artisan tinker

// Делаем пользователя админом
User::where('email', 'your@email.com')->update(['role' => 'admin']);

// Проверяем
User::where('role', 'admin')->count(); // должно быть > 0
```

---

## 📋 День 2: Frontend (4 часа)

### Шаг 2.1: Добавляем админ-меню в Dashboard.vue (1 час)

```vue
<!-- resources/js/Pages/Dashboard.vue -->
<!-- НАЙТИ блок с меню (около строки 50-130) -->
<!-- ДОБАВИТЬ после основного меню перед </nav> -->

<!-- Админское меню -->
<div v-if="$page.props.auth.user?.role === 'admin' ||
         $page.props.auth.user?.role === 'moderator'"
     class="border-t mt-4 pt-4">
    <div class="px-4 mb-2">
        <span class="text-xs font-semibold text-gray-500 uppercase">
            🛡️ Администрирование
        </span>
    </div>

    <Link href="/profile/moderation"
          class="flex items-center justify-between px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
        <span>Модерация объявлений</span>
        <span v-if="pendingCount" class="bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full">
            {{ pendingCount }}
        </span>
    </Link>
</div>
```

### Шаг 2.2: Добавляем кнопки модерации в ItemCard (2 часа)

```vue
<!-- resources/js/src/entities/ad/ui/ItemCard/ItemCard.vue -->
<!-- НАЙТИ блок с кнопками действий (ищи @click="handleEdit") -->
<!-- ДОБАВИТЬ после существующих кнопок -->

<!-- Кнопки модерации -->
<div v-if="$page.props.moderationMode" class="flex gap-2 mt-4 pt-4 border-t">
    <button @click="approveAd"
            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
        ✅ Одобрить
    </button>
    <button @click="showRejectDialog = true"
            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
        ❌ Отклонить
    </button>
</div>

<!-- Диалог отклонения (добавить перед </template>) -->
<div v-if="showRejectDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg max-w-md w-full">
        <h3 class="text-lg font-semibold mb-4">Причина отклонения</h3>
        <textarea v-model="rejectReason"
                  class="w-full p-2 border rounded"
                  rows="3"
                  placeholder="Укажите причину..."></textarea>
        <div class="flex gap-2 mt-4">
            <button @click="rejectAd" class="flex-1 px-4 py-2 bg-red-600 text-white rounded">
                Отклонить
            </button>
            <button @click="showRejectDialog = false" class="flex-1 px-4 py-2 bg-gray-200 rounded">
                Отмена
            </button>
        </div>
    </div>
</div>
```

### Шаг 2.3: Добавляем методы в script ItemCard (1 час)

```javascript
// resources/js/src/entities/ad/ui/ItemCard/ItemCard.vue
// ДОБАВИТЬ в <script setup> после других ref

const showRejectDialog = ref(false)
const rejectReason = ref('')

const approveAd = () => {
    router.post(`/profile/moderation/${props.item.id}/approve`, {}, {
        onSuccess: () => {
            // Объявление исчезнет после перезагрузки страницы
        }
    })
}

const rejectAd = () => {
    router.post(`/profile/moderation/${props.item.id}/reject`, {
        reason: rejectReason.value
    }, {
        onSuccess: () => {
            showRejectDialog.value = false
            rejectReason.value = ''
        }
    })
}
```

---

## 📋 День 3: Дополнительные функции (4 часа)

### Шаг 3.1: Управление пользователями (1 час)
```php
// app/Http/Controllers/Profile/ProfileController.php
// ДОБАВИТЬ новый метод

public function users(Request $request)
{
    abort_if(!in_array(auth()->user()->role, ['admin']), 403);

    $users = User::query()
        ->when($request->search, function($q, $search) {
            $q->where('email', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%");
        })
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    return Inertia::render('Dashboard', [
        'profiles' => $users,
        'userManagementMode' => true
    ]);
}

public function toggleUserStatus(User $user)
{
    abort_if(!in_array(auth()->user()->role, ['admin']), 403);

    $user->update([
        'is_blocked' => !$user->is_blocked
    ]);

    return back()->with('success', $user->is_blocked ? 'Заблокирован' : 'Разблокирован');
}
```

### Шаг 3.2: Система жалоб (1 час)
```php
// app/Http/Controllers/Profile/ProfileController.php
// ДОБАВИТЬ методы

public function complaints()
{
    abort_if(!in_array(auth()->user()->role, ['admin', 'moderator']), 403);

    // Используем поле rejection_reason в ads для хранения жалоб
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
```

### Шаг 3.3: Управление мастерами (30 мин)
```php
// app/Http/Controllers/Profile/ProfileController.php
// ДОБАВИТЬ метод

public function masters()
{
    abort_if(!in_array(auth()->user()->role, ['admin']), 403);

    // Используем существующий MasterService!
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
    abort_if(!in_array(auth()->user()->role, ['admin']), 403);

    $master = MasterProfile::findOrFail($masterId);
    $master->update(['is_verified' => !$master->is_verified]);

    return back()->with('success', 'Статус верификации изменен');
}
```

### Шаг 3.4: Модерация отзывов (30 мин)
```php
// app/Http/Controllers/Profile/ProfileController.php
// ДОБАВИТЬ метод

public function reviews()
{
    abort_if(!in_array(auth()->user()->role, ['admin', 'moderator']), 403);

    // Используем существующий ReviewModerationService!
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

### Шаг 3.5: Обновление меню в Dashboard.vue (30 мин)
```vue
<!-- resources/js/Pages/Dashboard.vue -->
<!-- ЗАМЕНИТЬ админское меню на расширенное -->

<!-- Админское меню -->
<div v-if="$page.props.auth.user?.role === 'admin' ||
         $page.props.auth.user?.role === 'moderator'"
     class="border-t mt-4 pt-4">
    <div class="px-4 mb-2">
        <span class="text-xs font-semibold text-gray-500 uppercase">
            🛡️ Администрирование
        </span>
    </div>

    <Link href="/profile/moderation"
          class="flex items-center justify-between px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
        <span>📝 Модерация объявлений</span>
        <span v-if="pendingCount" class="bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full">
            {{ pendingCount }}
        </span>
    </Link>

    <Link href="/profile/reviews"
          class="flex items-center justify-between px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
        <span>⭐ Модерация отзывов</span>
    </Link>

    <Link href="/profile/complaints"
          class="flex items-center justify-between px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
        <span>⚠️ Жалобы</span>
    </Link>

    <Link v-if="$page.props.auth.user?.role === 'admin'"
          href="/profile/users"
          class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
        <span>👥 Пользователи</span>
    </Link>

    <Link v-if="$page.props.auth.user?.role === 'admin'"
          href="/profile/masters"
          class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
        <span>💆 Мастера</span>
    </Link>
</div>
```

### Шаг 3.6: Добавление маршрутов (10 мин)
```php
// routes/web.php
// ДОБАВИТЬ в группу middleware(['auth'])

Route::get('/profile/users', [ProfileController::class, 'users']);
Route::post('/profile/users/{user}/toggle', [ProfileController::class, 'toggleUserStatus']);

Route::get('/profile/complaints', [ProfileController::class, 'complaints']);
Route::post('/profile/complaints/{ad}/resolve', [ProfileController::class, 'resolveComplaint']);

Route::get('/profile/masters', [ProfileController::class, 'masters']);
Route::post('/profile/masters/{master}/verify', [ProfileController::class, 'toggleMasterVerification']);

Route::get('/profile/reviews', [ProfileController::class, 'reviews']);
Route::post('/profile/reviews/{review}/moderate', [ProfileController::class, 'moderateReview']);
```

## 📋 День 4: Тестирование и доработки (4 часа)

### Шаг 3.1: Создаем тестовые данные (30 мин)
```bash
php artisan tinker

// Создаем объявления для модерации
for($i = 0; $i < 10; $i++) {
    Ad::factory()->create(['status' => 'waiting_payment']);
}
```

### Шаг 3.2: Тестируем функционал (1 час)
1. Войти под админом
2. Увидеть админское меню ✓
3. Перейти в модерацию ✓
4. Одобрить объявление ✓
5. Отклонить с причиной ✓
6. Проверить изменение статуса в БД ✓

### Шаг 3.3: Добавляем статистику (опционально, 1.5 часа)
```vue
<!-- resources/js/Pages/Dashboard.vue -->
<!-- ДОБАВИТЬ в начало основного контента если moderationMode -->

<div v-if="moderationMode" class="bg-white rounded-lg p-6 mb-6">
    <h2 class="text-xl font-semibold mb-4">Статистика модерации</h2>
    <div class="grid grid-cols-4 gap-4">
        <div class="text-center">
            <div class="text-2xl font-bold text-orange-600">{{ profiles.total }}</div>
            <div class="text-sm text-gray-600">Ожидают</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-green-600">{{ todayApproved || 0 }}</div>
            <div class="text-sm text-gray-600">Одобрено сегодня</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-red-600">{{ todayRejected || 0 }}</div>
            <div class="text-sm text-gray-600">Отклонено сегодня</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-blue-600">1.5 мин</div>
            <div class="text-sm text-gray-600">Среднее время</div>
        </div>
    </div>
</div>
```

### Шаг 3.4: Горячие клавиши (опционально, 1 час)
```javascript
// resources/js/Pages/Dashboard.vue
// ДОБАВИТЬ в onMounted()

if (moderationMode) {
    document.addEventListener('keydown', (e) => {
        if (e.key === 'a' && !e.ctrlKey) {
            // Одобрить первое объявление
            document.querySelector('[data-approve-btn]')?.click()
        } else if (e.key === 'r' && !e.ctrlKey) {
            // Отклонить первое объявление
            document.querySelector('[data-reject-btn]')?.click()
        }
    })
}
```

---

## ✅ Проверка готовности

### Минимальный функционал:
- [ ] Админ видит пункт "Администрирование" в меню
- [ ] Переход на /profile/moderation работает
- [ ] Отображаются объявления со статусом waiting_payment
- [ ] Кнопка "Одобрить" меняет статус на active
- [ ] Кнопка "Отклонить" меняет статус на rejected
- [ ] После действия объявление исчезает из списка

### Что НЕ делаем (экономим время):
- ❌ Отдельную страницу для админки
- ❌ Новые компоненты
- ❌ Сложную систему прав
- ❌ WebSocket обновления
- ❌ Графики и аналитику (пока)

---

## 🎯 Результат

**За 3 дня получаем:**
- Рабочую модерацию объявлений
- Минимум кода (< 150 строк)
- Использование готовых компонентов
- Легко расширяемую систему

**Сравнение:**
- Старый план: 20 дней, 30+ файлов, 3000+ строк
- Новый план: 3 дня, 0 новых файлов, 150 строк

---

## 🚨 Частые ошибки и решения

### Ошибка: "403 Forbidden при переходе в модерацию"
**Решение:** Проверить роль пользователя в БД:
```sql
SELECT role FROM users WHERE email = 'your@email.com';
-- Должно быть 'admin' или 'moderator'
```

### Ошибка: "Не видно админского меню"
**Решение:** Проверить в Vue DevTools значение:
```javascript
$page.props.auth.user.role // должно быть 'admin'
```

### Ошибка: "Кнопки модерации не появляются"
**Решение:** Проверить передачу `moderationMode`:
```php
// В методе moderation() контроллера
return Inertia::render('Dashboard', [
    'profiles' => $ads,
    'moderationMode' => true // ← обязательно!
]);
```

---

## 💡 Дальнейшее развитие

После запуска базового функционала можно добавить:
1. Модерацию отзывов (копировать подход с объявлениями)
2. Управление пользователями (список + блокировка)
3. Простую аналитику (счетчики и графики)
4. Массовые операции (выбор чекбоксами)

Но это уже после того, как базовый функционал работает!

---

*Документ создан: 2025-01-22*
*Основан на принципах KISS и уроках из LESSONS*