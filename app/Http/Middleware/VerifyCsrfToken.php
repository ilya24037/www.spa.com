<?php

namespace App\Http\Middleware;

use App\Application\Http\Middleware${middleware} as BaseVerifyCsrfToken;

/**
 * Legacy VerifyCsrfToken for backward compatibility
 * @deprecated Use App\Application\Http\Middleware${middleware} instead
 */
class VerifyCsrfToken extends BaseVerifyCsrfToken
{
    // Все функциональность наследуется из Application middleware
}
