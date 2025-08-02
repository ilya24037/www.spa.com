<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

// Проверяем наличие поля photos
$hasPhotosColumn = Schema::hasColumn('ads', 'photos');

if ($hasPhotosColumn) {
    echo "Поле 'photos' уже существует в таблице ads\n";
} else {
    echo "Добавляем поле 'photos' в таблицу ads...\n";
    
    try {
        Schema::table('ads', function (Blueprint $table) {
            $table->json('photos')->nullable()->comment('Фотографии объявления в JSON формате');
        });
        
        // Добавляем запись в таблицу миграций
        DB::table('migrations')->insert([
            'migration' => '2025_08_01_170000_add_photos_field_to_ads_table',
            'batch' => DB::table('migrations')->max('batch') + 1
        ]);
        
        echo "Поле 'photos' успешно добавлено!\n";
    } catch (\Exception $e) {
        echo "Ошибка: " . $e->getMessage() . "\n";
    }
}

// Проверяем объявление 166
$ad = \App\Domain\Ad\Models\Ad::find(166);
if ($ad) {
    echo "\nОбъявление 166 найдено:\n";
    echo "Заголовок: " . $ad->title . "\n";
    echo "Фото: " . json_encode($ad->photos) . "\n";
}