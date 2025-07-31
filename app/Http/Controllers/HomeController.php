<?php

namespace App\Http\Controllers;

use App\Application\Http\Controllers\HomeController as BaseHomeController;

/**
 * Legacy HomeController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers\HomeController instead
 */
class HomeController extends BaseHomeController
{
    // Все функциональность наследуется из Application контроллера
}