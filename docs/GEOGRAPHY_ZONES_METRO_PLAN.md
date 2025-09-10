# 🚀 ДЕТАЛЬНЫЙ ПЛАН: Геолокационная система с районами и метро по принципу Avito

**Создано:** 09.09.2025  
**Автор:** AI Development Team  
**Версия:** 1.0  
**Статус:** Готов к реализации

---

## 🎯 ОБЩАЯ ЗАДАЧА

Реализовать полноценную геолокационную систему, которая:
- **Автоматически определяет город** из введенного адреса через геокодинг
- **Показывает "В выбранные зоны"** только для крупных городов (как на Avito)
- **Подгружает актуальные районы** для каждого города автоматически
- **Показывает станции метро** только для городов с метрополитеном
- **Работает точно как на Avito** - знакомое UX для пользователей

### 🎯 Бизнес-цель
Повысить удобство размещения объявлений и точность геолокации услуг, что приведет к:
- Увеличению конверсии размещения объявлений на 15-20%
- Улучшению качества геоданных пользователей
- Снижению количества некорректно размещенных объявлений

---

## 📋 ЭТАПЫ РЕАЛИЗАЦИИ

### ЭТАП 1: База данных и справочники (Backend) 📅 Неделя 1

#### 1.1 Создание миграций
**Файл:** `database/migrations/2025_01_01_000001_create_geography_tables.php`

```sql
-- Справочник городов России
CREATE TABLE cities (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL UNIQUE,
  region VARCHAR(255) NOT NULL,
  has_metro BOOLEAN DEFAULT FALSE,
  has_districts BOOLEAN DEFAULT TRUE,
  population INTEGER UNSIGNED,
  latitude DECIMAL(10,8),
  longitude DECIMAL(11,8),
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_cities_name (name),
  INDEX idx_cities_has_metro (has_metro),
  INDEX idx_cities_population (population)
);

-- Районы городов (административные и исторические)
CREATE TABLE city_districts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  city_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  sort_order INTEGER DEFAULT 0,
  is_central BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE,
  INDEX idx_districts_city_id (city_id),
  INDEX idx_districts_sort_order (sort_order)
);

-- Линии метрополитена
CREATE TABLE metro_lines (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  city_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  color VARCHAR(7) NOT NULL COMMENT 'HEX цвет линии',
  sort_order INTEGER DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE,
  INDEX idx_metro_lines_city_id (city_id),
  INDEX idx_metro_lines_sort_order (sort_order)
);

-- Станции метро
CREATE TABLE metro_stations (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  line_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  sort_order INTEGER DEFAULT 0,
  latitude DECIMAL(10,8) NULL,
  longitude DECIMAL(11,8) NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (line_id) REFERENCES metro_lines(id) ON DELETE CASCADE,
  INDEX idx_metro_stations_line_id (line_id),
  INDEX idx_metro_stations_sort_order (sort_order),
  INDEX idx_metro_stations_coordinates (latitude, longitude)
);
```

#### 1.2 Справочник данных
**Файл:** `database/seeders/GeographySeeder.php`

**Города-миллионники с метро (критерий >500к населения или наличие метро):**
```php
$citiesWithMetro = [
    ['name' => 'Москва', 'region' => 'Москва', 'population' => 12678079, 'has_metro' => true],
    ['name' => 'Санкт-Петербург', 'region' => 'Ленинградская область', 'population' => 5384342, 'has_metro' => true],
    ['name' => 'Новосибирск', 'region' => 'Новосибирская область', 'population' => 1633595, 'has_metro' => true],
    ['name' => 'Екатеринбург', 'region' => 'Свердловская область', 'population' => 1495066, 'has_metro' => true],
    ['name' => 'Казань', 'region' => 'Республика Татарстан', 'population' => 1257391, 'has_metro' => true],
    ['name' => 'Нижний Новгород', 'region' => 'Нижегородская область', 'population' => 1252236, 'has_metro' => true],
    ['name' => 'Самара', 'region' => 'Самарская область', 'population' => 1156644, 'has_metro' => true],
];

$largeCitiesWithoutMetro = [
    ['name' => 'Челябинск', 'region' => 'Челябинская область', 'population' => 1196680],
    ['name' => 'Омск', 'region' => 'Омская область', 'population' => 1154507],
    ['name' => 'Ростов-на-Дону', 'region' => 'Ростовская область', 'population' => 1142162],
    ['name' => 'Уфа', 'region' => 'Республика Башкортостан', 'population' => 1135727],
    ['name' => 'Красноярск', 'region' => 'Красноярский край', 'population' => 1103301],
    ['name' => 'Воронеж', 'region' => 'Воронежская область', 'population' => 1058261],
    ['name' => 'Пермь', 'region' => 'Пермский край', 'population' => 1049199],
    ['name' => 'Волгоград', 'region' => 'Волгоградская область', 'population' => 1008998],
    ['name' => 'Краснодар', 'region' => 'Краснодарский край', 'population' => 948827],
];
```

**Детальные данные по районам Москвы:**
```php
$moscowDistricts = [
    // Центральный административный округ
    'ЦАО' => [
        'Арбат', 'Басманный', 'Замоскворечье', 'Красносельский', 
        'Мещанский', 'Пресненский', 'Таганский', 'Тверской', 
        'Хамовники', 'Якиманка'
    ],
    // Северный административный округ  
    'САО' => [
        'Аэропорт', 'Беговой', 'Бескудниковский', 'Войковский', 
        'Головинский', 'Дегунино Восточное', 'Дегунино Западное', 
        'Дмитровский', 'Коптево', 'Левобережный', 'Молжаниновский', 
        'Савеловский', 'Сокол', 'Тимирязевский', 'Ховрино', 'Хорошевский'
    ],
    // ... остальные 10 округов
];
```

**Полные данные метро Москвы:**
```php
$moscowMetroLines = [
    ['name' => 'Сокольническая', 'color' => '#E42313', 'stations' => [
        'Бульвар Рокоссовского', 'Черкизовская', 'Преображенская площадь',
        'Сокольники', 'Красносельская', 'Комсомольская', 'Красные ворота',
        'Чистые пруды', 'Лубянка', 'Охотный ряд', 'Библиотека им. Ленина',
        'Кропоткинская', 'Парк культуры', 'Фрунзенская', 'Спортивная',
        'Воробьевы горы', 'Университет', 'Проспект Вернадского', 'Юго-Западная',
        'Тропарево', 'Румянцево', 'Саларьево'
    ]],
    ['name' => 'Замоскворецкая', 'color' => '#4F8242', 'stations' => [
        'Ховрино', 'Беломорская', 'Речной вокзал', 'Водный стадион',
        'Войковская', 'Сокол', 'Аэропорт', 'Динамо', 'Белорусская',
        'Маяковская', 'Тверская', 'Театральная', 'Новокузнецкая',
        'Павелецкая', 'Автозаводская', 'Технопарк', 'Коломенская',
        'Каширская', 'Кантемировская', 'Царицыно', 'Орехово', 'Домодедовская',
        'Красногвардейская', 'Алма-Атинская'
    ]],
    // ... остальные 12 линий
];
```

### ЭТАП 2: Backend Domain (DDD Architecture) 📅 Неделя 1-2

#### 2.1 Модели домена Geography
**Структура:** `app/Domain/Geography/`

```php
// app/Domain/Geography/Models/City.php
<?php
declare(strict_types=1);

namespace App\Domain\Geography\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * City model
 * 
 * @property int $id
 * @property string $name
 * @property string $region  
 * @property bool $has_metro
 * @property bool $has_districts
 * @property int $population
 * @property float $latitude
 * @property float $longitude
 */
class City extends Model
{
    protected $fillable = [
        'name', 'region', 'has_metro', 'has_districts', 
        'population', 'latitude', 'longitude'
    ];

    protected $casts = [
        'has_metro' => 'boolean',
        'has_districts' => 'boolean',
        'population' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    /**
     * Районы города
     */
    public function districts(): HasMany
    {
        return $this->hasMany(CityDistrict::class)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Линии метро города
     */
    public function metroLines(): HasMany  
    {
        return $this->hasMany(MetroLine::class)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Все станции метро города
     */
    public function metroStations(): HasManyThrough
    {
        return $this->hasManyThrough(MetroStation::class, MetroLine::class)
            ->orderBy('metro_lines.sort_order')
            ->orderBy('metro_stations.sort_order');
    }

    /**
     * Определить есть ли опция "В выбранные зоны"
     */
    public function hasZonesOption(): bool
    {
        return $this->population > 500000 || $this->has_metro;
    }

    /**
     * Поиск города по названию (нечеткий поиск)
     */
    public function scopeSearchByName($query, string $name)
    {
        return $query->where('name', 'LIKE', "%{$name}%")
            ->orWhere('name', 'SOUNDS LIKE', $name);
    }
}
```

```php
// app/Domain/Geography/Models/CityDistrict.php
<?php
declare(strict_types=1);

namespace App\Domain\Geography\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CityDistrict model
 */
class CityDistrict extends Model
{
    protected $fillable = ['city_id', 'name', 'sort_order', 'is_central'];

    protected $casts = [
        'city_id' => 'integer',
        'sort_order' => 'integer', 
        'is_central' => 'boolean'
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
```

```php
// app/Domain/Geography/Models/MetroLine.php
<?php
declare(strict_types=1);

namespace App\Domain\Geography\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MetroLine model
 */
class MetroLine extends Model
{
    protected $fillable = ['city_id', 'name', 'color', 'sort_order'];

    protected $casts = [
        'city_id' => 'integer',
        'sort_order' => 'integer'
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function stations(): HasMany
    {
        return $this->hasMany(MetroStation::class, 'line_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
```

```php
// app/Domain/Geography/Models/MetroStation.php  
<?php
declare(strict_types=1);

namespace App\Domain\Geography\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MetroStation model
 */
class MetroStation extends Model
{
    protected $fillable = [
        'line_id', 'name', 'sort_order', 'latitude', 'longitude'
    ];

    protected $casts = [
        'line_id' => 'integer',
        'sort_order' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    public function line(): BelongsTo
    {
        return $this->belongsTo(MetroLine::class, 'line_id');
    }
}
```

