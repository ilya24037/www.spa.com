<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Domain\Ad\Services\DraftService;
use App\Domain\User\Models\User;

$ad = DB::table('ads')->where('id', 34)->first();

if (!$ad) {
    echo "Объявление ID 34 не найдено\n";
    exit;
}

echo "ОБЪЯВЛЕНИЕ ID 34 В БАЗЕ:\n";
echo "==========================================\n";
echo "work_format: {$ad->work_format}\n";
echo "address: {$ad->address}\n";
echo "geo: " . substr($ad->geo, 0, 100) . "...\n";
echo "\nphotos (raw): {$ad->photos}\n";

// Проверим декодирование photos
$photos = json_decode($ad->photos, true);
echo "\nДекодированные photos:\n";
if (is_array($photos)) {
    echo "Массив с " . count($photos) . " элементами:\n";
    foreach($photos as $i => $photo) {
        echo "  " . ($i+1) . ". " . (is_array($photo) ? json_encode($photo) : $photo) . "\n";
    }
} else {
    echo "Не массив или пустое значение\n";
}

// Проверим через DraftService
echo "\n==========================================\n";
echo "ЧЕРЕЗ DraftService::prepareForDisplay:\n";
echo "==========================================\n";

$draftService = app(DraftService::class);
$user = User::find($ad->user_id);
$adModel = \App\Domain\Ad\Models\Ad::find($ad->id);

if ($adModel) {
    $displayData = $draftService->prepareForDisplay($adModel, $user);

    $workFormat = $displayData['work_format'] ?? 'не установлено';
    if (is_object($workFormat)) {
        $workFormat = $workFormat->value ?? (string)$workFormat;
    }
    echo "\nwork_format: " . $workFormat . "\n";
    echo "address: " . ($displayData['address'] ?? 'не установлено') . "\n";

    echo "\nphotos:\n";
    if (isset($displayData['photos'])) {
        if (is_array($displayData['photos'])) {
            echo "  Массив с " . count($displayData['photos']) . " элементами\n";
            foreach($displayData['photos'] as $i => $photo) {
                echo "  " . ($i+1) . ". " . (is_array($photo) ? json_encode($photo) : $photo) . "\n";
            }
        } else {
            echo "  Тип: " . gettype($displayData['photos']) . "\n";
            echo "  Значение: " . $displayData['photos'] . "\n";
        }
    } else {
        echo "  Не установлено\n";
    }
}