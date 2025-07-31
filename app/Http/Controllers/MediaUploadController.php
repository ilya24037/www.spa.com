<?php

namespace App\Http\Controllers;

use App\Application\Http\Controllers${controller} as BaseMediaUploadController;

/**
 * Legacy MediaUploadController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers${controller} instead
 */
class MediaUploadController extends BaseMediaUploadController
{
    // Все функциональность наследуется из Application контроллера
}