#### 2.2 Сервисы домена
```php
// app/Domain/Geography/Services/GeographyService.php
<?php
declare(strict_types=1);

namespace App\Domain\Geography\Services;

use App\Domain\Geography\Models\City;
use App\Domain\Geography\DTOs\CityGeographyDTO;
use App\Domain\Geography\DTOs\MetroDataDTO;
use App\Domain\Geography\Repositories\GeographyRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Основной сервис географических данных
 */
class GeographyService
{
    public function __construct(
        private GeographyRepository $repository,
        private CityDetectionService $cityDetectionService
    ) {}

    /**
     * Получить город по названию
     */
    public function getCityByName(string $name): ?City
    {
        return Cache::remember(
            "city_by_name_{$name}",
            3600,
            fn() => $this->repository->findCityByName($name)
        );
    }

    /**
     * Получить полные географические данные города
     */
    public function getCityData(string $cityName): ?CityGeographyDTO
    {
        return Cache::remember(
            "city_geography_{$cityName}",
            3600,
            function() use ($cityName) {
                $city = $this->repository->findCityByName($cityName);
                if (!$city) {
                    return null;
                }

                $districts = $this->repository->getCityDistricts($city->id);
                $metro = $city->has_metro ? $this->getMetroData($city->id) : null;
                
                return new CityGeographyDTO(
                    city: $city,
                    districts: $districts,
                    metro: $metro,
                    hasZonesOption: $city->hasZonesOption()
                );
            }
        );
    }

    /**
     * Получить данные метро города
     */
    public function getMetroData(int $cityId): ?MetroDataDTO
    {
        $lines = $this->repository->getCityMetroLines($cityId);
        if ($lines->isEmpty()) {
            return null;
        }

        $groupedStations = [];
        foreach ($lines as $line) {
            $groupedStations[$line->name] = $line->stations->toArray();
        }

        return new MetroDataDTO(
            lines: $lines,
            groupedStations: $groupedStations
        );
    }

    /**
     * Определить город из адреса
     */
    public function detectCityFromAddress(string $address): ?City
    {
        return $this->cityDetectionService->detectFromAddress($address);
    }
}
```

```php
// app/Domain/Geography/Services/CityDetectionService.php  
<?php
declare(strict_types=1);

namespace App\Domain\Geography\Services;

use App\Domain\Geography\Models\City;
use App\Domain\Geography\Repositories\GeographyRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Сервис определения города из адреса
 */
class CityDetectionService
{
    public function __construct(
        private GeographyRepository $repository
    ) {}

    /**
     * Определить город из адреса
     */
    public function detectFromAddress(string $address): ?City
    {
        $cacheKey = "detect_city_" . md5($address);
        
        return Cache::remember($cacheKey, 1800, function() use ($address) {
            // 1. Попробовать парсинг адреса регулярками
            $cityName = $this->parseAddressForCity($address);
            if ($cityName) {
                $city = $this->fuzzySearchCity($cityName);
                if ($city) {
                    return $city;
                }
            }

            // 2. Fallback: геокодинг через Yandex API
            return $this->fallbackGeocoding($address);
        });
    }

    /**
     * Парсинг адреса для извлечения города
     */
    private function parseAddressForCity(string $address): ?string
    {
        // Шаблоны для русских адресов
        $patterns = [
            // "г. Екатеринбург, ул. Ленина, 1"
            '/г\.\s*([А-Яа-я\s\-]+),/',
            // "Москва, Тверская улица, 1"  
            '/^([А-Яа-я\s\-]+),/',
            // "Екатеринбург, ул. Ленина, 1"
            '/^([А-Яа-я]{3,}),/',
            // "Свердловская область, Екатеринбург"
            '/область,\s*([А-Яа-я\s\-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $address, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * Нечеткий поиск города в базе
     */
    private function fuzzySearchCity(string $cityName): ?City
    {
        // Точное совпадение
        $city = $this->repository->findCityByName($cityName);
        if ($city) {
            return $city;
        }

        // Поиск с LIKE
        $cities = $this->repository->searchCitiesByName($cityName);
        if ($cities->isNotEmpty()) {
            return $cities->first();
        }

        return null;
    }

    /**
     * Геокодинг через Yandex API
     */
    private function fallbackGeocoding(string $address): ?City
    {
        try {
            $response = Http::timeout(5)->get(
                'https://geocode-maps.yandex.ru/1.x/',
                [
                    'format' => 'json',
                    'geocode' => $address,
                    'results' => 1,
                    'lang' => 'ru_RU',
                    'apikey' => config('services.yandex.geocoding_key')
                ]
            );

            $data = $response->json();
            $geoObjects = $data['response']['GeoObjectCollection']['featureMember'] ?? [];
            
            if (empty($geoObjects)) {
                return null;
            }

            $geoObject = $geoObjects[0]['GeoObject'];
            $components = $geoObject['metaDataProperty']['GeocoderMetaData']['Address']['Components'] ?? [];
            
            // Ищем компонент "locality" (город)
            foreach ($components as $component) {
                if ($component['kind'] === 'locality') {
                    return $this->fuzzySearchCity($component['name']);
                }
            }

            return null;

        } catch (\Exception $e) {
            \Log::warning('Geocoding fallback failed', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
```

#### 2.3 DTOs (Data Transfer Objects)
```php
// app/Domain/Geography/DTOs/CityGeographyDTO.php
<?php
declare(strict_types=1);

namespace App\Domain\Geography\DTOs;

use App\Domain\Geography\Models\City;
use Illuminate\Database\Eloquent\Collection;

/**
 * Полные географические данные города
 */
readonly class CityGeographyDTO
{
    public function __construct(
        public City $city,
        public Collection $districts,
        public ?MetroDataDTO $metro,
        public bool $hasZonesOption
    ) {}

    /**
     * Конвертация в массив для API
     */
    public function toArray(): array
    {
        return [
            'city' => [
                'id' => $this->city->id,
                'name' => $this->city->name,
                'region' => $this->city->region,
                'hasMetro' => $this->city->has_metro,
                'hasDistricts' => $this->city->has_districts,
                'population' => $this->city->population,
                'coordinates' => [
                    'lat' => $this->city->latitude,
                    'lng' => $this->city->longitude
                ]
            ],
            'districts' => $this->districts->map(fn($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'sortOrder' => $d->sort_order,
                'isCentral' => $d->is_central
            ])->toArray(),
            'metro' => $this->metro?->toArray(),
            'hasZonesOption' => $this->hasZonesOption
        ];
    }
}
```

```php
// app/Domain/Geography/DTOs/MetroDataDTO.php
<?php
declare(strict_types=1);

namespace App\Domain\Geography\DTOs;

use Illuminate\Database\Eloquent\Collection;

/**
 * Данные метрополитена города
 */
readonly class MetroDataDTO
{
    public function __construct(
        public Collection $lines,      // MetroLine с загруженными stations
        public array $groupedStations  // Группировка по линиям
    ) {}

    /**
     * Конвертация в массив для API
     */
    public function toArray(): array
    {
        return [
            'lines' => $this->lines->map(fn($line) => [
                'id' => $line->id,
                'name' => $line->name,
                'color' => $line->color,
                'sortOrder' => $line->sort_order,
                'stations' => $line->stations->map(fn($station) => [
                    'id' => $station->id,
                    'name' => $station->name,
                    'sortOrder' => $station->sort_order,
                    'coordinates' => $station->latitude ? [
                        'lat' => $station->latitude,
                        'lng' => $station->longitude
                    ] : null
                ])->toArray()
            ])->toArray(),
            'groupedStations' => $this->groupedStations
        ];
    }

    /**
     * Получить все станции плоским списком
     */
    public function getAllStationsFlat(): array
    {
        $stations = [];
        foreach ($this->lines as $line) {
            foreach ($line->stations as $station) {
                $stations[] = $station->name;
            }
        }
        return $stations;
    }
}
```

#### 2.4 Repository
```php
// app/Domain/Geography/Repositories/GeographyRepository.php
<?php
declare(strict_types=1);

namespace App\Domain\Geography\Repositories;

use App\Domain\Common\Repositories\BaseRepository;
use App\Domain\Geography\Models\City;
use App\Domain\Geography\Models\CityDistrict;
use App\Domain\Geography\Models\MetroLine;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository для географических данных
 */
class GeographyRepository extends BaseRepository
{
    public function __construct(City $model)
    {
        parent::__construct($model);
    }

    /**
     * Найти город по названию (точное совпадение)
     */
    public function findCityByName(string $name): ?City
    {
        return $this->model
            ->where('name', $name)
            ->first();
    }

    /**
     * Найти города по названию (нечеткий поиск)
     */
    public function searchCitiesByName(string $query): Collection
    {
        return $this->model
            ->where('name', 'LIKE', "%{$query}%")
            ->orderByRaw("CASE WHEN name = ? THEN 0 ELSE 1 END", [$query])
            ->orderBy('population', 'DESC')
            ->limit(5)
            ->get();
    }

    /**
     * Получить город с районами
     */
    public function findCityWithDistricts(int $cityId): ?City
    {
        return $this->model
            ->with(['districts' => function($query) {
                $query->orderBy('sort_order')->orderBy('name');
            }])
            ->find($cityId);
    }

    /**
     * Получить районы города
     */
    public function getCityDistricts(int $cityId): Collection
    {
        return CityDistrict::where('city_id', $cityId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Получить линии метро города со станциями
     */
    public function getCityMetroLines(int $cityId): Collection
    {
        return MetroLine::where('city_id', $cityId)
            ->with(['stations' => function($query) {
                $query->orderBy('sort_order')->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Проверить есть ли метро в городе
     */
    public function cityHasMetro(int $cityId): bool
    {
        $city = $this->find($cityId);
        return $city?->has_metro ?? false;
    }
}
```

