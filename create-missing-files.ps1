# create-missing-files.ps1
# Скрипт для создания недостающих файлов проекта SPA.COM

Write-Host " СОЗДАНИЕ НЕДОСТАЮЩИХ ФАЙЛОВ ПРОЕКТА SPA.COM" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan

# Проверка, что мы в корне Laravel проекта
if (-not (Test-Path "artisan")) {
    Write-Host " Ошибка: Запустите скрипт из корня Laravel проекта!" -ForegroundColor Red
    exit 1
}

# Функция создания файла с содержимым
function Create-FileWithContent {
    param($Path, $Content)
    
    $dir = Split-Path $Path -Parent
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
    
    if (-not (Test-Path $Path)) {
        Set-Content -Path $Path -Value $Content -Encoding UTF8
        Write-Host " Создан: $Path" -ForegroundColor Green
    } else {
        Write-Host " Существует: $Path" -ForegroundColor Yellow
    }
}

# 1. СОЗДАЕМ НЕДОСТАЮЩИЕ МОДЕЛИ
Write-Host "`n Создание моделей..." -ForegroundColor Magenta

# Schedule.php
$scheduleModel = @'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_profile_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_working_day',
        'break_start',
        'break_end'
    ];

    protected $casts = [
        'is_working_day' => 'boolean',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'break_start' => 'datetime:H:i',
        'break_end' => 'datetime:H:i'
    ];

    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }
}
'@
Create-FileWithContent "app/Models/Schedule.php" $scheduleModel

# WorkZone.php
$workZoneModel = @'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_profile_id',
        'district',
        'metro_station',
        'radius_km',
        'extra_charge',
        'is_primary'
    ];

    protected $casts = [
        'radius_km' => 'integer',
        'extra_charge' => 'decimal:2',
        'is_primary' => 'boolean'
    ];

    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }
}
'@
Create-FileWithContent "app/Models/WorkZone.php" $workZoneModel

# 2. СОЗДАЕМ НЕДОСТАЮЩИЕ КОНТРОЛЛЕРЫ
Write-Host "`n Создание контроллеров..." -ForegroundColor Magenta

$reviewController = @'
<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = Review::with(['client', 'masterProfile', 'service'])
            ->where('client_id', auth()->id())
            ->latest()
            ->paginate(10);

        return Inertia::render('Reviews/Index', [
            'reviews' => $reviews
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string|min:10|max:1000',
            'would_recommend' => 'boolean'
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        
        // Проверяем право оставить отзыв
        if ($booking->client_id !== auth()->id() || $booking->status !== 'completed') {
            abort(403);
        }

        $review = Review::create([
            ...$validated,
            'client_id' => auth()->id(),
            'master_profile_id' => $booking->master_profile_id,
            'service_id' => $booking->service_id,
            'rating_overall' => $validated['rating'],
            'rating_quality' => $validated['rating'],
            'rating_punctuality' => $validated['rating'],
            'rating_communication' => $validated['rating'],
            'rating_price_quality' => $validated['rating']
        ]);

        return redirect()->back()->with('success', 'Спасибо за ваш отзыв!');
    }
}
'@
Create-FileWithContent "app/Http/Controllers/ReviewController.php" $reviewController

# 3. СОЗДАЕМ НЕДОСТАЮЩИЕ VUE СТРАНИЦЫ
Write-Host "`n Создание Vue страниц..." -ForegroundColor Magenta

# Masters/Create.vue
$mastersCreate = @'
<template>
    <AppLayout>
        <Head title="Стать мастером" />
        
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-2xl mx-auto">
                <h1 class="text-3xl font-bold mb-8">Стать мастером на SPA.COM</h1>
                
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Основная информация -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-semibold mb-4">Основная информация</h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Имя для клиентов</label>
                                <input v-model="form.display_name" type="text" class="w-full border-gray-300 rounded-lg" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">О себе</label>
                                <textarea v-model="form.bio" rows="4" class="w-full border-gray-300 rounded-lg" required></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg">
                        Создать профиль
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const form = useForm({
    display_name: '',
    bio: '',
    phone: '',
    city: 'Москва',
    district: '',
    home_service: true,
    salon_service: false
})

const submit = () => {
    form.post(route('masters.store'))
}
</script>
'@
Create-FileWithContent "resources/js/Pages/Masters/Create.vue" $mastersCreate

# 4. СОЗДАЕМ МИГРАЦИИ ДЛЯ НЕДОСТАЮЩИХ ТАБЛИЦ
Write-Host "`n Создание миграций..." -ForegroundColor Magenta

# Используем artisan для создания миграций
Write-Host "Создание миграций через artisan..." -ForegroundColor Yellow
php artisan make:migration create_schedules_table --create=schedules 2>$null
php artisan make:migration create_work_zones_table --create=work_zones 2>$null

