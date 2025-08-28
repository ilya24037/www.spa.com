<?php

echo "🔍 ТЕСТ ВЫЗОВА MasterController\n\n";

// Очищаем логи
file_put_contents('C:/www.spa.com/storage/logs/laravel.log', '');

// Делаем HTTP запрос к странице мастера
$url = 'http://spa.test/masters/klassiceskii-massaz-ot-anny-1';
echo "📋 Делаем HTTP запрос к: $url\n";

// Используем cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n";

if ($error) {
    echo "❌ cURL Error: $error\n";
} elseif ($httpCode !== 200) {
    echo "❌ HTTP Error: $httpCode\n";
    echo "Response snippet: " . substr($response, 0, 200) . "...\n";
} else {
    echo "✅ HTTP запрос успешен\n";
    
    // Проверяем, есть ли в ответе признаки Inertia
    if (strpos($response, 'inertia') !== false || strpos($response, 'Masters/Show') !== false) {
        echo "✅ Inertia page найдена в ответе\n";
    } else {
        echo "❌ Inertia page НЕ найдена в ответе\n";
    }
    
    // Ищем упоминания фото
    if (strpos($response, 'photos') !== false) {
        echo "✅ Поле 'photos' найдено в ответе\n";
    } else {
        echo "❌ Поле 'photos' НЕ найдено в ответе\n";
    }
}

// Проверяем логи Laravel после запроса
sleep(1);
echo "\n📋 Проверяем логи Laravel:\n";

$logFile = 'C:/www.spa.com/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    
    if (empty(trim($logContent))) {
        echo "❌ Логи пусты - MasterController НЕ вызывался!\n";
    } else {
        echo "✅ Есть записи в логах:\n";
        echo $logContent . "\n";
        
        // Ищем конкретные записи
        if (strpos($logContent, 'MasterController::show вызван') !== false) {
            echo "✅ MasterController::show ВЫЗВАН!\n";
        }
        
        if (strpos($logContent, 'Photos double JSON decoded') !== false) {
            echo "✅ Обработка фотографий ВЫПОЛНЕНА!\n";
        }
    }
} else {
    echo "❌ Лог файл не найден\n";
}

echo "\n🎯 ДИАГНОСТИКА:\n";
if ($httpCode === 200 && empty(trim(file_get_contents($logFile)))) {
    echo "❌ ПРОБЛЕМА: Страница загружается, но MasterController НЕ вызывается\n";
    echo "ВОЗМОЖНЫЕ ПРИЧИНЫ:\n";
    echo "1. Кэш маршрутов - выполните: php artisan route:clear\n";
    echo "2. Неправильный маршрут - проверьте routes/web.php\n";
    echo "3. Fallback обработка - страница отдается статически\n";
} elseif ($httpCode === 200) {
    echo "✅ MasterController вызывается, проблема в другом\n";
} else {
    echo "❌ ПРОБЛЕМА: Страница не загружается (HTTP $httpCode)\n";
}

echo "\nДля браузерной диагностики откройте:\n";
echo "1. F12 → Network → обновите страницу\n";
echo "2. Найдите запрос к /masters/klassiceskii-massaz-ot-anny-1\n";
echo "3. Проверьте Response JSON - есть ли поле props.master.photos?\n";