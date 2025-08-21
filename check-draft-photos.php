<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Foundation\Http\Kernel::class)->handle(
    $request = \Illuminate\Http\Request::capture()
);

echo "🔍 Проверка черновиков с фотографиями\n";
echo "=====================================\n\n";

// Получаем все черновики
$drafts = \App\Domain\Ad\Models\Ad::where('status', 'draft')
    ->orderBy('updated_at', 'desc')
    ->limit(5)
    ->get();

foreach ($drafts as $draft) {
    echo "📝 Черновик ID: {$draft->id}\n";
    echo "   User ID: {$draft->user_id}\n";
    echo "   Title: {$draft->title}\n";
    echo "   Updated: {$draft->updated_at}\n";
    
    // Анализируем поле photos
    $photosRaw = $draft->getAttributes()['photos'] ?? null;
    echo "   Photos raw type: " . gettype($photosRaw) . "\n";
    
    if ($photosRaw) {
        echo "   Photos length: " . strlen($photosRaw) . " chars\n";
        echo "   Photos preview: " . substr($photosRaw, 0, 100) . "...\n";
        
        // Пробуем декодировать
        $decoded = json_decode($photosRaw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "   ✅ JSON valid, items: " . count($decoded) . "\n";
            if (is_array($decoded) && count($decoded) > 0) {
                echo "   First item: " . (is_string($decoded[0]) ? $decoded[0] : json_encode($decoded[0])) . "\n";
            }
        } else {
            echo "   ❌ JSON error: " . json_last_error_msg() . "\n";
            
            // Попробуем исправить
            echo "   🔧 Пытаемся исправить...\n";
            
            // Очищаем поле photos
            $draft->photos = '[]';
            $draft->save();
            echo "   ✅ Поле photos очищено\n";
        }
    } else {
        echo "   Photos: EMPTY\n";
    }
    
    echo "\n";
}

echo "\n✅ Проверка завершена\n";