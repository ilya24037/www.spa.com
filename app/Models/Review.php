<?php

namespace App\Models;

use App\Domain\Review\Models\Review as BaseReview;

/**
 * Legacy Review model for backward compatibility
 * @deprecated Use App\Domain\Review\Models\Review instead
 */
class Review extends BaseReview
{
    // Все функциональность наследуется из Domain модели
}