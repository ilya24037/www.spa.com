<?php

namespace App\Domain\Common\Repositories;

use App\Domain\Common\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Базовый репозиторий с общими CRUD операциями
 * Согласно карте рефакторинга - устранение дублирования кода
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    /**
     * Найти модель по ID
     */
    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Найти модель по ID или выбросить исключение
     */
    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Получить все записи
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    /**
     * Создать новую запись
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Обновить запись
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * Обновить модель
     */
    public function updateModel(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    /**
     * Удалить запись
     */
    public function delete(int $id): bool
    {
        $model = $this->find($id);
        return $model ? $model->delete() : false;
    }

    /**
     * Удалить модель
     */
    public function deleteModel(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Получить записи с пагинацией
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->paginate($perPage, $columns);
    }

    /**
     * Найти записи по условию
     */
    public function findWhere(array $where, array $columns = ['*']): Collection
    {
        $query = $this->model->newQuery();
        
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->get($columns);
    }

    /**
     * Найти одну запись по условию
     */
    public function findWhereFirst(array $where, array $columns = ['*']): ?Model
    {
        $query = $this->model->newQuery();
        
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->first($columns);
    }

    /**
     * Подсчитать количество записей
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Проверить существование записи
     */
    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * Получить новый экземпляр запроса
     */
    protected function newQuery()
    {
        return $this->model->newQuery();
    }

    /**
     * Применить фильтры к запросу
     */
    protected function applyFilters($query, array $filters = [])
    {
        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
        }
        
        return $query;
    }
}