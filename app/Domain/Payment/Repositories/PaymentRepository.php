<?php

namespace App\Domain\Payment\Repositories;

use App\Domain\Payment\Models\Payment;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Domain\Common\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

/**
 * Упрощенный репозиторий для работы с платежами
 * Делегирует работу специализированным репозиториям
 * 
 * @extends BaseRepository<Payment>
 */
class PaymentRepository extends BaseRepository
{
    private PaymentCrudRepository $crudRepository;
    private PaymentQueryRepository $queryRepository;
    private PaymentStatsRepository $statsRepository;
    private PaymentBusinessRepository $businessRepository;

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
        
        $this->crudRepository = new PaymentCrudRepository();
        $this->queryRepository = new PaymentQueryRepository();
        $this->statsRepository = new PaymentStatsRepository();
        $this->businessRepository = new PaymentBusinessRepository();
    }

    // === CRUD ОПЕРАЦИИ ===

    /**
     * Найти платеж по ID
     */
    public function find(int $id): ?Payment
    {
        return $this->crudRepository->find($id);
    }

    /**
     * Создать новый платеж
     */
    public function create(array $data): Payment
    {
        return $this->crudRepository->create($data);
    }

    /**
     * Обновить платеж
     */
    public function update(int $id, array $data): bool
    {
        return $this->crudRepository->update($id, $data);
    }
    
    /**
     * Обновить платеж (старая сигнатура для обратной совместимости)
     */
    public function updatePayment(Payment $payment, array $data): bool
    {
        return $this->crudRepository->updatePayment($payment, $data);
    }

    /**
     * Удалить платеж
     */
    public function delete(int $id): bool
    {
        return $this->crudRepository->delete($id);
    }
    
    /**
     * Удалить платеж (старая сигнатура для обратной совместимости)
     */
    public function deletePayment(Payment $payment): bool
    {
        return $this->crudRepository->deletePayment($payment);
    }

    // === ПОИСК И ЗАПРОСЫ ===

    /**
     * Найти платеж по номеру
     */
    public function findByNumber(string $paymentNumber): ?Payment
    {
        return $this->queryRepository->findByNumber($paymentNumber);
    }

    /**
     * Найти платеж по внешнему ID
     */
    public function findByExternalId(string $externalId, ?string $gateway = null): ?Payment
    {
        return $this->queryRepository->findByExternalId($externalId, $gateway);
    }

    /**
     * Получить платежи пользователя
     */
    public function getUserPayments(
        int $userId, 
        array $filters = [], 
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->queryRepository->getUserPayments($userId, $filters, $perPage);
    }

    /**
     * Получить платежи по статусу
     */
    public function getByStatus(PaymentStatus $status, ?int $limit = null): Collection
    {
        return $this->queryRepository->getByStatus($status, $limit);
    }

    /**
     * Получить платежи по типу
     */
    public function getByType(PaymentType $type, array $filters = []): Collection
    {
        return $this->queryRepository->getByType($type, $filters);
    }

    /**
     * Получить платежи по методу оплаты
     */
    public function getByMethod(PaymentMethod $method, array $filters = []): Collection
    {
        return $this->queryRepository->getByMethod($method, $filters);
    }

    /**
     * Получить успешные платежи
     */
    public function getSuccessful(array $filters = []): Collection
    {
        return $this->queryRepository->getSuccessful($filters);
    }

    /**
     * Получить неудачные платежи
     */
    public function getFailed(array $filters = []): Collection
    {
        return $this->queryRepository->getFailed($filters);
    }

    /**
     * Получить ожидающие платежи
     */
    public function getPending(array $filters = []): Collection
    {
        return $this->queryRepository->getPending($filters);
    }

    /**
     * Поиск платежей
     */
    public function search(
        string $query, 
        array $filters = [], 
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->queryRepository->search($query, $filters, $perPage);
    }

    /**
     * Экспорт платежей в массив
     */
    public function export(array $filters = [], int $limit = 1000): Collection
    {
        return $this->queryRepository->export($filters, $limit);
    }

    // === СТАТИСТИКА ===

    /**
     * Получить статистику платежей
     */
    public function getStatistics(array $filters = []): array
    {
        return $this->statsRepository->getStatistics($filters);
    }

    /**
     * Получить статистику по методам оплаты
     */
    public function getStatsByMethod(array $filters = []): array
    {
        return $this->statsRepository->getStatsByMethod($filters);
    }

    /**
     * Получить статистику по типам платежей
     */
    public function getStatsByType(array $filters = []): array
    {
        return $this->statsRepository->getStatsByType($filters);
    }

    /**
     * Получить статистику по статусам
     */
    public function getStatsByStatus(array $filters = []): array
    {
        return $this->statsRepository->getStatsByStatus($filters);
    }

    /**
     * Получить ежедневную статистику
     */
    public function getDailyStats(Carbon $from, Carbon $to): Collection
    {
        return $this->statsRepository->getDailyStats($from, $to);
    }

    /**
     * Получить топ пользователей по платежам
     */
    public function getTopUsers(int $limit = 10, array $filters = []): Collection
    {
        return $this->statsRepository->getTopUsers($limit, $filters);
    }

    /**
     * Получить сводку по комиссиям
     */
    public function getFeeSummary(array $filters = []): array
    {
        return $this->statsRepository->getFeeSummary($filters);
    }

    /**
     * Получить конверсию платежей
     */
    public function getConversionRate(array $filters = []): float
    {
        return $this->statsRepository->getConversionRate($filters);
    }

    /**
     * Получить среднее время обработки
     */
    public function getAverageProcessingTime(array $filters = []): ?float
    {
        return $this->statsRepository->getAverageProcessingTime($filters);
    }

    // === БИЗНЕС-ЛОГИКА ===

    /**
     * Получить просроченные платежи
     */
    public function getExpired(): Collection
    {
        return $this->businessRepository->getExpired();
    }

    /**
     * Получить платежи для возврата
     */
    public function getRefundable(array $filters = []): Collection
    {
        return $this->businessRepository->getRefundable($filters);
    }

    /**
     * Получить платежи, требующие внимания
     */
    public function getRequiringAttention(): Collection
    {
        return $this->businessRepository->getRequiringAttention();
    }

    /**
     * Пакетное обновление статуса
     */
    public function batchUpdateStatus(
        array $paymentIds, 
        PaymentStatus $status, 
        array $additionalData = []
    ): int {
        return $this->businessRepository->batchUpdateStatus($paymentIds, $status, $additionalData);
    }

    /**
     * Получить дублирующиеся платежи
     */
    public function getDuplicates(): Collection
    {
        return $this->businessRepository->getDuplicates();
    }

    /**
     * Очистка истекших платежей
     */
    public function cleanupExpired(): int
    {
        return $this->businessRepository->cleanupExpired();
    }

    // === УСТАРЕВШИЙ МЕТОД ДЛЯ СОВМЕСТИМОСТИ ===

    /**
     * Применить фильтры к запросу
     * @deprecated Метод перенесен в специализированные репозитории
     */
    protected function applyFilters($query, array $filters = []): void
    {
        // Этот метод оставлен для обратной совместимости, но логика перенесена
        // в специализированные репозитории
    }

    /**
     * Построить базовый запрос для статистики
     * @deprecated Метод перенесен в PaymentStatsRepository
     */
    protected function buildStatsQuery(array $filters = [])
    {
        // Этот метод оставлен для обратной совместимости
        return $this->model->query();
    }
}