#### 2.5 API Controllers
```php
// app/Application/Http/Controllers/Geography/GeographyController.php
<?php
declare(strict_types=1);

namespace App\Application\Http\Controllers\Geography;

use App\Application\Http\Controllers\Controller;
use App\Domain\Geography\Services\GeographyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * API контроллер географических данных
 */
class GeographyController extends Controller
{
    public function __construct(
        private GeographyService $geographyService
    ) {}

    /**
     * Определить город из адреса
     * POST /api/geography/detect-city
     */
    public function detectCity(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required|string|min:3|max:500'
        ]);

        $city = $this->geographyService->detectCityFromAddress(
            $request->input('address')
        );

        if (!$city) {
            return response()->json([
                'city' => null,
                'confidence' => 0,
                'source' => 'none'
            ]);
        }

        return response()->json([
            'city' => [
                'id' => $city->id,
                'name' => $city->name,
                'region' => $city->region,
                'hasMetro' => $city->has_metro,
                'hasDistricts' => $city->has_districts,
                'population' => $city->population,
                'coordinates' => [
                    'lat' => $city->latitude,
                    'lng' => $city->longitude
                ]
            ],
            'confidence' => 0.9,
            'source' => 'database'
        ]);
    }

    /**
     * Получить полные данные города
     * GET /api/geography/cities/{cityName}
     */
    public function getCityData(string $cityName): JsonResponse
    {
        $cityData = $this->geographyService->getCityData($cityName);

        if (!$cityData) {
            return response()->json([
                'message' => 'Город не найден'
            ], 404);
        }

        return response()->json($cityData->toArray());
    }

    /**
     * Получить районы города
     * GET /api/geography/cities/{cityId}/districts
     */
    public function getDistricts(int $cityId): JsonResponse
    {
        $districts = $this->geographyService
            ->repository
            ->getCityDistricts($cityId);

        return response()->json([
            'data' => $districts->map(fn($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'sortOrder' => $d->sort_order,
                'isCentral' => $d->is_central
            ])->toArray()
        ]);
    }

    /**
     * Получить данные метро города
     * GET /api/geography/cities/{cityId}/metro
     */
    public function getMetro(int $cityId): JsonResponse
    {
        $metroData = $this->geographyService->getMetroData($cityId);

        if (!$metroData) {
            return response()->json([
                'message' => 'В этом городе нет метро'
            ], 404);
        }

        return response()->json($metroData->toArray());
    }
}
```

**Регистрация маршрутов в** `routes/api.php`:
```php
use App\Application\Http\Controllers\Geography\GeographyController;

// Geography routes
Route::prefix('geography')->group(function () {
    Route::post('detect-city', [GeographyController::class, 'detectCity']);
    Route::get('cities/{cityName}', [GeographyController::class, 'getCityData']);
    Route::get('cities/{cityId}/districts', [GeographyController::class, 'getDistricts']);
    Route::get('cities/{cityId}/metro', [GeographyController::class, 'getMetro']);
});
```

### ЭТАП 3: Frontend Entity (FSD Architecture) 📅 Неделя 2

#### 3.1 TypeScript типы
**Файл:** `resources/js/src/entities/geography/model/types.ts`
```typescript
/**
 * Типы для географического домена
 */

export interface City {
  id: number
  name: string
  region: string
  hasMetro: boolean
  hasDistricts: boolean
  population: number
  coordinates: { lat: number; lng: number }
}

export interface District {
  id: number
  cityId?: number
  name: string
  sortOrder: number
  isCentral: boolean
}

export interface MetroLine {
  id: number
  cityId?: number
  name: string
  color: string
  sortOrder: number
  stations: MetroStation[]
}

export interface MetroStation {
  id: number
  lineId?: number
  name: string
  sortOrder: number
  coordinates?: { lat: number; lng: number }
}

export interface CityGeography {
  city: City
  districts: District[]
  metro: {
    lines: MetroLine[]
    groupedStations: Record<string, MetroStation[]>
  } | null
  hasZonesOption: boolean
}

export interface DetectCityRequest {
  address: string
}

export interface DetectCityResponse {
  city: City | null
  confidence: number // 0-1, где 1 = 100% уверенность
  source: 'database' | 'geocoding' | 'fallback' | 'none'
}

// Вспомогательные типы
export interface CitySearchResult {
  cities: City[]
  query: string
  total: number
}

export interface GeographyError {
  code: string
  message: string
  details?: Record<string, any>
}

// Константы
export const CITY_DETECTION_CONFIDENCE_THRESHOLD = 0.7
export const LARGE_CITY_POPULATION_THRESHOLD = 500000
```

#### 3.2 API клиент
**Файл:** `resources/js/src/entities/geography/api/geographyApi.ts`
```typescript
/**
 * API клиент для работы с географическими данными
 */

import { api } from '@/shared/api'
import type { 
  DetectCityRequest,
  DetectCityResponse, 
  CityGeography,
  District, 
  MetroLine,
  City
} from '../model/types'

class GeographyApiError extends Error {
  constructor(
    message: string,
    public readonly statusCode?: number,
    public readonly response?: any
  ) {
    super(message)
    this.name = 'GeographyApiError'
  }
}

export const geographyApi = {
  /**
   * Определить город из адреса
   */
  async detectCity(address: string): Promise<DetectCityResponse> {
    try {
      const response = await api.post('/api/geography/detect-city', { address })
      return response.data
    } catch (error) {
      console.warn('City detection failed:', error)
      throw new GeographyApiError(
        'Не удалось определить город',
        error.response?.status,
        error.response?.data
      )
    }
  },

  /**
   * Получить полные географические данные города
   */
  async getCityData(cityName: string): Promise<CityGeography> {
    try {
      const response = await api.get(`/api/geography/cities/${encodeURIComponent(cityName)}`)
      return response.data
    } catch (error) {
      if (error.response?.status === 404) {
        throw new GeographyApiError('Город не найден', 404)
      }
      
      console.error('Failed to load city data:', error)
      throw new GeographyApiError(
        'Не удалось загрузить данные города',
        error.response?.status,
        error.response?.data
      )
    }
  },

  /**
   * Получить районы города
   */
  async getDistricts(cityId: number): Promise<District[]> {
    try {
      const response = await api.get(`/api/geography/cities/${cityId}/districts`)
      return response.data.data || []
    } catch (error) {
      console.error('Failed to load districts:', error)
      throw new GeographyApiError(
        'Не удалось загрузить список районов',
        error.response?.status
      )
    }
  },

  /**
   * Получить данные метро города
   */
  async getMetro(cityId: number): Promise<MetroLine[]> {
    try {
      const response = await api.get(`/api/geography/cities/${cityId}/metro`)
      return response.data.lines || []
    } catch (error) {
      if (error.response?.status === 404) {
        // В городе нет метро - это нормально
        return []
      }
      
      console.error('Failed to load metro data:', error)
      throw new GeographyApiError(
        'Не удалось загрузить данные метро',
        error.response?.status
      )
    }
  },

  /**
   * Поиск городов по названию
   */
  async searchCities(query: string): Promise<City[]> {
    try {
      const response = await api.get('/api/geography/cities/search', {
        params: { q: query, limit: 10 }
      })
      return response.data.data || []
    } catch (error) {
      console.error('City search failed:', error)
      return []
    }
  }
}

// Экспорт типов ошибок
export { GeographyApiError }
```

