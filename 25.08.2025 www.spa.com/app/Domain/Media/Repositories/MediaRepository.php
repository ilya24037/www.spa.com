<?php

namespace App\Domain\Media\Repositories;

/**
 * Минимальная заглушка MediaRepository для восстановления работоспособности
 * TODO: Реализовать правильную DDD архитектуру позже
 */
class MediaRepository
{
    public function __construct()
    {
        // Пустой конструктор
    }
    
    // Минимальные заглушки методов для совместимости
    public function find($id) { return null; }
    public function create(array $data) { return null; }
    public function update($id, array $data) { return false; }
    public function delete($id) { return false; }
    public function getStatistics() { return []; }
    public function search(array $filters, $limit = 10) { return []; }
    public function getTopLargestFiles($limit = 5) { return []; }
    public function findForEntity($entity, $collection = null) { return []; }
    public function forceDelete($id) { return false; }
    public function restore($id) { return false; }
    public function reorderForEntity($entity, array $mediaIds, $collection = null) { return false; }
    public function cleanupExpired() { return 0; }
    public function cleanupOrphaned() { return 0; }
    public function countForEntity($entity, $collection = null) { return 0; }
}