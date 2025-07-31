<?php

namespace App\Http\Requests;

use App\Application\Http\Requests${request} as BaseProfileUpdateRequest;

/**
 * Legacy ProfileUpdateRequest for backward compatibility
 * @deprecated Use App\Application\Http\Requests${request} instead
 */
class ProfileUpdateRequest extends BaseProfileUpdateRequest
{
    // Все функциональность наследуется из Application request
}
