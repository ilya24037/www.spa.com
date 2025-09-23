# 🔧 ТЕХНИЧЕСКАЯ СПЕЦИФИКАЦИЯ АДМИНИСТРАТИВНОЙ ПАНЕЛИ

## 📋 ОБЩИЕ ТРЕБОВАНИЯ

### Архитектурные принципы:
- **DDD (Domain-Driven Design)** - следуем существующей архитектуре проекта
- **Feature-Sliced Design** - модульная структура frontend
- **SOLID принципы** - чистый и поддерживаемый код
- **Repository Pattern** - абстракция работы с данными

### Технологический стек:
- **Backend**: Laravel 12, PHP 8.2+, MySQL 8.0, Redis
- **Frontend**: Vue 3, TypeScript, Inertia.js, Tailwind CSS, Pinia
- **Инфраструктура**: Docker, Nginx, Git, GitHub Actions

---

## 🗄️ БАЗА ДАННЫХ

### Новые таблицы:

#### admin_actions
```sql
CREATE TABLE admin_actions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    admin_id BIGINT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(255) NOT NULL,
    target_id BIGINT NOT NULL,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_admin_id (admin_id),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### complaints
```sql
CREATE TABLE complaints (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    complainant_id BIGINT NOT NULL,
    target_type VARCHAR(255) NOT NULL,
    target_id BIGINT NOT NULL,
    category VARCHAR(100) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('new', 'in_progress', 'resolved', 'rejected') DEFAULT 'new',
    priority ENUM('low', 'normal', 'high', 'critical') DEFAULT 'normal',
    assigned_to BIGINT NULL,
    resolution TEXT NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_complainant_id (complainant_id),
    INDEX idx_target (target_type, target_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (complainant_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);
```

#### moderation_queue
```sql
CREATE TABLE moderation_queue (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    item_type VARCHAR(255) NOT NULL,
    item_id BIGINT NOT NULL,
    priority ENUM('low', 'normal', 'high', 'critical') DEFAULT 'normal',
    status ENUM('pending', 'in_progress', 'completed', 'skipped') DEFAULT 'pending',
    assigned_to BIGINT NULL,
    sla_deadline TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_item (item_type, item_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_sla_deadline (sla_deadline),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);
```

#### system_settings
```sql
CREATE TABLE system_settings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(255) UNIQUE NOT NULL,
    value TEXT NOT NULL,
    type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    category VARCHAR(100) DEFAULT 'general',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_category (category),
    INDEX idx_is_public (is_public)
);
```

### Изменения в существующих таблицах:

#### users (добавить поля)
```sql
ALTER TABLE users ADD COLUMN is_blocked BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN blocked_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN blocked_reason TEXT NULL;
ALTER TABLE users ADD COLUMN blocked_by BIGINT NULL;
ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN login_count INT DEFAULT 0;

ALTER TABLE users ADD INDEX idx_is_blocked (is_blocked);
ALTER TABLE users ADD INDEX idx_blocked_at (blocked_at);
ALTER TABLE users ADD INDEX idx_last_login_at (last_login_at);

ALTER TABLE users ADD FOREIGN KEY (blocked_by) REFERENCES users(id) ON DELETE SET NULL;
```

#### ads (добавить поля для модерации)
```sql
ALTER TABLE ads ADD COLUMN moderated_by BIGINT NULL;
ALTER TABLE ads ADD COLUMN moderated_at TIMESTAMP NULL;
ALTER TABLE ads ADD COLUMN moderation_notes TEXT NULL;
ALTER TABLE ads ADD COLUMN rejection_reason VARCHAR(255) NULL;
ALTER TABLE ads ADD COLUMN priority ENUM('low', 'normal', 'high', 'critical') DEFAULT 'normal';

ALTER TABLE ads ADD INDEX idx_moderated_by (moderated_by);
ALTER TABLE ads ADD INDEX idx_moderated_at (moderated_at);
ALTER TABLE ads ADD INDEX idx_priority (priority);

ALTER TABLE ads ADD FOREIGN KEY (moderated_by) REFERENCES users(id) ON DELETE SET NULL;
```

---

## 🔗 API ENDPOINTS

### Авторизация и доступ
```
GET    /admin                           # Главная страница админки
POST   /admin/login                     # Вход в админку (если отдельный)
POST   /admin/logout                    # Выход из админки
```

### Модерация объявлений
```
GET    /admin/ads                       # Список объявлений на модерации
GET    /admin/ads/{id}                  # Детали объявления
POST   /admin/ads/{id}/approve          # Одобрить объявление
POST   /admin/ads/{id}/reject           # Отклонить объявление
POST   /admin/ads/{id}/request-revision # Запросить исправления
POST   /admin/ads/mass-action           # Массовые действия
GET    /admin/ads/export                # Экспорт списка объявлений
```

### Управление пользователями
```
GET    /admin/users                     # Список пользователей
GET    /admin/users/{id}                # Профиль пользователя
POST   /admin/users/{id}/block          # Заблокировать пользователя
POST   /admin/users/{id}/unblock        # Разблокировать пользователя
POST   /admin/users/{id}/change-role    # Изменить роль пользователя
GET    /admin/users/{id}/history        # История действий пользователя
GET    /admin/users/export              # Экспорт пользователей
```

### Управление мастерами
```
GET    /admin/masters                   # Список мастеров
GET    /admin/masters/{id}              # Профиль мастера
POST   /admin/masters/{id}/verify       # Верифицировать мастера
POST   /admin/masters/{id}/reject       # Отклонить верификацию
GET    /admin/masters/{id}/documents    # Документы мастера
POST   /admin/masters/{id}/rating       # Изменить рейтинг мастера
```

### Модерация отзывов
```
GET    /admin/reviews                   # Список отзывов на модерации
GET    /admin/reviews/{id}              # Детали отзыва
POST   /admin/reviews/{id}/approve      # Одобрить отзыв
POST   /admin/reviews/{id}/reject       # Отклонить отзыв
DELETE /admin/reviews/{id}              # Удалить отзыв
```

### Система жалоб
```
GET    /admin/complaints                # Список жалоб
GET    /admin/complaints/{id}           # Детали жалобы
POST   /admin/complaints/{id}/assign    # Назначить ответственного
POST   /admin/complaints/{id}/resolve   # Разрешить жалобу
POST   /admin/complaints/{id}/reject    # Отклонить жалобу
GET    /admin/complaints/categories     # Категории жалоб
```

### Аналитика и отчеты
```
GET    /admin/analytics                 # Главная страница аналитики
GET    /admin/analytics/users           # Аналитика пользователей
GET    /admin/analytics/ads             # Аналитика объявлений
GET    /admin/analytics/revenue         # Финансовая аналитика
GET    /admin/analytics/moderation      # Аналитика модерации
POST   /admin/reports/generate          # Генерация отчета
GET    /admin/reports/{id}/download     # Скачивание отчета
```

### Системные настройки
```
GET    /admin/settings                  # Системные настройки
POST   /admin/settings                  # Сохранить настройки
GET    /admin/settings/categories       # Управление категориями
POST   /admin/settings/categories       # Сохранить категории
GET    /admin/settings/templates        # Шаблоны уведомлений
POST   /admin/settings/templates        # Сохранить шаблоны
```

---

## 🎨 КОМПОНЕНТЫ И ИНТЕРФЕЙСЫ

### TypeScript интерфейсы:

#### AdminUser
```typescript
interface AdminUser {
  id: number
  name: string
  email: string
  role: 'admin' | 'moderator' | 'support'
  permissions: string[]
  last_login_at: string | null
  created_at: string
}
```

#### ModerationItem
```typescript
interface ModerationItem {
  id: number
  type: 'ad' | 'review' | 'user' | 'master'
  item_id: number
  priority: 'low' | 'normal' | 'high' | 'critical'
  status: 'pending' | 'in_progress' | 'completed'
  assigned_to: AdminUser | null
  sla_deadline: string
  created_at: string
  updated_at: string
  item: Ad | Review | User | Master
}
```

#### AdminStats
```typescript
interface AdminStats {
  pending_ads: number
  pending_reviews: number
  pending_complaints: number
  total_users: number
  active_masters: number
  blocked_users: number
  revenue_today: number
  revenue_month: number
  moderation_queue_size: number
  average_response_time: number
  moderator_efficiency: {
    [moderator_id: number]: {
      name: string
      processed_today: number
      average_time: number
      accuracy_rate: number
    }
  }
}
```

#### Complaint
```typescript
interface Complaint {
  id: number
  complainant: User
  target_type: string
  target_id: number
  target: Ad | Review | User
  category: string
  reason: string
  status: 'new' | 'in_progress' | 'resolved' | 'rejected'
  priority: 'low' | 'normal' | 'high' | 'critical'
  assigned_to: AdminUser | null
  resolution: string | null
  resolved_at: string | null
  created_at: string
  updated_at: string
}
```

### Vue компоненты:

#### AdminLayout.vue
```vue
<template>
  <div class="admin-layout">
    <AdminSidebar 
      :user="adminUser"
      :stats="adminStats"
      :active-section="activeSection"
      @section-change="handleSectionChange"
    />
    
    <main class="admin-content">
      <AdminHeader 
        :title="pageTitle"
        :breadcrumbs="breadcrumbs"
        :actions="headerActions"
      />
      
      <div class="admin-main">
        <slot />
      </div>
    </main>
    
    <AdminNotifications 
      v-if="notifications.length > 0"
      :notifications="notifications"
      @dismiss="dismissNotification"
    />
  </div>
</template>
```

#### ModerationQueue.vue
```vue
<template>
  <div class="moderation-queue">
    <div class="queue-header">
      <h2>Очередь модерации</h2>
      <div class="queue-stats">
        <span class="stat-item">
          Всего: {{ totalItems }}
        </span>
        <span class="stat-item high-priority">
          Высокий приоритет: {{ highPriorityCount }}
        </span>
        <span class="stat-item overdue">
          Просрочено: {{ overdueCount }}
        </span>
      </div>
    </div>
    
    <div class="queue-filters">
      <select v-model="priorityFilter">
        <option value="">Все приоритеты</option>
        <option value="critical">Критичные</option>
        <option value="high">Высокие</option>
        <option value="normal">Обычные</option>
        <option value="low">Низкие</option>
      </select>
      
      <select v-model="typeFilter">
        <option value="">Все типы</option>
        <option value="ad">Объявления</option>
        <option value="review">Отзывы</option>
        <option value="user">Пользователи</option>
        <option value="master">Мастера</option>
      </select>
      
      <select v-model="assigneeFilter">
        <option value="">Все модераторы</option>
        <option value="unassigned">Не назначено</option>
        <option v-for="moderator in moderators" :key="moderator.id" :value="moderator.id">
          {{ moderator.name }}
        </option>
      </select>
    </div>
    
    <div class="queue-items">
      <ModerationCard
        v-for="item in filteredItems"
        :key="`${item.type}-${item.item_id}`"
        :item="item"
        :show-assignee="true"
        @approve="handleApprove"
        @reject="handleReject"
        @assign="handleAssign"
        @view-details="handleViewDetails"
      />
    </div>
    
    <div class="queue-pagination">
      <Pagination 
        :current-page="currentPage"
        :total-pages="totalPages"
        :per-page="perPage"
        :total-items="totalItems"
        @page-change="handlePageChange"
      />
    </div>
  </div>
</template>
```

#### AdminAnalytics.vue
```vue
<template>
  <div class="admin-analytics">
    <div class="analytics-header">
      <h1>Аналитика платформы</h1>
      <div class="date-range-picker">
        <input 
          v-model="dateRange.start" 
          type="date" 
          :max="dateRange.end"
        />
        <span>—</span>
        <input 
          v-model="dateRange.end" 
          type="date" 
          :min="dateRange.start"
          :max="today"
        />
        <button @click="applyDateRange">Применить</button>
      </div>
    </div>
    
    <div class="analytics-grid">
      <div class="analytics-card">
        <h3>Пользователи</h3>
        <LineChart 
          :data="userStats"
          :options="chartOptions"
        />
      </div>
      
      <div class="analytics-card">
        <h3>Объявления</h3>
        <BarChart 
          :data="adStats"
          :options="chartOptions"
        />
      </div>
      
      <div class="analytics-card">
        <h3>Выручка</h3>
        <LineChart 
          :data="revenueStats"
          :options="revenueChartOptions"
        />
      </div>
      
      <div class="analytics-card">
        <h3>Модерация</h3>
        <DonutChart 
          :data="moderationStats"
          :options="donutChartOptions"
        />
      </div>
    </div>
    
    <div class="detailed-reports">
      <h2>Детальные отчеты</h2>
      <div class="reports-grid">
        <ReportCard 
          title="Отчет по пользователям"
          description="Детальная статистика регистраций, активности и удержания пользователей"
          @generate="generateUserReport"
        />
        <ReportCard 
          title="Отчет по объявлениям"
          description="Анализ публикации объявлений, модерации и конверсии"
          @generate="generateAdReport"
        />
        <ReportCard 
          title="Финансовый отчет"
          description="Выручка, комиссии, платежи и финансовые показатели"
          @generate="generateFinancialReport"
        />
        <ReportCard 
          title="Отчет по модерации"
          description="Эффективность модераторов, время обработки, качество решений"
          @generate="generateModerationReport"
        />
      </div>
    </div>
  </div>
</template>
```

---

## ⚡ ПРОИЗВОДИТЕЛЬНОСТЬ И ОПТИМИЗАЦИЯ

### Кеширование:
```php
// Кеширование статистики админки
Cache::remember('admin.stats.daily', 3600, function () {
    return [
        'pending_ads' => Ad::where('status', 'pending')->count(),
        'pending_reviews' => Review::where('status', 'pending')->count(),
        'total_users' => User::count(),
        'active_masters' => User::where('role', 'master')->where('is_blocked', false)->count(),
        'revenue_today' => Payment::whereDate('created_at', today())->sum('amount'),
    ];
});

// Кеширование списка модераторов
Cache::remember('admin.moderators', 86400, function () {
    return User::where('role', 'moderator')
        ->orWhere('role', 'admin')
        ->select(['id', 'name', 'email'])
        ->get();
});
```

### Оптимизация запросов:
```php
// Eager loading для списка объявлений
$ads = Ad::with([
    'user:id,name,email',
    'photos:id,ad_id,path',
    'complaints:id,target_id,status,priority',
    'moderationQueue:id,item_id,priority,sla_deadline'
])
->where('status', 'pending')
->orderBy('priority', 'desc')
->orderBy('created_at', 'asc')
->paginate(20);

// Использование индексов для быстрого поиска
$users = User::query()
    ->when($search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    })
    ->when($role, function ($query, $role) {
        $query->where('role', $role);
    })
    ->when($blocked, function ($query, $blocked) {
        $query->where('is_blocked', $blocked);
    })
    ->orderBy('created_at', 'desc')
    ->paginate(50);
```

### Frontend оптимизация:
```typescript
// Lazy loading для тяжелых компонентов
const AnalyticsChart = defineAsyncComponent(() => import('@/components/AnalyticsChart.vue'))
const ReportExporter = defineAsyncComponent(() => import('@/components/ReportExporter.vue'))

// Debounce для поиска
const debouncedSearch = debounce((query: string) => {
  searchUsers(query)
}, 300)

// Виртуализация для больших списков
const virtualizedList = computed(() => {
  const startIndex = (currentPage.value - 1) * itemsPerPage.value
  const endIndex = startIndex + itemsPerPage.value
  return items.value.slice(startIndex, endIndex)
})
```

---

## 🔒 БЕЗОПАСНОСТЬ

### Аутентификация и авторизация:
```php
// AdminMiddleware
class AdminMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        $user = auth()->user();
        
        if (!$user->isAdmin() && !$user->isModerator()) {
            abort(403, 'Доступ запрещен');
        }
        
        // Проверка конкретных ролей если указаны
        if (!empty($roles) && !in_array($user->role, $roles)) {
            abort(403, 'Недостаточно прав');
        }
        
        // Логирование входа в админку
        AdminAction::create([
            'admin_id' => $user->id,
            'action' => 'admin_access',
            'target_type' => 'system',
            'target_id' => 0,
            'details' => [
                'route' => $request->route()->getName(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]
        ]);
        
        return $next($request);
    }
}

