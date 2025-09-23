# План реализации недостающего функционала админ-панели
## Дата: 2025-01-09

### Анализ текущего состояния:

#### ✅ Уже реализовано:
- Система жалоб (complaints(), resolveComplaint()) в ProfileController
- Блокировка объявлений (block() в AdModerationService)
- Модерация (approve, reject)
- Просмотр всех объявлений (allAds())
- Снятие с публикации (deactivate)
- Архивирование (archive)

#### ❌ Требуется реализовать:
1. Редактирование чужих объявлений админом
2. Доработка системы жалоб (UI)
3. Массовые действия
4. Логирование действий администратора

---

## 1. РЕДАКТИРОВАНИЕ ЧУЖИХ ОБЪЯВЛЕНИЙ (30 мин)

### Файл: `app/Application/Http/Controllers/Profile/ProfileController.php`

Добавить методы после allAds():

```php
/**
 * Редактирование объявления администратором
 */
public function editAd(Ad $ad)
{
    abort_if(!auth()->user()->isStaff(), 403);

    return Inertia::render('Ad/Edit', [
        'ad' => $ad->load(['user']),
        'adminEdit' => true,
        'returnUrl' => '/profile/admin/ads'
    ]);
}

/**
 * Сохранение изменений администратором
 */
public function updateAd(Request $request, Ad $ad)
{
    abort_if(!auth()->user()->isStaff(), 403);

    // Логируем действие
    Log::info('Admin edited ad', [
        'admin_id' => auth()->id(),
        'ad_id' => $ad->id,
        'changes' => $request->all()
    ]);

    $ad->update($request->validated());

    return redirect()->route('profile.admin.ads')
        ->with('success', 'Объявление обновлено');
}
```

### Файл: `routes/web.php`

После строки с allAds добавить:

```php
// Редактирование объявлений админом
Route::get('/profile/admin/ads/{ad}/edit', [ProfileController::class, 'editAd'])
    ->name('profile.admin.ads.edit');
Route::put('/profile/admin/ads/{ad}', [ProfileController::class, 'updateAd'])
    ->name('profile.admin.ads.update');
```

### Файл: `resources/js/src/entities/ad/ui/ItemCard/ItemCard.vue`

В блок с кнопками админа добавить:

```vue
<!-- После кнопки "Поднять просмотры" -->
<button
  v-if="$page.props.adminMode"
  @click="router.get(`/profile/admin/ads/${item.id}/edit`)"
  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
>
  ✏️ Редактировать
</button>
```

---

## 2. ДОРАБОТКА СИСТЕМЫ ЖАЛОБ (20 мин)

### Файл: `app/Application/Http/Controllers/Profile/ProfileController.php`

В методе allAds(), в блоке формирования $profiles (строка ~375) добавить:

```php
return [
    // ... существующие поля ...
    'has_complaints' => DB::table('complaints')
        ->where('ad_id', $ad->id)
        ->where('status', 'pending')
        ->exists(),
    'complaints_count' => DB::table('complaints')
        ->where('ad_id', $ad->id)
        ->where('status', 'pending')
        ->count(),
];
```

### Файл: `resources/js/src/entities/ad/ui/ItemCard/ItemCard.vue`

В template, после заголовка добавить:

```vue
<!-- Индикатор жалоб -->
<div v-if="item.has_complaints" class="absolute top-2 right-2 z-10">
  <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full animate-pulse">
    ⚠️ Жалобы ({{ item.complaints_count }})
  </span>
</div>

<!-- Кнопка просмотра жалоб в админском блоке -->
<button
  v-if="$page.props.adminMode && item.has_complaints"
  @click="router.get(`/profile/complaints?ad_id=${item.id}`)"
  class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
>
  👁️ Посмотреть жалобы
</button>
```

---

## 3. МАССОВЫЕ ДЕЙСТВИЯ (40 мин)

### Файл: `resources/js/Pages/Dashboard.vue`

В script setup добавить:

```javascript
// Состояние для массовых действий
const selectedAds = ref([])
const selectAll = ref(false)

// Методы массовых действий
const toggleSelectAll = () => {
  if (selectAll.value) {
    selectedAds.value = props.profiles.map(p => p.id)
  } else {
    selectedAds.value = []
  }
}

const bulkAction = (action) => {
  if (!selectedAds.value.length) {
    alert('Выберите объявления')
    return
  }

  if (!confirm(`Выполнить действие "${action}" для ${selectedAds.value.length} объявлений?`)) {
    return
  }

  router.post('/profile/admin/ads/bulk', {
    ids: selectedAds.value,
    action: action
  }, {
    onSuccess: () => {
      selectedAds.value = []
      selectAll.value = false
    }
  })
}
```

В template, перед списком объявлений добавить:

```vue
<!-- Панель массовых действий -->
<div v-if="props.adminMode" class="mb-4">
  <!-- Чекбокс "Выбрать все" -->
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <input
          type="checkbox"
          v-model="selectAll"
          @change="toggleSelectAll"
          class="w-4 h-4 text-blue-600"
        >
        <span class="text-sm text-gray-700">
          Выбрать все ({{ selectedAds.length }} из {{ profiles.length }})
        </span>
      </div>

      <!-- Кнопки массовых действий -->
      <div v-if="selectedAds.length > 0" class="flex gap-2">
        <button
          @click="bulkAction('approve')"
          class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700"
        >
          ✅ Одобрить
        </button>
        <button
          @click="bulkAction('block')"
          class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700"
        >
          🚫 Заблокировать
        </button>
        <button
          @click="bulkAction('archive')"
          class="px-4 py-2 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700"
        >
          📦 В архив
        </button>
        <button
          @click="bulkAction('delete')"
          class="px-4 py-2 bg-red-800 text-white text-sm rounded-lg hover:bg-red-900"
        >
          🗑️ Удалить
        </button>
      </div>
    </div>
  </div>
</div>
```

