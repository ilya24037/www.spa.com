<?php

namespace App\Domain\Payment\DTOs;

use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Domain\Payment\Services\PaymentFilterFactoryService;
use App\Domain\Payment\Services\PaymentFilterDateService;
use App\Domain\Payment\Services\PaymentFilterBuilderService;
use App\Domain\Payment\Services\PaymentFilterAnalysisService;
use Carbon\Carbon;

/**
 * DTO для фильтрации платежей
 */
class PaymentFilterDTO
{
    public function __construct(
        public ?array $statuses = null,
        public ?array $methods = null,
        public ?array $types = null,
        public ?int $userId = null,
        public ?string $gateway = null,
        public ?float $amountFrom = null,
        public ?float $amountTo = null,
        public ?Carbon $dateFrom = null,
        public ?Carbon $dateTo = null,
        public ?string $payableType = null,
        public ?bool $hasRefunds = null,
        public ?string $search = null,
        public ?string $currency = null,
        public ?string $sortBy = 'created_at',
        public ?string $sortOrder = 'desc',
        public int $page = 1,
        public int $perPage = 20,
    ) {}

    /**
     * Создать из массива
     */
    public static function fromArray(array $data): self
    {
        return new self(
            statuses: self::parseEnumArray($data['statuses'] ?? null, PaymentStatus::class),
            methods: self::parseEnumArray($data['methods'] ?? null, PaymentMethod::class),
            types: self::parseEnumArray($data['types'] ?? null, PaymentType::class),
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            gateway: $data['gateway'] ?? null,
            amountFrom: isset($data['amount_from']) ? (float) $data['amount_from'] : null,
            amountTo: isset($data['amount_to']) ? (float) $data['amount_to'] : null,
            dateFrom: isset($data['date_from']) ? Carbon::parse($data['date_from']) : null,
            dateTo: isset($data['date_to']) ? Carbon::parse($data['date_to']) : null,
            payableType: $data['payable_type'] ?? null,
            hasRefunds: isset($data['has_refunds']) ? (bool) $data['has_refunds'] : null,
            search: $data['search'] ?? null,
            currency: $data['currency'] ?? null,
            sortBy: $data['sort_by'] ?? 'created_at',
            sortOrder: $data['sort_order'] ?? 'desc',
            page: isset($data['page']) ? (int) $data['page'] : 1,
            perPage: isset($data['per_page']) ? (int) $data['per_page'] : 20,
        );
    }