#### 3.3 Pinia Store
**Файл:** `resources/js/src/entities/geography/model/geographyStore.ts`
```typescript
/**
 * Pinia store для управления географическими данными
 */

import { defineStore } from 'pinia'
import { ref, computed, readonly } from 'vue'
import { geographyApi, GeographyApiError } from '../api/geographyApi'
import type { 
  City, 
  District, 
  MetroLine, 
  CityGeography, 
  DetectCityResponse,
  CITY_DETECTION_CONFIDENCE_THRESHOLD,
  LARGE_CITY_POPULATION_THRESHOLD
} from './types'

interface CacheEntry<T> {
  data: T
  timestamp: number
  ttl: number
}

class MemoryCache<T> {
  private cache = new Map<string, CacheEntry<T>>()

  set(key: string, data: T, ttlMinutes = 60): void {
    this.cache.set(key, {
      data,
      timestamp: Date.now(),
      ttl: ttlMinutes * 60 * 1000
    })
  }

  get(key: string): T | null {
    const entry = this.cache.get(key)
    if (!entry) return null

    if (Date.now() - entry.timestamp > entry.ttl) {
      this.cache.delete(key)
      return null
    }

    return entry.data
  }

  clear(): void {
    this.cache.clear()
  }

  has(key: string): boolean {
    return this.get(key) !== null
  }
}

export const useGeographyStore = defineStore('geography', () => {
  // State
  const currentCity = ref<City | null>(null)
  const availableDistricts = ref<District[]>([])
  const availableMetro = ref<MetroLine[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Cache
  const cityCache = new MemoryCache<CityGeography>()
  const detectionCache = new MemoryCache<DetectCityResponse>()

  // Getters
  const hasMetro = computed(() => currentCity.value?.hasMetro ?? false)
  
  const hasZonesOption = computed(() => {
    if (!currentCity.value) return false
    
    return (
      currentCity.value.population > 500000 || // крупный город
      currentCity.value.hasMetro // или есть метро
    )
  })

  const districtsByName = computed(() => 
    availableDistricts.value.reduce((acc, district) => {
      acc[district.name] = district
      return acc
    }, {} as Record<string, District>)
  )

  const metroStationsByLine = computed(() => 
    availableMetro.value.reduce((acc, line) => {
      acc[line.name] = line.stations
      return acc
    }, {} as Record<string, MetroStation[]>)
  )

  const allMetroStations = computed(() => 
    availableMetro.value.flatMap(line => 
      line.stations.map(station => station.name)
    )
  )

  const cityInfo = computed(() => {
    if (!currentCity.value) return null

    return {
      name: currentCity.value.name,
      region: currentCity.value.region,
      population: currentCity.value.population,
      hasMetro: currentCity.value.hasMetro,
      hasZonesOption: hasZonesOption.value,
      coordinates: currentCity.value.coordinates
    }
  })

  // Actions

  /**
   * Определить город из адреса с кэшированием
   */
  const detectCityFromAddress = async (address: string): Promise<City | null> => {
    if (!address || address.length < 5) {
      return null
    }

    const cacheKey = `detect_${address.toLowerCase().trim()}`
    
    // Проверяем кэш
    const cached = detectionCache.get(cacheKey)
    if (cached) {
      if (cached.city && cached.confidence > 0.7) {
        await loadCityData(cached.city.name)
        return cached.city
      }
      return null
    }

    try {
      clearError()
      const result = await geographyApi.detectCity(address)
      
      // Кэшируем результат на 30 минут
      detectionCache.set(cacheKey, result, 30)
      
      if (result.city && result.confidence > 0.7) {
        await loadCityData(result.city.name)
        return result.city
      }

      return null
    } catch (error) {
      console.warn('City detection failed:', error)
      if (error instanceof GeographyApiError) {
        setError(`Ошибка определения города: ${error.message}`)
      } else {
        setError('Не удалось определить город из адреса')
      }
      return null
    }
  }

  /**
   * Загрузить полные данные города
   */
  const loadCityData = async (cityName: string): Promise<void> => {
    const cacheKey = `city_${cityName.toLowerCase()}`
    
    // Проверяем кэш
    const cached = cityCache.get(cacheKey)
    if (cached) {
      updateStateFromCityData(cached)
      return
    }

    try {
      setLoading(true)
      clearError()
      
      const cityData = await geographyApi.getCityData(cityName)
      
      // Кэшируем на 60 минут
      cityCache.set(cacheKey, cityData, 60)
      
      updateStateFromCityData(cityData)
      
    } catch (error) {
      console.error('Failed to load city data:', error)
      
      if (error instanceof GeographyApiError) {
        setError(error.message)
      } else {
        setError('Не удалось загрузить данные города')
      }
      
      // Очищаем состояние при ошибке
      resetState()
    } finally {
      setLoading(false)
    }
  }

  /**
   * Обновить состояние из данных города
   */
  const updateStateFromCityData = (cityData: CityGeography): void => {
    currentCity.value = cityData.city
    availableDistricts.value = cityData.districts
    availableMetro.value = cityData.metro?.lines || []
  }

  /**
   * Предзагрузка данных популярных городов
   */
  const preloadPopularCities = async (): Promise<void> => {
    const popularCities = [
      'Москва', 'Санкт-Петербург', 'Новосибирск', 
      'Екатеринбург', 'Казань', 'Нижний Новгород'
    ]

    // Запускаем загрузку параллельно, но не ждем завершения
    popularCities.forEach(cityName => {
      if (!cityCache.has(`city_${cityName.toLowerCase()}`)) {
        loadCityData(cityName).catch(error => {
          console.warn(`Failed to preload ${cityName}:`, error)
        })
      }
    })
  }

  /**
   * Очистить текущий город
   */
  const clearCity = (): void => {
    currentCity.value = null
    availableDistricts.value = []
    availableMetro.value = []
    clearError()
  }

  /**
   * Сбросить состояние
   */
  const resetState = (): void => {
    currentCity.value = null
    availableDistricts.value = []
    availableMetro.value = []
    error.value = null
    isLoading.value = false
  }

  /**
   * Очистить весь кэш
   */
  const clearCache = (): void => {
    cityCache.clear()
    detectionCache.clear()
  }

  /**
   * Установить ошибку
   */
  const setError = (message: string): void => {
    error.value = message
  }

  /**
   * Очистить ошибку
   */
  const clearError = (): void => {
    error.value = null
  }

  /**
   * Установить состояние загрузки
   */
  const setLoading = (loading: boolean): void => {
    isLoading.value = loading
  }

  // Public API
  return {
    // State (readonly)
    currentCity: readonly(currentCity),
    availableDistricts: readonly(availableDistricts),
    availableMetro: readonly(availableMetro),
    isLoading: readonly(isLoading),
    error: readonly(error),
    
    // Getters
    hasMetro,
    hasZonesOption,
    districtsByName,
    metroStationsByLine,
    allMetroStations,
    cityInfo,
    
    // Actions
    detectCityFromAddress,
    loadCityData,
    preloadPopularCities,
    clearCity,
    resetState,
    clearCache,
    clearError
  }
})

// Экспортируем тип store
export type GeographyStore = ReturnType<typeof useGeographyStore>
```

### ЭТАП 4: Обновление существующих компонентов 📅 Неделя 3

#### 4.1 Обновление useGeoData.ts
**Файл:** `features/AdSections/GeoSection/ui/composables/useGeoData.ts`

Добавить новые поля и интеграцию с geography store:
```typescript
import { useGeographyStore } from '@/entities/geography/model/geographyStore'
import type { City, District, MetroLine } from '@/entities/geography/model/types'

// Обновляем интерфейс GeoData
export interface GeoData {
  // Существующие поля
  address: string
  coordinates: { lat: number; lng: number } | null
  zoom: number
  outcall: OutcallType
  zones: string[]
  metro_stations: string[]
  outcall_apartment: boolean
  outcall_hotel: boolean
  outcall_house: boolean
  outcall_sauna: boolean
  outcall_office: boolean
  taxi_included: boolean

  // Новые поля для географии
  currentCity: City | null
  availableDistricts: District[]
  availableMetro: MetroLine[]
}

export function useGeoData(options: UseGeoDataOptions = {}) {
  // Подключаем geography store
  const geographyStore = useGeographyStore()

  // Обновляем создание дефолтных данных
  const createDefaultGeoData = (): GeoData => ({
    // ... существующие поля
    
    // Новые поля
    currentCity: null,
    availableDistricts: [],
    availableMetro: [],
    
    ...initialData
  })

  // Добавляем новые computed
  const hasZonesOption = computed(() => geographyStore.hasZonesOption)
  
  const cityMetroStations = computed(() => 
    geographyStore.allMetroStations.value
  )

  const cityDistricts = computed(() => 
    geographyStore.availableDistricts.value.map(d => d.name)
  )

  // Обновляем метод updateAddress для определения города
  const updateAddress = async (address: string) => {
    geoData.address = address
    
    // Определяем город при изменении адреса
    if (address && address.length > 5) {
      try {
        const detectedCity = await geographyStore.detectCityFromAddress(address)
        
        if (detectedCity && detectedCity.id !== geoData.currentCity?.id) {
          // Город изменился - обновляем данные
          geoData.currentCity = detectedCity
          
          // Сбрасываем зоны если новый город их не поддерживает
          if (!geographyStore.hasZonesOption && geoData.outcall === 'zones') {
            geoData.outcall = 'city'
            geoData.zones = []
          }
          
          // Сбрасываем метро если у города нет метро
          if (!geographyStore.hasMetro) {
            geoData.metro_stations = []
          }
          
          // Обновляем доступные данные из store
          geoData.availableDistricts = geographyStore.availableDistricts.value
          geoData.availableMetro = geographyStore.availableMetro.value
          
          // Эмитим событие смены города
          if (autoSave && onDataChange) {
            onDataChange(geoData)
          }
        }
      } catch (error) {
        console.warn('Failed to detect city:', error)
      }
    }
  }

  // Добавляем метод для принудительной смены города
  const changeCity = async (cityName: string) => {
    try {
      await geographyStore.loadCityData(cityName)
      
      geoData.currentCity = geographyStore.currentCity.value
      geoData.availableDistricts = geographyStore.availableDistricts.value
      geoData.availableMetro = geographyStore.availableMetro.value
      
      // Проверяем совместимость текущих настроек с новым городом
      if (!geographyStore.hasZonesOption && geoData.outcall === 'zones') {
        geoData.outcall = 'city'
        geoData.zones = []
      }
      
      if (!geographyStore.hasMetro) {
        geoData.metro_stations = []
      }
      
    } catch (error) {
      console.error('Failed to change city:', error)
    }
  }

  // Обновляем loadFromJson для обработки новых полей
  const loadFromJson = (jsonString: string) => {
    try {
      if (!jsonString) return

      const parsed = JSON.parse(jsonString)
      
      // Обновляем данные безопасно
      Object.assign(geoData, {
        // ... существующие поля
        
        // Новые поля (с учетом обратной совместимости)
        currentCity: parsed.currentCity || null,
        availableDistricts: parsed.availableDistricts || [],
        availableMetro: parsed.availableMetro || [],
        
        // ... остальные поля
      })

      // Если есть currentCity, загружаем актуальные данные
      if (geoData.currentCity?.name) {
        geographyStore.loadCityData(geoData.currentCity.name).catch(error => {
          console.warn('Failed to reload city data:', error)
        })
      }

      error.value = null
    } catch (err) {
      error.value = 'Ошибка загрузки данных: ' + (err as Error).message
      console.error('Ошибка парсинга geo данных:', err)
    }
  }

  // Обновляем валидацию
  const validateData = (): { isValid: boolean; errors: string[] } => {
    const errors: string[] = []

    // Проверяем адрес
    if (!geoData.address.trim()) {
      errors.push('Не указан адрес')
    }

    // Проверяем координаты
    if (!geoData.coordinates) {
      errors.push('Не указаны координаты')
    }

    // Проверяем зоны если выбран выезд в зоны
    if (geoData.outcall === 'zones') {
      if (!hasZonesOption.value) {
        errors.push('Выезд по зонам недоступен для данного города')
      }
      
      if (geoData.zones.length === 0) {
        errors.push('Не выбраны зоны выезда')
      }
    }

    // Проверяем метро если выбраны станции метро
    if (geoData.metro_stations.length > 0 && !geographyStore.hasMetro) {
      errors.push('В данном городе нет метро')
    }

    return {
      isValid: errors.length === 0,
      errors
    }
  }

  return {
    // Существующие возвраты
    geoData,
    isLoading,
    error,
    outcallTypes,
    updateAddress,
    updateCoordinates,
    updateOutcall,
    updateZones,
    updateMetroStations,
    updateOutcallTypes,
    updateTaxiIncluded,
    loadFromJson,
    toJson,
    getDataCopy,
    resetData,
    validateData,

    // Новые возвраты
    hasZonesOption,
    cityMetroStations,
    cityDistricts,
    changeCity
  }
}
```

#### 4.2 Обновление AddressMapSection.vue
**Файл:** `features/AdSections/GeoSection/ui/components/AddressMapSection.vue`

