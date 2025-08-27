<?php

namespace App\Domain\Payment\Services;

/**
 * Временная заглушка для PaymentGatewayManager
 * TODO: Реализовать полноценный менеджер платежных шлюзов
 */
class PaymentGatewayManager
{
    public function getDefaultGateway()
    {
        return null;
    }
    
    public function process($payment)
    {
        return false;
    }
}