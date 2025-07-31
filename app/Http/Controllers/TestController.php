<?php

namespace App\Http\Controllers;

use App\Application\Http\Controllers${controller} as BaseTestController;

/**
 * Legacy TestController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers${controller} instead
 */
class TestController extends BaseTestController
{
    // Все функциональность наследуется из Application контроллера
}
