<?php

namespace App\Domain\Analytics\Collectors;

use App\Domain\Analytics\Contracts\AnalyticsServiceInterface;
use App\Domain\Analytics\Collectors\BaseConversionCollector;
use App\Domain\Analytics\Collectors\ContactConversionCollector;
use App\Domain\Analytics\Collectors\InteractionCollector;
use App\Domain\Analytics\Analytics\ConversionAnalyzer;
use App\Domain\Analytics\Models\UserAction;
use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;
use App\Domain\Booking\Models\Booking;
use Illuminate\Http\Request;

/**
 * Упрощенный сборщик конверсий - делегирует работу специализированным коллекторам
 */
class ConversionCollector
{
    protected BaseConversionCollector $baseCollector;
    protected ContactConversionCollector $contactCollector;
    protected InteractionCollector $interactionCollector;
    protected ConversionAnalyzer $analyzer;

    // Стоимости конверсий (можно вынести в конфиг)
    protected array $conversionValues = [
        UserAction::ACTION_REGISTER => 10.0,
        UserAction::ACTION_CREATE_AD => 50.0,
        UserAction::ACTION_BOOK_SERVICE => 100.0,
        UserAction::ACTION_COMPLETE_BOOKING => 200.0,
        UserAction::ACTION_MAKE_PAYMENT => 300.0,
        UserAction::ACTION_LEAVE_REVIEW => 15.0,
        UserAction::ACTION_CONTACT_MASTER => 25.0,
    ];

    public function __construct(AnalyticsServiceInterface $analyticsService)
    {
        $this->baseCollector = new BaseConversionCollector($analyticsService, $this->conversionValues);
        $this->contactCollector = new ContactConversionCollector($analyticsService, $this->conversionValues);
        $this->interactionCollector = new InteractionCollector($analyticsService);
        $this->analyzer = new ConversionAnalyzer($this->conversionValues);
    }

    // === БАЗОВЫЕ КОНВЕРСИИ ===

    /**
     * Собрать регистрацию пользователя
     */
    public function collectRegistration(User $user, Request $request, array $additionalData = []): ?UserAction
    {
        return $this->baseCollector->collectRegistration($user, $request, $additionalData);
    }

    /**
     * Собрать создание объявления
     */
    public function collectAdCreation(Ad $ad, Request $request, array $additionalData = []): ?UserAction
    {
        return $this->baseCollector->collectAdCreation($ad, $request, $additionalData);
    }

    /**
     * Собрать бронирование услуги
     */
    public function collectBooking(Booking $booking, Request $request, array $additionalData = []): ?UserAction
    {
        return $this->baseCollector->collectBooking($booking, $request, $additionalData);
    }

    /**
     * Собрать завершение бронирования
     */
    public function collectBookingCompletion(Booking $booking, array $additionalData = []): ?UserAction
    {
        return $this->baseCollector->collectBookingCompletion($booking, $additionalData);
    }

    /**
     * Собрать платеж
     */
    public function collectPayment(
        float $amount,
        string $paymentMethod,
        string $paymentFor,
        Request $request,
        array $additionalData = []
    ): ?UserAction {
        return $this->baseCollector->collectPayment($amount, $paymentMethod, $paymentFor, $request, $additionalData);
    }

    // === КОНТАКТНЫЕ КОНВЕРСИИ ===

    /**
     * Собрать контакт с мастером
     */
    public function collectMasterContact(
        int $masterId, 
        string $contactMethod, 
        Request $request,
        array $additionalData = []
    ): ?UserAction {
        return $this->contactCollector->collectMasterContact($masterId, $contactMethod, $request, $additionalData);
    }

    /**
     * Собрать телефонный клик
     */
    public function collectPhoneClick(int $masterId, string $phoneNumber, Request $request): ?UserAction
    {
        return $this->contactCollector->collectPhoneClick($masterId, $phoneNumber, $request);
    }

    /**
     * Собрать WhatsApp клик
     */
    public function collectWhatsAppClick(int $masterId, string $whatsappNumber, Request $request): ?UserAction
    {
        return $this->contactCollector->collectWhatsAppClick($masterId, $whatsappNumber, $request);
    }

    // === ВЗАИМОДЕЙСТВИЯ ===

    /**
     * Собрать добавление в избранное
     */
    public function collectFavoriteAdd(
        string $favoriteableType,
        int $favoriteableId,
        Request $request,
        array $additionalData = []
    ): ?UserAction {
        return $this->interactionCollector->collectFavoriteAdd($favoriteableType, $favoriteableId, $request, $additionalData);
    }

    /**
     * Собрать поиск
     */
    public function collectSearch(
        string $query,
        int $resultsCount,
        Request $request,
        array $filters = []
    ): ?UserAction {
        return $this->interactionCollector->collectSearch($query, $resultsCount, $request, $filters);
    }

    /**
     * Собрать загрузку фото
     */
    public function collectPhotoUpload(
        string $uploadableType,
        int $uploadableId,
        int $photoCount,
        Request $request,
        array $additionalData = []
    ): ?UserAction {
        return $this->interactionCollector->collectPhotoUpload($uploadableType, $uploadableId, $photoCount, $request, $additionalData);
    }

    /**
     * Собрать жалобу
     */
    public function collectReport(
        string $reportableType,
        int $reportableId,
        string $reason,
        Request $request,
        array $additionalData = []
    ): ?UserAction {
        return $this->interactionCollector->collectReport($reportableType, $reportableId, $reason, $request, $additionalData);
    }

    /**
     * Массовый сбор конверсий (для исторических данных)
     */
    public function collectBatch(array $actions): array
    {
        return $this->interactionCollector->collectBatch($actions);
    }

    // === АНАЛИТИКА ===

    /**
     * Получить воронку конверсий для пользователя
     */
    public function getUserConversionFunnel(int $userId, int $days = 30): array
    {
        return $this->analyzer->getUserConversionFunnel($userId, $days);
    }

    /**
     * Получить статистику сборщика конверсий
     */
    public function getCollectorStats(): array
    {
        return $this->analyzer->getConversionStats();
    }

    /**
     * Установить кастомную стоимость конверсии
     */
    public function setConversionValue(string $actionType, float $value): void
    {
        $this->conversionValues[$actionType] = $value;
        $this->analyzer->setConversionValue($actionType, $value);
    }

    /**
     * Получить стоимость конверсии
     */
    public function getConversionValue(string $actionType): float
    {
        return $this->analyzer->getConversionValue($actionType);
    }
}