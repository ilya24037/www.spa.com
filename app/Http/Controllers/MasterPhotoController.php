<?php

namespace App\Http\Controllers;

use App\Application\Http\Controllers\MasterPhotoController as BaseMasterPhotoController;

/**
 * Legacy MasterPhotoController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers\MasterPhotoController instead
 */
class MasterPhotoController extends BaseMasterPhotoController
{
    // Все функциональность наследуется из Application контроллера
}