<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Тестирование исправления кнопки 'Сохранить изменения'...\n\n";

// Проверяем объявление 128
$ad = \App\Domain\Ad\Models\Ad::find(128);

if ($ad) {
    echo "📋 ОБЪЯВЛЕНИЕ 128:\n";
    echo "================\n";
    echo "ID: {$ad->id}\n";
    echo "Статус: {$ad->status->value}\n";
    echo "Заголовок: {$ad->title}\n";
    echo "Адрес: {$ad->address}\n";
    echo "Цена: {$ad->price}\n";
    
    echo "\n✅ ИЗМЕНЕНИЯ:\n";
    echo "=============\n";
    echo "1. ✅ FormActions.vue - изменен type кнопки с 'submit' на 'button'\n";
    echo "2. ✅ AdForm.vue - добавлена условная логика @submit для активных объявлений\n";
    echo "3. ✅ adFormModel.ts - обновлен handleSubmit для перенаправления\n";
    echo "4. ✅ AdForm.vue - убрана валидация canSubmit для активных объявлений\n";
    
    echo "\n🎯 ОЖИДАЕМЫЙ РЕЗУЛЬТАТ:\n";
    echo "======================\n";
    echo "- Кнопка 'Сохранить изменения' должна быть активна\n";
    echo "- При клике должно сохраняться объявление\n";
    echo "- После сохранения - переход к списку активных объявлений\n";
    echo "- Кнопка 'Отменить и выйти' должна работать\n";
    
    echo "\n🌐 ССЫЛКА ДЛЯ ТЕСТИРОВАНИЯ:\n";
    echo "==========================\n";
    echo "http://localhost:8000/ads/128/edit\n";
    
} else {
    echo "❌ Объявление 128 не найдено\n";
}

echo "\n🎉 Готово к тестированию!\n";