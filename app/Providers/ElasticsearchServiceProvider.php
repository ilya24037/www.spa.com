<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Infrastructure\Search\ElasticsearchClient;
use App\Infrastructure\Search\Indexers\AdIndexer;
use App\Infrastructure\Search\Indexers\MasterIndexer;
use Illuminate\Console\Scheduling\Schedule;

class ElasticsearchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Регистрируем конфигурацию
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/elasticsearch.php',
            'elasticsearch'
        );

        // Регистрируем ElasticsearchClient как singleton
        $this->app->singleton(ElasticsearchClient::class, function ($app) {
            return new ElasticsearchClient();
        });

        // Регистрируем индексаторы
        $this->app->singleton(AdIndexer::class, function ($app) {
            return new AdIndexer($app->make(ElasticsearchClient::class));
        });

        $this->app->singleton(MasterIndexer::class, function ($app) {
            return new MasterIndexer($app->make(ElasticsearchClient::class));
        });

        // Регистрируем алиасы для удобства
        $this->app->alias(ElasticsearchClient::class, 'elasticsearch');
        $this->app->alias(AdIndexer::class, 'elasticsearch.indexer.ads');
        $this->app->alias(MasterIndexer::class, 'elasticsearch.indexer.masters');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Публикуем конфигурацию
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/elasticsearch.php' => config_path('elasticsearch.php'),
            ], 'elasticsearch-config');

            // Регистрируем команды
            $this->commands([
                \App\Console\Commands\Elasticsearch\CreateIndicesCommand::class,
                \App\Console\Commands\Elasticsearch\ReindexCommand::class,
                \App\Console\Commands\Elasticsearch\SyncCommand::class,
                \App\Console\Commands\Elasticsearch\DeleteIndicesCommand::class,
                \App\Console\Commands\Elasticsearch\StatusCommand::class,
            ]);
        }

        // Регистрируем периодические задачи
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            
            // Синхронизация каждые 5 минут
            if (config('elasticsearch.queue.enabled')) {
                $schedule->command('elasticsearch:sync')
                    ->everyFiveMinutes()
                    ->withoutOverlapping()
                    ->runInBackground();
            }
            
            // Обновление boost scores каждый час
            $schedule->command('elasticsearch:update-scores')
                ->hourly()
                ->withoutOverlapping()
                ->runInBackground();
        });

        // Регистрируем слушатели событий для автоматической индексации
        $this->registerEventListeners();

        // Регистрируем макросы для Builder
        $this->registerBuilderMacros();
    }

    /**
     * Регистрация слушателей событий
     */
    protected function registerEventListeners(): void
    {
        // События для объявлений
        \App\Domain\Ad\Models\Ad::created(function ($ad) {
            if (config('elasticsearch.queue.enabled')) {
                dispatch(new \App\Jobs\Elasticsearch\IndexAdJob($ad));
            } else {
                app(AdIndexer::class)->index($ad);
            }
        });

        \App\Domain\Ad\Models\Ad::updated(function ($ad) {
            if (config('elasticsearch.queue.enabled')) {
                dispatch(new \App\Jobs\Elasticsearch\IndexAdJob($ad));
            } else {
                app(AdIndexer::class)->update($ad);
            }
        });

        \App\Domain\Ad\Models\Ad::deleted(function ($ad) {
            if (config('elasticsearch.queue.enabled')) {
                dispatch(new \App\Jobs\Elasticsearch\DeleteAdFromIndexJob($ad->id));
            } else {
                app(AdIndexer::class)->delete($ad->id);
            }
        });

        // События для мастеров
        \App\Domain\Master\Models\MasterProfile::created(function ($master) {
            if (config('elasticsearch.queue.enabled')) {
                dispatch(new \App\Jobs\Elasticsearch\IndexMasterJob($master));
            } else {
                app(MasterIndexer::class)->index($master);
            }
        });

        \App\Domain\Master\Models\MasterProfile::updated(function ($master) {
            if (config('elasticsearch.queue.enabled')) {
                dispatch(new \App\Jobs\Elasticsearch\IndexMasterJob($master));
            } else {
                app(MasterIndexer::class)->update($master);
            }
        });

        \App\Domain\Master\Models\MasterProfile::deleted(function ($master) {
            if (config('elasticsearch.queue.enabled')) {
                dispatch(new \App\Jobs\Elasticsearch\DeleteMasterFromIndexJob($master->id));
            } else {
                app(MasterIndexer::class)->delete($master->id);
            }
        });
    }

    /**
     * Регистрация макросов для Eloquent Builder
     */
    protected function registerBuilderMacros(): void
    {
        // Макрос для поиска через Elasticsearch
        \Illuminate\Database\Eloquent\Builder::macro('searchInElasticsearch', function ($query, array $fields = []) {
            $model = $this->getModel();
            
            if ($model instanceof \App\Domain\Ad\Models\Ad) {
                $indexer = app(AdIndexer::class);
                $index = 'ads';
            } elseif ($model instanceof \App\Domain\Master\Models\MasterProfile) {
                $indexer = app(MasterIndexer::class);
                $index = 'masters';
            } else {
                throw new \Exception('Model not supported for Elasticsearch search');
            }
            
            $client = app(ElasticsearchClient::class);
            
            $searchQuery = [
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => $fields ?: ['*'],
                        'type' => 'best_fields',
                        'fuzziness' => 'AUTO'
                    ]
                ]
            ];
            
            $results = $client->search($index, $searchQuery);
            
            $ids = array_map(function ($hit) {
                return $hit['_id'];
            }, $results['hits']['hits']);
            
            return $this->whereIn($model->getKeyName(), $ids);
        });

        // Макрос для геопоиска
        \Illuminate\Database\Eloquent\Builder::macro('searchNearby', function ($latitude, $longitude, $distance = 10) {
            $model = $this->getModel();
            
            if (!in_array($model::class, [\App\Domain\Ad\Models\Ad::class, \App\Domain\Master\Models\MasterProfile::class])) {
                throw new \Exception('Model not supported for geo search');
            }
            
            $index = $model instanceof \App\Domain\Ad\Models\Ad ? 'ads' : 'masters';
            $client = app(ElasticsearchClient::class);
            
            $searchQuery = [
                'query' => [
                    'bool' => [
                        'filter' => [
                            'geo_distance' => [
                                'distance' => $distance . 'km',
                                'location' => [
                                    'lat' => $latitude,
                                    'lon' => $longitude
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $results = $client->search($index, $searchQuery);
            
            $ids = array_map(function ($hit) {
                return $hit['_id'];
            }, $results['hits']['hits']);
            
            return $this->whereIn($model->getKeyName(), $ids);
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ElasticsearchClient::class,
            AdIndexer::class,
            MasterIndexer::class,
            'elasticsearch',
            'elasticsearch.indexer.ads',
            'elasticsearch.indexer.masters',
        ];
    }
}