<?php

namespace App\Http\Requests;

use App\Application\Http\Requests${request} as BaseCreateAdRequest;

/**
 * Legacy CreateAdRequest for backward compatibility
 * @deprecated Use App\Application\Http\Requests${request} instead
 */
class CreateAdRequest extends BaseCreateAdRequest
{
    // Все функциональность наследуется из Application request
}
