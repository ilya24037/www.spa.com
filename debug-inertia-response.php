<?php

echo "🔍 ОТЛАДКА INERTIA RESPONSE\n\n";

// Делаем запрос с Inertia заголовками
$url = 'http://spa.test/masters/klassiceskii-massaz-ot-anny-1';
echo "📋 Делаем Inertia запрос к: $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Inertia: true',
    'X-Inertia-Version: 1.0',
    'Accept: text/html, application/xhtml+xml',
    'X-Requested-With: XMLHttpRequest'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Inertia запрос успешен\n\n";
    
    // Парсим JSON ответ
    $data = json_decode($response, true);
    
    if ($data) {
        echo "📋 СТРУКТУРА INERTIA RESPONSE:\n";
        echo "Component: " . ($data['component'] ?? 'undefined') . "\n";
        echo "Version: " . ($data['version'] ?? 'undefined') . "\n";
        
        if (isset($data['props'])) {
            echo "\n📋 PROPS:\n";
            
            // Проверяем мастера
            if (isset($data['props']['master'])) {
                $master = $data['props']['master'];
                echo "✅ master prop найден\n";
                echo "   master.id: " . ($master['id'] ?? 'undefined') . "\n";
                echo "   master.display_name: " . ($master['display_name'] ?? 'undefined') . "\n";
                
                // КЛЮЧЕВАЯ ПРОВЕРКА: есть ли photos?
                if (isset($master['photos'])) {
                    echo "   ✅ master.photos найден!\n";
                    echo "   master.photos count: " . count($master['photos']) . "\n";
                    
                    foreach ($master['photos'] as $i => $photo) {
                        echo "     " . ($i + 1) . ". " . ($photo['url'] ?? 'no url') . "\n";
                    }
                } else {
                    echo "   ❌ master.photos НЕ найден!\n";
                }
            } else {
                echo "❌ master prop НЕ найден!\n";
            }
            
            // Проверяем gallery
            if (isset($data['props']['gallery'])) {
                echo "\n✅ gallery prop найден\n";
                echo "   gallery count: " . count($data['props']['gallery']) . "\n";
            } else {
                echo "\n❌ gallery prop НЕ найден!\n";
            }
            
        } else {
            echo "❌ props не найдены в ответе\n";
        }
        
        // Выводим полную структуру для анализа (только ключи)
        echo "\n📋 ПОЛНАЯ СТРУКТУРА (ключи):\n";
        function printKeys($array, $prefix = '') {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    echo "$prefix$key (array[" . count($value) . "])\n";
                    if (count($value) < 20) { // Ограничиваем вывод
                        printKeys($value, "$prefix  ");
                    }
                } else {
                    $type = gettype($value);
                    $preview = is_string($value) ? substr($value, 0, 50) : $value;
                    echo "$prefix$key ($type): $preview\n";
                }
            }
        }
        
        if ($data) {
            printKeys($data);
        }
        
    } else {
        echo "❌ Не удалось парсить JSON ответ\n";
        echo "Raw response (first 500 chars):\n";
        echo substr($response, 0, 500) . "\n";
    }
    
} else {
    echo "❌ Ошибка запроса: HTTP $httpCode\n";
}

echo "\n🎯 СЛЕДУЮЩИЙ ШАГ:\n";
echo "Если master.photos НЕ найден в Inertia response,\n";
echo "то проблема в MasterController - данные не передаются в Inertia\n";