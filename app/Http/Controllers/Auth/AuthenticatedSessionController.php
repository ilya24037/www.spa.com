<?php

namespace App\Http\Controllers\Auth;

use App\Application\Http\Controllers\Auth\AuthenticatedSessionController as BaseAuthenticatedSessionController;

/**
 * Legacy AuthenticatedSessionController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers\Auth\AuthenticatedSessionController instead
 */
class AuthenticatedSessionController extends BaseAuthenticatedSessionController
{
    // Все функциональность наследуется из Application контроллера
}