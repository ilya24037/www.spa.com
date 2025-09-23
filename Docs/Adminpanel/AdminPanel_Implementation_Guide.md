# 🛠️ РУКОВОДСТВО ПО РЕАЛИЗАЦИИ АДМИНИСТРАТИВНОЙ ПАНЕЛИ

## 🎯 ВВЕДЕНИЕ

Данное руководство содержит пошаговые инструкции по реализации каждого компонента административной панели SPA Platform. Следуйте инструкциям последовательно для получения стабильно работающей системы.

---

## 📋 ПОДГОТОВКА К РАЗРАБОТКЕ

### Проверка готовности проекта:
```bash
# 1. Убедитесь, что проект запущен
php artisan serve
npm run dev

# 2. Проверьте доступ к базе данных
php artisan migrate:status

# 3. Убедитесь, что есть пользователь с ролью admin
php artisan tinker
>>> User::where('role', 'admin')->first()
```

### Создание админ пользователя (если нет):
```php
// database/seeders/AdminUserSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@spa.com'],
            [
                'name' => 'Администратор',
                'email' => 'admin@spa.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
        
        User::firstOrCreate(
            ['email' => 'moderator@spa.com'],
            [
                'name' => 'Модератор',
                'email' => 'moderator@spa.com',
                'password' => Hash::make('moderator123'),
                'role' => 'moderator',
                'email_verified_at' => now(),
            ]
        );
    }
}
```

```bash
# Запустить сидер
php artisan db:seed --class=AdminUserSeeder
```

---

## 🏗️ ЭТАП 1: БАЗОВАЯ СТРУКТУРА (ДЕНЬ 1-4)

### День 1: Middleware и роуты

#### 1.1 Создать AdminMiddleware
```bash
php artisan make:middleware AdminMiddleware
```

```php
<?php
// app/Application/Http/Middleware/AdminMiddleware.php

namespace App\Application\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Необходимо войти в систему');
        }
        
        $user = auth()->user();
        
        if (!$user->isAdmin() && !$user->isModerator()) {
            abort(403, 'Доступ к административной панели запрещен');
        }
        
        return $next($request);
    }
}
```

#### 1.2 Зарегистрировать middleware
```php
// bootstrap/app.php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Добавляем админ middleware
        $middleware->alias([
            'admin' => \App\Application\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

#### 1.3 Создать роуты админки
```php
// routes/web.php

// В конец файла добавить:
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Главная страница админки
    Route::get('/', [\App\Application\Http\Controllers\Admin\AdminController::class, 'dashboard'])
         ->name('dashboard');
    
    // Модерация объявлений
    Route::prefix('ads')->name('ads.')->group(function () {
        Route::get('/', [\App\Application\Http\Controllers\Admin\AdModerationController::class, 'index'])
             ->name('index');
        Route::get('/{ad}', [\App\Application\Http\Controllers\Admin\AdModerationController::class, 'show'])
             ->name('show');
        Route::post('/{ad}/approve', [\App\Application\Http\Controllers\Admin\AdModerationController::class, 'approve'])
             ->name('approve');
        Route::post('/{ad}/reject', [\App\Application\Http\Controllers\Admin\AdModerationController::class, 'reject'])
             ->name('reject');
        Route::post('/{ad}/request-revision', [\App\Application\Http\Controllers\Admin\AdModerationController::class, 'requestRevision'])
             ->name('request-revision');
    });
    
    // Управление пользователями
    Route::resource('users', \App\Application\Http\Controllers\Admin\UserManagementController::class)
         ->only(['index', 'show', 'update']);
    Route::post('/users/{user}/block', [\App\Application\Http\Controllers\Admin\UserManagementController::class, 'block'])
         ->name('users.block');
    Route::post('/users/{user}/unblock', [\App\Application\Http\Controllers\Admin\UserManagementController::class, 'unblock'])
         ->name('users.unblock');
    
    // Управление мастерами
    Route::resource('masters', \App\Application\Http\Controllers\Admin\MasterManagementController::class)
         ->only(['index', 'show']);
    Route::post('/masters/{master}/verify', [\App\Application\Http\Controllers\Admin\MasterManagementController::class, 'verify'])
         ->name('masters.verify');
    
    // Модерация отзывов
    Route::resource('reviews', \App\Application\Http\Controllers\Admin\ReviewModerationController::class)
         ->only(['index', 'show', 'update', 'destroy']);
    
    // Жалобы
    Route::resource('complaints', \App\Application\Http\Controllers\Admin\ComplaintController::class)
         ->only(['index', 'show', 'update']);
    
    // Аналитика
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [\App\Application\Http\Controllers\Admin\AnalyticsController::class, 'index'])
             ->name('index');
        Route::get('/export/{type}', [\App\Application\Http\Controllers\Admin\AnalyticsController::class, 'export'])
             ->name('export');
    });
    
    // Настройки
    Route::resource('settings', \App\Application\Http\Controllers\Admin\SystemSettingsController::class)
         ->only(['index', 'store']);
});
```

### День 2: Базовые контроллеры

#### 2.1 Создать AdminController
```bash
php artisan make:controller Admin/AdminController
```

```php
<?php
// app/Application/Http/Controllers/Admin/AdminController.php

