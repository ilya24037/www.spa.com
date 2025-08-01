<?php

namespace App\Models;

use App\Domain\Payment\Models\Payment as DomainPayment;

/**
 * Legacy-адаптер для Payment
 * @deprecated Используйте App\Domain\Payment\Models\Payment
 */
class Payment extends DomainPayment
{
    // Все функциональность наследуется из Domain модели
}