<?php

namespace App\Http\Controllers;

use App\Application\Http\Controllers\CompareController as BaseCompareController;

/**
 * Legacy CompareController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers\CompareController instead
 */
class CompareController extends BaseCompareController
{
    // Все функциональность наследуется из Application контроллера
}