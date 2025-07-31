<?php

namespace App\Http\Controllers;

use App\Application\Http\Controllers\SearchController as BaseSearchController;

/**
 * Legacy SearchController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers\SearchController instead
 */
class SearchController extends BaseSearchController
{
    // Все функциональность наследуется из Application контроллера
}