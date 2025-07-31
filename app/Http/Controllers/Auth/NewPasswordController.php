<?php

namespace App\Http\Controllers\Auth;

use App\Application\Http\Controllers\Auth\NewPasswordController as BaseNewPasswordController;

/**
 * Legacy NewPasswordController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers\Auth\NewPasswordController instead
 */
class NewPasswordController extends BaseNewPasswordController
{
    // Все функциональность наследуется из Application контроллера
}
