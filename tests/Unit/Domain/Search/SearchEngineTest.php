<?php

namespace Tests\Unit\Domain\Search;

use Tests\TestCase;
use App\Domain\Search\SearchEngine;
use App\Domain\Search\Contracts\SearchableInterface;
use App\Domain\Search\Engines\ElasticSearchEngine;
use App\Domain\Search\Engines\DatabaseSearchEngine;
use App\Domain\Search\DTOs\SearchQueryDTO;
use App\Domain\Search\DTOs\SearchResultDTO;
use Mockery;

class SearchEngineTest extends TestCase
{
    private SearchEngine $searchEngine;
    private $mockElasticEngine;
    private $mockDatabaseEngine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockElasticEngine = Mockery::mock(ElasticSearchEngine::class);
        $this->mockDatabaseEngine = Mockery::mock(DatabaseSearchEngine::class);

        $this->searchEngine = new SearchEngine(
            $this->mockElasticEngine,
            $this->mockDatabaseEngine
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_perform_global_search()
    {
        $query = new SearchQueryDTO(
            query: 'массаж',
            types: ['masters', 'services'],
            filters: ['city' => 'Moscow'],
            limit: 10
        );

        $expectedResults = [
            'masters' => [
                new SearchResultDTO(
                    id: 1,
                    type: 'master',
                    title: 'Мастер массажа',
                    description: 'Опытный мастер',
                    url: '/masters/1',
                    score: 0.95
                )
            ],
            'services' => [
                new SearchResultDTO(
                    id: 1,
                    type: 'service',
                    title: 'Классический массаж',
                    description: 'Расслабляющий массаж',
                    url: '/services/1',
                    score: 0.90
                )
            ]
        ];

        $this->mockElasticEngine
            ->shouldReceive('search')
            ->once()
            ->with($query)
            ->andReturn($expectedResults);

        $results = $this->searchEngine->search($query);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('masters', $results);
        $this->assertArrayHasKey('services', $results);
        $this->assertCount(1, $results['masters']);
        $this->assertCount(1, $results['services']);
    }

    /** @test */
    public function it_falls_back_to_database_when_elastic_fails()
    {
        $query = new SearchQueryDTO(
            query: 'массаж',
            types: ['masters'],
            limit: 10
        );

        $this->mockElasticEngine
            ->shouldReceive('search')
            ->once()
            ->andThrow(new \Exception('ElasticSearch is down'));

        $expectedResults = [
            'masters' => [
                new SearchResultDTO(
                    id: 1,
                    type: 'master',
                    title: 'Мастер',
                    description: 'Описание',
                    url: '/masters/1',
                    score: 0.80
                )
            ]
        ];

        $this->mockDatabaseEngine
            ->shouldReceive('search')
            ->once()
            ->with($query)
            ->andReturn($expectedResults);

        $results = $this->searchEngine->search($query);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('masters', $results);
    }

    /** @test */
    public function it_can_search_specific_type()
    {
        $query = new SearchQueryDTO(
            query: 'релакс',
            types: ['services'],
            limit: 5
        );

        $expectedResults = [
            'services' => [
                new SearchResultDTO(
                    id: 2,
                    type: 'service',
                    title: 'Релакс массаж',
                    description: 'Полное расслабление',
                    url: '/services/2',
                    score: 0.95
                )
            ]
        ];

        $this->mockElasticEngine
            ->shouldReceive('search')
            ->once()
            ->andReturn($expectedResults);

        $results = $this->searchEngine->searchByType('services', 'релакс', ['limit' => 5]);

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('Релакс массаж', $results[0]->title);
    }

    /** @test */
    public function it_can_get_suggestions()
    {
        $query = 'масс';
        $expectedSuggestions = [
            'массаж',
            'массажист',
            'массажный салон'
        ];

        $this->mockElasticEngine
            ->shouldReceive('suggest')
            ->once()
            ->with($query, 5)
            ->andReturn($expectedSuggestions);

        $suggestions = $this->searchEngine->suggest($query);

        $this->assertIsArray($suggestions);
        $this->assertCount(3, $suggestions);
        $this->assertContains('массаж', $suggestions);
    }

    /** @test */
    public function it_can_get_popular_searches()
    {
        $expected = [
            'классический массаж',
            'тайский массаж',
            'спортивный массаж'
        ];

        $this->mockElasticEngine
            ->shouldReceive('getPopularSearches')
            ->once()
            ->with(10)
            ->andReturn($expected);

        $popular = $this->searchEngine->getPopularSearches();

        $this->assertIsArray($popular);
        $this->assertCount(3, $popular);
    }

    /** @test */
    public function it_can_index_searchable_model()
    {
        $model = Mockery::mock(SearchableInterface::class);
        
        $model->shouldReceive('toSearchableArray')->once()->andReturn([
            'id' => 1,
            'title' => 'Test',
            'description' => 'Test description'
        ]);

        $model->shouldReceive('searchableAs')->once()->andReturn('masters');
        $model->shouldReceive('getKey')->once()->andReturn(1);

        $this->mockElasticEngine
            ->shouldReceive('index')
            ->once()
            ->with('masters', 1, [
                'id' => 1,
                'title' => 'Test',
                'description' => 'Test description'
            ])
            ->andReturn(true);

        $result = $this->searchEngine->index($model);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_remove_from_index()
    {
        $model = Mockery::mock(SearchableInterface::class);
        
        $model->shouldReceive('searchableAs')->once()->andReturn('masters');
        $model->shouldReceive('getKey')->once()->andReturn(1);

        $this->mockElasticEngine
            ->shouldReceive('delete')
            ->once()
            ->with('masters', 1)
            ->andReturn(true);

        $result = $this->searchEngine->remove($model);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_reindex_all_models()
    {
        $models = collect([
            Mockery::mock(SearchableInterface::class),
            Mockery::mock(SearchableInterface::class)
        ]);

        foreach ($models as $index => $model) {
            $model->shouldReceive('toSearchableArray')->once()->andReturn([
                'id' => $index + 1,
                'title' => "Test {$index}"
            ]);
            $model->shouldReceive('searchableAs')->once()->andReturn('masters');
            $model->shouldReceive('getKey')->once()->andReturn($index + 1);
        }

        $this->mockElasticEngine
            ->shouldReceive('bulkIndex')
            ->once()
            ->andReturn(true);

        $result = $this->searchEngine->reindexAll($models);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_empty_search_results()
    {
        $query = new SearchQueryDTO(
            query: 'несуществующий запрос',
            types: ['masters', 'services']
        );

        $this->mockElasticEngine
            ->shouldReceive('search')
            ->once()
            ->andReturn([
                'masters' => [],
                'services' => []
            ]);

        $results = $this->searchEngine->search($query);

        $this->assertIsArray($results);
        $this->assertEmpty($results['masters']);
        $this->assertEmpty($results['services']);
    }

    /** @test */
    public function it_applies_filters_correctly()
    {
        $query = new SearchQueryDTO(
            query: 'массаж',
            types: ['masters'],
            filters: [
                'city' => 'Moscow',
                'min_rating' => 4.5,
                'has_home_service' => true
            ],
            sort: 'rating',
            order: 'desc'
        );

        $this->mockElasticEngine
            ->shouldReceive('search')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return $arg->filters['city'] === 'Moscow' 
                    && $arg->filters['min_rating'] === 4.5
                    && $arg->sort === 'rating'
                    && $arg->order === 'desc';
            }))
            ->andReturn(['masters' => []]);

        $this->searchEngine->search($query);
    }

    /** @test */
    public function it_validates_search_query()
    {
        $query = new SearchQueryDTO(
            query: '',
            types: []
        );

        $errors = $query->validate();

        $this->assertArrayHasKey('query', $errors);
        $this->assertArrayHasKey('types', $errors);
    }
}