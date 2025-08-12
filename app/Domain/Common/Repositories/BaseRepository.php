<?php

namespace App\Domain\Common\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Базовый репозиторий с основными CRUD операциями
 * @template T of Model
 */
abstract class BaseRepository
{
    /**
     * @var Model|Builder
     */
    protected $model;

    /**
     * Получить класс модели
     * @return class-string<T>
     */
    abstract protected function getModelClass(): string;

    public function __construct()
    {
        $modelClass = $this->getModelClass();
        $this->model = new $modelClass();
    }

    /**
     * Найти модель по ID
     */
    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
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
        $model = $this->findOrFail($id);
        return $model->update($data);
    }

    /**
     * Удалить запись по ID
     */
    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);
        return $model->delete();
    }
    
    /**
     * Удалить модель
     */
    public function deleteModel(Model $model): bool
    {
        return $model->delete();
    }
}