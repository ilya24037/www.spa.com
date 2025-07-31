<?php

namespace App\Models;

use App\Domain\Service\Models\Service as BaseService;

/**
 * Legacy Service model for backward compatibility
 * @deprecated Use App\Domain\Service\Models\Service instead
 */
class Service extends BaseService
{
    // Все функциональность наследуется из Domain модели
}