// Проверка прав на конкретные действия
class AdModerationController extends Controller
{
    public function approve(Ad $ad)
    {
        // Проверяем, может ли текущий админ модерировать объявления
        if (!auth()->user()->can('moderate_ads')) {
            abort(403, 'Нет прав на модерацию объявлений');
        }
        
        // Проверяем, не модерировал ли уже это объявление другой админ
        if ($ad->moderated_by && $ad->moderated_by !== auth()->id()) {
            return back()->withErrors(['message' => 'Объявление уже обработано другим модератором']);
        }
        
        // Выполняем действие
        $this->moderationService->approve($ad, auth()->user());
        
        return back()->with('success', 'Объявление одобрено');
    }
}
```

### Логирование действий:
```php
// Трейт для автоматического логирования
trait LogsAdminActions
{
    protected static function bootLogsAdminActions()
    {
        static::created(function ($model) {
            self::logAction('create', $model);
        });
        
        static::updated(function ($model) {
            self::logAction('update', $model);
        });
        
        static::deleted(function ($model) {
            self::logAction('delete', $model);
        });
    }
    
    protected static function logAction(string $action, $model)
    {
        if (auth()->check() && auth()->user()->isAdmin()) {
            AdminAction::create([
                'admin_id' => auth()->id(),
                'action' => $action . '_' . class_basename($model),
                'target_type' => get_class($model),
                'target_id' => $model->id,
                'details' => $model->getChanges(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        }
    }
}
```

### Валидация и санитизация:
```php
// FormRequest для админских действий
class ApproveAdRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->can('moderate_ads');
    }
    
    public function rules()
    {
        return [
            'notes' => 'nullable|string|max:1000',
            'notify_user' => 'boolean'
        ];
    }
    
    protected function prepareForValidation()
    {
        $this->merge([
            'notes' => strip_tags($this->notes),
            'notify_user' => $this->boolean('notify_user')
        ]);
    }
}
```

---

## 🚀 ДЕПЛОЙ И МОНИТОРИНГ

### CI/CD Pipeline:
```yaml
# .github/workflows/admin-panel.yml
name: Admin Panel Deployment

on:
  push:
    branches: [main]
    paths: 
      - 'app/Application/Http/Controllers/Admin/**'
      - 'app/Domain/Admin/**'
      - 'resources/js/Pages/Admin/**'

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          
      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader
        
