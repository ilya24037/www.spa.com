<?php

namespace App\Http\Requests\Auth;

use App\Application\Http\Requests\Auth\LoginRequest as BaseLoginRequest;

/**
 * Legacy LoginRequest for backward compatibility
 * @deprecated Use App\Application\Http\Requests\Auth\LoginRequest instead
 */
class LoginRequest extends BaseLoginRequest
{
    // Все функциональность наследуется из Application request
}