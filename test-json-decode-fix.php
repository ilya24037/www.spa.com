<?php

echo "🔧 ТЕСТ ИСПРАВЛЕНИЯ JSON ДЕКОДИРОВАНИЯ\n\n";

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=laravel_auth", "root", "Animatori2025!");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT photos FROM ads WHERE id = 55");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $photosJson = $result['photos'];
    echo "📸 Raw JSON из БД:\n";
    echo $photosJson . "\n\n";
    
    echo "🔍 ТЕСТИРОВАНИЕ РАЗЛИЧНЫХ СПОСОБОВ ДЕКОДИРОВАНИЯ:\n\n";
    
    // Способ 1: Обычный json_decode
    echo "1️⃣ Обычный json_decode:\n";
    $decoded1 = json_decode($photosJson, true);
    if (is_array($decoded1)) {
        echo "✅ Успешно! Получено " . count($decoded1) . " фотографий\n";
        foreach ($decoded1 as $i => $photo) {
            echo "   " . ($i + 1) . ". $photo\n";
        }
    } else {
        echo "❌ Не удалось декодировать\n";
        echo "   JSON error: " . json_last_error_msg() . "\n";
    }
    
    // Способ 2: Двойное декодирование
    echo "\n2️⃣ Двойное декодирование:\n";
    $decoded2 = json_decode(json_decode($photosJson, true), true);
    if (is_array($decoded2)) {
        echo "✅ Успешно! Получено " . count($decoded2) . " фотографий\n";
    } else {
        echo "❌ Не удалось декодировать\n";
    }
    
    // Способ 3: Удаление экранирования
    echo "\n3️⃣ Удаление экранирования:\n";
    $unescaped = stripslashes($photosJson);
    echo "Unescaped: $unescaped\n";
    $decoded3 = json_decode($unescaped, true);
    if (is_array($decoded3)) {
        echo "✅ Успешно! Получено " . count($decoded3) . " фотографий\n";
    } else {
        echo "❌ Не удалось декодировать\n";
    }
    
    // Найдем рабочий способ
    $workingMethod = null;
    $photos = null;
    
    if (is_array($decoded1)) {
        $workingMethod = "json_decode(\$photosJson, true)";
        $photos = $decoded1;
    } elseif (is_array($decoded2)) {
        $workingMethod = "json_decode(json_decode(\$photosJson, true), true)";
        $photos = $decoded2;
    } elseif (is_array($decoded3)) {
        $workingMethod = "json_decode(stripslashes(\$photosJson), true)";
        $photos = $decoded3;
    }
    
    if ($workingMethod && $photos) {
        echo "\n✅ РАБОЧИЙ СПОСОБ: $workingMethod\n";
        echo "\n📸 Проверка физических файлов:\n";
        
        foreach ($photos as $i => $photoUrl) {
            $fullPath = "C:/www.spa.com/public" . $photoUrl;
            $exists = file_exists($fullPath);
            $size = $exists ? filesize($fullPath) : 0;
            
            echo "   " . ($i + 1) . ". " . basename($photoUrl) . " - ";
            echo $exists ? "✅ СУЩЕСТВУЕТ ($size bytes)" : "❌ НЕ СУЩЕСТВУЕТ";
            echo "\n";
        }
        
        echo "\n🔧 КОД ДЛЯ MasterController:\n";
        echo "// Исправленное декодирование JSON фотографий\n";
        echo "if (\$ad && \$ad->photos) {\n";
        echo "    \$photosJson = \$ad->photos;\n";
        echo "    \$photosArray = $workingMethod;\n";
        echo "    \n";
        echo "    if (is_array(\$photosArray) && count(\$photosArray) > 0) {\n";
        echo "        \$adPhotos = array_map(function(\$photoUrl) {\n";
        echo "            return [\n";
        echo "                'url' => \$photoUrl,\n";
        echo "                'thumbnail_url' => \$photoUrl,\n";
        echo "                'alt' => 'Фото мастера'\n";
        echo "            ];\n";
        echo "        }, \$photosArray);\n";
        echo "    }\n";
        echo "}\n";
    } else {
        echo "\n❌ НИ ОДИН СПОСОБ НЕ РАБОТАЕТ!\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Ошибка БД: " . $e->getMessage() . "\n";
}