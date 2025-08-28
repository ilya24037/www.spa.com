<?php

require_once 'vendor/autoload.php';

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;

// Загружаем Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 ТЕСТ SCHEDULE_NOTES ЧЕРЕЗ ВЕБЕРФЕЙС\n";
echo "======================================\n\n";

try {
    // Находим существующий черновик пользователя
    $user = User::where('email', 'anna@spa.test')->first();
    if (!$user) {
        echo "❌ Пользователь anna@spa.test не найден!\n";
        exit;
    }
    
    $draft = Ad::where('user_id', $user->id)
               ->where('status', 'draft')
               ->first();
    
    if (!$draft) {
        echo "❌ Черновик не найден! Сначала создайте черновик в браузере.\n";
        exit;
    }
    
    echo "✅ Найден черновик ID: {$draft->id}\n";
    echo "📝 Текущий schedule_notes: '{$draft->schedule_notes}'\n\n";
    
    // Имитируем данные, которые приходят из формы через DraftController
    echo "🔧 ИМИТАЦИЯ ЗАПРОСА ИЗ ФОРМЫ:\n";
    
    $testScheduleNotes = "Заметки из веб-формы: " . date('H:i:s') . " - возможны изменения графика по договоренности";
    
    // Данные как они приходят из formDataBuilder
    $formData = [
        'id' => $draft->id,
        'schedule_notes' => $testScheduleNotes,
        'schedule' => [
            'monday' => ['enabled' => true, 'from' => '09:00', 'to' => '18:00'],
            'tuesday' => ['enabled' => true, 'from' => '10:00', 'to' => '19:00']
        ],
        'description' => $draft->description,
        'title' => $draft->title
    ];
    
    echo "   Отправляем schedule_notes: '{$testScheduleNotes}'\n";
    echo "   Длина: " . strlen($testScheduleNotes) . " символов\n\n";
    
    // Обновляем черновик (имитируем DraftController::update)
    echo "💾 СОХРАНЕНИЕ ЧЕРЕЗ МОДЕЛЬ:\n";
    
    // Проверяем что поле schedule_notes есть в $fillable
    $fillableFields = $draft->getFillable();
    $hasScheduleNotesInFillable = in_array('schedule_notes', $fillableFields);
    echo "   schedule_notes в \$fillable: " . ($hasScheduleNotesInFillable ? "✅ ДА" : "❌ НЕТ") . "\n";
    
    // Обновляем поля
    $draft->fill($formData);
    
    echo "   После fill() - schedule_notes: '{$draft->schedule_notes}'\n";
    echo "   Поле изменено: " . ($draft->isDirty('schedule_notes') ? "✅ ДА" : "❌ НЕТ") . "\n";
    
    // Сохраняем
    $saved = $draft->save();
    echo "   Сохранение: " . ($saved ? "✅ УСПЕШНО" : "❌ ОШИБКА") . "\n\n";
    
    if ($saved) {
        // Перезагружаем из БД
        $draft->refresh();
        
        echo "🔍 ПРОВЕРКА РЕЗУЛЬТАТА:\n";
        echo "   schedule_notes из БД: '{$draft->schedule_notes}'\n";
        echo "   Длина: " . strlen($draft->schedule_notes ?? '') . " символов\n";
        
        if ($draft->schedule_notes === $testScheduleNotes) {
            echo "✅ SUCCESS! SCHEDULE_NOTES СОХРАНЕН ПРАВИЛЬНО!\n\n";
            
            echo "🌐 URL для проверки в браузере:\n";
            echo "   http://spa.test/ads/{$draft->id}/edit\n\n";
            
            echo "🔍 ИНСТРУКЦИИ ДЛЯ ТЕСТИРОВАНИЯ:\n";
            echo "1. Откройте URL в браузере\n";
            echo "2. Найдите секцию 'График работы'\n";  
            echo "3. Проверьте поле 'Дополнительная информация о графике работы'\n";
            echo "4. Должно содержать: '{$testScheduleNotes}'\n";
            echo "5. Измените текст и сохраните черновик\n";
            echo "6. Обновите страницу и проверьте, что изменения сохранились\n\n";
            
        } else {
            echo "❌ ОШИБКА! Schedule_notes не сохранен!\n";
            echo "   Ожидали: '{$testScheduleNotes}'\n";
            echo "   Получили: '{$draft->schedule_notes}'\n";
        }
    }

} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . "\n";
    echo "Строка: " . $e->getLine() . "\n";
}