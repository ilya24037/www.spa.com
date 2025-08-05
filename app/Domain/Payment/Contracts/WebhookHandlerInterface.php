<?php

namespace App\Domain\Payment\Contracts;

use Illuminate\Http\Request;

/**
 * Интерфейс для обработчиков webhook от платежных систем
 */
interface WebhookHandlerInterface
{
    /**
     * Проверить подпись webhook
     */
    public function verifySignature(Request $request): bool;

    /**
     * Обработать webhook
     */
    public function handle(Request $request): array;

    /**
     * Получить тип события из webhook
     */
    public function getEventType(Request $request): ?string;

    /**
     * Получить ID платежа из webhook
     */
    public function getPaymentId(Request $request): ?string;

    /**
     * Получить статус платежа из webhook
     */
    public function getPaymentStatus(Request $request): ?string;

    /**
     * Поддерживает ли обработчик данный тип события
     */
    public function supportsEvent(string $eventType): bool;

    /**
     * Получить название платежного гейтвея
     */
    public function getGatewayName(): string;
}