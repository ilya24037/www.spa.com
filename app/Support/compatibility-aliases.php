<?php

/**
 * Алиасы для обратной совместимости при рефакторинге
 * 
 * Этот файл позволяет старому коду продолжать работать
 * пока мы постепенно обновляем импорты
 */

// DTOs
class_alias('App\Domain\User\DTOs\RegisterUserDTO', 'App\DTOs\UserRegistrationDTO');
class_alias('App\Domain\Booking\DTOs\CreateBookingDTO', 'App\DTOs\CreateBookingDTO');
class_alias('App\Domain\Booking\DTOs\UpdateBookingDTO', 'App\DTOs\UpdateBookingDTO');
class_alias('App\Domain\Master\DTOs\CreateMasterDTO', 'App\DTOs\CreateMasterDTO');
class_alias('App\Domain\Master\DTOs\UpdateMasterDTO', 'App\DTOs\UpdateMasterDTO');
class_alias('App\Domain\Master\DTOs\MasterFilterDTO', 'App\DTOs\MasterFilterDTO');
class_alias('App\Domain\Payment\DTOs\CreatePaymentDTO', 'App\DTOs\CreatePaymentDTO');
class_alias('App\Domain\Payment\DTOs\ProcessPaymentDTO', 'App\DTOs\ProcessPaymentDTO');
class_alias('App\Domain\Review\DTOs\CreateReviewDTO', 'App\DTOs\CreateReviewDTO');
class_alias('App\Domain\Notification\DTOs\SendNotificationDTO', 'App\DTOs\SendNotificationDTO');

// Repositories - ВРЕМЕННО ОТКЛЮЧЕНЫ
// class_alias('App\Domain\User\Repositories\UserRepository', 'App\Repositories\UserRepository');
// class_alias('App\Domain\Booking\Repositories\BookingRepository', 'App\Repositories\BookingRepository');
// class_alias('App\Domain\Master\Repositories\MasterRepository', 'App\Repositories\MasterRepository');
// class_alias('App\Domain\Payment\Repositories\PaymentRepository', 'App\Repositories\PaymentRepository');
// class_alias('App\Domain\Review\Repositories\ReviewRepository', 'App\Repositories\ReviewRepository');
// class_alias('App\Domain\Notification\Repositories\NotificationRepository', 'App\Repositories\NotificationRepository');

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

// Service aliases for backward compatibility
class_alias('App\Domain\Ad\Services\AdMediaService', 'App\Services\AdMediaService');
class_alias('App\Domain\Ad\Services\AdModerationService', 'App\Services\AdModerationService');
class_alias('App\Domain\Ad\Services\AdSearchService', 'App\Services\AdSearchService');
class_alias('App\Domain\Master\Services\MasterService', 'App\Services\MasterService');
class_alias('App\Domain\Payment\Services\PaymentGatewayService', 'App\Services\PaymentGatewayService');
class_alias('App\Domain\Payment\Services\PaymentService', 'App\Services\PaymentService');
class_alias('App\Domain\Review\Services\ReviewService', 'App\Services\ReviewService');
class_alias('App\Domain\User\Services\UserAuthService', 'App\Services\UserAuthService');

// Infrastructure service aliases
class_alias('App\Infrastructure\Cache\CacheService', 'App\Services\CacheService');
class_alias('App\Infrastructure\Feature\FeatureFlagService', 'App\Services\FeatureFlagService');
class_alias('App\Infrastructure\Media\AIMediaService', 'App\Services\AIMediaService');
class_alias('App\Infrastructure\Media\MediaService', 'App\Services\MediaService');
class_alias('App\Infrastructure\Notification\LegacyNotificationService', 'App\Services\LegacyNotificationService');
class_alias('App\Infrastructure\Adapters\BookingServiceAdapter', 'App\Services\BookingServiceAdapter');