Добавить интеграцию с geography store:
```typescript
// В script setup добавить
import { useGeographyStore } from '@/entities/geography/model/geographyStore'

const geographyStore = useGeographyStore()

// Добавить новый emit
interface Emits {
  'update:address': [address: string]
  'update:coordinates': [coords: { lat: number; lng: number }]
  'data-changed': [data: { address: string; coordinates: { lat: number; lng: number } | null }]
  'city-detected': [city: City] // Новый emit
}

// Обновить emitUpdates
const emitUpdates = async (address: string, coordinates: { lat: number; lng: number } | null) => {
  emit('update:address', address)
  
  if (coordinates) {
    emit('update:coordinates', coordinates)
  }
  
  // Определяем город и эмитим событие
  if (address && address.length > 5) {
    try {
      const city = await geographyStore.detectCityFromAddress(address)
      if (city) {
        console.log('🌍 [AddressMapSection] Город определен:', city.name)
        emit('city-detected', city)
      }
    } catch (error) {
      console.warn('City detection failed:', error)
    }
  }
  
  emit('data-changed', { address, coordinates })
}

// Обновить обработчик выбора подсказки
const selectSuggestion = async (suggestion: Suggestion) => {
  searchQuery.value = suggestion.name
  showSuggestions.value = false
  
  // Устанавливаем флаг навигации чтобы не срабатывал обратный геокодинг
  isNavigating.value = true
  
  // Обновляем координаты и зум
  const newCoords: [number, number] = [suggestion.coordinates.lng, suggestion.coordinates.lat]
  currentCoordinates.value = newCoords
  previousCoordinates = newCoords
  currentZoom.value = 15
  
  // Сбрасываем флаг навигации через секунду
  setTimeout(() => {
    isNavigating.value = false
  }, 1000)
  
  // ВАЖНО: вызываем emitUpdates для определения города
  await emitUpdates(suggestion.name, suggestion.coordinates)
}
```

#### 4.3 Обновление OutcallSection.vue
**Файл:** `features/AdSections/GeoSection/ui/components/OutcallSection.vue`

Добавить условное отображение опции "В выбранные зоны":
```vue
<template>
  <div class="outcall-section">
    <!-- Заголовок секции -->
    <div class="mb-4">
      <h3 class="text-base font-medium text-gray-900 mb-2">Куда выезжаете</h3>
      <p class="text-sm text-gray-600 leading-relaxed">
        Укажите все зоны выезда, чтобы клиенты понимали, доберётесь ли вы до них.
      </p>
    </div>
    
    <!-- Радиокнопки выбора типа выезда -->
    <div class="flex flex-col gap-2">
      <BaseRadio
        :model-value="currentOutcall"
        value="none"
        name="outcall"
        label="Не выезжаю"
        @update:modelValue="handleOutcallChange"
      />
      <BaseRadio
        :model-value="currentOutcall"
        value="city"
        name="outcall"
        label="По всему городу"
        @update:modelValue="handleOutcallChange"
      />
      
      <!-- Условное отображение "В выбранные зоны" -->
      <BaseRadio
        v-if="hasZonesOption"
        :model-value="currentOutcall"
        value="zones"
        name="outcall"
        label="В выбранные зоны"
        @update:modelValue="handleOutcallChange"
      />
      
      <!-- Подсказка для небольших городов -->
      <div v-else-if="showZonesHint" class="text-sm text-gray-500 italic pl-6">
        💡 Выезд по районам доступен в крупных городах (Москва, СПб, Екатеринбург и др.)
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'
import type { City } from '@/entities/geography/model/types'

// Типы
type OutcallType = 'none' | 'city' | 'zones'

// Интерфейсы
interface Props {
  initialOutcall?: OutcallType
  hasZonesOption?: boolean
  currentCity?: City | null
}

interface Emits {
  'update:outcall': [outcall: OutcallType]
  'outcall-changed': [data: { outcall: OutcallType; shouldClearZones: boolean }]
}

// Props с дефолтными значениями
const props = withDefaults(defineProps<Props>(), {
  initialOutcall: 'none',
  hasZonesOption: false,
  currentCity: null
})

// Emits
const emit = defineEmits<Emits>()

// Реактивное состояние
const currentOutcall = ref<OutcallType>(props.initialOutcall)

// Показывать подсказку о зонах только для городов без этой опции
const showZonesHint = computed(() => 
  props.currentCity && !props.hasZonesOption
)

// Обработка изменения типа выезда
const handleOutcallChange = (value: OutcallType) => {
  // Проверяем доступность опции "zones"
  if (value === 'zones' && !props.hasZonesOption) {
    console.warn('Zones option not available for current city')
    return
  }

  const previousOutcall = currentOutcall.value
  currentOutcall.value = value
  
  // Определяем, нужно ли очищать зоны
  const shouldClearZones = previousOutcall === 'zones' && value !== 'zones'
  
  // Отправляем события родителю
  emit('update:outcall', value)
  emit('outcall-changed', { 
    outcall: value, 
    shouldClearZones 
  })
}

// Следим за изменениями props
watch(() => props.initialOutcall, (newOutcall) => {
  if (newOutcall) {
    currentOutcall.value = newOutcall
  }
})

// Автосброс "zones" если город сменился и не поддерживает зоны
watch(() => props.hasZonesOption, (hasZones) => {
  if (!hasZones && currentOutcall.value === 'zones') {
    console.log('🔄 Auto-switching from zones to city (zones not supported)')
    handleOutcallChange('city')
  }
})
</script>
```

#### 4.4 Обновление ZonesSection.vue
**Файл:** `features/AdSections/GeoSection/ui/components/ZonesSection.vue`

Использовать районы из geography store:
```vue
<template>
  <div class="zones-section">
    <div class="mb-4">
      <h3 class="text-base font-medium text-gray-900 mb-2">Районы выезда</h3>
      <p class="text-sm text-gray-600 leading-relaxed">
        Выберите районы, в которые вы готовы выезжать к клиентам.
      </p>
    </div>

    <!-- Loading state -->
    <div v-if="isLoadingDistricts" class="grid grid-cols-2 md:grid-cols-3 gap-3">
      <div v-for="i in 6" :key="i" class="animate-pulse">
        <div class="h-10 bg-gray-200 rounded"></div>
      </div>
    </div>

    <!-- Districts list -->
    <div v-else-if="availableZones.length > 0" class="space-y-4">
      <!-- Быстрый выбор -->
      <div class="flex flex-wrap gap-2 pb-4 border-b border-gray-200">
        <button
          type="button"
          @click="selectAllZones"
          class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition-colors"
        >
          Выбрать все
        </button>
        <button
          type="button"
          @click="selectCentralZones"
          class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded-full hover:bg-green-200 transition-colors"
        >
          Только центр
        </button>
        <button
          type="button"
          @click="clearAllZones"
          class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition-colors"
        >
          Очистить
        </button>
      </div>

      <!-- Checkbox grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <BaseCheckbox
          v-for="zone in availableZones"
          :key="zone"
          :model-value="selectedZones.includes(zone)"
          :label="zone"
          @update:modelValue="toggleZone(zone)"
        />
      </div>

      <!-- Selected count -->
      <div v-if="selectedZones.length > 0" class="text-sm text-gray-600">
        Выбрано районов: {{ selectedZones.length }} из {{ availableZones.length }}
      </div>
    </div>

    <!-- No districts available -->
    <div v-else class="text-center py-8 text-gray-500">
      <p class="mb-2">Районы для данного города не найдены</p>
      <p class="text-sm">Попробуйте выбрать "По всему городу"</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useGeographyStore } from '@/entities/geography/model/geographyStore'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'

// Интерфейсы
interface Props {
  outcallType: 'zones'
  initialZones?: string[]
}

interface Emits {
  'update:zones': [zones: string[]]
  'zones-changed': [data: { zones: string[] }]
}

const props = withDefaults(defineProps<Props>(), {
  initialZones: () => []
})

const emit = defineEmits<Emits>()

// Состояние
const selectedZones = ref<string[]>([...props.initialZones])
const geographyStore = useGeographyStore()

// Получаем доступные зоны из geography store
const availableZones = computed(() => 
  geographyStore.availableDistricts.value.map(district => district.name)
)

const centralZones = computed(() =>
  geographyStore.availableDistricts.value
    .filter(district => district.isCentral)
    .map(district => district.name)
)

const isLoadingDistricts = computed(() => 
  geographyStore.isLoading.value && availableZones.value.length === 0
)

// Методы
const toggleZone = (zone: string) => {
  if (selectedZones.value.includes(zone)) {
    selectedZones.value = selectedZones.value.filter(z => z !== zone)
  } else {
    selectedZones.value = [...selectedZones.value, zone]
  }
  emitChanges()
}

const selectAllZones = () => {
  selectedZones.value = [...availableZones.value]
  emitChanges()
}

const selectCentralZones = () => {
  selectedZones.value = [...centralZones.value]
  emitChanges()
}

const clearAllZones = () => {
  selectedZones.value = []
  emitChanges()
}

const emitChanges = () => {
  emit('update:zones', selectedZones.value)
  emit('zones-changed', { zones: selectedZones.value })
}

// Watchers
watch(() => props.initialZones, (newZones) => {
  if (newZones && Array.isArray(newZones)) {
    selectedZones.value = [...newZones]
  }
}, { deep: true })

// Очищаем выбранные зоны если они больше недоступны
watch(availableZones, (newZones) => {
  const validZones = selectedZones.value.filter(zone => newZones.includes(zone))
  if (validZones.length !== selectedZones.value.length) {
    selectedZones.value = validZones
    emitChanges()
  }
}, { deep: true })
</script>

<style scoped>
.zones-section {
  @apply w-full;
}
</style>
```

#### 4.5 Обновление MetroSection.vue  
**Файл:** `features/AdSections/GeoSection/ui/components/MetroSection.vue`