namespace App\Application\Http\Controllers\Admin;

use App\Application\Http\Controllers\Controller;
use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use App\Domain\Review\Models\Review;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function dashboard(): Response
    {
        // Получаем статистику для дашборда
        $stats = [
            'pending_ads' => Ad::where('status', 'pending')->count(),
            'pending_reviews' => Review::where('status', 'pending')->count(),
            'total_users' => User::count(),
            'active_masters' => User::where('role', 'master')->where('is_blocked', false)->count(),
            'blocked_users' => User::where('is_blocked', true)->count(),
            'revenue_today' => 0, // TODO: Implement when payment system is ready
            'revenue_month' => 0, // TODO: Implement when payment system is ready
        ];
        
        // Получаем последние объявления на модерации
        $recentAds = Ad::where('status', 'pending')
            ->with(['user:id,name,email', 'photos'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return Inertia::render('Admin/Dashboard', [
            'adminStats' => $stats,
            'recentAds' => $recentAds,
            'pendingCount' => $stats['pending_ads'],
        ]);
    }
}
```

#### 2.2 Создать AdModerationController
```bash
php artisan make:controller Admin/AdModerationController
```

```php
<?php
// app/Application/Http/Controllers/Admin/AdModerationController.php

namespace App\Application\Http\Controllers\Admin;

use App\Application\Http\Controllers\Controller;
use App\Domain\Ad\Models\Ad;
use App\Application\Http\Resources\Ad\AdResource;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdModerationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Ad::with(['user:id,name,email', 'photos']);
        
        // Фильтрация по статусу
        $status = $request->get('status', 'pending');
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        // Фильтрация по приоритету
        if ($request->filled('priority')) {
            $query->where('priority', $request->get('priority'));
        }
        
        // Поиск
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
        
        // Сортировка
        $query->orderBy('priority', 'desc')
              ->orderBy('created_at', 'asc');
        
        $ads = $query->paginate(20);
        
        // Статистика для табов
        $counts = [
            'pending' => Ad::where('status', 'pending')->count(),
            'active' => Ad::where('status', 'active')->count(),
            'rejected' => Ad::where('status', 'rejected')->count(),
            'revision_required' => Ad::where('status', 'revision_required')->count(),
        ];
        
        return Inertia::render('Admin/Ads/Index', [
            'ads' => AdResource::collection($ads),
            'counts' => $counts,
            'filters' => $request->only(['status', 'priority', 'search']),
        ]);
    }
    
    public function show(Ad $ad): Response
    {
        $ad->load(['user.profile', 'photos', 'reviews']);
        
        return Inertia::render('Admin/Ads/Show', [
            'ad' => new AdResource($ad),
        ]);
    }
    
    public function approve(Ad $ad): RedirectResponse
    {
        $ad->update([
            'status' => 'active',
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);
        
        // TODO: Отправить уведомление пользователю
        
        return back()->with('success', 'Объявление одобрено');
    }
    
    public function reject(Request $request, Ad $ad): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);
        
        $ad->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);
        
        // TODO: Отправить уведомление пользователю
        
        return back()->with('success', 'Объявление отклонено');
    }
    
    public function requestRevision(Request $request, Ad $ad): RedirectResponse
    {
        $request->validate([
            'notes' => 'required|string|max:1000',
        ]);
        
        $ad->update([
            'status' => 'revision_required',
            'moderation_notes' => $request->notes,
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);
        
        // TODO: Отправить уведомление пользователю
        
        return back()->with('success', 'Запрошены исправления');
    }
}
```

### День 3: Миграции базы данных

#### 3.1 Создать миграции для новых таблиц
```bash
php artisan make:migration create_admin_actions_table
php artisan make:migration create_complaints_table
php artisan make:migration create_system_settings_table
php artisan make:migration add_admin_fields_to_users_table
php artisan make:migration add_moderation_fields_to_ads_table
```

#### 3.2 Миграция admin_actions
```php
<?php
// database/migrations/xxxx_create_admin_actions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('action', 100);
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->json('details')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['admin_id']);
            $table->index(['target_type', 'target_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
    }
};
```

#### 3.3 Миграция complaints
```php
<?php
// database/migrations/xxxx_create_complaints_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complainant_id')->constrained('users')->onDelete('cascade');
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->string('category', 100);
            $table->text('reason');
            $table->enum('status', ['new', 'in_progress', 'resolved', 'rejected'])->default('new');
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['complainant_id']);
            $table->index(['target_type', 'target_id']);
            $table->index(['status']);
            $table->index(['priority']);
            $table->index(['assigned_to']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
```

#### 3.4 Добавить поля в users
```php
<?php
// database/migrations/xxxx_add_admin_fields_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('blocked_at')->nullable();
            $table->text('blocked_reason')->nullable();
            $table->foreignId('blocked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('last_login_at')->nullable();
            $table->integer('login_count')->default(0);
            
            $table->index(['is_blocked']);
            $table->index(['blocked_at']);
            $table->index(['last_login_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['blocked_by']);
            $table->dropColumn([
                'is_blocked',
                'blocked_at', 
                'blocked_reason',
                'blocked_by',
                'last_login_at',
                'login_count'
            ]);
        });
    }
};
```

#### 3.5 Добавить поля модерации в ads
```php
<?php
// database/migrations/xxxx_add_moderation_fields_to_ads_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->foreignId('moderated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('moderated_at')->nullable();
            $table->text('moderation_notes')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            
            $table->index(['moderated_by']);
            $table->index(['moderated_at']);
            $table->index(['priority']);
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropForeign(['moderated_by']);
            $table->dropColumn([
                'moderated_by',
                'moderated_at',
                'moderation_notes',
                'rejection_reason',
                'priority'
            ]);
        });
    }
};
```

#### 3.6 Запустить миграции
```bash
php artisan migrate
```

### День 4: Создание Vue страниц

#### 4.1 Создать Admin/Dashboard.vue
```vue
<!-- resources/js/Pages/Admin/Dashboard.vue -->
<template>
  <Head title="Административная панель" />
  
  <div class="py-6 lg:py-8">
    <div class="flex gap-6">
      
      <!-- Боковая панель админки -->
      <SidebarWrapper 
        v-model="showSidebar"
        content-class="p-0"
        :show-desktop-header="false"
        :always-visible-desktop="true"
      >
        <!-- Профиль администратора -->
        <div class="p-6 border-b">
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 rounded-full bg-red-600 flex items-center justify-center text-white font-medium text-lg">
              {{ userInitial }}
            </div>
            <div>
              <div class="font-medium text-gray-900">{{ userName }}</div>
              <div class="text-sm text-red-600">👑 {{ userRole }}</div>
            </div>
          </div>
        </div>
        
        <!-- Админское меню -->
        <nav class="flex-1">
          <div class="py-2">
            <!-- Модерация (приоритет) -->
            <div class="px-4">
              <Link 
                href="/admin/ads"
                :class="[
                  'flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors',
                  isModerationRoute ? 'bg-red-50 text-red-700 font-medium' : 'text-gray-700 hover:bg-gray-50'
                ]"
              >
                <span class="flex items-center space-x-2">
                  <ShieldCheckIcon class="w-4 h-4" />
                  <span>Модерация объявлений</span>
                </span>
                <span v-if="pendingCount > 0" class="text-xs bg-red-500 text-white px-2 py-0.5 rounded-full">
                  {{ pendingCount }}
                </span>
              </Link>
            </div>
            
            <!-- Остальные разделы -->
            <div class="px-4 mt-2 space-y-1">
              <Link 
                href="/admin/users"
                class="flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                <UsersIcon class="w-4 h-4" />
                <span>Пользователи</span>
              </Link>
              
              <Link 
                href="/admin/masters"
                class="flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                <UserCheckIcon class="w-4 h-4" />
                <span>Мастера</span>
              </Link>
              
              <Link 
                href="/admin/reviews"
                class="flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                <ChatBubbleLeftIcon class="w-4 h-4" />
                <span>Отзывы</span>
              </Link>
              
              <Link 
                href="/admin/complaints"
                class="flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                <ExclamationTriangleIcon class="w-4 h-4" />
                <span>Жалобы</span>
              </Link>
              
              <Link 
                href="/admin/analytics"
                class="flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                <ChartBarIcon class="w-4 h-4" />
                <span>Аналитика</span>
              </Link>
              
              <Link 
                href="/admin/settings"
                class="flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
              >
                <CogIcon class="w-4 h-4" />
                <span>Настройки</span>
              </Link>
            </div>
          </div>
        </nav>
      </SidebarWrapper>
      
      <!-- Основной контент -->
      <section class="flex-1 space-y-6">
        
        <!-- Заголовок -->
        <div class="mb-6">
          <h1 class="text-2xl font-bold text-gray-900 mb-4">Административная панель</h1>
        </div>
        
        <!-- Статистические карточки -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <AdminStatCard 
            title="На модерации"
            :value="adminStats.pending_ads"
            icon="clock"
            color="yellow"
          />
          <AdminStatCard 
            title="Пользователей"
            :value="adminStats.total_users"
            icon="users"
            color="blue"
          />
          <AdminStatCard 
            title="Активных мастеров"
            :value="adminStats.active_masters"
            icon="user-check"
            color="green"
          />
          <AdminStatCard 
            title="Заблокированных"
            :value="adminStats.blocked_users"
            icon="shield-exclamation"
            color="red"
          />
        </div>
        
        <!-- Основной контент -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Последние объявления на модерации</h2>
            
            <div v-if="recentAds && recentAds.length > 0" class="space-y-4">
              <div 
                v-for="ad in recentAds" 
                :key="ad.id"
                class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <div class="flex items-center space-x-4">
                  <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                    <img 
                      v-if="ad.photos && ad.photos.length > 0"
                      :src="ad.photos[0].url" 
                      :alt="ad.title"
                      class="w-full h-full object-cover rounded-lg"
                    />
                    <span v-else class="text-gray-400 text-xs">Нет фото</span>
                  </div>
                  
                  <div>
                    <h3 class="font-medium text-gray-900">{{ ad.title }}</h3>
                    <p class="text-sm text-gray-500">{{ ad.user.name }} • {{ formatDate(ad.created_at) }}</p>
                  </div>
                </div>
                
                <div class="flex items-center space-x-2">
                  <Link 
                    :href="`/admin/ads/${ad.id}`"
                    class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors"
                  >
                    Подробнее
                  </Link>
                </div>
              </div>
            </div>
            
            <div v-else class="text-center py-8">
              <p class="text-gray-500">Нет объявлений на модерации</p>
            </div>
            
            <div class="mt-6 text-center">
              <Link 
                href="/admin/ads"
                class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors"
              >
                Перейти к модерации
                <ArrowRightIcon class="w-4 h-4 ml-2" />
              </Link>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
  
  <!-- Toast уведомления -->
  <Toast
    v-for="toast in toasts"
    :key="toast.id"
    :message="toast.message"
    :type="toast.type"
    :duration="toast.duration"
    @close="removeToast(toast.id)"
  />
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'

