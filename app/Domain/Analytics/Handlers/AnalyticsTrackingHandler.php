<?php

namespace App\Domain\Analytics\Handlers;

use App\Domain\Analytics\Models\PageView;
use App\Domain\Analytics\Models\UserAction;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\Analytics\DTOs\TrackPageViewDTO;
use App\Domain\Analytics\DTOs\TrackUserActionDTO;
use App\Domain\Analytics\Events\PageViewTracked;
use App\Domain\Analytics\Events\ConversionTracked;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик трекинга аналитических событий
 */
class AnalyticsTrackingHandler
{
    public function __construct(
        protected AnalyticsRepository $repository
    ) {}

    /**
     * Записать просмотр страницы
     */
    public function trackPageView(TrackPageViewDTO $dto): PageView
    {
        try {
            $pageView = $this->repository->createPageView($dto->toArray());

            // Детектим информацию об устройстве
            $deviceInfo = $pageView->detectDeviceInfo();
            $pageView->update($deviceInfo);

            // Детектим страну (асинхронно)
            $this->detectLocationAsync($pageView);

            // Отправляем событие
            event(new PageViewTracked($pageView));

            return $pageView;

        } catch (\Exception $e) {
            Log::error('Failed to track page view', [
                'error' => $e->getMessage(),
                'user_id' => $dto->userId,
                'url' => $dto->url,
            ]);
            
            throw $e;
        }
    }

    /**
     * Записать действие пользователя
     */
    public function trackUserAction(TrackUserActionDTO $dto): UserAction
    {
        try {
            // Проверяем, является ли действие конверсией
            if (!$dto->isConversion && $this->isConversionAction($dto->actionType)) {
                $dto->isConversion = true;
            }

            $userAction = $this->repository->createUserAction($dto->toArray());

            // Отправляем событие для конверсий
            if ($userAction->is_conversion) {
                event(new ConversionTracked($userAction));
            }

            Log::info('User action tracked', [
                'action_type' => $dto->actionType,
                'user_id' => $dto->userId,
                'actionable_type' => $dto->actionableType,
                'actionable_id' => $dto->actionableId,
                'is_conversion' => $userAction->is_conversion,
            ]);

            return $userAction;

        } catch (\Exception $e) {
            Log::error('Failed to track user action', [
                'error' => $e->getMessage(),
                'action_type' => $dto->actionType,
                'user_id' => $dto->userId,
            ]);
            
            throw $e;
        }
    }

    /**
     * Обновить длительность просмотра страницы
     */
    public function updatePageViewDuration(int $pageViewId, int $durationSeconds): bool
    {
        try {
            $pageView = PageView::find($pageViewId);
            
            if (!$pageView) {
                return false;
            }

            $pageView->updateDuration($durationSeconds);
            
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update page view duration', [
                'error' => $e->getMessage(),
                'page_view_id' => $pageViewId,
                'duration' => $durationSeconds,
            ]);
            
            return false;
        }
    }

    /**
     * Массовая запись событий
     */
    public function batchTrackPageViews(array $pageViewDTOs): array
    {
        $results = [];
        
        foreach ($pageViewDTOs as $dto) {
            try {
                $results[] = $this->trackPageView($dto);
            } catch (\Exception $e) {
                Log::error('Failed to batch track page view', [
                    'error' => $e->getMessage(),
                    'dto' => $dto->toArray(),
                ]);
                $results[] = null;
            }
        }

        return array_filter($results);
    }

    /**
     * Массовая запись действий пользователей
     */
    public function batchTrackUserActions(array $userActionDTOs): array
    {
        $results = [];
        
        foreach ($userActionDTOs as $dto) {
            try {
                $results[] = $this->trackUserAction($dto);
            } catch (\Exception $e) {
                Log::error('Failed to batch track user action', [
                    'error' => $e->getMessage(),
                    'dto' => $dto->toArray(),
                ]);
                $results[] = null;
            }
        }

        return array_filter($results);
    }

    /**
     * Проверить, является ли действие конверсией
     */
    public function isConversionAction(string $actionType): bool
    {
        $conversionActions = [
            UserAction::ACTION_REGISTER,
            UserAction::ACTION_CREATE_AD,
            UserAction::ACTION_BOOK_SERVICE,
            UserAction::ACTION_COMPLETE_BOOKING,
            UserAction::ACTION_MAKE_PAYMENT,
            UserAction::ACTION_LEAVE_REVIEW,
            UserAction::ACTION_CONTACT_MASTER,
        ];

        return in_array($actionType, $conversionActions);
    }

    /**
     * Асинхронное определение местоположения
     */
    protected function detectLocationAsync(PageView $pageView): void
    {
        // В реальном проекте это должно выполняться в очереди
        dispatch(function () use ($pageView) {
            try {
                $country = $pageView->detectCountry();
                if ($country && $country !== 'Unknown') {
                    $pageView->update(['country' => $country]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to detect location', [
                    'page_view_id' => $pageView->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Записать кастомное событие
     */
    public function trackCustomEvent(array $eventData): bool
    {
        try {
            // Создаем кастомное событие аналитики
            $this->repository->createCustomEvent($eventData);
            
            Log::info('Custom analytics event tracked', $eventData);
            
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to track custom event', [
                'error' => $e->getMessage(),
                'event_data' => $eventData,
            ]);
            
            return false;
        }
    }

    /**
     * Получить список типов конверсий
     */
    public function getConversionTypes(): array
    {
        return [
            UserAction::ACTION_REGISTER => 'Регистрация',
            UserAction::ACTION_CREATE_AD => 'Создание объявления',
            UserAction::ACTION_BOOK_SERVICE => 'Бронирование услуги',
            UserAction::ACTION_COMPLETE_BOOKING => 'Завершение бронирования',
            UserAction::ACTION_MAKE_PAYMENT => 'Оплата',
            UserAction::ACTION_LEAVE_REVIEW => 'Оставление отзыва',
            UserAction::ACTION_CONTACT_MASTER => 'Контакт с мастером',
        ];
    }

    /**
     * Валидировать данные перед трекингом
     */
    public function validateTrackingData(array $data): array
    {
        $errors = [];

        // Валидация URL
        if (isset($data['url']) && !filter_var($data['url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'invalid_url';
        }

        // Валидация IP адреса
        if (isset($data['ip_address']) && !filter_var($data['ip_address'], FILTER_VALIDATE_IP)) {
            $errors[] = 'invalid_ip_address';
        }

        // Валидация user_id
        if (isset($data['user_id']) && (!is_numeric($data['user_id']) || $data['user_id'] <= 0)) {
            $errors[] = 'invalid_user_id';
        }

        return $errors;
    }
}