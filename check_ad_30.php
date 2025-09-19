<?php
// Проверка объявления ID 30

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n==========================================\n";
echo "ПРОВЕРКА ОБЪЯВЛЕНИЯ ID: 30\n";
echo "==========================================\n\n";

$ad = DB::table('ads')->where('id', 30)->first();

if (!$ad) {
    die("Объявление ID 30 не найдено в БД\n");
}

echo "ID: {$ad->id}\n";
echo "Title: {$ad->title}\n";
echo "Status: {$ad->status}\n";
echo "Is Published: {$ad->is_published}\n\n";

echo "ПРОБЛЕМНЫЕ ПОЛЯ:\n";
echo "==========================================\n";

// specialty
echo "1. specialty: ";
if ($ad->specialty === 'massage_classic') {
    echo "'{$ad->specialty}' ✅✅✅ СОХРАНЕНО!\n";
} else {
    echo "'" . ($ad->specialty ?: 'NULL') . "' ❌\n";
}

// work_format
echo "2. work_format: ";
if ($ad->work_format === 'duo') {
    echo "'{$ad->work_format}' ✅✅✅ СОХРАНЕНО!\n";
} else {
    echo "'" . ($ad->work_format ?: 'NULL') . "' ❌\n";
}

// service_provider
echo "3. service_provider (RAW): {$ad->service_provider}\n";
echo "   service_provider (TYPE): " . gettype($ad->service_provider) . "\n";

// Попробуем декодировать
$decoded = json_decode($ad->service_provider, true);
if (is_array($decoded)) {
    echo "   service_provider (DECODED): " . json_encode($decoded) . "\n";
    echo "   Содержит 'women': " . (in_array('women', $decoded) ? "ДА ✅" : "НЕТ ❌") . "\n";
    echo "   Содержит 'men': " . (in_array('men', $decoded) ? "ДА ✅" : "НЕТ ❌") . "\n";

    if (in_array('women', $decoded) && in_array('men', $decoded)) {
        echo "   РЕЗУЛЬТАТ: ✅✅✅ СОХРАНЕНО КАК JSON!\n";
    }
} else {
    // Может быть двойное кодирование?
    $double_decoded = json_decode(json_decode($ad->service_provider), true);
    if (is_array($double_decoded)) {
        echo "   ДВОЙНОЕ КОДИРОВАНИЕ обнаружено!\n";
        echo "   После двойного декодирования: " . json_encode($double_decoded) . "\n";
    } else {
        echo "   ❌ НЕ УДАЛОСЬ ДЕКОДИРОВАТЬ\n";
    }
}

echo "\n==========================================\n";
echo "ИТОГ:\n";
echo "==========================================\n";

$specialty_ok = ($ad->specialty === 'massage_classic');
$work_format_ok = ($ad->work_format === 'duo');
$service_provider_ok = is_array($decoded) && in_array('women', $decoded) && in_array('men', $decoded);

if ($specialty_ok && $work_format_ok && $service_provider_ok) {
    echo "✅✅✅ ВСЕ ПОЛЯ СОХРАНЕНЫ КОРРЕКТНО! ✅✅✅\n";
} else {
    echo "Результаты:\n";
    echo "  specialty: " . ($specialty_ok ? "✅" : "❌") . "\n";
    echo "  work_format: " . ($work_format_ok ? "✅" : "❌") . "\n";
    echo "  service_provider: " . ($service_provider_ok ? "✅" : "❌") . "\n";
}

echo "\nSQL для проверки:\n";
echo "SELECT id, specialty, work_format, service_provider FROM ads WHERE id = 30;\n";