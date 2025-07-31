<?php

namespace App\Http\Controllers;

use App\Application\Http\Controllers${controller} as BaseMasterMediaController;

/**
 * Legacy MasterMediaController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers${controller} instead
 */
class MasterMediaController extends BaseMasterMediaController
{
    // Все функциональность наследуется из Application контроллера
}
