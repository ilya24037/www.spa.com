<?php
echo "=== PHP LIMITS ===\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "max_input_vars: " . ini_get('max_input_vars') . "\n";
echo "max_input_nesting_level: " . ini_get('max_input_nesting_level') . "\n";
echo "max_input_time: " . ini_get('max_input_time') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";

// Проверяем настройки Laravel
echo "\n=== LARAVEL CONFIG ===\n";
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "APP_ENV: " . env('APP_ENV') . "\n";
echo "APP_DEBUG: " . (env('APP_DEBUG') ? 'true' : 'false') . "\n";

// Проверяем максимальный размер JSON
echo "\n=== JSON LIMITS ===\n";
$testData = str_repeat('a', 1024 * 1024); // 1MB
$encoded = json_encode(['test' => $testData]);
echo "Can encode 1MB JSON: " . (strlen($encoded) > 0 ? 'YES' : 'NO') . "\n";

$testData = str_repeat('a', 5 * 1024 * 1024); // 5MB
$encoded = json_encode(['test' => $testData]);
echo "Can encode 5MB JSON: " . (strlen($encoded) > 0 ? 'YES' : 'NO') . "\n";