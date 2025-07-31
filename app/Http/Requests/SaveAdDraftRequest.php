<?php

namespace App\Http\Requests;

use App\Application\Http\Requests${request} as BaseSaveAdDraftRequest;

/**
 * Legacy SaveAdDraftRequest for backward compatibility
 * @deprecated Use App\Application\Http\Requests${request} instead
 */
class SaveAdDraftRequest extends BaseSaveAdDraftRequest
{
    // Все функциональность наследуется из Application request
}
