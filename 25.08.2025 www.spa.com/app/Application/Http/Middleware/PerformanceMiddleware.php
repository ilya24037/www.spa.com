<?php

namespace App\Application\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для оптимизации производительности
 * Реализует стратегии Wildberries для ускорения загрузки
 */
class PerformanceMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Обработка запроса
        $response = $next($request);
        
        // Применяем оптимизации к ответу
        $this->optimizeResponse($response, $request);
        
        // Логируем производительность
        $this->logPerformance($request, $startTime);
        
        return $response;
    }

    /**
     * Оптимизация HTTP ответа
     */
    private function optimizeResponse(Response $response, Request $request): void
    {
        // Добавляем заголовки кеширования
        $this->addCacheHeaders($response, $request);
        
        // Добавляем заголовки сжатия
        $this->addCompressionHeaders($response);
        
        // Добавляем заголовки безопасности
        $this->addSecurityHeaders($response);
        
        // Оптимизируем HTML (только для HTML ответов)
        if ($this->isHtmlResponse($response)) {
            $this->optimizeHtmlContent($response);
        }
        
        // Добавляем preload заголовки для критических ресурсов
        $this->addPreloadHeaders($response, $request);
    }

    /**
     * Добавление заголовков кеширования
     */
    private function addCacheHeaders(Response $response, Request $request): void
    {
        // Статичные ресурсы (JS, CSS, изображения)
        if ($this->isStaticAsset($request)) {
            $response->headers->add([
                'Cache-Control' => 'public, max-age=31536000, immutable', // 1 год
                'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000),
            ]);
            return;
        }

        // HTML страницы
        if ($this->isHtmlResponse($response)) {
            $cacheTime = $this->getPageCacheTime($request);
            
            $response->headers->add([
                'Cache-Control' => "public, max-age={$cacheTime}, s-maxage={$cacheTime}",
                'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + $cacheTime),
                'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', time()),
                'ETag' => '"' . md5($response->getContent()) . '"',
            ]);
        }

        // API ответы
        if ($this->isApiResponse($request)) {
            $response->headers->add([
                'Cache-Control' => 'public, max-age=300', // 5 минут
                'Vary' => 'Accept, Authorization',
            ]);
        }
    }

    /**
     * Добавление заголовков сжатия
     */
    private function addCompressionHeaders(Response $response): void
    {
        $response->headers->add([
            'Accept-Encoding' => 'gzip, deflate, br',
            'Content-Encoding' => 'gzip',
        ]);
    }

    /**
     * Добавление заголовков безопасности
     */
    private function addSecurityHeaders(Response $response): void
    {
        $response->headers->add([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ]);
    }

    /**
     * Добавление preload заголовков для критических ресурсов
     */
    private function addPreloadHeaders(Response $response, Request $request): void
    {
        $preloadLinks = [];

        // Критичные CSS файлы
        $preloadLinks[] = '</css/app.css>; rel=preload; as=style';
        
        // Критичные JS файлы
        $preloadLinks[] = '</js/app.js>; rel=preload; as=script';
        
        // Важные шрифты
        $preloadLinks[] = '</fonts/inter.woff2>; rel=preload; as=font; type=font/woff2; crossorigin';
        
        // Критичные изображения для главной страницы
        if ($request->is('/')) {
            $preloadLinks[] = '</images/logo.webp>; rel=preload; as=image';
            $preloadLinks[] = '</images/hero-bg.webp>; rel=preload; as=image';
        }

        if (!empty($preloadLinks)) {
            $response->headers->set('Link', implode(', ', $preloadLinks));
        }
    }

    /**
     * Оптимизация HTML контента
     */
    private function optimizeHtmlContent(Response $response): void
    {
        $content = $response->getContent();
        
        if (!$content) {
            return;
        }

        // Минификация HTML (только в production)
        if (app()->environment('production')) {
            $content = $this->minifyHtml($content);
        }

        // Оптимизация изображений в HTML
        $content = $this->optimizeImagesInHtml($content);
        
        // Добавление критичного CSS инлайн
        $content = $this->inlineCriticalCss($content);

        $response->setContent($content);
    }

    /**
     * Минификация HTML
     */
    private function minifyHtml(string $html): string
    {
        // Удаляем комментарии (кроме условных)
        $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>)).*?-->/s', '', $html);
        
        // Удаляем лишние пробелы и переносы строк
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        
        return trim($html);
    }

    /**
     * Оптимизация изображений в HTML
     */
    private function optimizeImagesInHtml(string $html): string
    {
        // Добавляем loading="lazy" к изображениям
        $html = preg_replace(
            '/<img(?![^>]*loading=)([^>]*)>/i',
            '<img$1 loading="lazy">',
            $html
        );

        // Добавляем decoding="async" для лучшей производительности
        $html = preg_replace(
            '/<img(?![^>]*decoding=)([^>]*)>/i',
            '<img$1 decoding="async">',
            $html
        );

        return $html;
    }

    /**
     * Инлайн критичного CSS
     */
    private function inlineCriticalCss(string $html): string
    {
        $criticalCss = $this->getCriticalCss();
        
        if ($criticalCss) {
            $inlineStyle = "<style>{$criticalCss}</style>";
            $html = str_replace('</head>', $inlineStyle . '</head>', $html);
        }

        return $html;
    }

    /**
     * Получение критичного CSS
     */
    private function getCriticalCss(): string
    {
        // В реальном проекте здесь будет содержимое критичного CSS файла
        return "
            /* Critical CSS */
            html { background: #fff; }
            body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
            .spinner { border: 2px solid #f3f3f3; border-top: 2px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        ";
    }

    /**
     * Логирование производительности
     */
    private function logPerformance(Request $request, float $startTime): void
    {
        $duration = (microtime(true) - $startTime) * 1000; // в миллисекундах
        
        // Логируем только медленные запросы (> 1 секунды)
        if ($duration > 1000) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => round($duration, 2),
                'memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);
        }

        // Отправляем метрики в систему мониторинга (например, New Relic, DataDog)
        if (app()->environment('production')) {
            $this->sendMetrics($request, $duration);
        }
    }

    /**
     * Отправка метрик в систему мониторинга
     */
    private function sendMetrics(Request $request, float $duration): void
    {
        // Здесь интеграция с системой мониторинга
        // Например, отправка в New Relic, DataDog, или собственную систему аналитики
        
        // Пример структуры метрик:
        $metrics = [
            'timestamp' => time(),
            'route' => $request->route() ? $request->route()->getName() : 'unknown',
            'method' => $request->method(),
            'response_time_ms' => round($duration, 2),
            'memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'is_mobile' => $this->isMobileRequest($request),
            'user_agent' => $request->userAgent(),
        ];

        // Отправка метрик (заглушка)
        Log::info('Performance metrics', $metrics);
    }

    /**
     * Проверка, является ли ответ HTML
     */
    private function isHtmlResponse(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        return str_contains($contentType, 'text/html');
    }

    /**
     * Проверка, является ли запрос API
     */
    private function isApiResponse(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    /**
     * Проверка, является ли запрос статичным ресурсом
     */
    private function isStaticAsset(Request $request): bool
    {
        $path = $request->path();
        return preg_match('/\.(js|css|png|jpg|jpeg|gif|webp|svg|woff|woff2|ttf|eot|ico)$/i', $path);
    }

    /**
     * Проверка, является ли запрос мобильным
     */
    private function isMobileRequest(Request $request): bool
    {
        $userAgent = $request->userAgent();
        return preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent);
    }

    /**
     * Получение времени кеширования для страницы
     */
    private function getPageCacheTime(Request $request): int
    {
        $route = $request->route() ? $request->route()->getName() : '';

        // Разное время кеширования для разных страниц
        $cacheTimes = [
            'home' => 300,              // 5 минут для главной
            'masters.show' => 600,      // 10 минут для профилей мастеров
            'masters.index' => 300,     // 5 минут для каталога
            'pages.about' => 3600,      // 1 час для статичных страниц
            'pages.contacts' => 3600,   // 1 час для контактов
        ];

        return $cacheTimes[$route] ?? 300; // По умолчанию 5 минут
    }
}