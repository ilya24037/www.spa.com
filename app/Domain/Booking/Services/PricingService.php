<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Service\Models\Service;
use App\Domain\User\Models\User;
use App\Enums\BookingType;
use Carbon\Carbon;

/**
 * Сервис расчета стоимости бронирований
 * Отвечает за все финансовые расчеты, скидки и комиссии
 */
class PricingService
{
    /**
     * Рассчитать стоимость бронирования
     */
    public function calculatePricing(Service $service, array $data): array
    {
        $servicePrice = $service->price;
        $travelFee = ($data['service_location'] === 'home') ? 500 : 0;
        
        // Логика скидок
        $discountAmount = $this->calculateDiscount($service, $data);
        $totalPrice = $servicePrice + $travelFee - $discountAmount;

        return [
            'service_price' => $servicePrice,
            'travel_fee' => $travelFee,
            'discount_amount' => $discountAmount,
            'total_price' => $totalPrice
        ];
    }

    /**
     * Расчет стоимости с учетом типа бронирования
     */
    public function calculatePricingWithType(
        Service $service, 
        array $data, 
        BookingType $type
    ): array {
        $servicePrice = $service->price;
        $deliveryFee = 0;
        
        if ($type->hasDeliveryFee()) {
            $deliveryFee = $data['delivery_fee'] ?? 500; // Стандартная плата за выезд
        }
        
        $discountAmount = $this->calculateDiscountForType($service, $data, $type);
        $totalPrice = $servicePrice + $deliveryFee - $discountAmount;

        return [
            'service_price' => $servicePrice,
            'delivery_fee' => $deliveryFee,
            'discount_amount' => $discountAmount,
            'total_price' => max(0, $totalPrice),
        ];
    }

    /**
     * Рассчитать размер скидки
     */
    public function calculateDiscount(Service $service, array $data): float
    {
        $discount = 0;
        
        // Скидка для первого бронирования
        if ($this->isFirstBooking($data)) {
            $discount += $service->price * 0.1; // 10% скидка
        }
        
        // Скидка за отсутствие выезда
        if ($data['service_location'] === 'salon') {
            $discount += 200; // фиксированная скидка за посещение салона
        }
        
        // Скидка в будние дни
        $bookingDate = Carbon::parse($data['booking_date']);
        if ($bookingDate->isWeekday()) {
            $discount += $service->price * 0.05; // 5% скидка в будни
        }
        
        return min($discount, $service->price * 0.3); // максимум 30% скидки
    }

    /**
     * Расчет скидки с учетом типа бронирования
     */
    public function calculateDiscountForType(
        Service $service, 
        array $data, 
        BookingType $type
    ): float {
        $discount = 0;
        
        // Базовые скидки
        if ($this->isFirstBooking($data)) {
            $discount += $service->price * 0.1;
        }
        
        // Скидки по типу бронирования
        if ($type === BookingType::INCALL) {
            $discount += 200; // Скидка за посещение салона
        } elseif ($type === BookingType::ONLINE) {
            $discount += $service->price * 0.05; // 5% скидка за онлайн
        }
        
        // Скидка в будние дни
        $startTime = Carbon::parse($data['start_time'] ?? $data['booking_date']);
        if ($startTime->isWeekday()) {
            $discount += $service->price * 0.05;
        }
        
        // Скидка за раннее бронирование (более чем за 3 дня)
        if ($startTime->diffInDays(now()) > 3) {
            $discount += $service->price * 0.03; // 3% за раннее бронирование
        }
        
        return min($discount, $service->price * 0.3); // максимум 30%
    }

    /**
     * Проверить, является ли это первым бронированием клиента
     */
    private function isFirstBooking(array $data): bool
    {
        // Если клиент авторизован, проверяем по user_id
        if (isset($data['client_id'])) {
            return !Booking::where('client_id', $data['client_id'])->exists();
        }
        
        // Если не авторизован, проверяем по телефону
        if (isset($data['client_phone'])) {
            return !Booking::where('client_phone', $data['client_phone'])->exists();
        }
        
        return false;
    }

    /**
     * Рассчитать комиссию платформы
     */
    public function calculatePlatformFee(float $totalPrice): float
    {
        // 15% комиссия платформы
        return round($totalPrice * 0.15, 2);
    }

    /**
     * Рассчитать сумму к выплате мастеру
     */
    public function calculateMasterPayout(float $totalPrice): float
    {
        $platformFee = $this->calculatePlatformFee($totalPrice);
        return $totalPrice - $platformFee;
    }

    /**
     * Рассчитать штраф за отмену
     */
    public function calculateCancellationPenalty(Booking $booking): float
    {
        $hoursUntilBooking = now()->diffInHours($booking->start_time);
        
        // Менее 2 часов - 100% штраф
        if ($hoursUntilBooking < 2) {
            return $booking->total_price;
        }
        
        // 2-6 часов - 50% штраф
        if ($hoursUntilBooking < 6) {
            return round($booking->total_price * 0.5, 2);
        }
        
        // 6-24 часа - 25% штраф
        if ($hoursUntilBooking < 24) {
            return round($booking->total_price * 0.25, 2);
        }
        
        // Более 24 часов - без штрафа
        return 0;
    }

    /**
     * Рассчитать стоимость пакета услуг
     */
    public function calculatePackagePrice(array $serviceIds): array
    {
        $services = Service::whereIn('id', $serviceIds)->get();
        $totalPrice = $services->sum('price');
        
        // Скидка за пакет услуг
        $packageDiscount = 0;
        if ($services->count() >= 3) {
            $packageDiscount = $totalPrice * 0.1; // 10% за 3+ услуги
        } elseif ($services->count() >= 2) {
            $packageDiscount = $totalPrice * 0.05; // 5% за 2 услуги
        }
        
        return [
            'base_price' => $totalPrice,
            'package_discount' => $packageDiscount,
            'final_price' => $totalPrice - $packageDiscount,
            'services_count' => $services->count()
        ];
    }

    /**
     * Применить промокод
     */
    public function applyPromoCode(float $totalPrice, string $promoCode): array
    {
        // Здесь должна быть логика проверки промокодов
        // Пока простая заглушка
        $validPromoCodes = [
            'FIRST10' => 0.1,    // 10% скидка
            'SUMMER20' => 0.2,   // 20% скидка
            'FRIEND15' => 0.15,  // 15% скидка
        ];
        
        if (!isset($validPromoCodes[$promoCode])) {
            return [
                'valid' => false,
                'discount' => 0,
                'final_price' => $totalPrice
            ];
        }
        
        $discountPercent = $validPromoCodes[$promoCode];
        $discountAmount = round($totalPrice * $discountPercent, 2);
        
        return [
            'valid' => true,
            'discount' => $discountAmount,
            'final_price' => $totalPrice - $discountAmount,
            'promo_code' => $promoCode
        ];
    }
}