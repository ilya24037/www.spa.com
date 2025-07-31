<?php

namespace App\Http\Controllers\Auth;

use App\Application\Http\Controllers\Auth\ConfirmablePasswordController as BaseConfirmablePasswordController;

/**
 * Legacy ConfirmablePasswordController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers\Auth\ConfirmablePasswordController instead
 */
class ConfirmablePasswordController extends BaseConfirmablePasswordController
{
    // Все функциональность наследуется из Application контроллера
}