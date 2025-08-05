<?php

namespace App\Support\Repositories;

use App\Support\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * Базовый репозиторий для всех репозиториев проекта
 * Реализует стандартные CRUD операции согласно CLAUDE.md
 * 
 * @template T of Model
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Найти запись по ID
     */
    public function find(int $id): ?Model
    {
        try {
            return $this->model->find($id);
        } catch (\Exception $e) {
            Log::error('Failed to find record', [
                'model' => get_class($this->model),
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Найти запись по ID или выбросить исключение
     */
    public function findOrFail(int $id): Model
    {
        try {
            return $this->model->findOrFail($id);
        } catch (\Exception $e) {
            Log::error('Record not found', [
                'model' => get_class($this->model),
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Создать новую запись
     */
    public function create(array $data): Model
    {
        try {
            $record = $this->model->create($data);
            
            Log::info('Record created successfully', [
                'model' => get_class($this->model),
                'id' => $record->id,
                'data_keys' => array_keys($data)
            ]);
            
            return $record;
        } catch (\Exception $e) {
            Log::error('Failed to create record', [
                'model' => get_class($this->model),
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Обновить запись по ID
     */
    public function update(int $id, array $data): bool
    {
        try {
            $updated = $this->model->where('id', $id)->update($data);
            
            if ($updated) {
                Log::info('Record updated successfully', [
                    'model' => get_class($this->model),
                    'id' => $id,
                    'updated_fields' => array_keys($data)
                ]);
            }
            
            return (bool) $updated;
        } catch (\Exception $e) {
            Log::error('Failed to update record', [
                'model' => get_class($this->model),
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Удалить запись по ID
     */
    public function delete(int $id): bool
    {
        try {
            $record = $this->find($id);
            if (!$record) {
                return false;
            }
            
            $deleted = $record->delete();
            
            if ($deleted) {
                Log::info('Record deleted successfully', [
                    'model' => get_class($this->model),
                    'id' => $id
                ]);
            }
            
            return $deleted;
        } catch (\Exception $e) {
            Log::error('Failed to delete record', [
                'model' => get_class($this->model),
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Получить все записи
     */
    public function all(): Collection
    {
        try {
            return $this->model->all();
        } catch (\Exception $e) {
            Log::error('Failed to fetch all records', [
                'model' => get_class($this->model),
                'error' => $e->getMessage()
            ]);
            return new Collection();
        }
    }

    /**
     * Базовый поиск с пагинацией
     * Наследники должны переопределить этот метод
     */
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        try {
            $query = $this->model->newQuery();
            
            // Базовая сортировка
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            
            $query->orderBy($sortBy, $sortOrder);
            
            return $query->paginate($perPage);
        } catch (\Exception $e) {
            Log::error('Search failed', [
                'model' => get_class($this->model),
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            
            // Возвращаем пустую пагинацию в случае ошибки
            return $this->model->newQuery()->paginate(0);
        }
    }

    /**
     * Получить модель репозитория
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Получить новый query builder
     */
    protected function query()
    {
        return $this->model->newQuery();
    }

    /**
     * Выполнить операцию в транзакции
     */
    protected function transaction(callable $callback)
    {
        try {
            return \DB::transaction($callback);
        } catch (\Exception $e) {
            Log::error('Transaction failed', [
                'model' => get_class($this->model),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Безопасное применение фильтров
     */
    protected function applyFilters($query, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            
            // Игнорируем системные параметры
            if (in_array($key, ['sort_by', 'sort_order', 'per_page'])) {
                continue;
            }
            
            // Применяем фильтр только если колонка существует
            if ($this->model->getConnection()->getSchemaBuilder()->hasColumn($this->model->getTable(), $key)) {
                $query->where($key, $value);
            }
        }
        
        return $query;
    }
}