// Heroicons
import {
  ShieldCheckIcon,
  UsersIcon,
  UserCheckIcon,
  ChatBubbleLeftIcon,
  ExclamationTriangleIcon,
  ChartBarIcon,
  CogIcon,
  ArrowRightIcon
} from '@heroicons/vue/24/outline'

// Компоненты
import { SidebarWrapper } from '@/src/shared/ui/layouts/SidebarWrapper'
import { Toast } from '@/src/shared/ui/molecules/Toast'
import AdminStatCard from '@/src/features/admin-panel/ui/AdminStatCard.vue'

// Интерфейсы
interface AdminDashboardProps {
  adminStats: {
    pending_ads: number
    pending_reviews: number
    total_users: number
    active_masters: number
    blocked_users: number
    revenue_today: number
    revenue_month: number
  }
  recentAds: any[]
  pendingCount: number
}

const props = withDefaults(defineProps<AdminDashboardProps>(), {
  adminStats: () => ({
    pending_ads: 0,
    pending_reviews: 0,
    total_users: 0,
    active_masters: 0,
    blocked_users: 0,
    revenue_today: 0,
    revenue_month: 0
  }),
  recentAds: () => [],
  pendingCount: 0
})

// Состояние
const showSidebar = ref(false)
const toasts = ref([])

