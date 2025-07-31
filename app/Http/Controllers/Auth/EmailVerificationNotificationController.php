<?php

namespace App\Http\Controllers\Auth;

use App\Application\Http\Controllers\Auth\EmailVerificationNotificationController as BaseEmailVerificationNotificationController;

/**
 * Legacy EmailVerificationNotificationController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers\Auth\EmailVerificationNotificationController instead
 */
class EmailVerificationNotificationController extends BaseEmailVerificationNotificationController
{
    // Все функциональность наследуется из Application контроллера
}
