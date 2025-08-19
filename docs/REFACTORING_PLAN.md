# 📋 ПЛАН РЕФАКТОРИНГА КОНТРОЛЛЕРОВ

## 🎯 ЦЕЛЬ РЕФАКТОРИНГА
Вынести всю бизнес-логику из контроллеров в сервисный слой согласно принципам Clean Architecture и DDD.

## 🚨 ТЕКУЩИЕ ПРОБЛЕМЫ

### Критические нарушения Clean Architecture:
- **HomeController**: 302 строки, из них 86 строк бизнес-логики
- **MasterController**: 493 строки, из них 200+ строк бизнес-логики
- Парсинг JSON прямо в контроллерах
- Форматирование данных в контроллерах
- Вычисления и трансформации в контроллерах

## 📊 НОВАЯ АРХИТЕКТУРА СЕРВИСОВ

### 1. Создаваемые сервисы и DTOs

```
app/Domain/Ad/
├── Services/
│   ├── AdTransformService.php      # Трансформация данных объявлений
│   ├── AdGeoService.php           # Работа с геолокацией
│   └── AdPricingService.php       # Работа с ценами
├── DTOs/
│   ├── AdMapDTO.php               # DTO для карты
│   └── AdHomePageDTO.php          # DTO для главной

app/Domain/Master/
├── Services/
│   ├── MasterGalleryService.php   # Работа с галереей
│   ├── MasterDTOBuilder.php       # Построение DTO
│   └── MasterApiService.php       # API трансформации
├── DTOs/
│   ├── MasterProfileDTO.php       # DTO профиля
│   └── MasterApiDTO.php           # DTO для API
```

## 🔧 ПЛАН РЕФАКТОРИНГА HomeController

### ШАГ 1: Создать AdTransformService

```php
<?php
namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\DTOs\AdHomePageDTO;
use Illuminate\Support\Collection;

class AdTransformService
{
    public function __construct(
        private AdGeoService $geoService,
        private AdPricingService $pricingService
    ) {}
    
    /**
     * Трансформация объявлений для главной страницы
     */
    public function transformForHomePage(Collection $ads): Collection
    {
        return $ads->map(fn($ad) => $this->transformSingleAd($ad));
    }
    
    private function transformSingleAd(Ad $ad): AdHomePageDTO
    {
        $coordinates = $this->geoService->extractCoordinates($ad->geo);
        $pricing = $this->pricingService->extractPricing($ad->prices, $ad->price);
        $photos = $this->extractPhotos($ad->photos);
        $services = $this->extractServices($ad->services);
        
        return new AdHomePageDTO([
            'id' => $ad->id,
            'name' => $ad->title ?? 'Мастер',
            'photo' => $photos['main'],
            'rating' => 4.5,
            'reviews_count' => 0,
            'price_from' => $pricing['min'],
            'services' => $services,
            'district' => $coordinates['district'] ?? 'Центральный район',
            'address' => $ad->address,
            'lat' => $coordinates['lat'],
            'lng' => $coordinates['lng']
        ]);
    }
    
    private function extractPhotos($photos): array
    {
        if (!$photos) {
            return ['main' => '/images/no-photo.svg'];
        }
        
        $photosData = is_string($photos) ? json_decode($photos, true) : $photos;
        
        if (!is_array($photosData) || empty($photosData)) {
            return ['main' => '/images/no-photo.svg'];
        }
        
        $firstPhoto = $photosData[0];
        
        if (is_array($firstPhoto)) {
            $main = $firstPhoto['preview'] ?? $firstPhoto['url'] ?? $firstPhoto['src'] ?? '/images/no-photo.svg';
        } else {
            $main = $firstPhoto;
        }
        
        return ['main' => $main];
    }
    
    private function extractServices($services): array
    {
        if (!$services) {
            return ['Классический массаж'];
        }
        
        $servicesData = is_string($services) ? json_decode($services, true) : $services;
        
        if (!is_array($servicesData)) {
            return ['Классический массаж'];
        }
        
        return array_slice(array_keys($servicesData), 0, 3);
    }
}
```

### ШАГ 2: Создать AdGeoService

