<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- CSP для разрешения Web Workers от Yandex Maps -->
    @if (config('app.env') === 'production')
    <meta http-equiv="Content-Security-Policy" content="worker-src blob: 'self' data: https: wss: 'unsafe-eval'; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: data: https:; connect-src 'self' https: wss:;">
    @else
    <!-- Development: более мягкая CSP для работы с Vite dev server -->
    <meta http-equiv="Content-Security-Policy" content="worker-src blob: 'self' data: https: http: wss: ws: 'unsafe-eval'; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: data: https: http: localhost:*; connect-src 'self' https: http: wss: ws: localhost:*;">
    @endif

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600&display=swap" rel="stylesheet" />
    
    @routes
    
    
    <!-- Scripts -->
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
    <meta name="csrf-token" content="{{ csrf_token() }}">
  </head>
  <body class="font-sans antialiased">
    @inertia

    <div id="overlay-root"></div>
  </body>
</html>
