<?php

namespace App\Http\Controllers\Auth;

use App\Application\Http\Controllers\Auth\EmailVerificationPromptController as BaseEmailVerificationPromptController;

/**
 * Legacy EmailVerificationPromptController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers\Auth\EmailVerificationPromptController instead
 */
class EmailVerificationPromptController extends BaseEmailVerificationPromptController
{
    // Все функциональность наследуется из Application контроллера
}
