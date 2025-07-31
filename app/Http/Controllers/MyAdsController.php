<?php

namespace App\Http\Controllers;

use App\Application\Http\Controllers${controller} as BaseMyAdsController;

/**
 * Legacy MyAdsController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers${controller} instead
 */
class MyAdsController extends BaseMyAdsController
{
    // Все функциональность наследуется из Application контроллера
}
