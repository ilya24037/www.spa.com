<?php

namespace App\Domain\Common\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Базовый интерфейс для всех репозиториев
 * Согласно карте рефакторинга - унификация CRUD операций
 */
interface RepositoryInterface
{
    /**
     * Найти модель по ID
     */
    public function find(int $id): ?Model;

    /**
     * Найти модель по ID или выбросить исключение
     */
    public function findOrFail(int $id): Model;

    /**
     * Получить все записи
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Создать новую запись
     */
    public function create(array $data): Model;

    /**
     * Обновить запись
     */
    public function update(int $id, array $data): bool;

    /**
     * Обновить модель
     */
    public function updateModel(Model $model, array $data): bool;

    /**
     * Удалить запись
     */
    public function delete(int $id): bool;

    /**
     * Удалить модель
     */
    public function deleteModel(Model $model): bool;

    /**
     * Получить записи с пагинацией
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Найти записи по условию
     */
    public function findWhere(array $where, array $columns = ['*']): Collection;

    /**
     * Найти одну запись по условию
     */
    public function findWhereFirst(array $where, array $columns = ['*']): ?Model;

    /**
     * Подсчитать количество записей
     */
    public function count(): int;

    /**
     * Проверить существование записи
     */
    public function exists(int $id): bool;
}