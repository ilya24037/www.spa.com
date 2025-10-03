# 🎯 ДЕТАЛЬНЫЙ ПЛАН: Объединение профилей в User

**Дата создания:** 2025-01-03
**Автор:** AI Assistant + Пользователь
**Цель:** Упростить архитектуру - один профиль User вместо трёх (User, UserProfile, MasterProfile)

---

## 📋 Этап 0: Подготовка

### ✅ 1. Git бекап создан
```bash
git commit -m "Backup before merging profiles into User"
git branch backup-before-profile-merge
```

### 2. Бекап БД (ПЕРЕД миграциями!)
```bash
# Через phpMyAdmin или:
mysqldump -u root spa_database > backups/backup_before_merge_$(date +%Y%m%d_%H%M%S).sql
```

### ✅ 3. План сохранён
Файл: `Docs/REFACTORING/merge-profiles-to-user-plan.md`

---

## 🔍 ЭТАП 1: Анализ и подготовка (2 часа)

### 1.1 Собрать все используемые поля MasterProfile

**Команда:**
```bash
grep -r "masterProfile->" app/ --include="*.php" -h | \
grep -oP "masterProfile->\K\w+" | sort | uniq -c | sort -rn > \
Docs/REFACTORING/masterProfile-fields-usage.txt
```

**Результат анализа (уже выполнен):**
```
63x - id
12x - folder_name (для медиа)
8x  - user_id
6x  - user
4x  - status, services, rating
3x  - display_name
2x  - reviews_count, is_verified
1x  - price_from, phone, main_photo
```

### 1.2 Маппинг полей MasterProfile → User

| MasterProfile поле | User поле | Действие |
|-------------------|-----------|----------|
| `id` | `id` | ✅ Уже есть |
| `user_id` | - | ❌ Удалить (сам User) |
| `slug` | `slug` | ➕ Добавить в users |
| `display_name` | `name` | ✅ Использовать существующий |
| `rating` | `rating` | ➕ Добавить в users |
| `reviews_count` | `reviews_count` | ➕ Добавить в users |
| `views_count` | `views_count` | ➕ Добавить в users |
| `status` | `status` | ✅ Уже есть |
| `is_verified` | `is_verified` | ➕ Добавить в users |
| `avatar` | `avatar_url` | ✅ Уже есть |
| `phone` | `phone` | ✅ Уже есть |

**Поля которые НЕ переносим (не используются):**
- age, height, weight, breast_size
- hair_color, eye_color, nationality
- bio, description, experience_years
- certificates, education
- whatsapp, telegram, show_contacts
- features, medical_certificate
- is_premium, premium_until
- meta_title, meta_description

### 1.3 Найти все FK зависимости

**Команда:**
```bash
grep -r "master_profile_id" database/migrations/ --include="*.php"
grep -r "master_profile_id" app/ --include="*.php" -n
```

**Найденные FK (нужно переименовать):**
- `bookings.master_profile_id` → `master_user_id`
- `reviews.master_profile_id` → `master_user_id`
- `master_services.master_profile_id` → `user_id`
- `favorites.master_profile_id` → `user_id`
- `photos.master_profile_id` → `user_id`
- `videos.master_profile_id` → `user_id`

---

## 🗄️ ЭТАП 2: Миграции БД (1 час)

### 2.1 Миграция: Добавить поля в users

**Файл:** `database/migrations/2025_01_03_000001_add_profile_fields_to_users.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // SEO и публичная информация
            $table->string('slug')->unique()->nullable()->after('name');

            // Рейтинг и статистика
            $table->decimal('rating', 3, 2)->default(0)->after('status');
            $table->unsignedInteger('reviews_count')->default(0)->after('rating');
            $table->unsignedInteger('views_count')->default(0)->after('reviews_count');

            // Верификация
            $table->boolean('is_verified')->default(false)->after('email_verified_at');

            // Индексы
            $table->index('slug');
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['rating']);
            $table->dropColumn(['slug', 'rating', 'reviews_count', 'views_count', 'is_verified']);
        });
    }
};
```

**Запуск:**
```bash
php artisan migrate
```

### 2.2 Миграция: Копировать данные из master_profiles