      - name: Run tests
        run: php artisan test --filter=AdminTest
        
      - name: Build frontend
        run: |
          npm ci
          npm run build
          
  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    steps:
      - name: Deploy to production
        run: |
          ssh ${{ secrets.SERVER_HOST }} "
            cd /var/www/spa.com
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan cache:clear
            php artisan config:cache
            php artisan route:cache
            npm ci --production
            npm run build
            sudo systemctl reload nginx
          "
```

### Мониторинг:
```php
// Health check для админки
Route::get('/admin/health', function () {
    $checks = [
        'database' => DB::connection()->getPdo() ? 'ok' : 'error',
        'redis' => Cache::store('redis')->get('health-check') !== null ? 'ok' : 'error',
        'queue' => Queue::size() < 1000 ? 'ok' : 'warning',
        'disk_space' => disk_free_space('/') > 1024*1024*1024 ? 'ok' : 'warning', // 1GB
    ];
    
    $status = in_array('error', $checks) ? 'error' : 
              (in_array('warning', $checks) ? 'warning' : 'ok');
    
    return response()->json([
        'status' => $status,
        'checks' => $checks,
        'timestamp' => now()->toISOString()
    ], $status === 'error' ? 500 : 200);
});

// Метрики для мониторинга
class AdminMetricsController extends Controller
{
    public function metrics()
    {
        return response()->json([
            'moderation_queue_size' => ModerationQueue::where('status', 'pending')->count(),
            'average_response_time' => $this->getAverageResponseTime(),
            'active_moderators' => $this->getActiveModerators(),
            'error_rate' => $this->getErrorRate(),
            'memory_usage' => memory_get_usage(true),
            'cpu_usage' => sys_getloadavg()[0]
        ]);
    }
}
```

---

## 📊 ТЕСТИРОВАНИЕ

### Unit тесты:
```php
// tests/Unit/Admin/ModerationServiceTest.php
class ModerationServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected ModerationService $service;
    protected User $admin;
    protected Ad $ad;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(ModerationService::class);
        $this->admin = User::factory()->admin()->create();
        $this->ad = Ad::factory()->pending()->create();
    }
    
    public function test_admin_can_approve_ad()
    {
        $this->actingAs($this->admin);
        
        $result = $this->service->approve($this->ad, $this->admin);
        
        $this->assertTrue($result);
        $this->assertEquals('active', $this->ad->fresh()->status);
        $this->assertEquals($this->admin->id, $this->ad->fresh()->moderated_by);
        $this->assertNotNull($this->ad->fresh()->moderated_at);
        
        // Проверяем логирование
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $this->admin->id,
            'action' => 'approve_ad',
            'target_type' => 'App\Domain\Ad\Models\Ad',
            'target_id' => $this->ad->id
        ]);
    }
    
    public function test_admin_can_reject_ad_with_reason()
    {
        $this->actingAs($this->admin);
        
        $reason = 'Неподходящий контент';
        $result = $this->service->reject($this->ad, $this->admin, $reason);
        
        $this->assertTrue($result);
        $this->assertEquals('rejected', $this->ad->fresh()->status);
        $this->assertEquals($reason, $this->ad->fresh()->rejection_reason);
    }
}
```

### Feature тесты:
```php
// tests/Feature/Admin/AdModerationTest.php
class AdModerationTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $admin;
    protected Ad $ad;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->admin()->create();
        $this->ad = Ad::factory()->pending()->create();
    }
    
    public function test_admin_can_view_moderation_queue()
    {
        $this->actingAs($this->admin)
             ->get(route('admin.ads.index'))
             ->assertStatus(200)
             ->assertSee('Модерация объявлений')
             ->assertSee($this->ad->title);
    }
    
    public function test_admin_can_approve_ad_via_api()
    {
        $response = $this->actingAs($this->admin)
                         ->post(route('admin.ads.approve', $this->ad), [
                             'notes' => 'Объявление соответствует требованиям',
                             'notify_user' => true
                         ]);
        
        $response->assertRedirect()
                 ->assertSessionHas('success');
        
        $this->assertEquals('active', $this->ad->fresh()->status);
    }
    
    public function test_non_admin_cannot_access_moderation()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
             ->get(route('admin.ads.index'))
             ->assertStatus(403);
    }
}
```

### Frontend тесты:
```typescript
// tests/admin/ModerationCard.test.ts
import { mount } from '@vue/test-utils'
import ModerationCard from '@/Pages/Admin/components/ModerationCard.vue'
import { createTestingPinia } from '@pinia/testing'

