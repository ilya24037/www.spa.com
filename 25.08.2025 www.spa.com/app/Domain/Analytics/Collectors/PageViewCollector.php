<?php

namespace App\Domain\Analytics\Collectors;

use App\Domain\Analytics\Contracts\AnalyticsServiceInterface;
use App\Domain\Analytics\Models\PageView;
use App\Domain\Analytics\DTOs\TrackPageViewDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Сборщик данных просмотров страниц
 */
class PageViewCollector
{
    protected AnalyticsServiceInterface $analyticsService;

    public function __construct(AnalyticsServiceInterface $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Собрать данные просмотра страницы
     */
    public function collect(
        Request $request,
        ?string $viewableType = null,
        ?int $viewableId = null,
        ?string $title = null,
        array $metadata = []
    ): ?PageView {
        try {
            // Исключаем API запросы и административные страницы
            if ($this->shouldSkip($request)) {
                return null;
            }

            // Добавляем контекстную информацию
            $metadata = array_merge($metadata, [
                'route_name' => $request->route()?->getName(),
                'controller_action' => $this->getControllerAction($request),
                'utm_source' => $request->get('utm_source'),
                'utm_medium' => $request->get('utm_medium'),
                'utm_campaign' => $request->get('utm_campaign'),
                'utm_term' => $request->get('utm_term'),
                'utm_content' => $request->get('utm_content'),
                'gclid' => $request->get('gclid'), // Google Ads
                'fbclid' => $request->get('fbclid'), // Facebook Ads
            ]);

            // Убираем пустые значения
            $metadata = array_filter($metadata, function ($value) {
                return $value !== null && $value !== '';
            });

            $dto = TrackPageViewDTO::fromRequest($request, [
                'viewable_type' => $viewableType,
                'viewable_id' => $viewableId,
                'title' => $title,
                'metadata' => $metadata
            ]);

            return $this->analyticsService->trackPageView($dto);

        } catch (\Exception $e) {
            Log::error('PageViewCollector failed', [
                'error' => $e->getMessage(),
                'url' => $request->fullUrl(),
                'user_id' => Auth::id(),
            ]);

            return null;
        }
    }

    /**
     * Собрать просмотр мастера
     */
    public function collectMasterView(Request $request, int $masterProfileId, ?string $masterName = null): ?PageView
    {
        return $this->collect(
            request: $request,
            viewableType: \App\Domain\Master\Models\MasterProfile::class,
            viewableId: $masterProfileId,
            title: $masterName ? "Профиль мастера: {$masterName}" : 'Профиль мастера',
            metadata: [
                'content_type' => 'master_profile',
                'master_id' => $masterProfileId,
            ]
        );
    }

    /**
     * Собрать просмотр объявления
     */
    public function collectAdView(Request $request, int $adId, ?string $adTitle = null): ?PageView
    {
        return $this->collect(
            request: $request,
            viewableType: \App\Domain\Ad\Models\Ad::class,
            viewableId: $adId,
            title: $adTitle ? "Объявление: {$adTitle}" : 'Объявление',
            metadata: [
                'content_type' => 'ad',
                'ad_id' => $adId,
            ]
        );
    }

    /**
     * Собрать просмотр главной страницы
     */
    public function collectHomeView(Request $request): ?PageView
    {
        return $this->collect(
            request: $request,
            title: 'Главная страница',
            metadata: [
                'content_type' => 'homepage',
                'search_query' => $request->get('q'),
                'category' => $request->get('category'),
                'location' => $request->get('location'),
            ]
        );
    }

    /**
     * Собрать просмотр результатов поиска
     */
    public function collectSearchView(Request $request, int $resultsCount = 0): ?PageView
    {
        return $this->collect(
            request: $request,
            title: 'Результаты поиска',
            metadata: [
                'content_type' => 'search_results',
                'search_query' => $request->get('q'),
                'category' => $request->get('category'),
                'location' => $request->get('location'),
                'price_min' => $request->get('price_min'),
                'price_max' => $request->get('price_max'),
                'results_count' => $resultsCount,
                'page' => $request->get('page', 1),
            ]
        );
    }

    /**
     * Собрать просмотр профиля пользователя
     */
    public function collectProfileView(Request $request, int $userId, string $section = 'general'): ?PageView
    {
        return $this->collect(
            request: $request,
            viewableType: \App\Domain\User\Models\User::class,
            viewableId: $userId,
            title: 'Профиль пользователя',
            metadata: [
                'content_type' => 'user_profile',
                'profile_section' => $section,
                'profile_user_id' => $userId,
            ]
        );
    }

    /**
     * Обновить длительность просмотра (вызывается из JavaScript)
     */
    public function updateDuration(int $pageViewId, int $durationSeconds): bool
    {
        try {
            return $this->analyticsService->updatePageViewDuration($pageViewId, $durationSeconds);
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
     * Массовый сбор просмотров (для исторических данных)
     */
    public function collectBatch(array $pageViews): array
    {
        $results = ['success' => 0, 'failed' => 0];

        foreach ($pageViews as $pageViewData) {
            try {
                $dto = TrackPageViewDTO::fromArray($pageViewData);
                $this->analyticsService->trackPageView($dto);

                $results['success']++;

            } catch (\Exception $e) {
                $results['failed']++;
                
                Log::warning('Failed to collect page view in batch', [
                    'error' => $e->getMessage(),
                    'data' => $pageViewData,
                ]);
            }
        }

        return $results;
    }

    /**
     * Получить статистику сборщика
     */
    public function getCollectorStats(): array
    {
        $today = now()->startOfDay();
        
        return [
            'today_collected' => PageView::where('viewed_at', '>=', $today)->count(),
            'week_collected' => PageView::where('viewed_at', '>=', now()->startOfWeek())->count(),
            'month_collected' => PageView::where('viewed_at', '>=', now()->startOfMonth())->count(),
            'total_collected' => PageView::count(),
            'unique_sessions_today' => PageView::where('viewed_at', '>=', $today)
                ->distinct('session_id')->count(),
            'bots_filtered_today' => PageView::where('viewed_at', '>=', $today)
                ->where('is_bot', true)->count(),
        ];
    }

    // ============ HELPER METHODS ============

    /**
     * Проверить, нужно ли пропустить сбор данных
     */
    protected function shouldSkip(Request $request): bool
    {
        $skipPatterns = [
            '/api/',
            '/admin/',
            '/telescope/',
            '/horizon/',
            '/_debugbar/',
            '/livewire/',
            '.json',
            '.xml',
            '.txt',
            '.css',
            '.js',
            '.png',
            '.jpg',
            '.jpeg',
            '.gif',
            '.svg',
            '.ico',
            '.woff',
            '.woff2',
            '.ttf',
            '.pdf',
        ];

        $url = $request->getPathInfo();

        foreach ($skipPatterns as $pattern) {
            if (strpos($url, $pattern) !== false) {
                return true;
            }
        }

        // Пропускаем AJAX запросы без специального заголовка
        if ($request->ajax() && !$request->header('X-Track-Analytics')) {
            return true;
        }

        // Пропускаем запросы от ботов (базовая проверка)
        $userAgent = $request->header('User-Agent', '');
        $botPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'slurp',
            'googlebot', 'bingbot', 'yandexbot', 'facebookexternalhit'
        ];

        foreach ($botPatterns as $botPattern) {
            if (stripos($userAgent, $botPattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Получить действие контроллера
     */
    protected function getControllerAction(Request $request): ?string
    {
        $route = $request->route();
        
        if (!$route) {
            return null;
        }

        $action = $route->getAction();
        
        if (isset($action['controller'])) {
            return $action['controller'];
        }

        return null;
    }

    /**
     * Проверить, является ли запрос от поискового робота
     */
    protected function isBot(string $userAgent): bool
    {
        $botPatterns = [
            'googlebot', 'bingbot', 'slurp', 'duckduckbot',
            'baiduspider', 'yandexbot', 'facebookexternalhit',
            'twitterbot', 'linkedinbot', 'whatsapp', 'telegram',
            'crawler', 'spider', 'scraper', 'bot'
        ];

        $userAgent = strtolower($userAgent);

        foreach ($botPatterns as $pattern) {
            if (strpos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Очистить URL от служебных параметров
     */
    protected function cleanUrl(string $url): string
    {
        $parsed = parse_url($url);
        
        if (!isset($parsed['query'])) {
            return $url;
        }

        parse_str($parsed['query'], $params);
        
        // Удаляем служебные параметры
        $excludeParams = [
            '_token', 'csrf_token', '_method',
            'timestamp', 'signature', 'hash'
        ];

        foreach ($excludeParams as $param) {
            unset($params[$param]);
        }

        $cleanQuery = http_build_query($params);
        
        $cleanUrl = $parsed['scheme'] . '://' . $parsed['host'];
        if (isset($parsed['port'])) {
            $cleanUrl .= ':' . $parsed['port'];
        }
        $cleanUrl .= $parsed['path'] ?? '/';
        
        if ($cleanQuery) {
            $cleanUrl .= '?' . $cleanQuery;
        }

        return $cleanUrl;
    }
}