```php
<?php
namespace App\Domain\Ad\Services;

class AdGeoService
{
    /**
     * Извлечение координат из разных форматов geo
     */
    public function extractCoordinates($geo): array
    {
        if (!$geo) {
            return ['lat' => null, 'lng' => null];
        }
        
        $geoData = is_string($geo) ? json_decode($geo, true) : $geo;
        
        if (!is_array($geoData)) {
            return ['lat' => null, 'lng' => null];
        }
        
        // Поддержка разных форматов
        if (isset($geoData['lat']) && isset($geoData['lng'])) {
            return [
                'lat' => (float)$geoData['lat'],
                'lng' => (float)$geoData['lng'],
                'district' => $geoData['district'] ?? null
            ];
        }
        
        if (isset($geoData['coordinates'])) {
            return [
                'lat' => (float)$geoData['coordinates']['lat'],
                'lng' => (float)$geoData['coordinates']['lng'],
                'district' => $geoData['district'] ?? null
            ];
        }
        
        return ['lat' => null, 'lng' => null];
    }
}
```

### ШАГ 3: Создать AdPricingService

```php
<?php
namespace App\Domain\Ad\Services;

class AdPricingService
{
    /**
     * Извлечение цен из разных форматов
     */
    public function extractPricing($prices, $fallbackPrice = null): array
    {
        $defaultPrice = 2000;
        
        if (!$prices && !$fallbackPrice) {
            return ['min' => $defaultPrice, 'unit' => 'за услугу'];
        }
        
        if ($prices) {
            $pricesData = is_string($prices) ? json_decode($prices, true) : $prices;
            
            if (is_array($pricesData) && !empty($pricesData)) {
                $priceValues = array_column($pricesData, 'price');
                
                if (!empty($priceValues)) {
                    return [
                        'min' => min($priceValues),
                        'unit' => $this->detectUnit($pricesData)
                    ];
                }
            }
        }
        
        if ($fallbackPrice) {
            return ['min' => (float)$fallbackPrice, 'unit' => 'за услугу'];
        }
        
        return ['min' => $defaultPrice, 'unit' => 'за услугу'];
    }
    
    private function detectUnit(array $prices): string
    {
        foreach ($prices as $key => $value) {
            if (strpos($key, '_1h') !== false || strpos($key, '_2h') !== false) {
                return 'за час';
            }
            if (strpos($key, '_night') !== false) {
                return 'за ночь';
            }
        }
        return 'за услугу';
    }
}
```

### ШАГ 4: Рефакторинг HomeController

```php
<?php
namespace App\Application\Http\Controllers;

use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\Services\AdTransformService;
use App\Domain\Service\Services\CategoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __construct(
        private AdService $adService,
        private AdTransformService $transformer,
        private CategoryService $categoryService
    ) {}
    
    public function index(Request $request)
    {
        // Получаем активные объявления
        $ads = $this->adService->getActiveAdsForHome(12);
        
        // Трансформируем для отображения
        $transformed = $this->transformer->transformForHomePage($ads);
        
        // Если нет данных - используем тестовые
        if ($transformed->isEmpty()) {
            $transformed = collect($this->getTestMasters());
        }
        
        return Inertia::render('Home', [
            'masters' => [
                'data' => $transformed,
                'meta' => [
                    'total' => $transformed->count(),
                    'per_page' => 12,
                    'current_page' => 1
                ]
            ],
            'categories' => $this->categoryService->getActiveCategories(),
            'districts' => $this->categoryService->getDistricts(),
            'priceRange' => ['min' => 1000, 'max' => 10000],
            'currentCity' => $request->get('city', 'Москва')
        ]);
    }
    
    private function getTestMasters(): array
    {
        // Только тестовые данные без логики
        return [
            [
                'id' => 1,
                'name' => 'Анна Петрова',
                'photo' => '/images/masters/1.png',
                'rating' => 4.8,
                'reviews_count' => 47,
                'price_from' => 2500,
                'services' => ['Классический массаж', 'Релакс массаж'],
                'district' => 'Центральный',
                'metro' => 'Арбатская'
            ]
        ];
    }
}
```

## 🔧 ПЛАН РЕФАКТОРИНГА MasterController

### ШАГ 1: Создать MasterGalleryService

