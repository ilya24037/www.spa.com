<?php

namespace App\Models;

use App\Domain\User\Models\UserBalance as DomainUserBalance;

/**
 * Legacy-адаптер для UserBalance
 * @deprecated Используйте App\Domain\User\Models\UserBalance
 */
class UserBalance extends DomainUserBalance
{
    // Все функциональность наследуется из Domain модели
}