<?php

namespace App\Models;

use App\Domain\Review\Models\ReviewReply as BaseReviewReply;

/**
 * Legacy ReviewReply model for backward compatibility
 * @deprecated Use App\Domain\Review\Models\ReviewReply instead
 */
class ReviewReply extends BaseReviewReply
{
    // Все функциональность наследуется из Domain модели
}