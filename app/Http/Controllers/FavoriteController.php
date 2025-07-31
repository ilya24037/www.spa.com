<?php

namespace App\Http\Controllers;

use App\Application\Http\Controllers\FavoriteController as BaseFavoriteController;

/**
 * Legacy FavoriteController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers\FavoriteController instead
 */
class FavoriteController extends BaseFavoriteController
{
    // Все функциональность наследуется из Application контроллера
}