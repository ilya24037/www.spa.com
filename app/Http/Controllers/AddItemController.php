<?php

namespace App\Http\Controllers;

use App\Application\Http\Controllers${controller} as BaseAddItemController;

/**
 * Legacy AddItemController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers${controller} instead
 */
class AddItemController extends BaseAddItemController
{
    // Все функциональность наследуется из Application контроллера
}