// Search service aliases
class_alias('App\Domain\Search\Engines\AdSearchEngine', 'App\Services\Search\AdSearchEngine');
class_alias('App\Domain\Search\Services\BaseSearchEngine', 'App\Services\Search\BaseSearchEngine');
class_alias('App\Domain\Search\Services\GlobalSearchEngine', 'App\Services\Search\GlobalSearchEngine');
class_alias('App\Domain\Search\Engines\MasterSearchEngine', 'App\Services\Search\MasterSearchEngine');
class_alias('App\Domain\Search\Services\RecommendationEngine', 'App\Services\Search\RecommendationEngine');
class_alias('App\Domain\Search\Services\SearchEngineInterface', 'App\Services\Search\SearchEngineInterface');
class_alias('App\Domain\Search\Services\SearchFilter', 'App\Services\Search\SearchFilter');
class_alias('App\Domain\Search\Services\SearchResult', 'App\Services\Search\SearchResult');
class_alias('App\Domain\Search\Services\ServiceSearchEngine', 'App\Services\Search\ServiceSearchEngine');

// Notification channel aliases
class_alias('App\Infrastructure\Notification\Channels\ChannelInterface', 'App\Services\Notification\Channels\ChannelInterface');
class_alias('App\Infrastructure\Notification\Channels\DatabaseChannel', 'App\Services\Notification\Channels\DatabaseChannel');
class_alias('App\Infrastructure\Notification\Channels\EmailChannel', 'App\Services\Notification\Channels\EmailChannel');
class_alias('App\Infrastructure\Notification\Channels\PushChannel', 'App\Services\Notification\Channels\PushChannel');
class_alias('App\Infrastructure\Notification\Channels\SlackChannel', 'App\Services\Notification\Channels\SlackChannel');
class_alias('App\Infrastructure\Notification\Channels\SmsChannel', 'App\Services\Notification\Channels\SmsChannel');
class_alias('App\Infrastructure\Notification\Channels\TelegramChannel', 'App\Services\Notification\Channels\TelegramChannel');
class_alias('App\Infrastructure\Notification\Channels\WebSocketChannel', 'App\Services\Notification\Channels\WebSocketChannel');

// Helper aliases
class_alias('App\Support\Helpers\ImageHelper', 'App\Helpers\ImageHelper');
class_alias('App\Support\Helpers\VideoHelper', 'App\Helpers\VideoHelper');

// Trait aliases
class_alias('App\Support\Traits\Cacheable', 'App\Traits\Cacheable');
class_alias('App\Support\Traits\HasUniqueSlug', 'App\Traits\HasUniqueSlug');
// class_alias('App\Support\Traits\HasUserProfile', 'App\Traits\HasUserProfile'); // Removed during DDD refactoring
class_alias('App\Support\Traits\HasUserRoles', 'App\Traits\HasUserRoles');

// Request aliases
class_alias('App\Application\Http\Requests\Ad\CreateAdRequest', 'App\Http\Requests\CreateAdRequest');
class_alias('App\Application\Http\Requests\SaveAdDraftRequest', 'App\Http\Requests\SaveAdDraftRequest');
class_alias('App\Application\Http\Requests\Booking\CreateBookingRequest', 'App\Http\Requests\StoreBookingRequest');
class_alias('App\Application\Http\Requests\Ad\UpdateAdRequest', 'App\Http\Requests\UpdateAdRequest');
class_alias('App\Application\Http\Requests\ProfileUpdateRequest', 'App\Http\Requests\ProfileUpdateRequest');
class_alias('App\Application\Http\Requests\Auth\LoginRequest', 'App\Http\Requests\Auth\LoginRequest');

// Master domain aliases
class_alias('App\Domain\Master\Models\MasterProfile', 'App\Models\BaseMasterProfile');
class_alias('App\Domain\Master\Models\MasterMedia', 'App\Models\MasterMedia');
class_alias('App\Domain\Master\Models\MasterSchedule', 'App\Models\MasterSchedule');

// Ad domain aliases
class_alias('App\Domain\Ad\Models\Ad', 'App\Models\BaseAd');
class_alias('App\Domain\Ad\Models\AdMedia', 'App\Models\AdMedia');
class_alias('App\Domain\Ad\Models\AdPricing', 'App\Models\AdPricing');
class_alias('App\Domain\Ad\Models\AdLocation', 'App\Models\AdLocation');