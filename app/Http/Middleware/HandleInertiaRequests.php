<?php

namespace App\Http\Middleware;

use App\Application\Http\Middleware${middleware} as BaseHandleInertiaRequests;

/**
 * Legacy HandleInertiaRequests for backward compatibility
 * @deprecated Use App\Application\Http\Middleware${middleware} instead
 */
class HandleInertiaRequests extends BaseHandleInertiaRequests
{
    // Все функциональность наследуется из Application middleware
}
