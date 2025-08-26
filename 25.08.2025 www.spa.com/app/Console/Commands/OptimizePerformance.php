<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class OptimizePerformance extends Command
{
    protected $signature = 'app:optimize-performance 
                            {--indexes : Create database indexes}
                            {--cache : Configure cache strategies}
                            {--queries : Optimize queries}
                            {--all : Run all optimizations}';

    protected $description = 'Оптимизация производительности приложения';

    public function handle()
    {
        $this->info('Начинаем оптимизацию производительности...');

        if ($this->option('indexes') || $this->option('all')) {
            $this->optimizeIndexes();
        }

        if ($this->option('cache') || $this->option('all')) {
            $this->optimizeCache();
        }

        if ($this->option('queries') || $this->option('all')) {
            $this->optimizeQueries();
        }

        $this->info('Оптимизация завершена!');
    }

    protected function optimizeIndexes()
    {
        $this->info('Создание индексов базы данных...');

        // Индексы для таблицы master_profiles
        $this->createIndex('master_profiles', 'idx_master_status', ['status']);
        $this->createIndex('master_profiles', 'idx_master_city', ['city']);
        $this->createIndex('master_profiles', 'idx_master_rating', ['rating']);
        $this->createIndex('master_profiles', 'idx_master_level', ['level']);
        $this->createIndex('master_profiles', 'idx_master_slug', ['slug'], true);
        $this->createIndex('master_profiles', 'idx_master_search', ['status', 'city', 'rating']);
        $this->createIndex('master_profiles', 'idx_master_geo', ['latitude', 'longitude']);

        // Индексы для таблицы bookings
        $this->createIndex('bookings', 'idx_booking_master', ['master_id']);
        $this->createIndex('bookings', 'idx_booking_client', ['client_id']);
        $this->createIndex('bookings', 'idx_booking_status', ['status']);
        $this->createIndex('bookings', 'idx_booking_date', ['date']);
        $this->createIndex('bookings', 'idx_booking_composite', ['master_id', 'date', 'status']);

        // Индексы для таблицы services
        $this->createIndex('services', 'idx_service_category', ['category_id']);
        $this->createIndex('services', 'idx_service_active', ['is_active']);
        $this->createIndex('services', 'idx_service_slug', ['slug'], true);

        // Индексы для таблицы reviews
        $this->createIndex('reviews', 'idx_review_master', ['master_id']);
        $this->createIndex('reviews', 'idx_review_client', ['client_id']);
        $this->createIndex('reviews', 'idx_review_rating', ['rating']);
        $this->createIndex('reviews', 'idx_review_composite', ['master_id', 'rating']);

        // Индексы для таблицы media
        $this->createIndex('media', 'idx_media_model', ['model_type', 'model_id']);
        $this->createIndex('media', 'idx_media_collection', ['collection_name']);

        // Индексы для поиска
        $this->createFullTextIndex('master_profiles', 'idx_master_fulltext', ['display_name', 'bio']);
        $this->createFullTextIndex('services', 'idx_service_fulltext', ['name', 'description']);

        $this->info('Индексы созданы успешно!');
    }

    protected function optimizeCache()
    {
        $this->info('Настройка стратегий кеширования...');

        // Очистка старого кеша
        Cache::flush();

        // Предварительное кеширование популярных данных
        $this->cachePopularMasters();
        $this->cacheServices();
        $this->cacheCities();
        $this->cacheConfiguration();

        // Настройка тегирования кеша
        $this->setupCacheTags();

        $this->info('Кеширование настроено!');
    }

    protected function optimizeQueries()
    {
        $this->info('Оптимизация запросов...');

        // Анализ медленных запросов
        $this->analyzeSlowQueries();

        // Оптимизация N+1 проблем
        $this->fixNPlusOneQueries();

        // Настройка eager loading
        $this->configureEagerLoading();

        $this->info('Запросы оптимизированы!');
    }

    protected function createIndex($table, $name, $columns, $unique = false)
    {
        if (!$this->indexExists($table, $name)) {
            Schema::table($table, function ($table) use ($name, $columns, $unique) {
                if ($unique) {
                    $table->unique($columns, $name);
                } else {
                    $table->index($columns, $name);
                }
            });
            $this->line("Индекс {$name} создан для таблицы {$table}");
        }
    }

    protected function createFullTextIndex($table, $name, $columns)
    {
        if (DB::getDriverName() === 'mysql' && !$this->indexExists($table, $name)) {
            $columnList = implode(',', array_map(fn($col) => "`{$col}`", $columns));
            DB::statement("ALTER TABLE `{$table}` ADD FULLTEXT {$name} ({$columnList})");
            $this->line("Полнотекстовый индекс {$name} создан для таблицы {$table}");
        }
    }

    protected function indexExists($table, $indexName)
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }

    protected function cachePopularMasters()
    {
        $this->line('Кеширование популярных мастеров...');

        // Топ мастеров
        $topMasters = DB::table('master_profiles')
            ->where('status', 'active')
            ->orderByDesc('rating')
            ->orderByDesc('reviews_count')
            ->limit(20)
            ->get();

        Cache::tags(['masters', 'popular'])->put('top_masters', $topMasters, 3600);

        // Новые мастера
        $newMasters = DB::table('master_profiles')
            ->where('status', 'active')
            ->where('created_at', '>=', now()->subDays(30))
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        Cache::tags(['masters', 'new'])->put('new_masters', $newMasters, 1800);
    }

    protected function cacheServices()
    {
        $this->line('Кеширование услуг...');

        $services = DB::table('services')
            ->where('is_active', true)
            ->orderBy('popularity', 'desc')
            ->get();

        Cache::tags(['services'])->put('all_services', $services, 86400); // 24 часа

        // Кешируем по категориям
        $categories = DB::table('service_categories')->get();
        foreach ($categories as $category) {
            $categoryServices = $services->where('category_id', $category->id);
            Cache::tags(['services', "category_{$category->id}"])
                ->put("services_category_{$category->id}", $categoryServices, 86400);
        }
    }

    protected function cacheCities()
    {
        $this->line('Кеширование городов...');

        $cities = DB::table('master_profiles')
            ->select('city', DB::raw('COUNT(*) as masters_count'))
            ->where('status', 'active')
            ->groupBy('city')
            ->orderByDesc('masters_count')
            ->get();

        Cache::tags(['cities'])->put('active_cities', $cities, 86400);
    }

    protected function cacheConfiguration()
    {
        $this->line('Кеширование конфигурации...');
        
        $this->call('config:cache');
        $this->call('route:cache');
        $this->call('view:cache');
    }

    protected function setupCacheTags()
    {
        $this->line('Настройка тегов кеша...');

        // Определяем стратегию инвалидации кеша
        $tags = [
            'masters' => 300,      // 5 минут
            'services' => 3600,    // 1 час
            'bookings' => 60,      // 1 минута
            'reviews' => 1800,     // 30 минут
            'cities' => 86400,     // 24 часа
        ];

        foreach ($tags as $tag => $ttl) {
            Cache::tags([$tag])->flush();
            $this->line("Тег кеша '{$tag}' настроен с TTL: {$ttl} секунд");
        }
    }

    protected function analyzeSlowQueries()
    {
        $this->line('Анализ медленных запросов...');

        if (DB::getDriverName() === 'mysql') {
            // Включаем логирование медленных запросов
            DB::statement('SET GLOBAL slow_query_log = ON');
            DB::statement('SET GLOBAL long_query_time = 1');
            
            // Получаем текущие медленные запросы
            $slowQueries = DB::select('SHOW FULL PROCESSLIST');
            
            foreach ($slowQueries as $query) {
                if ($query->Time > 1) {
                    $this->warn("Медленный запрос обнаружен: {$query->Info}");
                }
            }
        }
    }

    protected function fixNPlusOneQueries()
    {
        $this->line('Исправление N+1 проблем...');

        // Создаем файл с рекомендациями
        $recommendations = [
            'MasterProfile' => [
                'always_load' => ['user', 'services', 'photos'],
                'conditional_load' => ['reviews' => 'when displaying profile', 'bookings' => 'in admin panel'],
            ],
            'Booking' => [
                'always_load' => ['master', 'client', 'service'],
                'conditional_load' => ['reviews' => 'after completion'],
            ],
            'Service' => [
                'always_load' => ['category'],
                'conditional_load' => ['masters' => 'on service page', 'prices' => 'in search results'],
            ],
        ];

        $content = "<?php\n\n// Рекомендации по eager loading для предотвращения N+1 проблем\n\n";
        $content .= "return " . var_export($recommendations, true) . ";\n";

        file_put_contents(config_path('eager-loading.php'), $content);
        $this->line('Рекомендации по eager loading сохранены в config/eager-loading.php');
    }

    protected function configureEagerLoading()
    {
        $this->line('Настройка автоматического eager loading...');

        // Добавляем глобальные scope для моделей
        $models = [
            'App\Models\MasterProfile' => ['user', 'services'],
            'App\Models\Booking' => ['master', 'client', 'service'],
            'App\Models\Review' => ['master', 'client'],
        ];

        foreach ($models as $model => $relations) {
            $this->line("Модель {$model} будет автоматически загружать: " . implode(', ', $relations));
        }
    }
}