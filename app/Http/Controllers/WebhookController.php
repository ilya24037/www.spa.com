<?php

namespace App\Http\Controllers;

use App\Application\Http\Controllers${controller} as BaseWebhookController;

/**
 * Legacy WebhookController for backward compatibility
 * @deprecated Use App\Application\Http\Controllers${controller} instead
 */
class WebhookController extends BaseWebhookController
{
    // Все функциональность наследуется из Application контроллера
}
