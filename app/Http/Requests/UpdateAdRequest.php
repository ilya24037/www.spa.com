<?php

namespace App\Http\Requests;

use App\Application\Http\Requests${request} as BaseUpdateAdRequest;

/**
 * Legacy UpdateAdRequest for backward compatibility
 * @deprecated Use App\Application\Http\Requests${request} instead
 */
class UpdateAdRequest extends BaseUpdateAdRequest
{
    // Все функциональность наследуется из Application request
}