```php
<?php
namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Support\Helpers\ImageHelper;

class MasterGalleryService
{
    /**
     * Построение галереи мастера
     */
    public function buildGallery(MasterProfile $profile): array
    {
        $gallery = [];
        
        // Добавляем аватар
        if ($profile->avatar) {
            $gallery[] = $this->createGalleryItem(
                0, 
                $profile->avatar_url,
                'Фото ' . $profile->display_name,
                true
            );
        }
        
        // Добавляем фото
        foreach ($profile->photos as $photo) {
            $gallery[] = $this->createGalleryItem(
                $photo->id,
                ImageHelper::getImageUrl($photo->path),
                $photo->alt ?? 'Фото мастера',
                $photo->is_main ?? false
            );
        }
        
        // Если нет фото - заглушки
        if (empty($gallery)) {
            $gallery = $this->getPlaceholderGallery();
        }
        
        return $gallery;
    }
    
    private function createGalleryItem($id, $url, $alt, $isMain): array
    {
        return [
            'id' => $id,
            'url' => $url,
            'thumb' => $url,
            'alt' => $alt,
            'is_main' => $isMain
        ];
    }
    
    private function getPlaceholderGallery(): array
    {
        return collect(range(1, 4))->map(fn($i) => [
            'id' => $i,
            'url' => asset("images/placeholders/master-{$i}.jpg"),
            'thumb' => asset("images/placeholders/master-{$i}-thumb.jpg"),
            'alt' => "Фото {$i}",
            'is_main' => $i === 1
        ])->toArray();
    }
}
```

### ШАГ 2: Создать MasterDTOBuilder

```php
<?php
namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\DTOs\MasterProfileDTO;

class MasterDTOBuilder
{
    public function __construct(
        private MasterGalleryService $galleryService,
        private MasterService $masterService
    ) {}
    
    /**
     * Построение DTO профиля мастера
     */
    public function buildProfileDTO(MasterProfile $profile, ?int $userId = null): array
    {
        $isFavorite = $userId 
            ? $this->masterService->isFavorite($profile->id, $userId)
            : false;
            
        $priceRange = $this->calculatePriceRange($profile);
        
        return [
            'id' => $profile->id,
            'name' => $profile->display_name,
            'slug' => $profile->slug,
            'bio' => $profile->bio,
            'experience_years' => $profile->experience_years,
            'rating' => (float)$profile->rating,
            'reviews_count' => $profile->reviews_count,
            'views_count' => $profile->views_count,
            'price_from' => $priceRange['min'],
            'price_to' => $priceRange['max'],
            'avatar' => $profile->avatar_url,
            'is_available_now' => $profile->isAvailableNow(),
            'is_favorite' => $isFavorite,
            'is_verified' => $profile->is_verified,
            'is_premium' => $profile->isPremium(),
            'phone' => $profile->show_contacts ? $profile->phone : null,
            'whatsapp' => $profile->whatsapp,
            'telegram' => $profile->telegram,
            'show_contacts' => $profile->show_contacts,
            'city' => $profile->city,
            'district' => $profile->district,
            'metro_station' => $profile->metro_station,
            'age' => $profile->age,
            'height' => $profile->height,
            'weight' => $profile->weight,
            'breast_size' => $profile->breast_size,
            'gallery' => $this->galleryService->buildGallery($profile),
            'services' => $this->buildServices($profile),
            'schedules' => $this->buildSchedules($profile),
            'reviews' => $this->buildReviews($profile)
        ];
    }
    
    /**
     * Построение мета-тегов
     */
    public function buildMeta(MasterProfile $profile): array
    {
        return [
            'title' => $profile->meta_title,
            'description' => $profile->meta_description,
            'keywords' => $this->buildKeywords($profile),
            'og:title' => $profile->meta_title,
            'og:description' => $profile->meta_description,
            'og:image' => $profile->avatar_url ?? asset('images/default-master.jpg'),
            'og:url' => $profile->url,
            'og:type' => 'profile'
        ];
    }
    
    private function calculatePriceRange(MasterProfile $profile): array
    {
        if (!$profile->services || $profile->services->isEmpty()) {
            return ['min' => 0, 'max' => 0];
        }
        
        return [
            'min' => $profile->services->min('price'),
            'max' => $profile->services->max('price')
        ];
    }
    
    private function buildServices($profile)
    {
        if (!$profile->services || $profile->services->isEmpty()) {
            return collect([]);
        }
        
        return $profile->services->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'category' => $s->category->name ?? 'Массаж',
            'price' => $s->price,
            'duration' => $s->duration,
            'description' => $s->description
        ]);
    }
    
    private function buildSchedules($profile)
    {
        if (!$profile->schedules || $profile->schedules->isEmpty()) {
            return collect([]);
        }
        
        return $profile->schedules->map(fn($sch) => [
            'id' => $sch->id,
            'day_of_week' => $sch->day_of_week,
            'start_time' => $sch->start_time,
            'end_time' => $sch->end_time,
            'is_working_day' => $sch->is_working_day ?? true
        ]);
    }
    
    private function buildReviews($profile)
    {
        if (!$profile->reviews || $profile->reviews->isEmpty()) {
            return collect([]);
        }
        
        return $profile->reviews->take(5)->map(fn($r) => [
            'id' => $r->id,
            'rating' => $r->rating_overall ?? $r->rating,
            'comment' => $r->comment,
            'client_name' => $r->user->name ?? 'Анонимный клиент',
            'created_at' => $r->created_at
        ]);
    }
    
    private function buildKeywords(MasterProfile $profile): string
    {
        return implode(', ', [
            $profile->display_name,
            'массаж',
            $profile->city,
            $profile->district,
            'массажист'
        ]);
    }
}
```

