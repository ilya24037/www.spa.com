<?php

namespace App\Support\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Базовый интерфейс для всех репозиториев
 * 
 * @template T of Model
 */
interface RepositoryInterface
{
    /**
     * Найти запись по ID
     * 
     * @return T|null
     */
    public function find(int $id): ?Model;

    /**
     * Найти запись по ID или выбросить исключение
     * 
     * @return T
     */
    public function findOrFail(int $id): Model;

    /**
     * Получить все записи
     * 
     * @return Collection<int, T>
     */
    public function all(): Collection;

    /**
     * Создать новую запись
     * 
     * @return T
     */
    public function create(array $data): Model;

    /**
     * Обновить запись
     */
    public function update(int $id, array $data): bool;

    /**
     * Удалить запись
     */
    public function delete(int $id): bool;

    /**
     * Получить записи с пагинацией
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Найти записи по условию
     * 
     * @return Collection<int, T>
     */
    public function findBy(string $field, $value): Collection;

    /**
     * Найти первую запись по условию
     * 
     * @return T|null
     */
    public function findOneBy(string $field, $value): ?Model;

    /**
     * Проверить существование записи
     */
    public function exists(int $id): bool;

    /**
     * Получить количество записей
     */
    public function count(): int;

    /**
     * Получить записи с условием where
     * 
     * @return Collection<int, T>
     */
    public function where(string $field, $operator, $value = null): Collection;

    /**
     * Обновить или создать запись
     * 
     * @return T
     */
    public function updateOrCreate(array $attributes, array $values = []): Model;
}