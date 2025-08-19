<?php

namespace App\Domain\User\Services;

use App\Domain\User\Models\User;
use App\Application\Services\Integration\UserAdsIntegrationService;
use App\Application\Services\Integration\UserBookingIntegrationService;
use App\Application\Services\Integration\UserFavoritesIntegrationService;
use App\Application\Services\Integration\UserMasterIntegrationService;
use App\Application\Services\Integration\UserReviewsIntegrationService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Сервис интеграции пользователя с другими доменами
 * Заменяет Integration трейты для соблюдения Clean Architecture
 * Все методы из трейтов перенесены сюда
 */
class UserIntegrationService
{
    public function __construct(
        private UserAdsIntegrationService $adsIntegration,
        private UserBookingIntegrationService $bookingIntegration,
        private UserFavoritesIntegrationService $favoritesIntegration,
        private UserMasterIntegrationService $masterIntegration,
        private UserReviewsIntegrationService $reviewsIntegration
    ) {}

    // ======== ADS INTEGRATION METHODS ========

    /**
     * Получить все объявления пользователя
     */
    public function getAds(User $user): Collection
    {
        return $this->adsIntegration->getUserAds($user->id);
    }

    /**
     * Получить активные объявления пользователя
     */
    public function getActiveAds(User $user): Collection
    {
        return $this->adsIntegration->getUserActiveAds($user->id);
    }

    /**
     * Получить черновики пользователя
     */
    public function getDraftAds(User $user): Collection
    {
        return $this->adsIntegration->getUserDraftAds($user->id);
    }

    /**
     * Получить архивированные объявления
     */
    public function getArchivedAds(User $user): Collection
    {
        return $this->adsIntegration->getUserArchivedAds($user->id);
    }

    /**
     * Получить количество всех объявлений
     */
    public function getAdsCount(User $user): int
    {
        return $this->adsIntegration->getUserAdsCount($user->id);
    }

    /**
     * Получить количество активных объявлений
     */
    public function getActiveAdsCount(User $user): int
    {
        return $this->adsIntegration->getUserActiveAdsCount($user->id);
    }

    /**
     * Проверить может ли пользователь создать новое объявление
     */
    public function canCreateAd(User $user): bool
    {
        return $this->adsIntegration->canUserCreateAd($user->id);
    }

    /**
     * Проверить принадлежит ли объявление пользователю
     */
    public function ownsAd(User $user, int $adId): bool
    {
        return $this->adsIntegration->userOwnsAd($user->id, $adId);
    }

    /**
     * Создать новое объявление
     */
    public function createAd(User $user, array $adData): ?int
    {
        return $this->adsIntegration->createUserAd($user->id, $adData);
    }

    /**
     * Обновить объявление
     */
    public function updateAd(User $user, int $adId, array $adData): bool
    {
        return $this->adsIntegration->updateUserAd($user->id, $adId, $adData);
    }

    /**
     * Удалить объявление
     */
    public function deleteAd(User $user, int $adId): bool
    {
        return $this->adsIntegration->deleteUserAd($user->id, $adId);
    }

    /**
     * Получить статистику объявлений
     */
    public function getAdsStatistics(User $user): array
    {
        return $this->adsIntegration->getUserAdsStatistics($user->id);
    }

    // ======== BOOKING INTEGRATION METHODS ========

    /**
     * Получить все бронирования пользователя
     */
    public function getBookings(User $user): Collection
    {
        return $this->bookingIntegration->getUserBookings($user->id);
    }

    /**
     * Получить активные бронирования
     */
    public function getActiveBookings(User $user): Collection
    {
        return $this->bookingIntegration->getUserActiveBookings($user->id);
    }

    /**
     * Получить завершенные бронирования
     */
    public function getCompletedBookings(User $user): Collection
    {
        return $this->bookingIntegration->getUserCompletedBookings($user->id);
    }

    /**
     * Получить отмененные бронирования
     */
    public function getCancelledBookings(User $user): Collection
    {
        return $this->bookingIntegration->getUserCancelledBookings($user->id);
    }

    /**
     * Создать новое бронирование
     */
    public function createBooking(User $user, array $bookingData): ?int
    {
        return $this->bookingIntegration->createUserBooking($user->id, $bookingData);
    }

    /**
     * Отменить бронирование
     */
    public function cancelBooking(User $user, int $bookingId): bool
    {
        return $this->bookingIntegration->cancelUserBooking($user->id, $bookingId);
    }

    /**
     * Получить статистику бронирований
     */
    public function getBookingStatistics(User $user): array
    {
        return $this->bookingIntegration->getUserBookingStatistics($user->id);
    }

    // ======== FAVORITES INTEGRATION METHODS ========

    /**
     * Получить избранных мастеров
     */
    public function getFavoriteMasters(User $user): Collection
    {
        return $this->favoritesIntegration->getUserFavoriteMasters($user->id);
    }

    /**
     * Получить избранные объявления
     */
    public function getFavoriteAds(User $user): Collection
    {
        return $this->favoritesIntegration->getUserFavoriteAds($user->id);
    }

    /**
     * Добавить в избранное
     */
    public function addToFavorites(User $user, string $type, int $itemId): bool
    {
        return $this->favoritesIntegration->addUserFavorite($user->id, $type, $itemId);
    }

    /**
     * Удалить из избранного
     */
    public function removeFromFavorites(User $user, string $type, int $itemId): bool
    {
        return $this->favoritesIntegration->removeUserFavorite($user->id, $type, $itemId);
    }