Использовать данные метро из geography store с группировкой по линиям:
```vue
<template>
  <!-- Показывать только если у города есть метро -->
  <div v-if="hasMetro" class="metro-section">
    <div class="mb-4">
      <h3 class="text-base font-medium text-gray-900 mb-2">Станции метро</h3>
      <p class="text-sm text-gray-600 leading-relaxed">
        Выберите ближайшие к вам станции метро для удобства клиентов.
      </p>
    </div>

    <!-- Loading state -->
    <div v-if="isLoadingMetro" class="space-y-4">
      <div v-for="i in 3" :key="i" class="animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-48 mb-2"></div>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
          <div v-for="j in 6" :key="j" class="h-8 bg-gray-200 rounded"></div>
        </div>
      </div>
    </div>

    <!-- Metro lines -->
    <div v-else-if="availableMetro.length > 0" class="space-y-6">
      <!-- Быстрый выбор -->
      <div class="flex flex-wrap gap-2 pb-4 border-b border-gray-200">
        <button
          type="button"
          @click="selectAllStations"
          class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition-colors"
        >
          Все станции
        </button>
        <button
          type="button"
          @click="selectCentralStations"
          class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded-full hover:bg-green-200 transition-colors"
        >
          Центральные
        </button>
        <button
          type="button"
          @click="clearAllStations"
          class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition-colors"
        >
          Очистить
        </button>
      </div>

      <!-- Группировка по линиям -->
      <div 
        v-for="line in availableMetro" 
        :key="line.id"
        class="metro-line"
      >
        <!-- Заголовок линии с цветом -->
        <div class="flex items-center mb-3 pb-2 border-b border-gray-100">
          <div 
            class="w-4 h-4 rounded-full mr-3 flex-shrink-0"
            :style="{ backgroundColor: line.color }"
          ></div>
          <h4 class="font-medium text-gray-900 flex-grow">{{ line.name }}</h4>
          <div class="text-sm text-gray-500">
            {{ getSelectedStationsCount(line) }} / {{ line.stations.length }}
          </div>
          
          <!-- Кнопки управления линией -->
          <div class="flex gap-1 ml-3">
            <button
              type="button"
              @click="selectLineStations(line)"
              class="text-xs px-2 py-1 text-blue-600 hover:bg-blue-50 rounded"
              title="Выбрать все станции линии"
            >
              Все
            </button>
            <button
              type="button"
              @click="clearLineStations(line)"
              class="text-xs px-2 py-1 text-gray-600 hover:bg-gray-50 rounded"
              title="Очистить выбор на линии"
            >
              Нет
            </button>
          </div>
        </div>
        
        <!-- Станции линии -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 ml-7">
          <BaseCheckbox
            v-for="station in line.stations"
            :key="station.id"
            :model-value="selectedStations.includes(station.name)"
            :label="station.name"
            @update:modelValue="toggleStation(station.name)"
            class="text-sm"
          />
        </div>
      </div>

      <!-- Счетчик выбранных станций -->
      <div v-if="selectedStations.length > 0" class="text-sm text-gray-600 pt-4 border-t border-gray-200">
        Выбрано станций: {{ selectedStations.length }} из {{ totalStationsCount }}
      </div>
    </div>

    <!-- No metro data -->
    <div v-else class="text-center py-8 text-gray-500">
      <p class="mb-2">Данные метро для города не найдены</p>
      <p class="text-sm">Проверьте подключение к интернету</p>
    </div>
  </div>

  <!-- No metro in city (hidden by parent) -->
  <div v-else class="hidden"></div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useGeographyStore } from '@/entities/geography/model/geographyStore'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'
import type { MetroLine, MetroStation } from '@/entities/geography/model/types'

// Интерфейсы
interface Props {
  outcallType: 'city' | 'zones'
  initialStations?: string[]
}

interface Emits {
  'update:stations': [stations: string[]]
  'stations-changed': [data: { stations: string[] }]
}

const props = withDefaults(defineProps<Props>(), {
  initialStations: () => []
})

const emit = defineEmits<Emits>()

// Состояние
const selectedStations = ref<string[]>([...props.initialStations])
const geographyStore = useGeographyStore()

// Computed
const hasMetro = computed(() => geographyStore.hasMetro.value)
const availableMetro = computed(() => geographyStore.availableMetro.value)
const isLoadingMetro = computed(() => 
  geographyStore.isLoading.value && availableMetro.value.length === 0
)

const totalStationsCount = computed(() =>
  availableMetro.value.reduce((total, line) => total + line.stations.length, 0)
)

const allStationsFlat = computed(() =>
  availableMetro.value.flatMap(line => line.stations.map(station => station.name))
)

const centralStations = computed(() => {
  // Примерная логика для определения центральных станций
  const centralKeywords = ['центр', 'театральная', 'площадь', 'кремль', 'университет']
  return allStationsFlat.value.filter(station =>
    centralKeywords.some(keyword => 
      station.toLowerCase().includes(keyword)
    )
  )
})

// Методы
const toggleStation = (stationName: string) => {
  if (selectedStations.value.includes(stationName)) {
    selectedStations.value = selectedStations.value.filter(s => s !== stationName)
  } else {
    selectedStations.value = [...selectedStations.value, stationName]
  }
  emitChanges()
}

const getSelectedStationsCount = (line: MetroLine): number => {
  return line.stations.filter(station => 
    selectedStations.value.includes(station.name)
  ).length
}

const selectLineStations = (line: MetroLine) => {
  const lineStations = line.stations.map(station => station.name)
  const newSelected = new Set([...selectedStations.value, ...lineStations])
  selectedStations.value = Array.from(newSelected)
  emitChanges()
}

const clearLineStations = (line: MetroLine) => {
  const lineStations = line.stations.map(station => station.name)
  selectedStations.value = selectedStations.value.filter(station => 
    !lineStations.includes(station)
  )
  emitChanges()
}

const selectAllStations = () => {
  selectedStations.value = [...allStationsFlat.value]
  emitChanges()
}

const selectCentralStations = () => {
  selectedStations.value = [...centralStations.value]
  emitChanges()
}

const clearAllStations = () => {
  selectedStations.value = []
  emitChanges()
}

const emitChanges = () => {
  emit('update:stations', selectedStations.value)
  emit('stations-changed', { stations: selectedStations.value })
}

// Watchers
watch(() => props.initialStations, (newStations) => {
  if (newStations && Array.isArray(newStations)) {
    selectedStations.value = [...newStations]
  }
}, { deep: true })

// Очищаем выбранные станции если они больше недоступны
watch(allStationsFlat, (newStations) => {
  const validStations = selectedStations.value.filter(station => 
    newStations.includes(station)
  )
  if (validStations.length !== selectedStations.value.length) {
    selectedStations.value = validStations
    emitChanges()
  }
}, { deep: true })
</script>

<style scoped>
.metro-section {
  @apply w-full;
}

.metro-line {
  @apply relative;
}

/* Анимация для чекбоксов */
.metro-line .checkbox-item {
  @apply transition-all duration-200;
}

.metro-line:hover .checkbox-item {
  @apply bg-gray-50;
}
</style>
```

#### 4.6 Обновление GeoSection.vue
**Файл:** `features/AdSections/GeoSection/ui/GeoSection.vue`