**Файл:** `database/migrations/2025_01_03_000002_migrate_master_profile_data_to_users.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Копируем данные порциями по 100 записей
        DB::table('master_profiles')->orderBy('id')->chunk(100, function ($profiles) {
            foreach ($profiles as $profile) {
                DB::table('users')
                    ->where('id', $profile->user_id)
                    ->update([
                        'slug' => $profile->slug,
                        'rating' => $profile->rating ?? 0,
                        'reviews_count' => $profile->reviews_count ?? 0,
                        'views_count' => $profile->views_count ?? 0,
                        'is_verified' => $profile->is_verified ?? false,
                    ]);
            }
        });

        // Создать slug для пользователей без профиля
        $usersWithoutSlug = DB::table('users')->whereNull('slug')->get();
        foreach ($usersWithoutSlug as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'slug' => \Illuminate\Support\Str::slug($user->name) . '-' . $user->id
                ]);
        }
    }

    public function down(): void
    {
        // Откатить невозможно без потери данных
        // Используй Git backup для восстановления
    }
};
```

**Запуск:**
```bash
php artisan migrate
```

### 2.3 Миграция: Обновить FK в bookings

**Файл:** `database/migrations/2025_01_03_000003_update_bookings_fk.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Удаляем старый FK
            $table->dropForeign(['master_profile_id']);
        });

        // Переносим данные: master_profile_id → master_user_id
        DB::statement('
            UPDATE bookings b
            INNER JOIN master_profiles mp ON b.master_profile_id = mp.id
            SET b.master_user_id = mp.user_id
        ');

        Schema::table('bookings', function (Blueprint $table) {
            // Удаляем старую колонку
            $table->dropColumn('master_profile_id');

            // Добавляем FK на users
            $table->foreign('master_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Откат через Git backup
    }
};
```

**Аналогично для:**
- reviews
- master_services
- favorites
- photos
- videos

### 2.4 Миграция: Удалить старые таблицы (⚠️ ТОЛЬКО В КОНЦЕ!)

**Файл:** `database/migrations/2025_01_03_999999_drop_old_profile_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ⚠️ ЗАПУСКАТЬ ТОЛЬКО ПОСЛЕ ПОЛНОГО ТЕСТИРОВАНИЯ!
        Schema::dropIfExists('master_profiles');
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('user_settings'); // если не используется
    }

    public function down(): void
    {
        // Восстановление через Git backup и SQL dump
    }
};
```

**❌ НЕ ЗАПУСКАТЬ до полного тестирования!**

---

## 💻 ЭТАП 3: Обновить модель User (30 минут)

### 3.1 Обновить fillable в User.php

**Файл:** `app/Domain/User/Models/User.php`

```php
protected $fillable = [
    'name',
    'email',
    'password',
    'role',
    'status',
    'email_verified_at',
    'phone',
    'avatar_url',

    // Новые поля из MasterProfile:
    'slug',
    'rating',
    'reviews_count',
    'views_count',
    'is_verified',
];
```

### 3.2 Добавить автогенерацию slug

```php
protected static function boot()
{
    parent::boot();

    static::creating(function ($user) {
        if (!$user->slug && $user->name) {
            $user->slug = Str::slug($user->name) . '-' . ($user->id ?? rand(1000, 9999));
        }
    });

    // Обновить slug если изменилось имя
    static::updating(function ($user) {
        if ($user->isDirty('name') && !$user->isDirty('slug')) {
            $user->slug = Str::slug($user->name) . '-' . $user->id;
        }
    });
}
```

### 3.3 Удалить связь с MasterProfile

```php
// ❌ УДАЛИТЬ:
use App\Domain\User\Traits\HasMasterProfile;

// ❌ УДАЛИТЬ в use:
use HasMasterProfile;

// ❌ УДАЛИТЬ методы:
public function masterProfile() { ... }
```

### 3.4 Добавить casts

```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
        'status' => UserStatus::class,

        // Новые:
        'rating' => 'decimal:2',
        'reviews_count' => 'integer',
        'views_count' => 'integer',
        'is_verified' => 'boolean',
    ];
}
```

---

## 🔄 ЭТАП 4: Массовая замена в коде (4-6 часов)

### 4.1 Контроллеры

**Список файлов (20 штук):**
1. `AdController.php`
2. `MasterController.php`
3. `BookingController.php`
4. `FavoritesController.php`
5. `MediaUploadController.php`
6. `HomeController.php`
7. `ProfileController.php`
8. ... (проверить через grep)

**Шаблон замены:**

```php
// ❌ БЫЛО:
$user->load('masterProfile');
$user->masterProfile->slug;
$user->masterProfile->rating;
$ad->user->masterProfile->id;

// ✅ СТАЛО:
// load не нужен - всё в User
$user->slug;
$user->rating;
$ad->user->id;
```

**Пример в AdController.php (строка 91):**