### ШАГ 3: Создать MasterApiService

```php
<?php
namespace App\Domain\Master\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\AdGeoService;
use App\Domain\Ad\Services\AdPricingService;
use Illuminate\Support\Collection;

class MasterApiService
{
    public function __construct(
        private AdGeoService $geoService,
        private AdPricingService $pricingService
    ) {}
    
    /**
     * Получение отфильтрованных объявлений
     */
    public function getFilteredAds(array $filters): Collection
    {
        $query = Ad::query()
            ->where('status', 'active')
            ->whereNotNull('geo')
            ->where('geo', '!=', '[]')
            ->where('geo', '!=', '{}');
        
        if (isset($filters['city'])) {
            $query->where('address', 'LIKE', '%' . $filters['city'] . '%');
        }
        
        if (isset($filters['search'])) {
            $query->where('title', 'LIKE', '%' . $filters['search'] . '%');
        }
        
        $sort = $filters['sort'] ?? 'relevance';
        switch ($sort) {
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            case 'price':
                $query->orderByRaw('CAST(JSON_EXTRACT(price, "$.min") AS UNSIGNED) ASC');
                break;
            default:
                $query->orderByDesc('created_at');
        }
        
        return $query->with('user')->get();
    }
    
    /**
     * Трансформация для API
     */
    public function transformForApi(Collection $ads): Collection
    {
        return $ads->map(function ($ad) {
            $coordinates = $this->geoService->extractCoordinates($ad->geo);
            
            if (!$coordinates['lat'] || !$coordinates['lng']) {
                return null;
            }
            
            $pricing = $this->pricingService->extractPricing($ad->prices, $ad->price);
            
            return [
                'id' => $ad->id,
                'name' => $this->extractUserName($ad),
                'photo' => $this->extractPhoto($ad->photos),
                'address' => $ad->address,
                'rating' => 4.5 + (rand(0, 50) / 100),
                'reviews_count' => rand(5, 150),
                'price_from' => $pricing['min'],
                'price_unit' => $pricing['unit'],
                'days_ago' => $this->calculateDaysAgo($ad->created_at),
                'services' => $this->extractServices($ad->services),
                'is_online' => true,
                'is_premium' => false,
                'is_verified' => true,
                'coordinates' => [
                    'lat' => $coordinates['lat'],
                    'lng' => $coordinates['lng']
                ]
            ];
        })->filter()->values();
    }
    
    private function extractUserName($ad): string
    {
        if ($ad->user) {
            return $ad->user->name ?: $ad->user->email;
        }
        return 'Массажист';
    }
    
    private function extractPhoto($photos): ?string
    {
        if (!$photos) {
            return null;
        }
        
        $photosData = is_string($photos) ? json_decode($photos, true) : $photos;
        
        if (is_array($photosData) && !empty($photosData)) {
            return $photosData[0];
        }
        
        return null;
    }
    
    private function calculateDaysAgo($createdAt): int
    {
        if (!$createdAt) {
            return 0;
        }
        
        return floor($createdAt->diffInDays(now()));
    }
    
    private function extractServices($services): array
    {
        if (!$services) {
            return [];
        }
        
        $servicesData = is_string($services) ? json_decode($services, true) : $services;
        
        if (!is_array($servicesData)) {
            return [];
        }
        
        $result = [];
        foreach ($servicesData as $serviceGroup) {
            if (is_array($serviceGroup) && isset($serviceGroup['services'])) {
                foreach ($serviceGroup['services'] as $service) {
                    if (is_array($service) && isset($service['name'])) {
                        $result[] = ['name' => $service['name']];
                    }
                }
            }
        }
        
        return $result;
    }
}
```

