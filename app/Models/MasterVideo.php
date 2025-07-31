<?php

namespace App\Models;

use App\Domain\Media\Models\Video;

/**
 * Legacy MasterVideo model for backward compatibility
 * @deprecated Use App\Domain\Media\Models\Video instead
 */
class MasterVideo extends Video
{
    // Все функциональность наследуется из Domain модели
}