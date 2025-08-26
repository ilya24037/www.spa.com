<?php

namespace App\Application\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    /**
     * Кешируемые маршруты и их TTL
     */
    protected array $cacheableRoutes = [
        'masters.index' => 300,         // 5 минут
        'masters.show' => 600,          // 10 минут
        'services.index' => 3600,       // 1 час
        'services.show' => 3600,        // 1 час
        'home' => 1800,                 // 30 минут
        'search' => 60,                 // 1 минута
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Кешируем только GET запросы
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Проверяем, нужно ли кешировать этот маршрут
        $routeName = $request->route()->getName();
        if (!isset($this->cacheableRoutes[$routeName])) {
            return $next($request);
        }

        // Пропускаем кеш для авторизованных пользователей
        if ($request->user()) {
            return $next($request);
        }

        // Генерируем уникальный ключ кеша
        $cacheKey = $this->generateCacheKey($request);

        // Пытаемся получить из кеша
        if (Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);
            return response($cachedResponse['content'])
                ->header('X-Cache', 'HIT')
                ->header('Content-Type', $cachedResponse['content_type']);
        }

        // Выполняем запрос
        $response = $next($request);

        // Кешируем успешные ответы
        if ($response->getStatusCode() === 200) {
            $ttl = $this->cacheableRoutes[$routeName];
            
            Cache::put($cacheKey, [
                'content' => $response->getContent(),
                'content_type' => $response->headers->get('Content-Type', 'text/html'),
            ], $ttl);

            $response->header('X-Cache', 'MISS');
        }

        return $response;
    }

    /**
     * Генерация уникального ключа кеша
     */
    protected function generateCacheKey(Request $request): string
    {
        $url = $request->url();
        $queryParams = $request->query();
        ksort($queryParams);

        $locale = app()->getLocale();
        $device = $this->getDeviceType($request);

        return 'response_cache:' . md5($url . ':' . json_encode($queryParams) . ':' . $locale . ':' . $device);
    }

    /**
     * Определение типа устройства
     */
    protected function getDeviceType(Request $request): string
    {
        $userAgent = $request->header('User-Agent', '');
        
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $userAgent)) {
            return 'tablet';
        }
        
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $userAgent)) {
            return 'mobile';
        }
        
        return 'desktop';
    }

    /**
     * Очистка кеша для конкретного маршрута
     */
    public static function clearRouteCache(string $routeName): void
    {
        Cache::tags(['response_cache', $routeName])->flush();
    }

    /**
     * Очистка всего кеша ответов
     */
    public static function clearAllCache(): void
    {
        Cache::tags(['response_cache'])->flush();
    }
}