<?php

/**
 * Алиасы для обратной совместимости при рефакторинге
 * 
 * Этот файл позволяет старому коду продолжать работать
 * пока мы постепенно обновляем импорты
 */

// DTOs
class_alias('App\Domain\User\DTOs\UserRegistrationDTO', 'App\DTOs\UserRegistrationDTO');
class_alias('App\Domain\Booking\DTOs\CreateBookingDTO', 'App\DTOs\CreateBookingDTO');
class_alias('App\Domain\Booking\DTOs\UpdateBookingDTO', 'App\DTOs\UpdateBookingDTO');
class_alias('App\Domain\Master\DTOs\CreateMasterDTO', 'App\DTOs\CreateMasterDTO');
class_alias('App\Domain\Master\DTOs\UpdateMasterDTO', 'App\DTOs\UpdateMasterDTO');
class_alias('App\Domain\Master\DTOs\MasterFilterDTO', 'App\DTOs\MasterFilterDTO');
class_alias('App\Domain\Payment\DTOs\CreatePaymentDTO', 'App\DTOs\CreatePaymentDTO');
class_alias('App\Domain\Payment\DTOs\ProcessPaymentDTO', 'App\DTOs\ProcessPaymentDTO');
class_alias('App\Domain\Review\DTOs\CreateReviewDTO', 'App\DTOs\CreateReviewDTO');
class_alias('App\Domain\Notification\DTOs\SendNotificationDTO', 'App\DTOs\SendNotificationDTO');

// Repositories
class_alias('App\Domain\User\Repositories\UserRepository', 'App\Repositories\UserRepository');
class_alias('App\Domain\Booking\Repositories\BookingRepository', 'App\Repositories\BookingRepository');
class_alias('App\Domain\Master\Repositories\MasterRepository', 'App\Repositories\MasterRepository');
class_alias('App\Domain\Payment\Repositories\PaymentRepository', 'App\Repositories\PaymentRepository');
class_alias('App\Domain\Review\Repositories\ReviewRepository', 'App\Repositories\ReviewRepository');
class_alias('App\Domain\Notification\Repositories\NotificationRepository', 'App\Repositories\NotificationRepository');

// Actions
class_alias('App\Domain\Booking\Actions\CreateBookingAction', 'App\Actions\CreateBookingAction');
class_alias('App\Domain\Booking\Actions\CancelBookingAction', 'App\Actions\CancelBookingAction');
class_alias('App\Domain\Booking\Actions\ConfirmBookingAction', 'App\Actions\ConfirmBookingAction');
class_alias('App\Domain\Booking\Actions\CompleteBookingAction', 'App\Actions\CompleteBookingAction');
class_alias('App\Domain\Booking\Actions\RescheduleBookingAction', 'App\Actions\RescheduleBookingAction');
class_alias('App\Domain\Payment\Actions\CreatePaymentAction', 'App\Actions\CreatePaymentAction');
class_alias('App\Domain\Payment\Actions\ProcessPaymentAction', 'App\Actions\ProcessPaymentAction');
class_alias('App\Domain\Payment\Actions\RefundPaymentAction', 'App\Actions\RefundPaymentAction');

// User domain aliases
class_alias('App\Domain\User\Models\User', 'App\Models\BaseUser');
class_alias('App\Domain\User\Models\UserProfile', 'App\Models\UserProfile');
class_alias('App\Domain\User\Models\UserSettings', 'App\Models\UserSettings');

// Master domain aliases
class_alias('App\Domain\Master\Models\MasterProfile', 'App\Models\BaseMasterProfile');
class_alias('App\Domain\Master\Models\MasterMedia', 'App\Models\MasterMedia');
class_alias('App\Domain\Master\Models\MasterSchedule', 'App\Models\MasterSchedule');

// Ad domain aliases
class_alias('App\Domain\Ad\Models\Ad', 'App\Models\BaseAd');
class_alias('App\Domain\Ad\Models\AdMedia', 'App\Models\AdMedia');
class_alias('App\Domain\Ad\Models\AdPricing', 'App\Models\AdPricing');
class_alias('App\Domain\Ad\Models\AdLocation', 'App\Models\AdLocation');