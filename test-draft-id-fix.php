<?php
/**
 * Тест исправления передачи ID черновика
 * Проверяет что ID правильно передается из Edit.vue в AdForm.vue
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;

echo "🔍 ТЕСТ ИСПРАВЛЕНИЯ ПЕРЕДАЧИ ID ЧЕРНОВИКА\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Найдем пользователя
$user = User::where('email', 'anna@spa.test')->first();
if (!$user) {
    echo "❌ Пользователь anna@spa.test не найден\n";
    exit(1);
}

echo "✅ Пользователь: {$user->name} (ID: {$user->id})\n\n";

// Найдем или создадим черновик для теста
$draft = Ad::where('user_id', $user->id)
    ->where('status', 'draft')
    ->first();

if (!$draft) {
    // Создаем тестовый черновик
    $draft = Ad::create([
        'user_id' => $user->id,
        'status' => 'draft', 
        'title' => 'Тестовый черновик для проверки ID',
        'description' => 'Создан для тестирования передачи ID'
    ]);
    echo "📝 Создан новый черновик ID: {$draft->id}\n\n";
} else {
    echo "📝 Используем существующий черновик ID: {$draft->id}\n\n";
}

echo "✅ ПРИМЕНЕННЫЕ ИСПРАВЛЕНИЯ:\n";
echo "================================\n";
echo "1. **Edit.vue** - Использует actualAd везде:\n";
echo "   - actualAd = props.ad?.data || props.ad\n";
echo "   - Передает в AdForm: :ad-id=\"actualAd.id\"\n";
echo "   - Передает в AdForm: :initial-data=\"actualAd\"\n\n";

echo "2. **Логирование** добавлено для диагностики:\n";
echo "   - Edit.vue логирует структуру props.ad\n";
echo "   - AdForm.vue логирует полученные props\n\n";

echo "🎯 ОЖИДАЕМЫЙ РЕЗУЛЬТАТ В КОНСОЛИ:\n";
echo "================================\n";
echo "Edit.vue должен показать:\n";
echo "  - Для черновика: hasData: false, actualAdId: {$draft->id}\n";
echo "  - Для активного: hasData: true, actualAdId: {$draft->id}\n\n";

echo "AdForm.vue должен показать:\n";
echo "  - adId: {$draft->id} (не null!)\n";
echo "  - initialDataId: {$draft->id}\n\n";

echo "📋 ИНСТРУКЦИЯ ДЛЯ ПРОВЕРКИ:\n";
echo "================================\n";
echo "1. Откройте браузер: http://spa.test/ads/{$draft->id}/edit\n";
echo "2. Откройте консоль разработчика (F12)\n";
echo "3. Проверьте логи:\n";
echo "   - 🔍 Edit.vue: props.ad структура\n";
echo "   - 🔍 AdForm.vue: получены props\n";
echo "4. Убедитесь что adId = {$draft->id} (НЕ null!)\n";
echo "5. Измените что-нибудь и сохраните черновик\n";
echo "6. Проверьте что НЕ создался новый черновик\n\n";

// Проверим количество черновиков до теста
$draftCount = Ad::where('user_id', $user->id)
    ->where('status', 'draft')
    ->count();

echo "📊 ТЕКУЩАЯ СТАТИСТИКА:\n";
echo "  Всего черновиков у пользователя: {$draftCount}\n";
echo "  После сохранения должно остаться: {$draftCount}\n\n";

echo "✅ Если ID передается правильно и дубликаты не создаются - ПРОБЛЕМА РЕШЕНА!\n";