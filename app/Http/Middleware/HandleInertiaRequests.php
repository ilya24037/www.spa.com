<?php

namespace App\Http\Middleware;

use App\Application\Http\Middleware\HandleInertiaRequests as BaseHandleInertiaRequests;

/**
 * Legacy HandleInertiaRequests for backward compatibility
 * @deprecated Use App\Application\Http\Middleware\HandleInertiaRequests instead
 */
class HandleInertiaRequests extends BaseHandleInertiaRequests
{
    // Все функциональность наследуется из Application middleware
}
