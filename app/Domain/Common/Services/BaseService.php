<?php

namespace App\Domain\Common\Services;

use App\Domain\Common\Contracts\ServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Базовый сервис с общими методами
 * Согласно карте рефакторинга - унификация бизнес-логики
 */
abstract class BaseService implements ServiceInterface
{
    /**
     * Валидация данных
     */
    public function validate(array $data, array $rules = []): bool
    {
        if (empty($rules)) {
            $rules = $this->getValidationRules();
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return true;
    }

    /**
     * Подготовка данных перед обработкой
     */
    public function prepareData(array $data): array
    {
        // Убираем null значения
        $data = array_filter($data, function ($value) {
            return $value !== null;
        });

        // Обрезаем строки
        return array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $data);
    }

    /**
     * Обработка ошибок
     */
    public function handleError(\Throwable $exception): void
    {
        $this->log('Service error: ' . $exception->getMessage(), [
            'exception' => $exception->getTraceAsString(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }

    /**
     * Логирование операций
     */
    public function log(string $message, array $context = []): void
    {
        Log::info($message, array_merge($context, [
            'service' => static::class,
            'timestamp' => now(),
        ]));
    }

    /**
     * Получить правила валидации (для переопределения в наследниках)
     */
    protected function getValidationRules(): array
    {
        return [];
    }

    /**
     * Выполнить операцию с обработкой ошибок
     */
    protected function executeWithErrorHandling(callable $operation)
    {
        try {
            return $operation();
        } catch (\Throwable $exception) {
            $this->handleError($exception);
            throw $exception;
        }
    }

    /**
     * Проверка разрешений (для переопределения в наследниках)
     */
    protected function checkPermissions($user = null, string $permission = ''): bool
    {
        return true; // По умолчанию разрешено
    }
}