// Пользователь
const page = usePage()
const user = computed(() => (page.props.auth as any)?.user || {})
const userName = computed(() => user.value.name || 'Администратор')
const userInitial = computed(() => userName.value.charAt(0).toUpperCase())
const userRole = computed(() => {
  switch (user.value.role) {
    case 'admin': return 'Администратор'
    case 'moderator': return 'Модератор'
    default: return 'Пользователь'
  }
})

// Проверка текущего роута
const isModerationRoute = computed(() => {
  return page.url.includes('/admin/ads')
})

// Форматирование даты
const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Toast функции
const addToast = (message: string, type = 'success', duration = 5000) => {
  const id = Date.now()
  toasts.value.push({ id, message, type, duration })
}

const removeToast = (id: number) => {
  toasts.value = toasts.value.filter(toast => toast.id !== id)
}
</script>

<style scoped>
/* Стили специфичные для админки */
.admin-layout {
  min-height: 100vh;
  background-color: #f9fafb;
}

.admin-stat-card {
  transition: transform 0.2s ease-in-out;
}

.admin-stat-card:hover {
  transform: translateY(-2px);
}
</style>
```

#### 4.2 Создать AdminStatCard.vue
```vue
<!-- resources/js/src/features/admin-panel/ui/AdminStatCard.vue -->
<template>
  <div class="bg-white p-4 rounded-lg shadow-sm border admin-stat-card">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">
          {{ title }}
        </p>
        <p class="text-2xl font-bold text-gray-900">
          {{ formattedValue }}
        </p>
      </div>
      <div :class="['p-2 rounded-lg', bgColorClass]">
        <component
          :is="getIconComponent(icon)"
          :class="['w-5 h-5', iconColorClass]"
        />
      </div>
    </div>
    
    <!-- Тренд -->
    <div v-if="trend !== undefined && trend !== null" class="mt-2 flex items-center text-sm">
      <span :class="trend > 0 ? 'text-green-600' : trend < 0 ? 'text-red-600' : 'text-gray-500'">
        {{ trend > 0 ? '+' : '' }}{{ trend }}%
      </span>
      <span class="text-gray-500 ml-1">за месяц</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  ClockIcon,
  UsersIcon,
  UserCheckIcon,
  ShieldExclamationIcon,
  CurrencyDollarIcon
} from '@heroicons/vue/24/outline'

