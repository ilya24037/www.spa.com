<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Foundation\Http\Kernel::class)->handle(
    $request = \Illuminate\Http\Request::capture()
);

echo "🔧 Исправление проблемы с черновиками\n";
echo "=====================================\n\n";

// Используем прямой SQL для обхода проблемы с памятью
try {
    // Увеличиваем буферы для текущей сессии
    \DB::statement("SET SESSION sort_buffer_size = 2097152");
    \DB::statement("SET SESSION read_rnd_buffer_size = 2097152");
    echo "✅ Буферы MySQL увеличены\n\n";
} catch (\Exception $e) {
    echo "⚠️ Не удалось увеличить буферы: " . $e->getMessage() . "\n\n";
}

// Получаем ID черновиков без загрузки всех данных
echo "🔍 Поиск проблемных черновиков...\n";

$draftsInfo = \DB::select("
    SELECT id, user_id, title,
           LENGTH(photos) as photos_len,
           LENGTH(services) as services_len,
           LENGTH(geo) as geo_len
    FROM ads 
    WHERE status = 'draft'
    ORDER BY id DESC
    LIMIT 10
");

echo "Найдено черновиков: " . count($draftsInfo) . "\n\n";

foreach ($draftsInfo as $info) {
    echo "📝 Черновик ID: {$info->id}\n";
    echo "   User: {$info->user_id}\n";
    echo "   Title: {$info->title}\n";
    echo "   Размеры полей:\n";
    echo "   - photos: {$info->photos_len} bytes\n";
    echo "   - services: {$info->services_len} bytes\n";
    echo "   - geo: {$info->geo_len} bytes\n";
    
    $needsFix = false;
    
    // Если какое-то поле слишком большое, исправляем
    if ($info->photos_len > 5000) {
        echo "   ⚠️ Поле photos слишком большое! Очищаем...\n";
        \DB::update("UPDATE ads SET photos = ? WHERE id = ?", ['[]', $info->id]);
        $needsFix = true;
    }
    
    if ($info->services_len > 5000) {
        echo "   ⚠️ Поле services слишком большое! Очищаем...\n";
        \DB::update("UPDATE ads SET services = ? WHERE id = ?", ['{}', $info->id]);
        $needsFix = true;
    }
    
    if ($info->geo_len > 1000) {
        echo "   ⚠️ Поле geo слишком большое! Очищаем...\n";
        \DB::update("UPDATE ads SET geo = ? WHERE id = ?", ['{}', $info->id]);
        $needsFix = true;
    }
    
    if ($needsFix) {
        echo "   ✅ Черновик исправлен\n";
    } else {
        echo "   ✅ Черновик в порядке\n";
    }
    
    echo "\n";
}

// Оптимизируем таблицу
echo "🔧 Оптимизация таблицы ads...\n";
try {
    \DB::statement("OPTIMIZE TABLE ads");
    echo "✅ Таблица оптимизирована\n";
} catch (\Exception $e) {
    echo "⚠️ Не удалось оптимизировать: " . $e->getMessage() . "\n";
}

echo "\n✅ Исправление завершено!\n";
echo "Попробуйте открыть черновик снова.\n";