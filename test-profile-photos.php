<?php

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "=== ПРОВЕРКА ФОТО В ЧЕРНОВИКАХ И АКТИВНЫХ ОБЪЯВЛЕНИЯХ ===\n\n";

// Берем первого пользователя с объявлениями
$user = User::whereHas('ads')->first();

if (!$user) {
    echo "Нет пользователей с объявлениями\n";
    exit;
}

echo "Пользователь ID: {$user->id}\n\n";

// Проверяем черновики
echo "ЧЕРНОВИКИ (draft):\n";
$drafts = Ad::where('user_id', $user->id)
    ->where('status', 'draft')
    ->select(['id', 'title', 'photos', 'status'])
    ->limit(3)
    ->get();

foreach ($drafts as $ad) {
    echo "  ID {$ad->id}: {$ad->title}\n";
    echo "    Raw photos: " . substr($ad->photos, 0, 100) . "...\n";
    
    $photos = is_string($ad->photos) ? json_decode($ad->photos, true) : $ad->photos;
    if (is_array($photos) && !empty($photos)) {
        $firstPhoto = $photos[0];
        echo "    Первое фото тип: " . gettype($firstPhoto) . "\n";
        if (is_array($firstPhoto)) {
            echo "    Ключи в фото: " . implode(', ', array_keys($firstPhoto)) . "\n";
            echo "    URL: " . ($firstPhoto['preview'] ?? $firstPhoto['url'] ?? $firstPhoto['src'] ?? 'НЕ НАЙДЕН') . "\n";
        } else {
            echo "    URL: $firstPhoto\n";
        }
    } else {
        echo "    Фото пусто или не массив\n";
    }
    echo "\n";
}

// Проверяем активные
echo "\nАКТИВНЫЕ (active):\n";
$active = Ad::where('user_id', $user->id)
    ->where('status', 'active')
    ->select(['id', 'title', 'photos', 'status'])
    ->limit(3)
    ->get();

foreach ($active as $ad) {
    echo "  ID {$ad->id}: {$ad->title}\n";
    echo "    Raw photos: " . substr($ad->photos, 0, 100) . "...\n";
    
    $photos = is_string($ad->photos) ? json_decode($ad->photos, true) : $ad->photos;
    if (is_array($photos) && !empty($photos)) {
        $firstPhoto = $photos[0];
        echo "    Первое фото тип: " . gettype($firstPhoto) . "\n";
        if (is_array($firstPhoto)) {
            echo "    Ключи в фото: " . implode(', ', array_keys($firstPhoto)) . "\n";
            echo "    URL: " . ($firstPhoto['preview'] ?? $firstPhoto['url'] ?? $firstPhoto['src'] ?? 'НЕ НАЙДЕН') . "\n";
        } else {
            echo "    URL: $firstPhoto\n";
        }
    } else {
        echo "    Фото пусто или не массив\n";
    }
    echo "\n";
}

echo "✅ Анализ завершен\n";
echo "\nЕсли фото в черновиках - массивы с ключами, а в активных - строки,\n";
echo "то нужно проверить процесс сохранения при публикации объявления.\n";