interface Props {
  title: string
  value: string | number
  icon: string
  color: 'yellow' | 'blue' | 'green' | 'red' | 'purple'
  trend?: number
}

const props = defineProps<Props>()

// Форматированное значение
const formattedValue = computed(() => {
  if (typeof props.value === 'number') {
    return props.value.toLocaleString('ru-RU')
  }
  return props.value
})

// Цвета для разных типов
const bgColorClass = computed(() => {
  const colors = {
    yellow: 'bg-yellow-100',
    blue: 'bg-blue-100',
    green: 'bg-green-100',
    red: 'bg-red-100',
    purple: 'bg-purple-100'
  }
  return colors[props.color]
})

const iconColorClass = computed(() => {
  const colors = {
    yellow: 'text-yellow-600',
    blue: 'text-blue-600',
    green: 'text-green-600',
    red: 'text-red-600',
    purple: 'text-purple-600'
  }
  return colors[props.color]
})

const getIconComponent = (iconName: string) => {
  const iconMap = {
    'clock': ClockIcon,
    'users': UsersIcon,
    'user-check': UserCheckIcon,
    'shield-exclamation': ShieldExclamationIcon,
    'currency': CurrencyDollarIcon
  }
  return iconMap[iconName as keyof typeof iconMap] || ClockIcon
}
</script>
```

#### 4.3 Создать структуру папок
```bash
mkdir -p resources/js/src/features/admin-panel/ui
mkdir -p resources/js/src/features/admin-panel/model
mkdir -p resources/js/Pages/Admin/Ads
mkdir -p resources/js/Pages/Admin/Users
mkdir -p resources/js/Pages/Admin/Masters
```

#### 4.4 Тестирование базовой структуры
```bash
# 1. Запустить сервер
php artisan serve

# 2. Запустить frontend
npm run dev

# 3. Войти как админ
# Email: admin@spa.com
# Password: admin123

