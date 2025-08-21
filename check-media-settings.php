<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "🔍 ПРОВЕРКА ПОЛЕЙ MEDIA_SETTINGS В БД\n";
echo "======================================\n\n";

// Проверяем структуру таблицы ads
$columns = DB::select('DESCRIBE ads');

echo "📋 Поля связанные с настройками медиа:\n";
foreach($columns as $column) {
    if(str_contains($column->Field, 'photo') || 
       str_contains($column->Field, 'watermark') || 
       str_contains($column->Field, 'download') || 
       str_contains($column->Field, 'gallery') ||
       str_contains($column->Field, 'media')) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }
}

echo "\n📋 Проверка черновика:\n";
$draft = \App\Domain\Ad\Models\Ad::where('status', 'draft')->first();
if ($draft) {
    echo "  ID: {$draft->id}\n";
    echo "  show_photos_in_gallery: " . ($draft->show_photos_in_gallery ? 'true' : 'false') . "\n";
    echo "  allow_download_photos: " . ($draft->allow_download_photos ? 'true' : 'false') . "\n";
    echo "  watermark_photos: " . ($draft->watermark_photos ? 'true' : 'false') . "\n";
}