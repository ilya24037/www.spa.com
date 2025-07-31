<?php

namespace App\Http\Controllers;

use App\Application\Http\Controllers\MasterController as BaseMasterController;

/**
 * Legacy MasterController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers\MasterController instead
 */
class MasterController extends BaseMasterController
{
    // Все функциональность наследуется из Application контроллера
}