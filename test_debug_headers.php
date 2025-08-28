<?php

echo "🔍 ТЕСТ DEBUG ЛОГИРОВАНИЯ ЗАГОЛОВКОВ\n";
echo "====================================\n\n";

echo "✅ ДОБАВЛЕНО В DraftController::update:\n";
echo "1. Логирование всех заголовков запроса\n";
echo "2. Проверка X-Inertia vs X-Requested-With\n";
echo "3. Логирование перед возвратом JSON ответа\n\n";

echo "📋 ЧТО ДЕЛАТЬ:\n";
echo "1. Откройте http://spa.test/ads/85/edit\n";
echo "2. Нажмите 'Сохранить черновик'\n";
echo "3. Проверьте логи Laravel\n\n";

echo "🔍 ЧТО ДОЛЖНО ПОЯВИТЬСЯ В ЛОГАХ:\n";
echo "- 🔍 DraftController: ПРОВЕРКА ЗАГОЛОВКОВ ДЛЯ ОТВЕТА\n";
echo "- X-Inertia: null или empty\n";
echo "- X-Requested-With: XMLHttpRequest\n";
echo "- is_ajax: true\n";
echo "- 🔍 DraftController: Возвращаем JSON ответ\n\n";

echo "❗ ЕСЛИ X-Inertia НЕ null:\n";
echo "Значит проблема в том что запрос определяется как Inertia\n";
echo "И возвращается редирект вместо JSON\n\n";

echo "❗ ЕСЛИ JSON ответ НЕ возвращается:\n";
echo "Значит есть исключение в коде до return\n\n";

echo "🚀 КОМАНДА ДЛЯ ПРОСМОТРА ЛОГОВ:\n";
echo 'tail -20 "C:\\www.spa.com\\storage\\logs\\laravel.log"' . "\n\n";

echo "✅ ОЖИДАЕМЫЙ РЕЗУЛЬТАТ:\n";
echo "После исправления черновик должен сохраняться без редиректа\n";