```php
// ❌ БЫЛО:
$ad->load(['user.masterProfile']);

// ✅ СТАЛО:
$ad->load(['user']); // или вообще убрать - user уже загружен
```

**Пример в FavoritesController.php:**

```php
// ❌ БЫЛО:
$item = $favorite->masterProfile ? [
    'id' => $favorite->masterProfile->id,
    'name' => $favorite->masterProfile->display_name ?? 'Мастер',
    'rating' => $favorite->masterProfile->rating ?? 0,
] : null;

// ✅ СТАЛО:
$item = $favorite->user ? [
    'id' => $favorite->user->id,
    'name' => $favorite->user->name ?? 'Мастер',
    'rating' => $favorite->user->rating ?? 0,
] : null;
```

### 4.2 Сервисы (19 файлов)

**Список:**
- `AdProfileService.php`
- `AdTransformService.php`
- `MasterService.php` → ❌ УДАЛИТЬ весь домен!
- `BookingService.php`
- `ReviewService.php`
- `RatingCalculator.php`
- `MasterRatingService.php` → переименовать в `UserRatingService.php`

**Пример в RatingCalculator.php:**

```php
// ❌ БЫЛО:
public function updateMasterRating(MasterProfile $profile, float $newRating)
{
    $profile->rating = $this->calculate($profile->reviews);
    $profile->save();
}

// ✅ СТАЛО:
public function updateUserRating(User $user, float $newRating)
{
    $user->rating = $this->calculate($user->reviews);
    $user->save();
}
```

### 4.3 Модели (13 файлов)

**Booking.php:**

```php
// ❌ БЫЛО:
public function masterProfile(): BelongsTo
{
    return $this->belongsTo(MasterProfile::class);
}

// ✅ СТАЛО:
public function master(): BelongsTo
{
    return $this->belongsTo(User::class, 'master_user_id');
}
```

**Review.php:**

```php
// ❌ БЫЛО:
public function masterProfile(): BelongsTo
{
    return $this->belongsTo(MasterProfile::class, 'master_profile_id');
}

// ✅ СТАЛО:
public function master(): BelongsTo
{
    return $this->belongsTo(User::class, 'master_user_id');
}
```

**Ad.php (строка 268-271):**

```php
// ❌ БЫЛО:
public function masterProfile(): BelongsTo
{
    return $this->belongsTo(\App\Domain\Master\Models\MasterProfile::class, 'user_id', 'user_id');
}

// ✅ УДАЛИТЬ - больше не нужно
```

### 4.4 Resources (API)

**UserResource.php:**

```php
// ❌ БЫЛО:
return [
    'id' => $this->id,
    'name' => $this->name,
    'profile' => $this->whenLoaded('profile'),
    'masterProfile' => $this->whenLoaded('masterProfile'),
];

// ✅ СТАЛО:
return [
    'id' => $this->id,
    'name' => $this->name,
    'slug' => $this->slug,
    'rating' => $this->rating,
    'reviews_count' => $this->reviews_count,
    'is_verified' => $this->is_verified,
    'avatar' => $this->avatar_url,
];
```

**AdResource.php:**

```php
// ❌ БЫЛО:
'master' => [
    'id' => $this->user->masterProfile->id,
    'name' => $this->user->masterProfile->display_name,
    'rating' => $this->user->masterProfile->rating,
]

// ✅ СТАЛО:
'master' => [
    'id' => $this->user->id,
    'name' => $this->user->name,
    'slug' => $this->user->slug,
    'rating' => $this->user->rating,
]
```

### 4.5 DTOs

**MasterProfileDTO.php → УДАЛИТЬ**

**Создать UserPublicDTO.php:**

```php
<?php

namespace App\Domain\User\DTOs;

class UserPublicDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly float $rating,
        public readonly int $reviewsCount,
        public readonly ?string $avatar,
        public readonly bool $isVerified,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            slug: $user->slug,
            rating: (float)$user->rating,
            reviewsCount: $user->reviews_count,
            avatar: $user->avatar_url,
            isVerified: $user->is_verified,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'rating' => $this->rating,
            'reviews_count' => $this->reviewsCount,
            'avatar' => $this->avatar,
            'is_verified' => $this->isVerified,
        ];
    }
}
```

---

## 🎨 ЭТАП 5: Frontend (Vue) (2 часа)

### 5.1 Обновить TypeScript интерфейсы

**Файл:** `resources/js/types/models.ts` (или создать)