    /**
     * Проверить в избранном ли элемент
     */
    public function isFavorite(User $user, string $type, int $itemId): bool
    {
        return $this->favoritesIntegration->isUserFavorite($user->id, $type, $itemId);
    }

    // ======== MASTER INTEGRATION METHODS ========

    /**
     * Получить профиль мастера пользователя
     */
    public function getMasterProfile(User $user)
    {
        return $this->masterIntegration->getUserMasterProfile($user->id);
    }

    /**
     * Создать профиль мастера
     */
    public function createMasterProfile(User $user, array $profileData)
    {
        return $this->masterIntegration->createUserMasterProfile($user->id, $profileData);
    }

    /**
     * Обновить профиль мастера
     */
    public function updateMasterProfile(User $user, array $profileData): bool
    {
        return $this->masterIntegration->updateUserMasterProfile($user->id, $profileData);
    }

    /**
     * Проверить может ли пользователь стать мастером
     */
    public function canBecomeMaster(User $user): bool
    {
        return $this->masterIntegration->canUserBecomeMaster($user->id);
    }

    /**
     * Получить статистику мастера
     */
    public function getMasterStatistics(User $user): array
    {
        return $this->masterIntegration->getUserMasterStatistics($user->id);
    }

    // ======== REVIEWS INTEGRATION METHODS ========

    /**
     * Получить отзывы о пользователе (как о мастере)
     */
    public function getReceivedReviews(User $user): Collection
    {
        return $this->reviewsIntegration->getUserReceivedReviews($user->id);
    }

    /**
     * Получить отзывы от пользователя (как клиента)
     */
    public function getGivenReviews(User $user): Collection
    {
        return $this->reviewsIntegration->getUserGivenReviews($user->id);
    }

    /**
     * Создать отзыв
     */
    public function createReview(User $user, array $reviewData): ?int
    {
        return $this->reviewsIntegration->createUserReview($user->id, $reviewData);
    }

    /**
     * Обновить отзыв
     */
    public function updateReview(User $user, int $reviewId, array $reviewData): bool
    {
        return $this->reviewsIntegration->updateUserReview($user->id, $reviewId, $reviewData);
    }

    /**
     * Удалить отзыв
     */
    public function deleteReview(User $user, int $reviewId): bool
    {
        return $this->reviewsIntegration->deleteUserReview($user->id, $reviewId);
    }

    /**
     * Получить статистику отзывов
     */
    public function getReviewsStatistics(User $user): array
    {
        return $this->reviewsIntegration->getUserReviewsStatistics($user->id);
    }

    // ======== COMBINED METHODS ========

    /**
     * Получить полную статистику пользователя
     */
    public function getFullStatistics(User $user): array
    {
        return [
            'ads' => $this->getAdsStatistics($user),
            'bookings' => $this->getBookingStatistics($user),
            'master' => $this->getMasterStatistics($user),
            'reviews' => $this->getReviewsStatistics($user),
        ];
    }

    /**
     * Получить дашборд данные пользователя
     */
    public function getDashboardData(User $user): array
    {
        return [
            'ads_count' => $this->getActiveAdsCount($user),
            'bookings_count' => $this->getActiveBookings($user)->count(),
            'favorites_count' => $this->getFavoriteMasters($user)->count(),
            'reviews_received' => $this->getReceivedReviews($user)->count(),
            'is_master' => $user->isMaster(),
            'can_create_ads' => $this->canCreateAd($user),
        ];
    }

    /**
     * Получить рекомендации для пользователя
     */
    public function getRecommendations(User $user): array
    {
        $recommendations = [];
        
        if (!$user->isMaster() && $this->canBecomeMaster($user)) {
            $recommendations[] = [
                'type' => 'become_master',
                'title' => 'Станьте мастером',
                'description' => 'Создайте профиль мастера и начните получать заказы'
            ];
        }
        
        if ($this->getActiveAdsCount($user) === 0 && $this->canCreateAd($user)) {
            $recommendations[] = [
                'type' => 'create_ad',
                'title' => 'Создайте объявление',
                'description' => 'Создайте первое объявление чтобы найти клиентов'
            ];
        }
        
        return $recommendations;
    }

    /**
     * Получить недавнюю активность пользователя
     */
    public function getRecentActivity(User $user, int $limit = 10): array
    {
        $activity = [];
        
        // Проверяем существование методов перед вызовом
        try {
            // Недавние объявления
            if (method_exists($this->adsIntegration, 'getRecentUserAds')) {
                $recentAds = $this->adsIntegration->getRecentUserAds($user->id, 3);
                foreach ($recentAds as $ad) {
                    $activity[] = [
                        'type' => 'ad_created',
                        'data' => $ad,
                        'timestamp' => $ad->created_at
                    ];
                }
            }
            
            // Недавние бронирования
            if (method_exists($this->bookingIntegration, 'getRecentUserBookings')) {
                $recentBookings = $this->bookingIntegration->getRecentUserBookings($user->id, 3);
                foreach ($recentBookings as $booking) {
                    $activity[] = [
                        'type' => 'booking_created', 
                        'data' => $booking,
                        'timestamp' => $booking->created_at
                    ];
                }
            }
            
            // Сортируем по времени и берем последние
            usort($activity, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
            
        } catch (\Exception $e) {
            // Логируем ошибку но не падаем
            \Log::warning('Error getting recent activity for user', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return array_slice($activity, 0, $limit);
    }
}