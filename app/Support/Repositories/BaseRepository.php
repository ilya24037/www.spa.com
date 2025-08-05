<?php

namespace App\Support\Repositories;

use App\Support\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Базовый репозиторий с общей логикой
 * 
 * @template T of Model
 * @implements RepositoryInterface<T>
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @var T
     */
    protected Model $model;

    /**
     * Конструктор базового репозитория
     * 
     * @param T $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Найти запись по ID
     * 
     * @return T|null
     */
    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Найти запись по ID или выбросить исключение
     * 
     * @return T
     */
    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Получить все записи
     * 
     * @return Collection<int, T>
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Создать новую запись
     * 
     * @return T
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
        $record = $this->findOrFail($id);
        return $record->update($data);
    }

    /**
     * Удалить запись
     */
    public function delete(int $id): bool
    {
        $record = $this->findOrFail($id);
        return $record->delete();
    }

    /**
     * Получить записи с пагинацией
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Найти записи по условию
     */
    public function findBy(string $field, $value): Collection
    {
        return $this->model->where($field, $value)->get();
    }

    /**
     * Найти первую запись по условию
     */
    public function findOneBy(string $field, $value): ?Model
    {
        return $this->model->where($field, $value)->first();
    }

    /**
     * Проверить существование записи
     */
    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * Получить количество записей
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Получить записи с условием where
     */
    public function where(string $field, $operator, $value = null): Collection
    {
        // Если передано только 2 аргумента, используем '=' как оператор
        if (func_num_args() === 2) {
            return $this->model->where($field, '=', $operator)->get();
        }
        
        return $this->model->where($field, $operator, $value)->get();
    }

    /**
     * Обновить или создать запись
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * Начать новый запрос
     */
    protected function newQuery()
    {
        return $this->model->newQuery();
    }

    /**
     * Загрузить связи
     */
    protected function with(array $relations)
    {
        return $this->model->with($relations);
    }
}