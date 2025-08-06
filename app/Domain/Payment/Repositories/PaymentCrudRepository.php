<?php

namespace App\Domain\Payment\Repositories;

use App\Domain\Payment\Models\Payment;
use App\Domain\Common\Repositories\BaseRepository;

/**
 * Репозиторий для базовых CRUD операций с платежами
 */
class PaymentCrudRepository extends BaseRepository
{
    /**
     * Получить класс модели
     */
    protected function getModelClass(): string
    {
        return Payment::class;
    }

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Найти платеж по ID
     */
    public function find(int $id): ?Payment
    {
        return $this->model->find($id);
    }

    /**
     * Создать новый платеж
     */
    public function create(array $data): Payment
    {
        return $this->model->create($data);
    }

    /**
     * Обновить платеж
     * Переопределяем базовый метод для совместимости
     */
    public function update(int $id, array $data): bool
    {
        $payment = $this->findOrFail($id);
        return $payment->update($data);
    }
    
    /**
     * Обновить платеж (старая сигнатура для обратной совместимости)
     */
    public function updatePayment(Payment $payment, array $data): bool
    {
        return $payment->update($data);
    }

    /**
     * Удалить платеж
     * Переопределяем базовый метод для совместимости
     */
    public function delete(int $id): bool
    {
        $payment = $this->findOrFail($id);
        return $payment->delete();
    }
    
    /**
     * Удалить платеж (старая сигнатура для обратной совместимости)
     */
    public function deletePayment(Payment $payment): bool
    {
        return $payment->delete();
    }

    /**
     * Найти платеж или выбросить исключение
     */
    public function findOrFail(int $id): Payment
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Создать несколько платежей
     */
    public function createMany(array $payments): bool
    {
        return $this->model->insert($payments);
    }

    /**
     * Обновить несколько платежей по условию
     */
    public function updateWhere(array $conditions, array $data): int
    {
        $query = $this->model->query();
        
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->update($data);
    }

    /**
     * Удалить платежи по условию
     */
    public function deleteWhere(array $conditions): int
    {
        $query = $this->model->query();
        
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->delete();
    }
}