describe('ModerationCard', () => {
  const mockAd = {
    id: 1,
    title: 'Тестовое объявление',
    status: 'pending',
    priority: 'normal',
    created_at: '2024-01-15T10:00:00Z',
    user: {
      id: 1,
      name: 'Тестовый пользователь',
      email: 'test@example.com'
    }
  }
  
  it('renders ad information correctly', () => {
    const wrapper = mount(ModerationCard, {
      props: { ad: mockAd },
      global: {
        plugins: [createTestingPinia()]
      }
    })
    
    expect(wrapper.text()).toContain('Тестовое объявление')
    expect(wrapper.text()).toContain('Тестовый пользователь')
  })
  
  it('emits approve event when approve button clicked', async () => {
    const wrapper = mount(ModerationCard, {
      props: { ad: mockAd },
      global: {
        plugins: [createTestingPinia()]
      }
    })
    
    await wrapper.find('[data-testid="approve-button"]').trigger('click')
    
    expect(wrapper.emitted('approve')).toBeTruthy()
    expect(wrapper.emitted('approve')[0]).toEqual([mockAd])
  })
  
  it('shows high priority badge for high priority ads', () => {
    const highPriorityAd = { ...mockAd, priority: 'high' }
    
    const wrapper = mount(ModerationCard, {
      props: { ad: highPriorityAd },
      global: {
        plugins: [createTestingPinia()]
      }
    })
    
    expect(wrapper.find('.priority-badge.high').exists()).toBe(true)
  })
})
```

---

## 📈 МЕТРИКИ И KPI

### Ключевые метрики:
```php
// AdminMetricsService
class AdminMetricsService
{
    public function getModerationMetrics(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'total_moderated' => Ad::whereBetween('moderated_at', [$startDate, $endDate])->count(),
            'approved_count' => Ad::where('status', 'active')->whereBetween('moderated_at', [$startDate, $endDate])->count(),
            'rejected_count' => Ad::where('status', 'rejected')->whereBetween('moderated_at', [$startDate, $endDate])->count(),
            'average_response_time' => $this->getAverageResponseTime($startDate, $endDate),
            'sla_compliance' => $this->getSLACompliance($startDate, $endDate),
            'moderator_efficiency' => $this->getModeratorEfficiency($startDate, $endDate)
        ];
    }
    
    private function getAverageResponseTime(Carbon $startDate, Carbon $endDate): float
    {
        return Ad::whereBetween('moderated_at', [$startDate, $endDate])
                 ->whereNotNull('moderated_at')
                 ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, moderated_at)) as avg_time')
                 ->value('avg_time') ?? 0;
    }
    
    private function getSLACompliance(Carbon $startDate, Carbon $endDate): float
    {
        $total = Ad::whereBetween('moderated_at', [$startDate, $endDate])->count();
        
        if ($total === 0) return 100;
        
        $compliant = Ad::whereBetween('moderated_at', [$startDate, $endDate])
                       ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, moderated_at) <= CASE 
                                   WHEN priority = "critical" THEN 1
                                   WHEN priority = "high" THEN 4
                                   WHEN priority = "normal" THEN 24
                                   ELSE 72 END')
                       ->count();
        
        return ($compliant / $total) * 100;
    }
}
```

### Дашборд метрик:
```vue
<template>
  <div class="metrics-dashboard">
    <div class="metrics-grid">
      <MetricCard
        title="Время отклика"
        :value="metrics.averageResponseTime"
        unit="мин"
        :target="240"
        :trend="metrics.responseTimeTrend"
        color="blue"
      />
      
      <MetricCard
        title="SLA соответствие"
        :value="metrics.slaCompliance"
        unit="%"
        :target="95"
        :trend="metrics.slaComplianceTrend"
        color="green"
      />
      
      <MetricCard
        title="Одобрено объявлений"
        :value="metrics.approvedCount"
        :target="metrics.totalModerated * 0.8"
        :trend="metrics.approvalRateTrend"
        color="purple"
      />
      
      <MetricCard
        title="Активных модераторов"
        :value="metrics.activeModerators"
        :trend="metrics.moderatorsTrend"
        color="orange"
      />
    </div>
    
    <div class="charts-section">
      <div class="chart-card">
        <h3>Объем модерации по дням</h3>
        <LineChart 
          :data="chartData.moderation"
          :options="chartOptions.moderation"
        />
      </div>
      
      <div class="chart-card">
        <h3>Эффективность модераторов</h3>
        <BarChart 
          :data="chartData.efficiency"
          :options="chartOptions.efficiency"
        />
      </div>
    </div>
  </div>
</template>
```

---

Эта техническая спецификация покрывает все аспекты разработки административной панели и служит детальным руководством для реализации каждого компонента системы.