# 4. Перейти на /admin
# Должна открыться страница админки
```

---

## 🔧 ЭТАП 2: МОДЕРАЦИЯ ОБЪЯВЛЕНИЙ (ДЕНЬ 5-9)

### День 5: Страница модерации объявлений

#### 5.1 Создать Admin/Ads/Index.vue
```vue
<!-- resources/js/Pages/Admin/Ads/Index.vue -->
<template>
  <AdminLayout :admin-stats="adminStats" :pending-count="pendingCount">
    
    <!-- Заголовок -->
    <div class="p-6 border-b">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Модерация объявлений</h1>
        <div class="flex items-center space-x-2">
          <select v-model="selectedPriority" @change="applyFilters" class="rounded-md border-gray-300">
            <option value="">Все приоритеты</option>
            <option value="critical">Критичные</option>
            <option value="high">Высокие</option>
            <option value="normal">Обычные</option>
            <option value="low">Низкие</option>
          </select>
          <input 
            v-model="searchQuery" 
            @input="applyFilters"
            type="text" 
            placeholder="Поиск объявлений..."
            class="rounded-md border-gray-300"
          />
        </div>
      </div>
    </div>
    
    <!-- Табы фильтров -->
    <div class="px-6 pt-6">
      <div class="flex items-center space-x-8 border-b">
        <button
          v-for="status in moderationStatuses"
          :key="status.key"
          :class="[
            'pb-2 text-base font-medium border-b-2 transition-colors',
            activeStatus === status.key 
              ? 'text-gray-900 border-gray-900' 
              : 'text-gray-500 border-transparent hover:text-gray-700'
          ]"
          @click="setActiveStatus(status.key)"
        >
          <span class="flex items-center gap-2">
            {{ status.label }}
            <sup v-if="counts[status.key]" class="text-sm font-normal">{{ counts[status.key] }}</sup>
          </span>
        </button>
      </div>
    </div>
    
    <!-- Список объявлений -->
    <div class="p-6">
      <div v-if="ads.data && ads.data.length > 0" class="space-y-4">
        <ModerationCard
          v-for="ad in ads.data"
          :key="ad.id"
          :ad="ad"
          @approve="handleApprove"
          @reject="handleReject"
          @request-revision="handleRequestRevision"
          @view-details="handleViewDetails"
        />
      </div>
      
      <!-- Пустое состояние -->
      <div v-else class="text-center py-16">
        <div class="max-w-md mx-auto">
          <div class="w-20 h-20 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
            <ShieldCheckIcon class="w-10 h-10 text-gray-400" />
          </div>
          <h3 class="text-xl font-medium text-gray-900 mb-3">{{ getEmptyStateTitle() }}</h3>
          <p class="text-gray-600 leading-relaxed">{{ getEmptyStateDescription() }}</p>
        </div>
      </div>
      
      <!-- Пагинация -->
      <div v-if="ads.data && ads.data.length > 0" class="mt-8">
        <Pagination 
          :links="ads.links"
          :meta="ads.meta"
        />
      </div>
    </div>
    
    <!-- Модальные окна -->
    <RejectModal 
      v-model="showRejectModal"
      :ad="selectedAd"
      @confirm="confirmReject"
    />
    
    <RevisionModal 
      v-model="showRevisionModal"
      :ad="selectedAd"
      @confirm="confirmRevision"
    />
    
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { ShieldCheckIcon } from '@heroicons/vue/24/outline'

// Компоненты
import AdminLayout from '@/src/features/admin-panel/ui/AdminLayout.vue'
import ModerationCard from '@/src/features/admin-panel/ui/ModerationCard.vue'
import RejectModal from '@/src/features/admin-panel/ui/RejectModal.vue'
import RevisionModal from '@/src/features/admin-panel/ui/RevisionModal.vue'
import Pagination from '@/src/shared/ui/molecules/Pagination/Pagination.vue'

// Props
interface Props {
  ads: {
    data: any[]
    links: any[]
    meta: any
  }
  counts: {
    pending: number
    active: number
    rejected: number
    revision_required: number
  }
  filters: {
    status?: string
    priority?: string
    search?: string
  }
  adminStats: any
  pendingCount: number
}

const props = defineProps<Props>()

// Состояние
const activeStatus = ref(props.filters.status || 'pending')
const selectedPriority = ref(props.filters.priority || '')
const searchQuery = ref(props.filters.search || '')
const showRejectModal = ref(false)
const showRevisionModal = ref(false)
const selectedAd = ref(null)

// Статусы модерации
const moderationStatuses = [
  { key: 'pending', label: 'Ожидают' },
  { key: 'revision_required', label: 'На исправлении' },
  { key: 'active', label: 'Одобренные' },
  { key: 'rejected', label: 'Отклоненные' }
]

// Методы
const setActiveStatus = (status: string) => {
  activeStatus.value = status
  applyFilters()
}

