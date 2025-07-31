<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\FeatureFlagService;
use Symfony\Component\HttpFoundation\Response;

class TrackFeatureUsage
{
    private FeatureFlagService $featureFlags;

    public function __construct(FeatureFlagService $featureFlags)
    {
        $this->featureFlags = $featureFlags;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        // Если feature не указан, пытаемся определить из маршрута
        if (!$feature) {
            $feature = $this->detectFeatureFromRoute($request);
        }

        if ($feature) {
            $user = $request->user();
            $enabled = $this->featureFlags->isEnabled($feature, $user);
            
            // Логируем использование
            if (config('features.track_usage', true)) {
                $this->featureFlags->logUsage($feature, $enabled, $user);
            }

            // Если функция выключена, возвращаем 404 или редирект
            if (!$enabled) {
                return $this->handleDisabledFeature($request, $feature);
            }

            // Добавляем информацию в request для использования в контроллерах
            $request->attributes->set('feature_flag', $feature);
            $request->attributes->set('feature_enabled', true);
        }

        $response = $next($request);

        // Добавляем заголовок для отладки
        if (config('app.debug') && $feature) {
            $response->headers->set('X-Feature-Flag', $feature);
        }

        return $response;
    }

    /**
     * Определить feature из маршрута
     */
    private function detectFeatureFromRoute(Request $request): ?string
    {
        $routeName = $request->route()->getName();
        
        $routeFeatureMap = [
            'api.v2.*' => 'api_v2',
            'booking.new.*' => 'new_booking_ui',
            'search.advanced' => 'advanced_search_filters',
            'master.ai-recommendations' => 'ai_recommendations',
            'payment.new.*' => 'new_payment_gateway',
        ];

        foreach ($routeFeatureMap as $pattern => $feature) {
            if (fnmatch($pattern, $routeName)) {
                return $feature;
            }
        }

        return null;
    }

    /**
     * Обработка выключенной функции
     */
    private function handleDisabledFeature(Request $request, string $feature): Response
    {
        // Для API возвращаем JSON ошибку
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'This feature is currently disabled',
                'feature' => config('app.debug') ? $feature : null
            ], 404);
        }

        // Для веб-запросов редиректим или показываем страницу
        $redirectMap = [
            'new_booking_ui' => route('booking.index'),
            'advanced_search_filters' => route('search.index'),
            'ai_recommendations' => route('masters.index'),
        ];

        if (isset($redirectMap[$feature])) {
            return redirect($redirectMap[$feature])
                ->with('info', 'Эта функция временно недоступна');
        }

        // По умолчанию показываем 404
        abort(404);
    }
}