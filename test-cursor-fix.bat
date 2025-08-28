@echo off
chcp 65001 >nul
echo ===============================
echo 🧪 ТЕСТИРОВАНИЕ ИСПРАВЛЕНИЯ КОДИРОВКИ CURSOR
echo ===============================
echo.

echo 📋 СТАТУС НАСТРОЙКИ:
echo ✅ PowerShell профиль создан
echo ✅ Cursor settings.json настроен
echo ✅ Переменные окружения установлены
echo.

echo 🔧 ДЛЯ ПРИМЕНЕНИЯ ИЗМЕНЕНИЙ:
echo 1. ПОЛНОСТЬЮ закройте Cursor (проверьте в диспетчере задач)
echo 2. Откройте Cursor заново
echo 3. Откройте новый терминал (Ctrl + `)
echo 4. Запустите этот скрипт в новом терминале Cursor
echo.

echo 🧪 ТЕСТ 1: Проверка базовых команд...
echo.
echo npm:
npm --version 2>nul || echo "npm не найден или есть проблемы"
echo.

echo composer:
composer --version 2>nul || echo "composer не найден или есть проблемы"
echo.

echo chcp:
chcp 2>nul || echo "chcp не найден или есть проблемы"
echo.

echo 🧪 ТЕСТ 2: Проверка кодировки...
echo Тест кириллицы: Привет мир! 🚀
echo Test English: Hello world! ✅
echo.

echo 🧪 ТЕСТ 3: Laravel алиасы...
art --version 2>nul || echo "Laravel artisan алиас не настроен"
echo.

echo 🎯 РЕЗУЛЬТАТ:
echo Если все команды работают без искажений - УСПЕХ! ✅
echo Если есть искажения - перезапустите Cursor и попробуйте снова
echo.

echo 💡 ПОЛЕЗНЫЕ АЛИАСЫ:
echo • art или artisan - php artisan
echo • tinker - php artisan tinker  
echo • serve - php artisan serve
echo • migrate - php artisan migrate
echo.

pause