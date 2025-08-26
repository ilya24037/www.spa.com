<?php

namespace App\Domain\Payment\DTOs;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Models\AdPlan;
use App\Domain\Payment\Models\Payment;
use Illuminate\Http\Request;

class CheckoutDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $adId,
        public readonly int $planId,
        public readonly string $paymentId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $description,
        public readonly array $metadata
    ) {}

    public static function fromRequest(Request $request, Ad $ad, AdPlan $plan): self
    {
        return new self(
            userId: auth()->id(),
            adId: $ad->id,
            planId: $plan->id,
            paymentId: Payment::generatePaymentId(),
            amount: $plan->price,
            currency: 'RUB',
            description: 'Публикация объявления на ' . $plan->days . ' дней',
            metadata: [
                'ad_title' => $ad->title,
                'plan_name' => $plan->name,
                'plan_days' => $plan->days
            ]
        );
    }
}