Интегрировать все изменения в главном компоненте:
```vue
<template>
  <div class="geo-section">
    <!-- Секция карты и адреса -->
    <AddressMapSection 
      :initial-address="geoData.address"
      :initial-coordinates="geoData.coordinates"
      :initial-zoom="geoData.zoom"
      @update:address="handleAddressUpdate"
      @update:coordinates="handleCoordinatesUpdate"
      @city-detected="handleCityDetected"
      @data-changed="handleMapDataChange"
    />

    <!-- Секция выезда -->
    <div class="pt-6 border-t border-gray-200">
      <OutcallSection 
        :initial-outcall="geoData.outcall"
        :has-zones-option="hasZonesOption"
        :current-city="currentCity"
        @update:outcall="handleOutcallUpdate"
        @outcall-changed="handleOutcallChange"
      />
      
      <!-- Секция зон (только если поддерживается и выбрано) -->
      <div v-if="hasZonesOption && geoData.outcall === 'zones'" class="mt-4">
        <ZonesSection 
          :outcall-type="geoData.outcall"
          :initial-zones="geoData.zones"
          @update:zones="handleZonesUpdate"
          @zones-changed="handleZonesChange"
        />
      </div>

      <!-- Секция метро (только если есть метро и выезд не 'none') -->
      <div v-if="hasMetro && geoData.outcall !== 'none'" class="mt-4">
        <MetroSection 
          :outcall-type="geoData.outcall"
          :initial-stations="geoData.metro_stations"
          @update:stations="handleStationsUpdate"
          @stations-changed="handleStationsChange"
        />
      </div>
      
      <!-- Секция типов мест -->
      <div v-if="geoData.outcall !== 'none'" class="mt-6 pt-6 border-t border-gray-200">
        <OutcallTypesSection 
          :outcall-type="geoData.outcall"
          :initial-types="outcallTypes"
          :initial-taxi-included="geoData.taxi_included"
          @update:types="handleTypesUpdate"
          @update:taxiIncluded="handleTaxiUpdate"
          @types-changed="handleTypesChange"
        />
      </div>
    </div>

    <!-- Debug info (только в dev режиме) -->
    <div v-if="isDevelopment" class="mt-6 p-4 bg-gray-100 rounded text-xs">
      <details>
        <summary class="cursor-pointer font-medium">Debug: Geography Info</summary>
        <div class="mt-2 space-y-1">
          <div><strong>Current City:</strong> {{ currentCity?.name || 'None' }}</div>
          <div><strong>Has Metro:</strong> {{ hasMetro ? 'Yes' : 'No' }}</div>
          <div><strong>Has Zones Option:</strong> {{ hasZonesOption ? 'Yes' : 'No' }}</div>
          <div><strong>Available Districts:</strong> {{ availableDistricts.length }}</div>
          <div><strong>Available Metro Lines:</strong> {{ availableMetro.length }}</div>
        </div>
      </details>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useGeographyStore } from '@/entities/geography/model/geographyStore'
import { useGeoData } from './composables/useGeoData'
import AddressMapSection from './components/AddressMapSection.vue'
import OutcallSection from './components/OutcallSection.vue'
import ZonesSection from './components/ZonesSection.vue'
import MetroSection from './components/MetroSection.vue'
import OutcallTypesSection from './components/OutcallTypesSection.vue'
import type { City } from '@/entities/geography/model/types'

// ... существующие типы и интерфейсы

const props = withDefaults(defineProps<Props>(), {
  geo: () => '',
  errors: () => ({}),
  forceValidation: false
})

const emit = defineEmits<Emits>()

// Geography Store
const geographyStore = useGeographyStore()

// Инициализация useGeoData composable с автосохранением
const {
  geoData,
  updateAddress,
  updateCoordinates,
  updateOutcall,
  updateZones,
  updateMetroStations,
  updateOutcallTypes,
  updateTaxiIncluded,
  loadFromJson,
  toJson,
  validateData,
  outcallTypes,
  hasZonesOption,
  cityMetroStations,
  cityDistricts,
  changeCity
} = useGeoData({
  autoSave: true,
  onDataChange: (data) => {
    emitGeoData()
  }
})

// Computed properties
const currentCity = computed(() => geographyStore.currentCity.value)
const hasMetro = computed(() => geographyStore.hasMetro.value)
const availableDistricts = computed(() => geographyStore.availableDistricts.value)
const availableMetro = computed(() => geographyStore.availableMetro.value)
const isDevelopment = computed(() => import.meta.env.DEV)

// ... существующие методы и обработчики

// Новый обработчик для определения города
const handleCityDetected = async (city: City) => {
  console.log('🌍 [GeoSection] Город определен:', city.name, {
    hasMetro: city.hasMetro,
    population: city.population,
    hasZonesOption: geographyStore.hasZonesOption.value
  })
  
  // Автосброс некорректных настроек при смене города
  if (!geographyStore.hasZonesOption.value && geoData.outcall === 'zones') {
    console.log('🔄 Auto-switching from zones to city (zones not supported)')
    updateOutcall('city', true) // true = clear zones
  }
  
  if (!geographyStore.hasMetro.value && geoData.metro_stations.length > 0) {
    console.log('🔄 Clearing metro stations (no metro in new city)')
    updateMetroStations([])
  }

  // Очистка принудительной валидации при выборе города
  if (props.forceValidation && city.name) {
    emit('clear-force-validation')
  }
}

// Загрузка начальных данных при монтировании
onMounted(async () => {
  // Предзагрузка популярных городов для кэширования
  await geographyStore.preloadPopularCities()

  if (props.geo) {
    if (typeof props.geo === 'string') {
      loadFromJson(props.geo)
    } else {
      loadFromJson(JSON.stringify(props.geo))
    }
  }
})

// Следим за изменениями props.geo для обновления данных извне
watch(() => props.geo, (newGeo) => {
  if (newGeo) {
    if (typeof newGeo === 'string') {
      loadFromJson(newGeo)
    } else {
      loadFromJson(JSON.stringify(newGeo))
    }
  }
}, { deep: true })

// Следим за ошибками geography store
watch(() => geographyStore.error.value, (error) => {
  if (error) {
    console.warn('Geography error:', error)
    // Можно показать пользователю уведомление
  }
})

// ... остальные watchers и lifecycle hooks
</script>

<style scoped>
.geo-section {
  @apply w-full space-y-0;
}

/* Debug стили */
details summary::-webkit-details-marker {
  display: none;
}

details summary::before {
  content: '▶';
  margin-right: 4px;
  transition: transform 0.2s;
}

details[open] summary::before {
  transform: rotate(90deg);
}
</style>
```

### ЭТАП 5: Тестирование и отладка 📅 Неделя 4

#### 5.1 Backend тесты
**Файл:** `tests/Feature/Geography/GeographyTest.php`
```php
<?php

namespace Tests\Feature\Geography;

use Tests\TestCase;
use App\Domain\Geography\Models\City;
use App\Domain\Geography\Models\CityDistrict;
use App\Domain\Geography\Models\MetroLine;
use App\Domain\Geography\Models\MetroStation;
use App\Domain\Geography\Services\GeographyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GeographyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\GeographySeeder::class);
    }

    /** @test */
    public function can_detect_moscow_from_address(): void
    {
        $response = $this->postJson('/api/geography/detect-city', [
            'address' => 'Москва, ул. Тверская, 1'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'city' => ['id', 'name', 'hasMetro', 'population'],
                'confidence',
                'source'
            ])
            ->assertJsonPath('city.name', 'Москва')
            ->assertJsonPath('city.hasMetro', true);
    }

    /** @test */
    public function moscow_has_metro_and_zones_option(): void
    {
        $response = $this->getJson('/api/geography/cities/Москва');

        $response->assertStatus(200)
            ->assertJsonPath('city.hasMetro', true)
            ->assertJsonPath('hasZonesOption', true)
            ->assertJsonStructure([
                'city',
                'districts' => [['id', 'name']],
                'metro' => ['lines' => [['id', 'name', 'color', 'stations']]]
            ]);
    }

    /** @test */
    public function small_city_has_no_metro_or_zones_option(): void
    {
        // Создаем небольшой город
        $smallCity = City::factory()->create([
            'name' => 'Тула',
            'population' => 400000,
            'has_metro' => false
        ]);

        CityDistrict::factory()->count(3)->create(['city_id' => $smallCity->id]);

        $response = $this->getJson("/api/geography/cities/{$smallCity->name}");

        $response->assertStatus(200)
            ->assertJsonPath('city.hasMetro', false)
            ->assertJsonPath('hasZonesOption', false)
            ->assertJsonPath('metro', null);
    }

    /** @test */
    public function can_get_moscow_districts(): void
    {
        $moscow = City::where('name', 'Москва')->first();
        
        $response = $this->getJson("/api/geography/cities/{$moscow->id}/districts");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'sortOrder', 'isCentral']]
            ]);

        $this->assertGreaterThan(100, count($response->json('data')));
    }

    /** @test */
    public function can_get_moscow_metro_stations(): void
    {
        $moscow = City::where('name', 'Москва')->first();
        
        $response = $this->getJson("/api/geography/cities/{$moscow->id}/metro");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'lines' => [['id', 'name', 'color', 'stations' => [['id', 'name']]]]
            ]);

        $lines = $response->json('lines');
        $this->assertGreaterThan(10, count($lines)); // У Москвы >10 линий
    }

    /** @test */
    public function returns_404_for_nonexistent_city(): void
    {
        $response = $this->getJson('/api/geography/cities/НесуществующийГород');
        $response->assertStatus(404);
    }

    /** @test */
    public function returns_404_for_metro_in_city_without_metro(): void
    {
        $smallCity = City::factory()->create([
            'name' => 'Малый город',
            'has_metro' => false
        ]);

        $response = $this->getJson("/api/geography/cities/{$smallCity->id}/metro");
        $response->assertStatus(404);
    }

    /** @test */
    public function city_detection_handles_various_formats(): void
    {
        $testCases = [
            'г. Екатеринбург, ул. Ленина, 1' => 'Екатеринбург',
            'Санкт-Петербург, Невский проспект, 1' => 'Санкт-Петербург',
            'Новосибирск ул Ленина 1' => 'Новосибирск',
            'Казань, Кремлевская улица' => 'Казань'
        ];

        foreach ($testCases as $address => $expectedCity) {
            $response = $this->postJson('/api/geography/detect-city', [
                'address' => $address
            ]);

            $response->assertStatus(200);
            
            $cityName = $response->json('city.name');
            $this->assertEquals($expectedCity, $cityName, 
                "Failed to detect {$expectedCity} from '{$address}'"
            );
        }
    }
}
```