```typescript
// ❌ УДАЛИТЬ:
interface MasterProfile {
    id: number
    slug: string
    display_name: string
    rating: number
    reviews_count: number
}

// ✅ ОБНОВИТЬ User:
interface User {
    id: number
    name: string
    email: string
    phone?: string
    avatar_url?: string

    // Новые поля:
    slug?: string
    rating?: number
    reviews_count?: number
    views_count?: number
    is_verified?: boolean
}
```

### 5.2 Заменить в компонентах

**AdDetail.vue (строки 413-420):**

```vue
<!-- ❌ БЫЛО: -->
<script setup lang="ts">
const masterProfileId = computed(() => adData.value.user?.masterProfile?.id || null)
const masterSlug = computed(() => adData.value.user?.masterProfile?.slug || 'master')
</script>

<!-- ✅ СТАЛО: -->
<script setup lang="ts">
const masterSlug = computed(() => adData.value.user?.slug || `user-${adData.value.user?.id}`)
</script>
```

**Обновить ссылку:**

```vue
<!-- ❌ БЫЛО: -->
<Link :href="`/masters/${masterSlug}-${masterProfileId}`">

<!-- ✅ СТАЛО: -->
<Link :href="`/users/${masterSlug}`">
```

**ItemCard.vue:**

```vue
<!-- ❌ БЫЛО: -->
{{ item.user?.masterProfile?.rating }}

<!-- ✅ СТАЛО: -->
{{ item.user?.rating }}
```

**Dashboard.vue:**

```vue
<!-- ❌ БЫЛО: -->
{{ ad.user.masterProfile.slug }}

<!-- ✅ СТАЛО: -->
{{ ad.user.slug }}
```

### 5.3 Удалить компоненты мастера

**Удалить целиком:**
```bash
rm -rf resources/js/src/widgets/master-profile/
rm -rf resources/js/Pages/Masters/
```

**Создать новый UserProfile.vue:**
```
resources/js/Pages/Users/Profile.vue
```

---

## 🧪 ЭТАП 6: Тестирование (2 часа)

### 6.1 Unit тесты Laravel

```bash
php artisan test --filter=UserTest
php artisan test --filter=AdTest
php artisan test --filter=BookingTest
php artisan test --filter=ReviewTest
```

**Обновить тесты:**

```php
// UserTest.php
public function test_user_has_slug_after_creation()
{
    $user = User::factory()->create(['name' => 'Анна Петрова']);

    $this->assertNotNull($user->slug);
    $this->assertStringContainsString('anna-petrova', $user->slug);
}

public function test_user_rating_defaults_to_zero()
{
    $user = User::factory()->create();

    $this->assertEquals(0, $user->rating);
    $this->assertEquals(0, $user->reviews_count);
}
```

### 6.2 Функциональное тестирование

**Чек-лист:**

- [ ] ✅ Регистрация нового пользователя → slug создаётся автоматически
- [ ] ✅ Создание объявления → `ad.user.slug` доступен
- [ ] ✅ Просмотр профиля пользователя → `/users/anna-123` работает
- [ ] ✅ Бронирование → `booking.master_user_id` сохраняется
- [ ] ✅ Создание отзыва → рейтинг обновляется в `users.rating`
- [ ] ✅ Избранное → `favorite.user_id` работает
- [ ] ✅ Загрузка фото → привязка к `user_id`
- [ ] ✅ Поиск мастеров → поиск по `users.slug` и `users.name`
- [ ] ✅ Главная страница → объявления показывают `user.rating`
- [ ] ✅ Детальная страница объявления → аватар и ссылка на профиль работают

### 6.3 Тесты маршрутов

**routes/web.php:**

```php
// ❌ УДАЛИТЬ:
Route::get('/masters/{slug}-{master}', [MasterController::class, 'show'])
    ->name('masters.show');
Route::get('/masters/{master}/edit', [MasterController::class, 'edit'])
    ->name('masters.edit');

// ✅ ДОБАВИТЬ:
Route::get('/users/{user:slug}', [UserController::class, 'showProfile'])
    ->name('users.profile');
Route::get('/profile/edit', [UserController::class, 'editProfile'])
    ->name('profile.edit')
    ->middleware('auth');
```

**Создать UserController.php:**

```php
<?php

namespace App\Application\Http\Controllers;

use App\Domain\User\Models\User;
use Inertia\Inertia;

class UserController extends Controller
{
    public function showProfile(User $user)
    {
        // Загрузить объявления пользователя
        $ads = $user->ads()
            ->whereIn('status', ['active', 'completed'])
            ->with(['photos'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Users/Profile', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'slug' => $user->slug,
                'avatar' => $user->avatar_url,
                'rating' => $user->rating,
                'reviews_count' => $user->reviews_count,
                'is_verified' => $user->is_verified,
                'created_at' => $user->created_at,
            ],
            'ads' => $ads,
        ]);
    }
}
```

