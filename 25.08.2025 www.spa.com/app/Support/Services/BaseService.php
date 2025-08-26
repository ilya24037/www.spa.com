<?php

namespace App\Support\Services;

use App\Support\Contracts\ServiceInterface;
use App\Support\Contracts\RepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Базовый сервис с общей логикой
 */
abstract class BaseService implements ServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    protected RepositoryInterface $repository;

    /**
     * Префикс для кеша
     */
    protected string $cachePrefix = '';

    /**
     * Время жизни кеша в секундах
     */
    protected int $cacheTTL = 3600;

    /**
     * Конструктор базового сервиса
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->cachePrefix = class_basename(static::class) . ':';
    }

    /**
     * Получить репозиторий сервиса
     */
    public function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Найти запись по ID
     */
    public function find(int $id)
    {
        $cacheKey = $this->getCacheKey('find', $id);
        
        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($id) {
            return $this->repository->find($id);
        });
    }

    /**
     * Создать новую запись
     */
    public function create(array $data)
    {
        try {
            DB::beginTransaction();
            
            $record = $this->repository->create($data);
            
            // Очистить кеш после создания
            $this->clearCache();
            
            DB::commit();
            
            Log::info(class_basename(static::class) . ': Record created', [
                'id' => $record->id
            ]);
            
            return $record;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error(class_basename(static::class) . ': Failed to create record', [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Обновить запись
     */
    public function update(int $id, array $data)
    {
        try {
            DB::beginTransaction();
            
            $result = $this->repository->update($id, $data);
            
            // Очистить кеш после обновления
            $this->clearCache();
            $this->clearCacheKey('find', $id);
            
            DB::commit();
            
            Log::info(class_basename(static::class) . ': Record updated', [
                'id' => $id
            ]);
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error(class_basename(static::class) . ': Failed to update record', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Удалить запись
     */
    public function delete(int $id): bool
    {
        try {
            DB::beginTransaction();
            
            $result = $this->repository->delete($id);
            
            // Очистить кеш после удаления
            $this->clearCache();
            $this->clearCacheKey('find', $id);
            
            DB::commit();
            
            Log::info(class_basename(static::class) . ': Record deleted', [
                'id' => $id
            ]);
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error(class_basename(static::class) . ': Failed to delete record', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить все записи
     */
    public function all(): Collection
    {
        $cacheKey = $this->getCacheKey('all');
        
        return Cache::remember($cacheKey, $this->cacheTTL, function () {
            return $this->repository->all();
        });
    }

    /**
     * Получить записи с пагинацией
     */
    public function paginate(int $perPage = 15)
    {
        return $this->repository->paginate($perPage);
    }

    /**
     * Получить ключ кеша
     */
    protected function getCacheKey(string $method, ...$params): string
    {
        return $this->cachePrefix . $method . ':' . implode(':', $params);
    }

    /**
     * Очистить ключ кеша
     */
    protected function clearCacheKey(string $method, ...$params): void
    {
        $key = $this->getCacheKey($method, ...$params);
        Cache::forget($key);
    }

    /**
     * Очистить весь кеш сервиса
     */
    protected function clearCache(): void
    {
        // Очистить основные ключи
        Cache::forget($this->getCacheKey('all'));
        
        // Можно добавить дополнительную логику очистки кеша
        // специфичную для конкретного сервиса
    }

    /**
     * Выполнить операцию в транзакции
     */
    protected function transaction(callable $callback)
    {
        try {
            DB::beginTransaction();
            
            $result = $callback();
            
            DB::commit();
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error(class_basename(static::class) . ': Transaction failed', [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}