В ItemCard добавить чекбокс:

```vue
<!-- В начале ItemCard -->
<div v-if="$page.props.adminMode" class="absolute top-4 left-4 z-10">
  <input
    type="checkbox"
    :value="item.id"
    v-model="selectedAds"
    class="w-5 h-5 text-blue-600 rounded"
  >
</div>
```

### Файл: `app/Application/Http/Controllers/Profile/ProfileController.php`

Добавить метод для массовых действий:

```php
/**
 * Массовые действия над объявлениями
 */
public function bulkAction(Request $request)
{
    abort_if(!auth()->user()->isStaff(), 403);

    $request->validate([
        'ids' => 'required|array',
        'ids.*' => 'exists:ads,id',
        'action' => 'required|in:approve,block,archive,delete'
    ]);

    $ads = Ad::whereIn('id', $request->ids)->get();
    $count = 0;

    foreach ($ads as $ad) {
        switch ($request->action) {
            case 'approve':
                if ($this->moderationService->approveAd($ad)) {
                    $count++;
                }
                break;

            case 'block':
                if ($this->moderationService->block($ad, 'Bulk block by admin')) {
                    $count++;
                }
                break;

            case 'archive':
                $ad->update(['status' => 'archived']);
                $count++;
                break;

            case 'delete':
                $ad->delete();
                $count++;
                break;
        }

        // Логируем каждое действие
        Log::info('Admin bulk action', [
            'admin_id' => auth()->id(),
            'ad_id' => $ad->id,
            'action' => $request->action
        ]);
    }

    return back()->with('success', "Выполнено для $count объявлений");
}
```

### Файл: `routes/web.php`

Добавить после роутов админ-панели:

```php
// Массовые действия
Route::post('/profile/admin/ads/bulk', [ProfileController::class, 'bulkAction'])
    ->name('profile.admin.ads.bulk');
```

---

## 4. ЛОГИРОВАНИЕ ДЕЙСТВИЙ АДМИНИСТРАТОРА (20 мин)

### Создать миграцию:

```bash
php artisan make:migration create_admin_actions_table
```

Файл миграции:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users');
            $table->string('action', 50); // approve, reject, block, edit, etc
            $table->string('model_type', 100); // Ad, User, etc
            $table->unsignedBigInteger('model_id');
            $table->json('data')->nullable(); // Дополнительные данные
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
    }
};
```

### Создать модель:

Файл: `app/Domain/Admin/Models/AdminAction.php`

```php
<?php

namespace App\Domain\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domain\User\Models\User;

class AdminAction extends Model
{
    protected $fillable = [
        'admin_id',
        'action',
        'model_type',
        'model_id',
        'data',
        'ip_address'
    ];

    protected $casts = [
        'data' => 'json'
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Получить связанную модель
     */
    public function model()
    {
        return $this->morphTo('model', 'model_type', 'model_id');
    }
}
```

### Создать трейт для логирования:

Файл: `app/Domain/Admin/Traits/LogsAdminActions.php`

```php
<?php

namespace App\Domain\Admin\Traits;

use App\Domain\Admin\Models\AdminAction;

trait LogsAdminActions
{
    /**
     * Логировать действие администратора
     */
    protected function logAdminAction($action, $model, $data = null)
    {
        AdminAction::create([
            'admin_id' => auth()->id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'data' => $data,
            'ip_address' => request()->ip()
        ]);
    }
}
```

### Использовать трейт в ProfileController:

В начале класса добавить:

```php
use App\Domain\Admin\Traits\LogsAdminActions;

class ProfileController extends Controller
{
    use LogsAdminActions;

    // В методе approve добавить:
    public function approve(Ad $ad)
    {
        // ... существующий код ...

        $this->logAdminAction('approve', $ad);

        return back()->with('success', 'Одобрено');
    }

    // В методе reject добавить:
    public function reject(Ad $ad, Request $request)
    {
        // ... существующий код ...

        $this->logAdminAction('reject', $ad, ['reason' => $request->reason]);

        return back()->with('success', 'Отклонено');
    }
}
```

### Создать страницу просмотра логов:

Добавить метод в ProfileController:

```php
/**
 * Просмотр логов администраторов
 */
public function adminLogs(Request $request)
{
    abort_if(!auth()->user()->isAdmin(), 403);

    $logs = AdminAction::with(['admin'])
        ->when($request->admin_id, function($q, $adminId) {
            $q->where('admin_id', $adminId);
        })
        ->when($request->action, function($q, $action) {
            $q->where('action', $action);
        })
        ->orderBy('created_at', 'desc')
        ->paginate(50);

    return Inertia::render('Admin/Logs', [
        'logs' => $logs
    ]);
}
```

---

## Результат внедрения:

- ✅ Админ может редактировать любые объявления
- ✅ Видны объявления с жалобами с индикаторами
- ✅ Реализованы массовые действия с чекбоксами
- ✅ Все действия администратора логируются в БД
- ✅ Есть страница просмотра логов

## Время реализации:
- Редактирование: 30 минут
- Жалобы UI: 20 минут
- Массовые действия: 40 минут
- Логирование: 20 минут
- **Итого: ~2 часа**

## Принципы CLAUDE.md:
- ✅ KISS - используем существующие компоненты
- ✅ DRY - переиспользуем AdModerationService
- ✅ SOLID - каждый метод выполняет одну задачу
- ✅ Минимальные изменения в существующем коде