<?php

namespace App\Http\Controllers\Auth;

use App\Application\Http\Controllers\Auth\VerifyEmailController as BaseVerifyEmailController;

/**
 * Legacy VerifyEmailController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers\Auth\VerifyEmailController instead
 */
class VerifyEmailController extends BaseVerifyEmailController
{
    // Все функциональность наследуется из Application контроллера
}