### ШАГ 4: Рефакторинг MasterController

```php
<?php
namespace App\Application\Http\Controllers;

use App\Domain\Master\Services\MasterService;
use App\Domain\Master\Services\MasterDTOBuilder;
use App\Domain\Master\Services\MasterApiService;
use App\Domain\Master\DTOs\UpdateMasterDTO;
use App\Application\Http\Requests\UpdateMasterRequest;
use App\Domain\Master\Models\MasterProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterController extends Controller
{
    public function __construct(
        private MasterService $masterService,
        private MasterDTOBuilder $dtoBuilder,
        private MasterApiService $apiService
    ) {}
    
    /**
     * Публичная карточка мастера
     */
    public function show(string $slug, int $master)
    {
        // Загружаем профиль
        $profile = $this->masterService->findWithRelations($master);
        
        // Проверяем SEO-URL
        if (!$this->masterService->isValidSlug($profile, $slug)) {
            return redirect()->route('masters.show', [
                'slug' => $profile->slug,
                'master' => $profile->id,
            ], 301);
        }
        
        // Обновляем метрики
        $this->masterService->ensureMetaTags($profile);
        $this->masterService->incrementViews($profile);
        
        // Строим DTO
        $masterDTO = $this->dtoBuilder->buildProfileDTO($profile, auth()->id());
        $meta = $this->dtoBuilder->buildMeta($profile);
        
        return Inertia::render('Masters/Show', [
            'master' => $masterDTO,
            'meta' => $meta,
            'similarMasters' => $this->masterService->getSimilarMasters(
                $profile->id, 
                $profile->city, 
                5
            ),
            'canReview' => auth()->check()
        ]);
    }
    
    /**
     * Страница редактирования профиля мастера
     */
    public function edit(MasterProfile $master)
    {
        $this->authorize('update', $master);
        
        $master->load(['photos', 'videos']);
        
        return Inertia::render('Masters/Edit', [
            'master' => $this->dtoBuilder->buildEditDTO($master)
        ]);
    }
    
    /**
     * Обновление профиля мастера
     */
    public function update(UpdateMasterRequest $request, MasterProfile $master)
    {
        $this->authorize('update', $master);
        
        try {
            $dto = UpdateMasterDTO::fromRequest($request->validated());
            $this->masterService->updateProfile($master->id, $dto);
            
            return redirect()->back()->with('success', 'Профиль обновлен успешно!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => 'Ошибка при обновлении профиля: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * API: Список мастеров
     */
    public function apiIndex(Request $request)
    {
        try {
            $filters = $request->only(['city', 'search', 'sort']);
            $ads = $this->apiService->getFilteredAds($filters);
            $transformed = $this->apiService->transformForApi($ads);
            
            return response()->json([
                'data' => $transformed,
                'total' => $transformed->count()
            ]);
        } catch (\Exception $e) {
            \Log::error('Ошибка в MasterController::apiIndex: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка загрузки объявлений',
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
    
    /**
     * API: Данные конкретного мастера
     */
    public function apiShow(int $master)
    {
        try {
            $profile = $this->masterService->findWithRelations($master);
            $dto = $this->dtoBuilder->buildApiDTO($profile);
            
            return response()->json($dto);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Мастер не найден',
                'message' => $e->getMessage()
            ], 404);
        }
    }
}
```

## 📅 ПЛАН МИГРАЦИИ

### Неделя 1: Подготовка (5 дней)

#### День 1-2: Создание сервисов
- [ ] Создать AdTransformService
- [ ] Создать AdGeoService  
- [ ] Создать AdPricingService
- [ ] Создать MasterGalleryService
- [ ] Создать MasterDTOBuilder
- [ ] Создать MasterApiService

#### День 3: Создание DTOs
- [ ] Создать AdHomePageDTO
- [ ] Создать AdMapDTO
- [ ] Создать MasterProfileDTO
- [ ] Создать MasterApiDTO

#### День 4: Написание тестов
- [ ] Unit-тесты для AdTransformService
- [ ] Unit-тесты для AdGeoService
- [ ] Unit-тесты для AdPricingService
- [ ] Unit-тесты для MasterGalleryService
- [ ] Unit-тесты для MasterDTOBuilder

#### День 5: Интеграционное тестирование
- [ ] Тесты интеграции сервисов
- [ ] Тесты производительности

### Неделя 2: Миграция (5 дней)

