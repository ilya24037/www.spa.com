<?php

/**
 * Скрипт для исправления namespace в файлах после рефакторинга
 */

$fixes = [
    // Domain Services
    'app/Domain/Ad/Services/AdModerationService.php' => ['App\Services', 'App\Domain\Ad\Services'],
    'app/Domain/Ad/Services/AdSearchService.php' => ['App\Services', 'App\Domain\Ad\Services'],
    'app/Domain/Master/Services/MasterService.php' => ['App\Services', 'App\Domain\Master\Services'],
    'app/Domain/Payment/Services/PaymentGatewayService.php' => ['App\Services', 'App\Domain\Payment\Services'],
    'app/Domain/Payment/Services/PaymentService.php' => ['App\Services', 'App\Domain\Payment\Services'],
    'app/Domain/Review/Services/ReviewService.php' => ['App\Services', 'App\Domain\Review\Services'],
    'app/Domain/User/Services/UserAuthService.php' => ['App\Services', 'App\Domain\User\Services'],
    
    // Domain Search Services
    'app/Domain/Search/Services/AdSearchEngine.php' => ['App\Services\Search', 'App\Domain\Search\Services'],
    'app/Domain/Search/Services/BaseSearchEngine.php' => ['App\Services\Search', 'App\Domain\Search\Services'],
    'app/Domain/Search/Services/GlobalSearchEngine.php' => ['App\Services\Search', 'App\Domain\Search\Services'],
    'app/Domain/Search/Services/MasterSearchEngine.php' => ['App\Services\Search', 'App\Domain\Search\Services'],
    'app/Domain/Search/Services/RecommendationEngine.php' => ['App\Services\Search', 'App\Domain\Search\Services'],
    'app/Domain/Search/Services/SearchEngineInterface.php' => ['App\Services\Search', 'App\Domain\Search\Services'],
    'app/Domain/Search/Services/SearchFilter.php' => ['App\Services\Search', 'App\Domain\Search\Services'],
    'app/Domain/Search/Services/SearchResult.php' => ['App\Services\Search', 'App\Domain\Search\Services'],
    'app/Domain/Search/Services/ServiceSearchEngine.php' => ['App\Services\Search', 'App\Domain\Search\Services'],
    
    // Infrastructure Services
    'app/Infrastructure/Adapters/BookingServiceAdapter.php' => ['App\Services', 'App\Infrastructure\Adapters'],
    'app/Infrastructure/Adapters/MasterServiceAdapter.php' => ['App\Services\Adapters', 'App\Infrastructure\Adapters'],
    'app/Infrastructure/Adapters/SearchServiceAdapter.php' => ['App\Services\Adapters', 'App\Infrastructure\Adapters'],
    'app/Infrastructure/Cache/CacheService.php' => ['App\Services', 'App\Infrastructure\Cache'],
    'app/Infrastructure/Feature/FeatureFlagService.php' => ['App\Services', 'App\Infrastructure\Feature'],
    'app/Infrastructure/Media/AIMediaService.php' => ['App\Services', 'App\Infrastructure\Media'],
    'app/Infrastructure/Media/MediaService.php' => ['App\Services', 'App\Infrastructure\Media'],
    'app/Infrastructure/Notification/LegacyNotificationService.php' => ['App\Services', 'App\Infrastructure\Notification'],
    
    // Support
    'app/Support/Helpers/ImageHelper.php' => ['App\Helpers', 'App\Support\Helpers'],
    'app/Support/Helpers/VideoHelper.php' => ['App\Helpers', 'App\Support\Helpers'],
    'app/Support/Traits/Cacheable.php' => ['App\Traits', 'App\Support\Traits'],
    'app/Support/Traits/HasUniqueSlug.php' => ['App\Traits', 'App\Support\Traits'],
    'app/Support/Traits/HasUserProfile.php' => ['App\Traits', 'App\Support\Traits'],
    'app/Support/Traits/HasUserRoles.php' => ['App\Traits', 'App\Support\Traits'],
    
    // DTOs
    'app/Domain/Notification/DTOs/CreateNotificationDTO.php' => ['App\DTOs\Notification', 'App\Domain\Notification\DTOs'],
    'app/Domain/Payment/DTOs/PaymentFilterDTO.php' => ['App\DTOs\Payment', 'App\Domain\Payment\DTOs'],
    'app/Domain/Payment/DTOs/PaymentResultDTO.php' => ['App\DTOs\Payment', 'App\Domain\Payment\DTOs'],
    'app/Domain/Review/DTOs/UpdateReviewDTO.php' => ['App\DTOs\Review', 'App\Domain\Review\DTOs'],
];

foreach ($fixes as $file => $namespaces) {
    [$oldNamespace, $newNamespace] = $namespaces;
    
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $content = str_replace(
            "namespace $oldNamespace;",
            "namespace $newNamespace;",
            $content
        );
        file_put_contents($file, $content);
        echo "Fixed: $file\n";
    } else {
        echo "Not found: $file\n";
    }
}

echo "\nDone! Now run 'composer dump-autoload' again.\n";