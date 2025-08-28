<?php

echo "🎯 ТЕСТ СОХРАНЕНИЯ ЧЕРНОВИКА ПОСЛЕ ИСПРАВЛЕНИЯ FILLABLE\n";
echo "=====================================================\n\n";

echo "✅ ИСПРАВЛЕНИЯ ВНЕСЕНЫ:\n";
echo "1. Добавлены поля в \$fillable модели Ad:\n";
echo "   - vk (контакты)\n";
echo "   - instagram (контакты)\n";
echo "   - radius (локация)\n";
echo "   - is_remote (локация)\n\n";

echo "🔍 МЕТОД ТЕСТИРОВАНИЯ:\n";
echo "1. Откройте http://spa.test/ads/85/edit\n";
echo "2. Заполните ЛЮБЫЕ поля в секциях:\n";
echo "   - Параметры: возраст, рост, вес, размер груди, цвет волос\n";
echo "   - Контакты: телефон, WhatsApp, Telegram, ВК, Instagram\n";
echo "   - Локация: адрес, радиус\n";
echo "3. Нажмите 'Сохранить черновик'\n";
echo "4. Проверьте логи Laravel\n\n";

echo "📋 ЧТО ДОЛЖНО ПОЯВИТЬСЯ В ЛОГАХ:\n";
echo "🔍 DraftController: ВСЕ ВХОДЯЩИЕ ДАННЫЕ\n";
echo "   - request_all: массив с 40+ полей\n";
echo "   - должны быть поля: age, phone, whatsapp, vk, instagram, address, radius\n\n";

echo "❗ ЕСЛИ В ЛОГАХ:\n";
echo "- request_all ПУСТОЙ = проблема на фронтенде\n";
echo "- request_all ПОЛНЫЙ = проблема в обработке backend\n\n";

echo "🚀 КОМАНДА ДЛЯ ПРОСМОТРА ЛОГОВ:\n";
echo 'Get-Content "C:\www.spa.com\storage\logs\laravel.log" -Tail 50' . "\n\n";

echo "✅ ПОСЛЕ ТЕСТИРОВАНИЯ:\n";
echo "Запустите еще раз: php diagnose_sections.php\n";
echo "И сравните результаты!\n";