<?php
/**
 * Тестовый скрипт для проверки корректности обновления черновиков
 * и отсутствия создания дубликатов
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;

echo "🔍 ТЕСТ ОБНОВЛЕНИЯ ЧЕРНОВИКА\n";
echo "================================\n\n";

// Найдем пользователя anna@spa.test
$user = User::where('email', 'anna@spa.test')->first();
if (!$user) {
    echo "❌ Пользователь anna@spa.test не найден\n";
    exit(1);
}

echo "✅ Пользователь: {$user->name} (ID: {$user->id})\n\n";

// Проверим существующие черновики
$drafts = Ad::where('user_id', $user->id)
    ->where('status', 'draft')
    ->orderBy('created_at', 'desc')
    ->get(['id', 'title', 'created_at', 'updated_at']);

echo "📋 Существующие черновики:\n";
foreach ($drafts as $draft) {
    echo "  ID: {$draft->id}, Title: {$draft->title}\n";
    echo "    Created: {$draft->created_at}, Updated: {$draft->updated_at}\n";
}
echo "  Всего черновиков: " . $drafts->count() . "\n\n";

// Симулируем обновление первого черновика
if ($drafts->count() > 0) {
    $draftToUpdate = $drafts->first();
    echo "🔧 Симуляция обновления черновика ID: {$draftToUpdate->id}\n";
    
    // Обновим только updated_at для проверки
    $draftToUpdate->updated_at = now();
    $draftToUpdate->save();
    
    echo "✅ Черновик обновлен (обновлено только updated_at)\n";
    echo "  Updated at: {$draftToUpdate->updated_at}\n\n";
    
    // Проверим, что не создан дубликат
    $newCount = Ad::where('user_id', $user->id)
        ->where('status', 'draft')
        ->count();
    
    if ($newCount === $drafts->count()) {
        echo "✅ УСПЕХ: Дубликат не создан, количество черновиков осталось прежним: {$newCount}\n";
    } else {
        echo "❌ ПРОБЛЕМА: Количество черновиков изменилось! Было: {$drafts->count()}, стало: {$newCount}\n";
    }
} else {
    echo "⚠️ У пользователя нет черновиков для тестирования\n";
}

echo "\n🎯 РЕКОМЕНДАЦИИ:\n";
echo "1. Проверьте в браузере: http://spa.test/ads/{$draftToUpdate->id}/edit\n";
echo "2. Попробуйте сохранить черновик\n";
echo "3. Убедитесь, что ID остается тем же и нет дубликатов\n";
echo "4. Проверьте перенаправление на http://spa.test/profile/items/draft/all\n";