#### День 1: Рефакторинг HomeController
- [ ] Внедрить AdTransformService
- [ ] Упростить метод index()
- [ ] Удалить бизнес-логику

#### День 2: Тестирование HomeController
- [ ] Функциональные тесты
- [ ] Регрессионное тестирование
- [ ] Проверка в браузере

#### День 3: Рефакторинг MasterController::show
- [ ] Внедрить MasterDTOBuilder
- [ ] Упростить метод show()
- [ ] Удалить формирование галереи

#### День 4: Рефакторинг MasterController::apiIndex
- [ ] Внедрить MasterApiService
- [ ] Упростить метод apiIndex()
- [ ] Удалить парсинг JSON

#### День 5: Финальное тестирование
- [ ] Полное регрессионное тестирование
- [ ] Нагрузочное тестирование
- [ ] Деплой на staging

## ✅ ОЖИДАЕМЫЕ РЕЗУЛЬТАТЫ

### Метрики до и после рефакторинга

| Метрика | До | После | Улучшение |
|---------|-----|-------|-----------|
| Строк в HomeController | 302 | ~50 | -83% |
| Строк в MasterController | 493 | ~100 | -80% |
| Бизнес-логика в контроллерах | 300+ строк | 0 | -100% |
| Количество методов в контроллере | 8-10 | 3-5 | -50% |
| Покрытие тестами | 0% | 80%+ | +80% |
| Время выполнения запросов | ~200ms | ~100ms | -50% |
| Соответствие Clean Architecture | ❌ | ✅ | 100% |
| Соответствие DDD | ❌ | ✅ | 100% |

## 🚀 КОМАНДЫ ДЛЯ ЗАПУСКА

### Создание файлов сервисов
```bash
# Создание сервисов для домена Ad
php artisan make:service Domain/Ad/Services/AdTransformService
php artisan make:service Domain/Ad/Services/AdGeoService
php artisan make:service Domain/Ad/Services/AdPricingService

# Создание сервисов для домена Master
php artisan make:service Domain/Master/Services/MasterGalleryService
php artisan make:service Domain/Master/Services/MasterDTOBuilder
php artisan make:service Domain/Master/Services/MasterApiService

# Создание DTOs
php artisan make:dto Domain/Ad/DTOs/AdHomePageDTO
php artisan make:dto Domain/Ad/DTOs/AdMapDTO
php artisan make:dto Domain/Master/DTOs/MasterProfileDTO
php artisan make:dto Domain/Master/DTOs/MasterApiDTO
```

### Запуск тестов
```bash
# Unit-тесты
php artisan test --filter=AdTransformServiceTest
php artisan test --filter=MasterDTOBuilderTest

# Интеграционные тесты
php artisan test --filter=HomeControllerTest
php artisan test --filter=MasterControllerTest

# Все тесты
php artisan test
```

## 📝 КОНТРОЛЬНЫЙ ЧЕКЛИСТ

### Перед началом рефакторинга
- [ ] Создан бэкап текущего кода
- [ ] Написаны тесты для текущей функциональности
- [ ] Задокументированы все API endpoints
- [ ] Проверена совместимость с frontend

### После завершения рефакторинга
- [ ] Все тесты проходят успешно
- [ ] Контроллеры не содержат бизнес-логики
- [ ] Сервисы покрыты тестами на 80%+
- [ ] Документация обновлена
- [ ] Code review пройден
- [ ] Производительность не ухудшилась

## 🎯 КРИТЕРИИ УСПЕХА

1. **Технические:**
   - Контроллеры содержат только HTTP-логику
   - Вся бизнес-логика в сервисном слое
   - Код соответствует SOLID принципам
   - Покрытие тестами > 80%

2. **Архитектурные:**
   - Соответствие Clean Architecture
   - Соответствие Domain-Driven Design
   - Четкое разделение ответственности
   - Переиспользуемые компоненты

3. **Производительность:**
   - Время ответа < 100ms
   - Потребление памяти не увеличилось
   - Количество запросов к БД оптимизировано

## 📚 ДОПОЛНИТЕЛЬНЫЕ МАТЕРИАЛЫ

- [Clean Architecture в Laravel](https://laravel-news.com/clean-architecture-laravel)
- [Domain-Driven Design](https://martinfowler.com/tags/domain%20driven%20design.html)
- [SOLID принципы](https://en.wikipedia.org/wiki/SOLID)
- [Паттерны рефакторинга](https://refactoring.guru/refactoring)