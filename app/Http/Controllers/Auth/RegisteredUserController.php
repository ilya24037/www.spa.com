<?php

namespace App\Http\Controllers\Auth;

use App\Application\Http\Controllers\Auth\RegisteredUserController as BaseRegisteredUserController;

/**
 * Legacy RegisteredUserController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers\Auth\RegisteredUserController instead
 */
class RegisteredUserController extends BaseRegisteredUserController
{
    // Все функциональность наследуется из Application контроллера
}
