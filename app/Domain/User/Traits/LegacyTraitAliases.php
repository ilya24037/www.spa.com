<?php

namespace App\Domain\User\Traits;

/**
 * Алиасы для старых трейтов 
 * Обеспечивают обратную совместимость во время миграции
 * 
 * ВРЕМЕННЫЙ ФАЙЛ - удалить после завершения рефакторинга всех файлов
 */

// Алиас для HasBookings (старый трейт с прямыми связями)
if (!trait_exists('App\Domain\User\Traits\HasBookings_Legacy')) {
    class_alias(
        'App\Domain\User\Traits\HasBookingIntegration',
        'App\Domain\User\Traits\HasBookings_Legacy'
    );
}

// Алиас для HasMasterProfile (старый трейт с прямыми связями)  
if (!trait_exists('App\Domain\User\Traits\HasMasterProfile_Legacy')) {
    class_alias(
        'App\Domain\User\Traits\HasMasterIntegration',
        'App\Domain\User\Traits\HasMasterProfile_Legacy'
    );
}

/**
 * ПЛАН МИГРАЦИИ:
 * 
 * 1. Этап 1: Заменить импорты в User модели ✅ ВЫПОЛНЕНО
 * 2. Этап 2: Обновить все контроллеры и сервисы (Этап 5)
 * 3. Этап 3: Убрать deprecated методы из новых трейтов
 * 4. Этап 4: Удалить старые трейты HasBookings и HasMasterProfile
 * 5. Этап 5: Удалить этот файл с алиасами
 */