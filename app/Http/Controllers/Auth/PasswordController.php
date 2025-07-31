<?php

namespace App\Http\Controllers\Auth;

use App\Application\Http\Controllers\Auth\PasswordController as BasePasswordController;

/**
 * Legacy PasswordController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers\Auth\PasswordController instead
 */
class PasswordController extends BasePasswordController
{
    // Все функциональность наследуется из Application контроллера
}
