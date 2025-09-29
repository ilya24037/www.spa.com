<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для настройки HTTP cache headers
 * Улучшает производительность через браузерное кеширование
 */
class CacheHeaders
{
    public function handle(Request $request, Closure $next, ...$options): Response
    {
        $response = $next($request);

        // Не кешируем POST, PUT, DELETE запросы
        if (!$request->isMethod('GET')) {
            return $response;
        }

        // Не кешируем админ-панель и личные данные
        if ($this->shouldNotCache($request)) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            return $response;
        }

        // Определяем тип контента и время кеширования
        $cacheTime = $this->getCacheTime($request, $options);

        if ($cacheTime > 0) {
            $response->headers->set('Cache-Control', "public, max-age={$cacheTime}");
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT');

            // ETag для проверки изменений
            if ($response->getContent()) {
                $etag = md5($response->getContent());
                $response->headers->set('ETag', $etag);

                // Проверяем If-None-Match
                if ($request->header('If-None-Match') === $etag) {
                    return response()->make('', 304);
                }
            }

            // Last-Modified заголовок
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        }

        return $response;
    }

    /**
     * Определить, не должен ли запрос кешироваться
     */
    private function shouldNotCache(Request $request): bool
    {
        $path = $request->path();

        // Не кешируем админ-панель
        if (str_starts_with($path, 'admin/')) {
            return true;
        }

        // Не кешируем API с личными данными
        if (str_starts_with($path, 'api/user/')) {
            return true;
        }

        // Не кешируем страницы профиля
        if (str_starts_with($path, 'profile/')) {
            return true;
        }

        // Не кешируем страницы с формами
        if (str_contains($path, 'create') || str_contains($path, 'edit')) {
            return true;
        }

        return false;
    }

    /**
     * Получить время кеширования в секундах
     */
    private function getCacheTime(Request $request, array $options): int
    {
        // Если время передано в параметрах middleware
        if (!empty($options[0]) && is_numeric($options[0])) {
            return (int) $options[0];
        }

        $path = $request->path();

        // Статические ресурсы - долгое кеширование
        if (str_contains($path, '.css') || str_contains($path, '.js')) {
            return 31536000; // 1 год
        }

        // Изображения - долгое кеширование
        if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg|ico)$/i', $path)) {
            return 2592000; // 30 дней
        }

        // API данные - краткое кеширование
        if (str_starts_with($path, 'api/')) {
            // Публичные данные
            if (str_contains($path, 'ads') || str_contains($path, 'masters') || str_contains($path, 'categories')) {
                return 300; // 5 минут
            }
            return 0; // Не кешируем остальные API
        }

        // Главная страница - умеренное кеширование
        if ($path === '/' || $path === 'home') {
            return 600; // 10 минут
        }

        // Страницы каталога - краткое кеширование
        if (str_contains($path, 'masters') || str_contains($path, 'ads')) {
            return 300; // 5 минут
        }

        // Остальные страницы - краткое кеширование
        return 180; // 3 минуты
    }
}