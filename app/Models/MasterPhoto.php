<?php

namespace App\Models;

use App\Domain\Media\Models\Photo;

/**
 * Legacy MasterPhoto model for backward compatibility
 * @deprecated Use App\Domain\Media\Models\Photo instead
 */
class MasterPhoto extends Photo
{
    // Все функциональность наследуется из Domain модели
}