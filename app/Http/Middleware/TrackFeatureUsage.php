<?php

namespace App\Http\Middleware;

use App\Application\Http\Middleware${middleware} as BaseTrackFeatureUsage;

/**
 * Legacy TrackFeatureUsage for backward compatibility
 * @deprecated Use App\Application\Http\Middleware${middleware} instead
 */
class TrackFeatureUsage extends BaseTrackFeatureUsage
{
    // Все функциональность наследуется из Application middleware
}