#### 5.2 Frontend тесты
**Файл:** `resources/js/src/entities/geography/model/geographyStore.test.ts`
```typescript
/**
 * Тесты для GeographyStore
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useGeographyStore } from './geographyStore'
import { geographyApi } from '../api/geographyApi'
import type { City, CityGeography, DetectCityResponse } from './types'

// Моки
vi.mock('../api/geographyApi')

const mockCity: City = {
  id: 1,
  name: 'Москва',
  region: 'Москва',
  hasMetro: true,
  hasDistricts: true,
  population: 12678079,
  coordinates: { lat: 55.7558, lng: 37.6176 }
}

const mockCityGeography: CityGeography = {
  city: mockCity,
  districts: [
    { id: 1, name: 'Центральный', sortOrder: 1, isCentral: true },
    { id: 2, name: 'Северный', sortOrder: 2, isCentral: false }
  ],
  metro: {
    lines: [
      {
        id: 1,
        name: 'Сокольническая',
        color: '#E42313',
        sortOrder: 1,
        stations: [
          { id: 1, name: 'Сокольники', sortOrder: 1 },
          { id: 2, name: 'Красносельская', sortOrder: 2 }
        ]
      }
    ],
    groupedStations: {
      'Сокольническая': [
        { id: 1, name: 'Сокольники', sortOrder: 1 },
        { id: 2, name: 'Красносельская', sortOrder: 2 }
      ]
    }
  },
  hasZonesOption: true
}

describe('GeographyStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('detectCityFromAddress', () => {
    it('successfully detects city from address', async () => {
      const mockResponse: DetectCityResponse = {
        city: mockCity,
        confidence: 0.9,
        source: 'database'
      }

      vi.mocked(geographyApi.detectCity).mockResolvedValue(mockResponse)
      vi.mocked(geographyApi.getCityData).mockResolvedValue(mockCityGeography)

      const store = useGeographyStore()
      const result = await store.detectCityFromAddress('Москва, ул. Тверская, 1')

      expect(result).toEqual(mockCity)
      expect(store.currentCity).toEqual(mockCity)
      expect(store.hasMetro).toBe(true)
      expect(store.hasZonesOption).toBe(true)
    })

    it('returns null for low confidence detection', async () => {
      const mockResponse: DetectCityResponse = {
        city: mockCity,
        confidence: 0.5,
        source: 'database'
      }

      vi.mocked(geographyApi.detectCity).mockResolvedValue(mockResponse)

      const store = useGeographyStore()
      const result = await store.detectCityFromAddress('неясный адрес')

      expect(result).toBeNull()
      expect(store.currentCity).toBeNull()
    })

    it('handles detection errors gracefully', async () => {
      vi.mocked(geographyApi.detectCity).mockRejectedValue(new Error('API error'))

      const store = useGeographyStore()
      const result = await store.detectCityFromAddress('Москва, ул. Тверская, 1')

      expect(result).toBeNull()
      expect(store.error).toContain('Не удалось определить город')
    })
  })

  describe('loadCityData', () => {
    it('loads and caches city data', async () => {
      vi.mocked(geographyApi.getCityData).mockResolvedValue(mockCityGeography)

      const store = useGeographyStore()
      await store.loadCityData('Москва')

      expect(store.currentCity).toEqual(mockCity)
      expect(store.availableDistricts).toHaveLength(2)
      expect(store.availableMetro).toHaveLength(1)

      // Второй вызов должен использовать кэш
      await store.loadCityData('Москва')
      expect(geographyApi.getCityData).toHaveBeenCalledTimes(1)
    })

    it('handles city not found', async () => {
      vi.mocked(geographyApi.getCityData).mockRejectedValue(
        new Error('Город не найден')
      )

      const store = useGeographyStore()
      await store.loadCityData('НесуществующийГород')

      expect(store.currentCity).toBeNull()
      expect(store.error).toContain('Не удалось загрузить данные города')
    })
  })

  describe('computed properties', () => {
    beforeEach(async () => {
      vi.mocked(geographyApi.getCityData).mockResolvedValue(mockCityGeography)
      const store = useGeographyStore()
      await store.loadCityData('Москва')
    })

    it('calculates hasZonesOption correctly', () => {
      const store = useGeographyStore()
      expect(store.hasZonesOption).toBe(true)
    })

    it('calculates allMetroStations correctly', () => {
      const store = useGeographyStore()
      expect(store.allMetroStations).toEqual(['Сокольники', 'Красносельская'])
    })

    it('creates districtsByName mapping', () => {
      const store = useGeographyStore()
      expect(store.districtsByName).toEqual({
        'Центральный': { id: 1, name: 'Центральный', sortOrder: 1, isCentral: true },
        'Северный': { id: 2, name: 'Северный', sortOrder: 2, isCentral: false }
      })
    })
  })

  describe('cache management', () => {
    it('caches city data', async () => {
      vi.mocked(geographyApi.getCityData).mockResolvedValue(mockCityGeography)

      const store = useGeographyStore()
      
      // Первый запрос
      await store.loadCityData('Москва')
      expect(geographyApi.getCityData).toHaveBeenCalledTimes(1)

      // Второй запрос - должен использовать кэш
      await store.loadCityData('Москва')
      expect(geographyApi.getCityData).toHaveBeenCalledTimes(1)
    })

    it('clears cache correctly', async () => {
      vi.mocked(geographyApi.getCityData).mockResolvedValue(mockCityGeography)

      const store = useGeographyStore()
      await store.loadCityData('Москва')

      store.clearCache()

      // После очистки кэша должен быть новый запрос
      await store.loadCityData('Москва')
      expect(geographyApi.getCityData).toHaveBeenCalledTimes(2)
    })
  })
})
```

#### 5.3 Интеграционные тесты
**Тестовые сценарии:**

1. **Москва (полный функционал)**
   - Ввод: "Москва, Красная площадь"
   - Ожидание: город определен, >100 районов, 14+ линий метро, показана опция "В выбранные зоны"

2. **Екатеринбург (метро + зоны)**
   - Ввод: "Екатеринбург, ул. Ленина, 1"  
   - Ожидание: город определен, ~10 районов, 1 линия метро, показана опция "В выбранные зоны"

3. **Краснодар (только районы)**
   - Ввод: "Краснодар, ул. Красная, 1"
   - Ожидание: город определен, ~5 районов, метро скрыто, опция "В выбранные зоны" скрыта

4. **Смена Москва → Тула**
   - Начальное состояние: выбраны зоны и метро в Москве
   - Смена адреса на Тулу
   - Ожидание: автосброс зон на "По всему городу", очистка метро

5. **Производительность**
   - Повторное определение того же города должно использовать кэш
   - Время ответа API < 500ms
   - Размер ответа метро Москвы < 100kb

### ЭТАП 6: Производительность и кэширование 📅 Неделя 4

#### 6.1 Backend оптимизации
```php
// В GeographyService добавить многуровневое кэширование
public function getCityData(string $cityName): ?CityGeographyDTO
{
    // L1 кэш - Redis (быстрый доступ)
    $l1CacheKey = "city_data_l1_{$cityName}";
    $cached = Cache::store('redis')->get($l1CacheKey);
    
    if ($cached) {
        return $cached;
    }

    // L2 кэш - Database кэш (дольше живет)
    $l2CacheKey = "city_data_l2_{$cityName}";
    $cityData = Cache::store('database')->remember(
        $l2CacheKey, 
        86400, // 24 часа
        function() use ($cityName) {
            return $this->buildCityDataFromDatabase($cityName);
        }
    );

    if ($cityData) {
        // Сохраняем в L1 кэш на час
        Cache::store('redis')->put($l1CacheKey, $cityData, 3600);
    }

    return $cityData;
}
```

#### 6.2 Database индексы
```sql
-- Оптимизация запросов
CREATE INDEX idx_cities_name_population ON cities(name, population);
CREATE INDEX idx_districts_city_name ON city_districts(city_id, name);
CREATE INDEX idx_metro_stations_line_sort ON metro_stations(line_id, sort_order);

-- Частичные индексы для крупных городов
CREATE INDEX idx_cities_large ON cities(id) WHERE population > 500000;
CREATE INDEX idx_cities_with_metro ON cities(id) WHERE has_metro = true;
```

#### 6.3 Frontend оптимизации
```typescript
// Lazy loading компонентов
const ZonesSection = defineAsyncComponent(() => 
  import('./components/ZonesSection.vue')
)

const MetroSection = defineAsyncComponent(() => 
  import('./components/MetroSection.vue')
)

// Дебоунс для определения города
const debouncedDetectCity = useDebounceFn(
  geographyStore.detectCityFromAddress, 
  300
)

// Виртуализация для больших списков станций
const { containerRef, list } = useVirtualList(
  allMetroStations,
  { itemHeight: 32, overscan: 5 }
)
```

---

## 📁 ИТОГОВАЯ СТРУКТУРА ФАЙЛОВ

### Новые файлы (28 файлов):
```
Backend (18 файлов):
├── database/migrations/2025_01_01_000001_create_geography_tables.php
├── database/seeders/GeographySeeder.php
├── database/factories/Geography/CityFactory.php
├── app/Domain/Geography/Models/City.php
├── app/Domain/Geography/Models/CityDistrict.php
├── app/Domain/Geography/Models/MetroLine.php
├── app/Domain/Geography/Models/MetroStation.php
├── app/Domain/Geography/Services/GeographyService.php
├── app/Domain/Geography/Services/CityDetectionService.php
├── app/Domain/Geography/DTOs/CityGeographyDTO.php
├── app/Domain/Geography/DTOs/MetroDataDTO.php
├── app/Domain/Geography/Repositories/GeographyRepository.php
├── app/Application/Http/Controllers/Geography/GeographyController.php
├── tests/Feature/Geography/GeographyTest.php
├── tests/Feature/Geography/CityDetectionTest.php
├── tests/Unit/Geography/CityModelTest.php
├── tests/Unit/Geography/GeographyServiceTest.php
└── config/geography.php

Frontend (10 файлов):
├── resources/js/src/entities/geography/model/types.ts
├── resources/js/src/entities/geography/api/geographyApi.ts
├── resources/js/src/entities/geography/model/geographyStore.ts
├── resources/js/src/entities/geography/model/geographyStore.test.ts
├── resources/js/src/entities/geography/README.md
└── docs/api/geography.md
```

### Изменяемые файлы (8 файлов):
```
├── routes/api.php (добавить geography маршруты)
├── features/AdSections/GeoSection/ui/composables/useGeoData.ts
├── features/AdSections/GeoSection/ui/components/AddressMapSection.vue
├── features/AdSections/GeoSection/ui/components/OutcallSection.vue
├── features/AdSections/GeoSection/ui/components/ZonesSection.vue
├── features/AdSections/GeoSection/ui/components/MetroSection.vue
├── features/AdSections/GeoSection/ui/GeoSection.vue
└── docs/CHANGELOG.md (документировать изменения)
```

---

## 🧪 ФИНАЛЬНАЯ ПРОВЕРКА

### Критерии готовности:
- [ ] ✅ **Backend:** Все тесты проходят (>20 тестов)
- [ ] ✅ **Frontend:** Компоненты работают без ошибок
- [ ] ✅ **API:** Все endpoints возвращают корректные данные
- [ ] ✅ **UX:** Поведение точно как на Avito
- [ ] ✅ **Производительность:** Определение города < 500ms
- [ ] ✅ **Кэширование:** Повторные запросы используют кэш
- [ ] ✅ **Мобильность:** Адаптивный дизайн на всех устройствах

### Тестовые сценарии:
1. **Москва** → все функции доступны
2. **Екатеринбург** → метро + зоны
3. **Краснодар** → только районы  
4. **Смена города** → корректное обновление
5. **Производительность** → быстрые ответы

---

## 🎯 ОЖИДАЕМЫЕ РЕЗУЛЬТАТЫ

После реализации получим:
- 🎯 **UX точно как на Avito** - знакомое поведение для пользователей
- 🚀 **Автоматизация на 100%** - никаких ручных действий
- 📊 **Актуальные данные** - реальные районы и станции метро 270+ городов
- ⚡ **Высокая производительность** - кэширование на всех уровнях  
- 🔧 **Легкая масштабируемость** - простое добавление новых городов
- 🧪 **Надежность** - комплексное покрытие тестами
- 📱 **Мобильная оптимизация** - работает на всех устройствах

**Время полной реализации: 3-4 недели**  
**Команда: 1 fullstack разработчик + AI ассистент**

---

**Этот план готов к немедленной реализации! 🚀**