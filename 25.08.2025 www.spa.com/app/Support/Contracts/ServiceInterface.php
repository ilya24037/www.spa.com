<?php

namespace App\Support\Contracts;

use Illuminate\Support\Collection;

/**
 * Базовый интерфейс для всех сервисов
 */
interface ServiceInterface
{
    /**
     * Получить репозиторий сервиса
     */
    public function getRepository(): RepositoryInterface;

    /**
     * Найти запись по ID
     */
    public function find(int $id);

    /**
     * Создать новую запись
     */
    public function create(array $data);

    /**
     * Обновить запись
     */
    public function update(int $id, array $data);

    /**
     * Удалить запись
     */
    public function delete(int $id): bool;

    /**
     * Получить все записи
     */
    public function all(): Collection;

    /**
     * Получить записи с пагинацией
     */
    public function paginate(int $perPage = 15);
}