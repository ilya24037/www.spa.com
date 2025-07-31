<?php

namespace App\Http\Controllers;

use App\Application\Http\Controllers\PaymentController as BasePaymentController;

/**
 * Legacy PaymentController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers\PaymentController instead
 */
class PaymentController extends BasePaymentController
{
    // Все функциональность наследуется из Application контроллера
}