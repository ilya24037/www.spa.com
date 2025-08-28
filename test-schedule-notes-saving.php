<?php

require_once 'vendor/autoload.php';

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;

// Загружаем Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 ТЕСТ СОХРАНЕНИЯ SCHEDULE_NOTES\n";
echo "=================================\n\n";

try {
    // Находим тестового пользователя
    $user = User::where('email', 'anna@spa.test')->first();
    if (!$user) {
        echo "❌ Пользователь anna@spa.test не найден!\n";
        exit;
    }
    
    echo "✅ Пользователь найден: {$user->name} (ID: {$user->id})\n\n";
    
    // Создаем новый черновик с schedule_notes
    $testScheduleNotes = "Тестовые заметки к расписанию - " . date('Y-m-d H:i:s');
    
    $ad = new Ad();
    $ad->user_id = $user->id;
    $ad->status = 'draft';
    $ad->title = 'Тест schedule_notes';
    $ad->category = 'relax';
    $ad->description = 'Тестовое описание';
    $ad->schedule_notes = $testScheduleNotes;
    $ad->schedule = [
        'monday' => ['enabled' => true, 'from' => '09:00', 'to' => '18:00']
    ];
    
    $saved = $ad->save();
    
    if ($saved) {
        echo "✅ Черновик создан успешно (ID: {$ad->id})\n";
        echo "📝 Тестовое значение: {$testScheduleNotes}\n\n";
        
        // Проверяем сохранение
        $ad->refresh();
        echo "🔍 ПРОВЕРКА СОХРАНЕНИЯ:\n";
        echo "   schedule_notes из БД: '{$ad->schedule_notes}'\n";
        echo "   Длина: " . strlen($ad->schedule_notes ?? '') . " символов\n";
        echo "   Тип: " . gettype($ad->schedule_notes) . "\n";
        
        if ($ad->schedule_notes === $testScheduleNotes) {
            echo "✅ SCHEDULE_NOTES СОХРАНЕН ПРАВИЛЬНО!\n\n";
            
            // Теперь тестируем обновление
            echo "🔄 ТЕСТ ОБНОВЛЕНИЯ:\n";
            $updatedNotes = "Обновленные заметки - " . date('Y-m-d H:i:s');
            $ad->schedule_notes = $updatedNotes;
            $updateSaved = $ad->save();
            
            if ($updateSaved) {
                $ad->refresh();
                echo "   Новое значение: '{$ad->schedule_notes}'\n";
                
                if ($ad->schedule_notes === $updatedNotes) {
                    echo "✅ ОБНОВЛЕНИЕ ТОЖЕ РАБОТАЕТ!\n\n";
                } else {
                    echo "❌ Обновление не сработало!\n";
                }
            } else {
                echo "❌ Ошибка при обновлении\n";
            }
            
        } else {
            echo "❌ SCHEDULE_NOTES НЕ СОХРАНЕН!\n";
            echo "   Ожидали: '{$testScheduleNotes}'\n";
            echo "   Получили: '{$ad->schedule_notes}'\n";
        }
        
        // Очистка тестовых данных
        echo "🧹 Удаляем тестовый черновик...\n";
        $ad->delete();
        echo "✅ Тестовый черновик удален\n";
        
    } else {
        echo "❌ Ошибка при создании черновика\n";
    }

} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . "\n";
    echo "Строка: " . $e->getLine() . "\n";
}