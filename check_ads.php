<?php
// Проверка объявлений в БД

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$output = "===== ПРОВЕРКА ОБЪЯВЛЕНИЙ В БД =====\n";
$output .= "Время: " . date('Y-m-d H:i:s') . "\n\n";

// Последние 5 объявлений
$ads = DB::table('ads')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

$output .= "📋 ПОСЛЕДНИЕ 5 ОБЪЯВЛЕНИЙ:\n";
$output .= "=====================================\n";

foreach ($ads as $ad) {
    $output .= "ID: {$ad->id}\n";
    $output .= "Title: {$ad->title}\n";
    $output .= "Status: {$ad->status}\n";
    $output .= "Is Published: {$ad->is_published}\n";
    $output .= "Specialty: " . ($ad->specialty ?: 'NULL') . "\n";
    $output .= "Work Format: " . ($ad->work_format ?: 'NULL') . "\n";
    $output .= "Service Provider: " . ($ad->service_provider ?: 'NULL') . "\n";
    $output .= "Created: {$ad->created_at}\n";
    $output .= "-------------------------------------\n";
}

// Статистика
$activeCount = DB::table('ads')->where('status', 'active')->count();
$draftCount = DB::table('ads')->where('status', 'draft')->count();
$publishedCount = DB::table('ads')->where('is_published', 1)->count();
$moderationCount = DB::table('ads')->where('status', 'active')->where('is_published', 0)->count();

$output .= "\n📊 СТАТИСТИКА:\n";
$output .= "=====================================\n";
$output .= "Всего активных: {$activeCount}\n";
$output .= "Всего черновиков: {$draftCount}\n";
$output .= "Опубликованных: {$publishedCount}\n";
$output .= "На модерации: {$moderationCount}\n";

// Проверка полей
$withSpecialty = DB::table('ads')->whereNotNull('specialty')->count();
$withWorkFormat = DB::table('ads')->whereNotNull('work_format')->count();
$withServiceProvider = DB::table('ads')->whereNotNull('service_provider')->count();

$output .= "\n✅ ЗАПОЛНЕННОСТЬ ПОЛЕЙ:\n";
$output .= "=====================================\n";
$output .= "С specialty: {$withSpecialty}\n";
$output .= "С work_format: {$withWorkFormat}\n";
$output .= "С service_provider: {$withServiceProvider}\n";

// Сохраняем в файл
file_put_contents(__DIR__ . '/CHECK_ADS_RESULT.txt', $output);
echo $output;
echo "\nРезультат сохранен в CHECK_ADS_RESULT.txt\n";