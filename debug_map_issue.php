<?php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

echo "=== ДИАГНОСТИКА ПРОБЛЕМЫ С КАРТОЙ ===" . PHP_EOL;

try {
    // Проверяем количество активных объявлений
    $activeAds = App\Domain\Ad\Models\Ad::where('status', 'active')->count();
    echo "Активных объявлений в БД: " . $activeAds . PHP_EOL;
    
    // Проверяем первые 3 активных объявления
    $ads = App\Domain\Ad\Models\Ad::where('status', 'active')->limit(3)->get();
    echo "Загружено объявлений: " . $ads->count() . PHP_EOL;
    
    foreach ($ads as $ad) {
        echo "ID: " . $ad->id . ", Title: " . $ad->title . PHP_EOL;
        echo "  Geo поле: " . ($ad->geo ? 'ЕСТЬ' : 'НЕТ') . PHP_EOL;
        if ($ad->geo) {
            $geo = json_decode($ad->geo, true);
            if (isset($geo['coordinates'])) {
                echo "  Координаты: " . json_encode($geo['coordinates']) . PHP_EOL;
            } else {
                echo "  Координат нет в geo" . PHP_EOL;
            }
        }
        echo "---" . PHP_EOL;
    }
    
    // Проверяем AdService
    $adService = app(App\Domain\Ad\Services\AdService::class);
    $homeAds = $adService->getActiveAdsForHome(3);
    echo "AdService вернул объявлений: " . $homeAds->count() . PHP_EOL;
    
} catch (Exception $e) {
    echo "ОШИБКА: " . $e->getMessage() . PHP_EOL;
    echo "Файл: " . $e->getFile() . " строка " . $e->getLine() . PHP_EOL;
}