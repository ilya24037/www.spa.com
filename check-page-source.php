<?php

echo "🔍 ПРОВЕРКА СОДЕРЖИМОГО СТРАНИЦЫ\n\n";

// Делаем обычный HTTP запрос
$url = 'http://spa.test/masters/klassiceskii-massaz-ot-anny-1';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Страница загружена\n\n";
    
    // Ищем Inertia page data
    if (preg_match('/<div id="app" data-page="([^"]*)"/', $response, $matches)) {
        echo "✅ Найден Inertia data-page\n";
        
        // Декодируем JSON
        $pageData = html_entity_decode($matches[1]);
        $data = json_decode($pageData, true);
        
        if ($data) {
            echo "✅ JSON успешно распарсен\n";
            echo "Component: " . ($data['component'] ?? 'undefined') . "\n";
            
            if (isset($data['props']['master'])) {
                $master = $data['props']['master'];
                echo "\n📋 MASTER DATA:\n";
                echo "   ID: " . ($master['id'] ?? 'undefined') . "\n";
                echo "   Display Name: " . ($master['display_name'] ?? 'undefined') . "\n";
                
                // КРИТИЧЕСКАЯ ПРОВЕРКА
                if (isset($master['photos'])) {
                    echo "   ✅ photos найдены: " . count($master['photos']) . " шт\n";
                    
                    foreach ($master['photos'] as $i => $photo) {
                        echo "     " . ($i + 1) . ". " . ($photo['url'] ?? 'no url') . "\n";
                    }
                } else {
                    echo "   ❌ photos НЕ найдены в master!\n";
                    echo "   Доступные ключи master: " . implode(', ', array_keys($master)) . "\n";
                }
            } else {
                echo "\n❌ master НЕ найден в props\n";
                echo "Доступные props: " . implode(', ', array_keys($data['props'] ?? [])) . "\n";
            }
            
            // Проверяем gallery отдельно
            if (isset($data['props']['gallery'])) {
                echo "\n✅ gallery найден: " . count($data['props']['gallery']) . " элементов\n";
                
                foreach ($data['props']['gallery'] as $i => $item) {
                    if (is_array($item)) {
                        echo "     " . ($i + 1) . ". " . ($item['url'] ?? 'no url') . "\n";
                    } else {
                        echo "     " . ($i + 1) . ". $item\n";
                    }
                }
            }
            
        } else {
            echo "❌ Не удалось распарсить JSON\n";
            echo "Raw page data (first 200 chars): " . substr($pageData, 0, 200) . "\n";
        }
        
    } else {
        echo "❌ Не найден data-page в HTML\n";
        
        // Проверим есть ли вообще #app
        if (strpos($response, 'id="app"') !== false) {
            echo "✅ #app найден, но без data-page\n";
        } else {
            echo "❌ #app НЕ найден в HTML\n";
        }
        
        // Ищем любые упоминания фотографий
        if (strpos($response, 'photos') !== false) {
            echo "✅ Слово 'photos' найдено в HTML\n";
        } else {
            echo "❌ Слово 'photos' НЕ найдено в HTML\n";
        }
    }
    
} else {
    echo "❌ Ошибка загрузки страницы: HTTP $httpCode\n";
}

echo "\n🎯 ИТОГ:\n";
echo "Если photos НЕ найдены в master объекте,\n";
echo "значит проблема в MasterController - данные не добавляются в \$masterArray\n";