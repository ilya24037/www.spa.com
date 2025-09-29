<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogFilamentErrors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log errors for admin panel requests
        if ($request->is('admin/*') || $request->is('admin')) {
            if ($response->exception) {
                $this->logError($request, $response->exception);
            }

            // Check for specific error patterns in response
            $content = $response->getContent();
            if ($content && is_string($content)) {
                if (str_contains($content, 'TypeError') ||
                    str_contains($content, 'duplicate') ||
                    str_contains($content, 'Argument #1')) {

                    Log::channel('admin_actions')->error('Filament Resource Error', [
                        'url' => $request->fullUrl(),
                        'method' => $request->method(),
                        'user' => auth()->user()?->email,
                        'error_indicators' => $this->extractErrorIndicators($content),
                    ]);
                }
            }
        }

        return $response;
    }

    private function logError(Request $request, \Throwable $exception): void
    {
        Log::channel('admin_actions')->error('Filament Exception', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user' => auth()->user()?->email,
            'exception' => [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'class' => get_class($exception),
            ],
            'request_data' => $request->except(['password', 'password_confirmation']),
        ]);
    }

    private function extractErrorIndicators(string $content): array
    {
        $indicators = [];

        // Extract TypeError details
        if (preg_match('/TypeError.*?Argument #(\d+).*?must be of type (\w+), (\w+) given/', $content, $matches)) {
            $indicators['type_error'] = [
                'argument' => $matches[1],
                'expected' => $matches[2],
                'given' => $matches[3],
            ];
        }

        // Extract duplicate key patterns
        if (preg_match_all('/duplicate.*?key.*?[\'"](\w+)[\'"]/', $content, $matches)) {
            $indicators['duplicate_keys'] = array_unique($matches[1]);
        }

        // Extract enum value patterns
        if (preg_match_all('/(AdStatus|UserStatus|MasterStatus)::\w+->value/', $content, $matches)) {
            $indicators['enum_usage'] = array_count_values($matches[0]);
        }

        return $indicators;
    }
}