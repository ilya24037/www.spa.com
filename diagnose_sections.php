<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 ДИАГНОСТИКА ЧАСТИЧНОГО СОХРАНЕНИЯ СЕКЦИЙ\n";
echo "==========================================\n\n";

// Находим последний черновик
$draft = App\Domain\Ad\Models\Ad::where('status', 'draft')->latest()->first();

if (!$draft) {
    echo "❌ Черновики не найдены в БД\n";
    exit;
}

echo "📋 Проверяем черновик ID: {$draft->id}\n";
echo "📅 Последнее обновление: {$draft->updated_at}\n\n";

// Проверяем основную информацию
echo "🔍 ОСНОВНАЯ ИНФОРМАЦИЯ:\n";
echo "  title: \"" . ($draft->title ?? 'NULL') . "\"\n";
echo "  specialty: \"" . ($draft->specialty ?? 'NULL') . "\"\n";
echo "  work_format: \"" . ($draft->work_format?->value ?? 'NULL') . "\"\n";
echo "  experience: \"" . ($draft->experience ?? 'NULL') . "\"\n";
echo "  description: \"" . substr($draft->description ?? 'NULL', 0, 50) . "...\"\n";
echo "  category: \"" . ($draft->category ?? 'NULL') . "\"\n\n";

// Проверяем цены и услуги (JSON поля)
echo "🔍 ЦЕНЫ И УСЛУГИ (JSON):\n";
$prices = $draft->prices;
$services = $draft->services;
$clients = $draft->clients;

echo "  prices: " . (empty($prices) ? '❌ ПУСТО' : '✅ ЗАПОЛНЕНО (' . strlen($prices) . ' символов)') . "\n";
if (!empty($prices)) {
    $pricesData = json_decode($prices, true);
    echo "    decoded: " . (is_array($pricesData) ? count($pricesData) . " элементов" : "ошибка декодирования") . "\n";
}

echo "  services: " . (empty($services) ? '❌ ПУСТО' : '✅ ЗАПОЛНЕНО (' . strlen($services) . ' символов)') . "\n";
if (!empty($services)) {
    $servicesData = json_decode($services, true);
    echo "    decoded: " . (is_array($servicesData) ? count($servicesData) . " элементов" : "ошибка декодирования") . "\n";
}

echo "  clients: " . (empty($clients) ? '❌ ПУСТО' : '✅ ЗАПОЛНЕНО (' . strlen($clients) . ' символов)') . "\n";
if (!empty($clients)) {
    $clientsData = json_decode($clients, true);
    echo "    decoded: " . (is_array($clientsData) ? count($clientsData) . " элементов" : "ошибка декодирования") . "\n";
}
echo "\n";

// Проверяем параметры
echo "🔍 ПАРАМЕТРЫ:\n";
echo "  age: \"" . ($draft->age ?? 'NULL') . "\"\n";
echo "  height: \"" . ($draft->height ?? 'NULL') . "\"\n";
echo "  weight: \"" . ($draft->weight ?? 'NULL') . "\"\n";
echo "  breast_size: \"" . ($draft->breast_size ?? 'NULL') . "\"\n";
echo "  hair_color: \"" . ($draft->hair_color ?? 'NULL') . "\"\n";
echo "  eye_color: \"" . ($draft->eye_color ?? 'NULL') . "\"\n";
echo "  nationality: \"" . ($draft->nationality ?? 'NULL') . "\"\n";
echo "  appearance: \"" . ($draft->appearance ?? 'NULL') . "\"\n\n";

// Проверяем контакты
echo "🔍 КОНТАКТЫ:\n";
echo "  phone: \"" . ($draft->phone ?? 'NULL') . "\"\n";
echo "  whatsapp: \"" . ($draft->whatsapp ?? 'NULL') . "\"\n";
echo "  telegram: \"" . ($draft->telegram ?? 'NULL') . "\"\n";
echo "  vk: \"" . ($draft->vk ?? 'NULL') . "\"\n";
echo "  instagram: \"" . ($draft->instagram ?? 'NULL') . "\"\n\n";

// Проверяем локацию
echo "🔍 ЛОКАЦИЯ:\n";
echo "  address: \"" . ($draft->address ?? 'NULL') . "\"\n";
echo "  geo: " . (empty($draft->geo) ? '❌ ПУСТО' : '✅ ЗАПОЛНЕНО') . "\n";
echo "  radius: \"" . ($draft->radius ?? 'NULL') . "\"\n";
echo "  is_remote: \"" . ($draft->is_remote ? 'true' : 'false') . "\"\n\n";

// Проверяем расписание
echo "🔍 РАСПИСАНИЕ:\n";
echo "  schedule: " . (empty($draft->schedule) ? '❌ ПУСТО' : '✅ ЗАПОЛНЕНО') . "\n";
echo "  schedule_notes: \"" . ($draft->schedule_notes ?? 'NULL') . "\"\n\n";

// Проверяем дополнительные поля
echo "🔍 ДОПОЛНИТЕЛЬНЫЕ ПОЛЯ:\n";
echo "  features: " . (empty($draft->features) ? '❌ ПУСТО' : '✅ ЗАПОЛНЕНО') . "\n";
echo "  additional_features: " . (empty($draft->additional_features) ? '❌ ПУСТО' : '✅ ЗАПОЛНЕНО') . "\n";
echo "  discount: \"" . ($draft->discount ?? 'NULL') . "\"\n";
echo "  gift: \"" . ($draft->gift ?? 'NULL') . "\"\n";
echo "  has_girlfriend: \"" . ($draft->has_girlfriend ? 'true' : 'false') . "\"\n\n";

echo "🎯 ЗАКЛЮЧЕНИЕ:\n";
echo "Сравните с тем, что вы видите в форме, чтобы определить\n";
echo "какие именно секции не сохраняются правильно.\n";