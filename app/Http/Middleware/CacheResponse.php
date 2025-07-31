<?php

namespace App\Http\Middleware;

use App\Application\Http\Middleware${middleware} as BaseCacheResponse;

/**
 * Legacy CacheResponse for backward compatibility
 * @deprecated Use App\Application\Http\Middleware${middleware} instead
 */
class CacheResponse extends BaseCacheResponse
{
    // Все функциональность наследуется из Application middleware
}
