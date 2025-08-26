<?php

namespace App\Domain\Analytics\Collectors;

use App\Domain\Analytics\Contracts\AnalyticsServiceInterface;
use App\Domain\Analytics\DTOs\TrackUserActionDTO;
use App\Domain\Analytics\Models\UserAction;
use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;
use App\Domain\Booking\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Сборщик базовых конверсий (регистрация, объявления, бронирования)
 */
class BaseConversionCollector
{
    protected AnalyticsServiceInterface $analyticsService;
    protected array $conversionValues;

    public function __construct(AnalyticsServiceInterface $analyticsService, array $conversionValues)
    {
        $this->analyticsService = $analyticsService;
        $this->conversionValues = $conversionValues;
    }

    /**
     * Собрать регистрацию пользователя
     */
    public function collectRegistration(User $user, Request $request, array $additionalData = []): ?UserAction
    {
        try {
            $properties = array_merge([
                'user_name' => $user->name,
                'user_email' => $user->email,
                'registration_source' => $request->get('source'),
                'referral_code' => $request->get('ref'),
            ], $additionalData);

            $dto = new TrackUserActionDTO(
                actionType: UserAction::ACTION_REGISTER,
                userId: $user->id,
                actionableType: User::class,
                actionableId: $user->id,
                properties: $properties,
                sessionId: $request->session()->getId(),
                isConversion: true,
                conversionValue: $this->conversionValues[UserAction::ACTION_REGISTER] ?? 0
            );

            return $this->analyticsService->trackUserAction($dto);

        } catch (\Exception $e) {
            Log::error('Failed to collect registration conversion', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return null;
        }
    }

    /**
     * Собрать создание объявления
     */
    public function collectAdCreation(Ad $ad, Request $request, array $additionalData = []): ?UserAction
    {
        try {
            $properties = array_merge([
                'ad_title' => $ad->title,
                'ad_type' => $ad->type,
                'ad_category' => $ad->category,
                'price' => $ad->price,
                'location' => $ad->location,
                'has_photos' => !empty($ad->photos),
                'photo_count' => is_array($ad->photos) ? count($ad->photos) : 0,
            ], $additionalData);

            $dto = new TrackUserActionDTO(
                actionType: UserAction::ACTION_CREATE_AD,
                userId: $ad->user_id,
                actionableType: Ad::class,
                actionableId: $ad->id,
                properties: $properties,
                sessionId: $request->session()->getId(),
                isConversion: true,
                conversionValue: $this->conversionValues[UserAction::ACTION_CREATE_AD] ?? 0
            );

            return $this->analyticsService->trackUserAction($dto);

        } catch (\Exception $e) {
            Log::error('Failed to collect ad creation conversion', [
                'error' => $e->getMessage(),
                'ad_id' => $ad->id,
            ]);

            return null;
        }
    }

    /**
     * Собрать бронирование услуги
     */
    public function collectBooking(Booking $booking, Request $request, array $additionalData = []): ?UserAction
    {
        try {
            $properties = array_merge([
                'service_type' => $booking->service_type,
                'booking_date' => $booking->booking_date->format('Y-m-d'),
                'booking_time' => $booking->booking_time,
                'duration' => $booking->duration,
                'price' => $booking->price,
                'master_id' => $booking->master_id,
                'payment_method' => $booking->payment_method,
            ], $additionalData);

            $dto = new TrackUserActionDTO(
                actionType: UserAction::ACTION_BOOK_SERVICE,
                userId: $booking->user_id,
                actionableType: Booking::class,
                actionableId: $booking->id,
                properties: $properties,
                sessionId: $request->session()->getId(),
                isConversion: true,
                conversionValue: $booking->price ?? $this->conversionValues[UserAction::ACTION_BOOK_SERVICE] ?? 0
            );

            return $this->analyticsService->trackUserAction($dto);

        } catch (\Exception $e) {
            Log::error('Failed to collect booking conversion', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->id,
            ]);

            return null;
        }
    }

    /**
     * Собрать завершение бронирования
     */
    public function collectBookingCompletion(Booking $booking, array $additionalData = []): ?UserAction
    {
        try {
            $properties = array_merge([
                'completion_date' => now()->format('Y-m-d H:i:s'),
                'original_price' => $booking->price,
                'final_price' => $booking->final_price ?? $booking->price,
                'duration_actual' => $booking->actual_duration,
                'rating_given' => $booking->rating,
                'tip_amount' => $booking->tip_amount ?? 0,
            ], $additionalData);

            $dto = new TrackUserActionDTO(
                actionType: UserAction::ACTION_COMPLETE_BOOKING,
                userId: $booking->user_id,
                actionableType: Booking::class,
                actionableId: $booking->id,
                properties: $properties,
                sessionId: null,
                isConversion: true,
                conversionValue: ($booking->final_price ?? $booking->price) ?: 
                    ($this->conversionValues[UserAction::ACTION_COMPLETE_BOOKING] ?? 0)
            );

            return $this->analyticsService->trackUserAction($dto);

        } catch (\Exception $e) {
            Log::error('Failed to collect booking completion conversion', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->id,
            ]);

            return null;
        }
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
        try {
            $properties = array_merge([
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'payment_for' => $paymentFor,
                'currency' => 'RUB',
                'payment_timestamp' => now()->format('Y-m-d H:i:s'),
            ], $additionalData);

            $dto = new TrackUserActionDTO(
                actionType: UserAction::ACTION_MAKE_PAYMENT,
                userId: auth()->id(),
                actionableType: null,
                actionableId: null,
                properties: $properties,
                sessionId: $request->session()->getId(),
                isConversion: true,
                conversionValue: $amount
            );

            return $this->analyticsService->trackUserAction($dto);

        } catch (\Exception $e) {
            Log::error('Failed to collect payment conversion', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ]);

            return null;
        }
    }
}