# 5. СОЗДАЕМ API ROUTES
Write-Host "`n Обновление API маршрутов..." -ForegroundColor Magenta

$apiRoutes = @'
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    MasterApiController,
    ServiceApiController,
    BookingApiController,
    ReviewApiController,
    SearchApiController
};

// Публичные маршруты
Route::prefix('v1')->group(function () {
    // Поиск и каталог
    Route::get('/masters', [MasterApiController::class, 'index']);
    Route::get('/masters/{master}', [MasterApiController::class, 'show']);
    Route::get('/services', [ServiceApiController::class, 'index']);
    Route::get('/categories', [ServiceApiController::class, 'categories']);
    
    // Поиск
    Route::get('/search', [SearchApiController::class, 'search']);
    Route::get('/search/suggestions', [SearchApiController::class, 'suggestions']);
});

// Защищенные маршруты
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Профиль пользователя
    Route::get('/user', fn(Request $request) => $request->user());
    
    // Бронирования
    Route::apiResource('bookings', BookingApiController::class);
    Route::post('/bookings/{booking}/cancel', [BookingApiController::class, 'cancel']);
    
    // Отзывы
    Route::apiResource('reviews', ReviewApiController::class)->only(['store', 'update']);
    
    // Избранное
    Route::post('/favorites/{master}', [MasterApiController::class, 'addToFavorites']);
    Route::delete('/favorites/{master}', [MasterApiController::class, 'removeFromFavorites']);
});
'@
Create-FileWithContent "routes/api.php" $apiRoutes

# 6. СОЗДАЕМ СТРУКТУРУ ПАПОК
Write-Host "`n Создание структуры папок..." -ForegroundColor Magenta

$folders = @(
    "app/Http/Controllers/Api",
    "resources/js/Pages/Bookings",
    "resources/js/Pages/Reviews", 
    "resources/js/Pages/Search",
    "resources/js/Pages/Admin",
    "resources/js/Components/UI",
    "resources/js/Components/Forms",
    "storage/app/public/avatars",
    "storage/app/public/certificates",
    "storage/app/public/services"
)

foreach ($folder in $folders) {
    if (-not (Test-Path $folder)) {
        New-Item -ItemType Directory -Path $folder -Force | Out-Null
        Write-Host " Создана папка: $folder" -ForegroundColor Green
    }
}

# ФИНАЛЬНЫЙ ОТЧЕТ
Write-Host "`n ГОТОВО!" -ForegroundColor Green
Write-Host "Теперь выполните:" -ForegroundColor Yellow
Write-Host "1. php artisan migrate" -ForegroundColor White
Write-Host "2. npm run dev" -ForegroundColor White
Write-Host "3. php artisan project:status --check" -ForegroundColor White
'@

###  **Решение 2: Расширение команды ProjectStatus**

Добавьте в `app/Console/Commands/ProjectStatus.php` новый метод:

```php
// Добавить в ProjectStatus.php новую опцию
protected $signature = 'project:status 
                        {--update= : Обновить статус задачи}
                        {--check : Автоматически проверить файлы}
                        {--export : Экспортировать в файл}
                        {--create-missing : Создать недостающие файлы}'; // НОВОЕ

// Добавить новый метод
private function createMissingFiles()
{
    $this->info(' Создание недостающих файлов...');
    
    $created = 0;
    $skipped = 0;
    
    // Шаблоны файлов
    $templates = [
        'app/Models/Schedule.php' => $this->getScheduleModelTemplate(),
        'app/Models/WorkZone.php' => $this->getWorkZoneModelTemplate(),
        'app/Http/Controllers/ReviewController.php' => $this->getReviewControllerTemplate(),
        // ... другие файлы
    ];
    
    foreach ($templates as $path => $content) {
        if (!File::exists(base_path($path))) {
            // Создаем директорию если не существует
            $dir = dirname(base_path($path));
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            
            // Создаем файл
            File::put(base_path($path), $content);
            $this->info(" Создан: {$path}");
            $created++;
        } else {
            $this->comment(" Существует: {$path}");
            $skipped++;
        }
    }
    
    $this->info("\n Результат:");
    $this->info(" Создано файлов: {$created}");
    $this->info(" Пропущено (уже существуют): {$skipped}");
    
    // Создаем миграции через artisan
    if (!glob(database_path('migrations/*_create_schedules_table.php'))) {
        $this->call('make:migration', ['name' => 'create_schedules_table', '--create' => 'schedules']);
    }
    
    if (!glob(database_path('migrations/*_create_work_zones_table.php'))) {
        $this->call('make:migration', ['name' => 'create_work_zones_table', '--create' => 'work_zones']);
    }
}

// В методе handle() добавить:
if ($this->option('create-missing')) {
    $this->createMissingFiles();
    return;
}