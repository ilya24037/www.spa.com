<?php

namespace App\Domain\Common\Contracts;

/**
 * Базовый интерфейс для всех сервисов
 * Согласно карте рефакторинга - унификация бизнес-логики
 */
interface ServiceInterface
{
    /**
     * Валидация данных
     */
    public function validate(array $data, array $rules = []): bool;

    /**
     * Подготовка данных перед обработкой
     */
    public function prepareData(array $data): array;

    /**
     * Обработка ошибок
     */
    public function handleError(\Throwable $exception): void;

    /**
     * Логирование операций
     */
    public function log(string $message, array $context = []): void;
}