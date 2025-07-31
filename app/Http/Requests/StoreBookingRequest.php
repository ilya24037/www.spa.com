<?php

namespace App\Http\Requests;

use App\Application\Http\Requests${request} as BaseStoreBookingRequest;

/**
 * Legacy StoreBookingRequest for backward compatibility
 * @deprecated Use App\Application\Http\Requests${request} instead
 */
class StoreBookingRequest extends BaseStoreBookingRequest
{
    // Все функциональность наследуется из Application request
}
