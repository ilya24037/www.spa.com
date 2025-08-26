<?php

namespace App\Domain\Analytics\Collectors;

use App\Domain\Analytics\Contracts\AnalyticsServiceInterface;
use App\Domain\Analytics\DTOs\TrackUserActionDTO;
use App\Domain\Analytics\Models\UserAction;
use App\Domain\Master\Models\MasterProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Сборщик контактных конверсий (телефон, WhatsApp, контакты с мастерами)
 */
class ContactConversionCollector
{
    protected AnalyticsServiceInterface $analyticsService;
    protected array $conversionValues;

    public function __construct(AnalyticsServiceInterface $analyticsService, array $conversionValues)
    {
        $this->analyticsService = $analyticsService;
        $this->conversionValues = $conversionValues;
    }

    /**
     * Собрать контакт с мастером
     */
    public function collectMasterContact(
        int $masterId, 
        string $contactMethod, 
        Request $request,
        array $additionalData = []
    ): ?UserAction {
        try {
            $properties = array_merge([
                'contact_method' => $contactMethod, // phone, whatsapp, telegram, message
                'master_id' => $masterId,
                'contact_timestamp' => now()->format('Y-m-d H:i:s'),
            ], $additionalData);

            $dto = new TrackUserActionDTO(
                actionType: UserAction::ACTION_CONTACT_MASTER,
                userId: Auth::id(),
                actionableType: MasterProfile::class,
                actionableId: $masterId,
                properties: $properties,
                sessionId: $request->session()->getId(),
                isConversion: true,
                conversionValue: $this->conversionValues[UserAction::ACTION_CONTACT_MASTER] ?? 0
            );

            return $this->analyticsService->trackUserAction($dto);

        } catch (\Exception $e) {
            Log::error('Failed to collect master contact conversion', [
                'error' => $e->getMessage(),
                'master_id' => $masterId,
                'contact_method' => $contactMethod,
            ]);

            return null;
        }
    }

    /**
     * Собрать телефонный клик
     */
    public function collectPhoneClick(int $masterId, string $phoneNumber, Request $request): ?UserAction
    {
        return $this->collectMasterContact(
            masterId: $masterId,
            contactMethod: 'phone',
            request: $request,
            additionalData: [
                'phone_number' => $phoneNumber,
                'click_source' => 'phone_button',
            ]
        );
    }

    /**
     * Собрать WhatsApp клик
     */
    public function collectWhatsAppClick(int $masterId, string $whatsappNumber, Request $request): ?UserAction
    {
        return $this->collectMasterContact(
            masterId: $masterId,
            contactMethod: 'whatsapp',
            request: $request,
            additionalData: [
                'whatsapp_number' => $whatsappNumber,
                'click_source' => 'whatsapp_button',
            ]
        );
    }

    /**
     * Собрать Telegram клик
     */
    public function collectTelegramClick(int $masterId, string $telegramUsername, Request $request): ?UserAction
    {
        return $this->collectMasterContact(
            masterId: $masterId,
            contactMethod: 'telegram',
            request: $request,
            additionalData: [
                'telegram_username' => $telegramUsername,
                'click_source' => 'telegram_button',
            ]
        );
    }

    /**
     * Собрать отправку сообщения
     */
    public function collectMessageSent(
        int $masterId, 
        string $messageType,
        Request $request,
        array $additionalData = []
    ): ?UserAction {
        return $this->collectMasterContact(
            masterId: $masterId,
            contactMethod: 'message',
            request: $request,
            additionalData: array_merge([
                'message_type' => $messageType,
                'click_source' => 'message_form',
            ], $additionalData)
        );
    }
}