    /**
     * Создать из HTTP запроса
     */
    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return self::fromArray($request->all());
    }

    // === ФАБРИЧНЫЕ МЕТОДЫ (делегируем в сервисы) ===

    /**
     * Фильтр для пользователя
     */
    public static function forUser(int $userId, array $additionalFilters = []): self
    {
        return PaymentFilterFactoryService::forUser($userId, $additionalFilters);
    }

    /**
     * Фильтр для успешных платежей
     */
    public static function successful(array $additionalFilters = []): self
    {
        return PaymentFilterFactoryService::successful($additionalFilters);
    }

    /**
     * Фильтр для неудачных платежей
     */
    public static function failed(array $additionalFilters = []): self
    {
        return PaymentFilterFactoryService::failed($additionalFilters);
    }

    /**
     * Фильтр для ожидающих платежей
     */
    public static function pending(array $additionalFilters = []): self
    {
        return PaymentFilterFactoryService::pending($additionalFilters);
    }

    /**
     * Фильтр за период
     */
    public static function forPeriod(Carbon $from, Carbon $to, array $additionalFilters = []): self
    {
        return PaymentFilterDateService::forPeriod($from, $to, $additionalFilters);
    }

    /**
     * Фильтр за сегодня
     */
    public static function today(array $additionalFilters = []): self
    {
        return PaymentFilterDateService::today($additionalFilters);
    }

    /**
     * Фильтр за вчера
     */
    public static function yesterday(array $additionalFilters = []): self
    {
        return PaymentFilterDateService::yesterday($additionalFilters);
    }

    /**
     * Фильтр за неделю
     */
    public static function thisWeek(array $additionalFilters = []): self
    {
        return PaymentFilterDateService::thisWeek($additionalFilters);
    }

    /**
     * Фильтр за месяц
     */
    public static function thisMonth(array $additionalFilters = []): self
    {
        return PaymentFilterDateService::thisMonth($additionalFilters);
    }

    /**
     * Преобразовать в массив для запроса
     */
    public function toArray(): array
    {
        return array_filter([
            'statuses' => $this->statuses ? array_map(fn($s) => $s->value, $this->statuses) : null,
            'methods' => $this->methods ? array_map(fn($m) => $m->value, $this->methods) : null,
            'types' => $this->types ? array_map(fn($t) => $t->value, $this->types) : null,
            'user_id' => $this->userId,
            'gateway' => $this->gateway,
            'amount_from' => $this->amountFrom,
            'amount_to' => $this->amountTo,
            'date_from' => $this->dateFrom?->toDateString(),
            'date_to' => $this->dateTo?->toDateString(),
            'payable_type' => $this->payableType,
            'has_refunds' => $this->hasRefunds,
            'search' => $this->search,
            'currency' => $this->currency,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ], function($value) {
            return $value !== null;
        });
    }

    /**
     * Получить параметры для репозитория
     */
    public function getRepositoryFilters(): array
    {
        return array_filter([
            'status' => $this->statuses ? $this->statuses[0] : null, // Для простоты берем первый
            'method' => $this->methods ? $this->methods[0] : null,
            'type' => $this->types ? $this->types[0] : null,
            'user_id' => $this->userId,
            'gateway' => $this->gateway,
            'amount_from' => $this->amountFrom,
            'amount_to' => $this->amountTo,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'payable_type' => $this->payableType,
            'has_refunds' => $this->hasRefunds,
        ], function($value) {
            return $value !== null;
        });
    }

    // === АНАЛИЗ И ПОСТРОЕНИЕ (делегируем в сервисы) ===

    /**
     * Проверить есть ли активные фильтры
     */
    public function hasActiveFilters(): bool
    {
        return app(PaymentFilterAnalysisService::class)->hasActiveFilters($this);
    }

    /**
     * Получить количество активных фильтров
     */
    public function getActiveFiltersCount(): int
    {
        return app(PaymentFilterAnalysisService::class)->getActiveFiltersCount($this);
    }

    /**
     * Получить описание фильтров
     */
    public function getDescription(): array
    {
        return app(PaymentFilterAnalysisService::class)->getDescription($this);
    }

    /**
     * Сбросить все фильтры
     */
    public function reset(): self
    {
        return app(PaymentFilterBuilderService::class)->reset($this);
    }

    /**
     * Установить сортировку
     */
    public function sortBy(string $field, string $order = 'desc'): self
    {
        return app(PaymentFilterBuilderService::class)->sortBy($this, $field, $order);
    }

    /**
     * Установить пагинацию
     */
    public function paginate(int $page, int $perPage = 20): self
    {
        return app(PaymentFilterBuilderService::class)->paginate($this, $page, $perPage);
    }

    /**
     * Добавить статус
     */
    public function withStatus(PaymentStatus $status): self
    {
        return app(PaymentFilterBuilderService::class)->withStatus($this, $status);
    }

    /**
     * Добавить метод
     */
    public function withMethod(PaymentMethod $method): self
    {
        return app(PaymentFilterBuilderService::class)->withMethod($this, $method);
    }

    /**
     * Добавить тип
     */
    public function withType(PaymentType $type): self
    {
        return app(PaymentFilterBuilderService::class)->withType($this, $type);
    }

    /**
     * Установить пользователя
     */
    public function forUser(int $userId): self
    {
        return app(PaymentFilterBuilderService::class)->forUser($this, $userId);
    }

    /**
     * Установить диапазон сумм
     */
    public function amountRange(float $from, float $to): self
    {
        return app(PaymentFilterBuilderService::class)->amountRange($this, $from, $to);
    }

    /**
     * Установить диапазон дат
     */
    public function dateRange(Carbon $from, Carbon $to): self
    {
        return app(PaymentFilterBuilderService::class)->dateRange($this, $from, $to);
    }

    /**
     * Установить поиск
     */
    public function search(string $query): self
    {
        return app(PaymentFilterBuilderService::class)->search($this, $query);
    }

    /**
     * Парсинг массива enum'ов
     */
    protected static function parseEnumArray(?array $values, string $enumClass): ?array
    {
        if (!$values) {
            return null;
        }

        return array_filter(
            array_map(fn($value) => $enumClass::tryFrom($value), $values)
        );
    }

}