const applyFilters = () => {
  router.get('/admin/ads', {
    status: activeStatus.value,
    priority: selectedPriority.value,
    search: searchQuery.value
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

const getEmptyStateTitle = () => {
  const titles = {
    pending: 'Нет объявлений на модерации',
    revision_required: 'Нет объявлений на исправлении',
    active: 'Нет одобренных объявлений',
    rejected: 'Нет отклоненных объявлений'
  }
  return titles[activeStatus.value] || 'Нет объявлений'
}

const getEmptyStateDescription = () => {
  const descriptions = {
    pending: 'Все объявления обработаны. Новые появятся здесь автоматически.',
    revision_required: 'Нет объявлений, ожидающих исправлений от пользователей.',
    active: 'Пока нет одобренных объявлений в системе.',
    rejected: 'Нет отклоненных объявлений.'
  }
  return descriptions[activeStatus.value] || 'Список пуст.'
}

// Обработчики действий
const handleApprove = (ad: any) => {
  router.post(`/admin/ads/${ad.id}/approve`, {}, {
    onSuccess: () => {
      // Toast уведомление будет показано через flash сообщение
    }
  })
}

const handleReject = (ad: any) => {
  selectedAd.value = ad
  showRejectModal.value = true
}

const handleRequestRevision = (ad: any) => {
  selectedAd.value = ad
  showRevisionModal.value = true
}

const handleViewDetails = (ad: any) => {
  router.visit(`/admin/ads/${ad.id}`)
}

const confirmReject = (reason: string) => {
  if (!selectedAd.value) return
  
  router.post(`/admin/ads/${selectedAd.value.id}/reject`, {
    reason: reason
  }, {
    onSuccess: () => {
      showRejectModal.value = false
      selectedAd.value = null
    }
  })
}

const confirmRevision = (notes: string) => {
  if (!selectedAd.value) return
  
  router.post(`/admin/ads/${selectedAd.value.id}/request-revision`, {
    notes: notes
  }, {
    onSuccess: () => {
      showRevisionModal.value = false
      selectedAd.value = null
    }
  })
}
</script>
```

#### 5.2 Создать ModerationCard.vue
```vue
<!-- resources/js/src/features/admin-panel/ui/ModerationCard.vue -->
<template>
  <div class="moderation-card bg-white border border-gray-200 rounded-lg hover:shadow-lg transition-shadow">
    <div class="p-4">
      <!-- Заголовок карточки -->
      <div class="flex items-start justify-between mb-4">
        <div class="flex items-center space-x-2">
          <span :class="['px-2 py-1 text-xs font-medium rounded-full', priorityBadgeClass]">
            {{ priorityText }}
          </span>
          <span class="text-sm text-gray-500">{{ timeAgo }}</span>
        </div>
        <div class="text-sm text-gray-500">
          ID: {{ ad.id }}
        </div>
      </div>
      
      <!-- Основной контент -->
      <div class="flex space-x-4">
        <!-- Изображение -->
        <div class="w-24 h-24 bg-gray-200 rounded-lg flex-shrink-0 overflow-hidden">
          <img 
            v-if="ad.photos && ad.photos.length > 0"
            :src="ad.photos[0].url" 
            :alt="ad.title"
            class="w-full h-full object-cover"
          />
          <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs">
            Нет фото
          </div>
        </div>
        
        <!-- Информация -->
        <div class="flex-1 min-w-0">
          <h3 class="font-medium text-gray-900 mb-2 truncate">{{ ad.title }}</h3>
          
          <div class="space-y-1 text-sm text-gray-600">
            <div class="flex items-center space-x-4">
              <span>👤 {{ ad.user.name }}</span>
              <span>📧 {{ ad.user.email }}</span>
            </div>
            
            <div v-if="ad.price" class="flex items-center space-x-4">
              <span>💰 от {{ formatPrice(ad.price) }} ₽</span>
            </div>
            
            <div class="flex items-center space-x-4">
              <span>📅 {{ formatDate(ad.created_at) }}</span>
              <span v-if="ad.moderated_at">🔍 {{ formatDate(ad.moderated_at) }}</span>
            </div>
          </div>
          
          <!-- Причина отклонения или заметки -->
          <div v-if="ad.rejection_reason" class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-800">
            <strong>Причина отклонения:</strong> {{ ad.rejection_reason }}
          </div>
          
          <div v-if="ad.moderation_notes" class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
            <strong>Заметки модератора:</strong> {{ ad.moderation_notes }}
          </div>
        </div>
      </div>
      
      <!-- Действия -->
      <div class="mt-4 flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <button
            v-if="canApprove"
            @click="$emit('approve', ad)"
            class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition-colors"
          >
            ✅ Одобрить
          </button>
          
          <button
            v-if="canReject"
            @click="$emit('reject', ad)"
            class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded-md hover:bg-red-200 transition-colors"
          >
            ❌ Отклонить
          </button>
          
          <button
            v-if="canRequestRevision"
            @click="$emit('request-revision', ad)"
            class="px-3 py-1 text-sm bg-yellow-100 text-yellow-700 rounded-md hover:bg-yellow-200 transition-colors"
          >
            🔄 На исправление
          </button>
        </div>
        
        <div class="flex items-center space-x-2">
          <button
            @click="$emit('view-details', ad)"
            class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors"
          >
            👁️ Подробнее
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  ad: {
    id: number
    title: string
    status: string
    priority: string
    created_at: string
    moderated_at?: string
    rejection_reason?: string
    moderation_notes?: string
    price?: number
    user: {
      name: string
      email: string
    }
    photos?: Array<{
      url: string
    }>
  }
}

const props = defineProps<Props>()

defineEmits<{
  'approve': [ad: any]
  'reject': [ad: any]
  'request-revision': [ad: any]
  'view-details': [ad: any]
}>()

// Computed properties
const priorityText = computed(() => {
  const priorities = {
    critical: 'Критично',
    high: 'Высокий',
    normal: 'Обычный',
    low: 'Низкий'
  }
  return priorities[props.ad.priority] || 'Обычный'
})

const priorityBadgeClass = computed(() => {
  const classes = {
    critical: 'bg-red-100 text-red-800',
    high: 'bg-orange-100 text-orange-800',
    normal: 'bg-blue-100 text-blue-800',
    low: 'bg-gray-100 text-gray-800'
  }
  return classes[props.ad.priority] || 'bg-gray-100 text-gray-800'
})

const timeAgo = computed(() => {
  const now = new Date()
  const created = new Date(props.ad.created_at)
  const diffInHours = Math.floor((now.getTime() - created.getTime()) / (1000 * 60 * 60))
  
  if (diffInHours < 1) return 'Только что'
  if (diffInHours < 24) return `${diffInHours} ч. назад`
  
  const diffInDays = Math.floor(diffInHours / 24)
  if (diffInDays < 7) return `${diffInDays} дн. назад`
  
  return formatDate(props.ad.created_at)
})

const canApprove = computed(() => {
  return props.ad.status === 'pending' || props.ad.status === 'revision_required'
})

const canReject = computed(() => {
  return props.ad.status === 'pending' || props.ad.status === 'revision_required'
})

const canRequestRevision = computed(() => {
  return props.ad.status === 'pending'
})

// Методы
const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatPrice = (price: number) => {
  return price.toLocaleString('ru-RU')
}
</script>

<style scoped>
.moderation-card {
  transition: transform 0.2s ease-in-out;
}

.moderation-card:hover {
  transform: translateY(-1px);
}
</style>
```

### День 6-9: Модальные окна и дополнительная функциональность

Аналогично создаются остальные компоненты (RejectModal.vue, RevisionModal.vue, AdminLayout.vue) и функциональность.

---

## 🧪 ТЕСТИРОВАНИЕ И ОТЛАДКА

### Проверочный чеклист для каждого этапа:

#### Этап 1 - Базовая структура:
- [ ] Админ может войти в систему
- [ ] Открывается страница /admin
- [ ] Отображается меню и статистика
- [ ] Работает навигация между разделами
- [ ] Middleware блокирует обычных пользователей

#### Этап 2 - Модерация объявлений:
- [ ] Отображается список объявлений на модерации
- [ ] Работают фильтры и поиск
- [ ] Можно одобрить объявление
- [ ] Можно отклонить с указанием причины
- [ ] Можно запросить исправления
- [ ] Обновляется статистика после действий

### Команды для тестирования:
```bash
# Создать тестовые данные
php artisan db:seed --class=AdminTestDataSeeder

# Запустить тесты
php artisan test --filter=AdminTest

# Проверить логи
tail -f storage/logs/laravel.log

# Очистить кеш если что-то не работает
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## 📚 ДОПОЛНИТЕЛЬНЫЕ РЕСУРСЫ

### Полезные команды Artisan:
```bash
# Создание компонентов
php artisan make:controller Admin/ComponentController
php artisan make:request Admin/ComponentRequest
php artisan make:resource Admin/ComponentResource
php artisan make:middleware ComponentMiddleware
php artisan make:model ComponentModel -m

# Работа с базой данных
php artisan migrate:fresh --seed
php artisan migrate:rollback --step=1
php artisan db:seed --class=SpecificSeeder

# Отладка
php artisan route:list --path=admin
php artisan tinker
```

### Структура файлов для справки:
```
app/
├── Application/Http/Controllers/Admin/
├── Domain/Admin/
│   ├── Models/
│   ├── Services/
│   └── DTOs/
resources/js/
├── Pages/Admin/
├── src/features/admin-panel/
└── src/shared/ui/
```

---

Это руководство обеспечивает пошаговую реализацию административной панели с детальными инструкциями и примерами кода для каждого этапа.