### 6.4 Frontend тестирование

**Проверить в браузере:**

1. http://localhost:8000/ → главная показывает объявления с рейтингом
2. http://localhost:8000/ads/1 → клик на аватар → переход на /users/anna-123
3. http://localhost:8000/users/anna-123 → профиль показывает объявления
4. http://localhost:8000/dashboard → мои объявления работают
5. http://localhost:8000/profile/edit → редактирование профиля

---

## 🗂️ ЭТАП 7: Очистка (1 час)

### 7.1 Удалить домен Master

```bash
rm -rf app/Domain/Master/
```

**Файлы для удаления:**
- `app/Domain/Master/Models/MasterProfile.php`
- `app/Domain/Master/Services/MasterService.php`
- `app/Domain/Master/Services/MasterRatingService.php`
- `app/Domain/Master/DTOs/MasterProfileDTO.php`
- `app/Domain/Master/Repositories/MasterRepository.php`
- И все остальные в `app/Domain/Master/`

### 7.2 Удалить UserProfile (если не используется)

```bash
# Проверить используется ли:
grep -r "UserProfile" app/ --include="*.php"

# Если нет - удалить:
rm app/Domain/User/Models/UserProfile.php
```

### 7.3 Удалить трейты

```bash
rm app/Domain/User/Traits/HasMasterProfile.php
```

### 7.4 Удалить контроллер Master

```bash
rm app/Application/Http/Controllers/MasterController.php
```

### 7.5 Обновить Policy

**UserPolicy.php:**

```php
// Добавить методы:
public function viewProfile(User $user, User $profile): bool
{
    return true; // Профили публичные
}

public function update(User $user, User $profile): bool
{
    return $user->id === $profile->id || $user->isAdmin();
}
```

### 7.6 Очистить кэш

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload
```

### 7.7 Удалить старые таблицы (⚠️ ПОСЛЕДНИЙ ШАГ!)

```bash
# ТОЛЬКО после полного тестирования!
php artisan migrate --path=database/migrations/2025_01_03_999999_drop_old_profile_tables.php
```

---

## 📊 ИТОГО: План по времени

| Этап | Описание | Время | Статус |
|------|----------|-------|--------|
| 0 | ✅ Бекап Git + План | 15 мин | ✅ DONE |
| 1 | Анализ и маппинг | 2 часа | ⏳ TODO |
| 2 | Миграции БД | 1 час | ⏳ TODO |
| 3 | Модель User | 30 мин | ⏳ TODO |
| 4 | Массовая замена PHP (80 файлов) | 6 часов | ⏳ TODO |
| 5 | Frontend Vue | 2 часа | ⏳ TODO |
| 6 | Тестирование | 2 часа | ⏳ TODO |
| 7 | Очистка | 1 час | ⏳ TODO |
| **ИТОГО** | | **~15 часов** | |

---

## ⚠️ КРИТИЧНЫЕ НАПОМИНАНИЯ

### ❌ НЕ делать до тестирования:
1. НЕ удалять таблицы `master_profiles` и `user_profiles`
2. НЕ удалять домен `app/Domain/Master/`
3. НЕ удалять старые компоненты Vue

### ✅ Делать после каждого этапа:
1. ✅ Git commit с описанием изменений
2. ✅ Запустить тесты: `php artisan test`
3. ✅ Проверить в браузере основные функции
4. ✅ Создать точку восстановления БД

### 🔄 Откат изменений:
```bash
# Откат к бекапу:
git checkout backup-before-profile-merge

# Восстановление БД:
mysql -u root spa_database < backups/backup_YYYYMMDD.sql

# Откат последней миграции:
php artisan migrate:rollback
```

---

## 📝 Логирование прогресса

### Этап 0: ✅ DONE (15 мин)
- Git commit создан: `b78247c5`
- Ветка бекапа: `backup-before-profile-merge`
- План сохранён в: `Docs/REFACTORING/merge-profiles-to-user-plan.md`

### Этап 1: ⏳ В РАБОТЕ
- [  ] Собрать используемые поля
- [  ] Создать маппинг
- [  ] Найти FK зависимости

---

**Дата последнего обновления:** 2025-01-03
**Версия плана:** 1.0
