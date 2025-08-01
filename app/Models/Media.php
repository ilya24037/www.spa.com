<?php

namespace App\Models;

use App\Domain\Media\Models\Media as DomainMedia;

/**
 * Legacy-адаптер для Media
 * @deprecated Используйте App\Domain\Media\Models\Media
 */
class Media extends DomainMedia
{
    // Все функциональность наследуется из Domain модели
}