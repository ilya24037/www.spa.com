<?php

namespace App\Models;

use App\Domain\Master\Models\Schedule as BaseSchedule;

/**
 * Legacy Schedule model for backward compatibility
 * @deprecated Use App\Domain\Master\Models\Schedule instead
 */
class Schedule extends BaseSchedule
{
    // Все функциональность наследуется из Domain модели
}