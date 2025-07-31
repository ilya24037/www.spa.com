<?php

namespace App\Http\Controllers\Auth;

use App\Application\Http\Controllers\Auth\PasswordResetLinkController as BasePasswordResetLinkController;

/**
 * Legacy PasswordResetLinkController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers\Auth\PasswordResetLinkController instead
 */
class PasswordResetLinkController extends BasePasswordResetLinkController
{
    // Все функциональность наследуется из Application контроллера
}
