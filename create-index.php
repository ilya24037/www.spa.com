<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Foundation\Http\Kernel::class)->handle(
    $request = \Illuminate\Http\Request::capture()
);

echo "🔧 Создание индекса для оптимизации запросов\n";
echo "=============================================\n\n";

try {
    // Проверяем существующие индексы
    echo "📊 Проверка существующих индексов...\n";
    $indexes = \DB::select("SHOW INDEX FROM ads");
    
    $hasUserStatusCreated = false;
    foreach ($indexes as $index) {
        if ($index->Key_name === 'idx_user_status_created') {
            $hasUserStatusCreated = true;
            break;
        }
    }
    
    if (!$hasUserStatusCreated) {
        echo "🔧 Создание индекса idx_user_status_created...\n";
        \DB::statement("CREATE INDEX idx_user_status_created ON ads (user_id, status, created_at DESC)");
        echo "✅ Индекс создан успешно!\n";
    } else {
        echo "✅ Индекс idx_user_status_created уже существует\n";
    }
    
    // Создаем дополнительный индекс для status и created_at
    $hasStatusCreated = false;
    foreach ($indexes as $index) {
        if ($index->Key_name === 'idx_status_created') {
            $hasStatusCreated = true;
            break;
        }
    }
    
    if (!$hasStatusCreated) {
        echo "🔧 Создание индекса idx_status_created...\n";
        \DB::statement("CREATE INDEX idx_status_created ON ads (status, created_at DESC)");
        echo "✅ Индекс создан успешно!\n";
    } else {
        echo "✅ Индекс idx_status_created уже существует\n";
    }
    
    // Анализируем таблицу для обновления статистики
    echo "\n🔧 Анализ таблицы ads...\n";
    \DB::statement("ANALYZE TABLE ads");
    echo "✅ Анализ завершен!\n";
    
    echo "\n✅ Оптимизация завершена!\n";
    echo "Теперь запросы с сортировкой должны работать быстрее.\n";
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}