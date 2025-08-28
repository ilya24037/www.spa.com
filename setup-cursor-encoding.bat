@echo off
chcp 65001 >nul
echo ===================================
echo 🚀 НАСТРОЙКА CURSOR - ИСПРАВЛЕНИЕ КОДИРОВКИ
echo ===================================
echo.

echo 📋 ШАГ 1: Проверяем PowerShell профиль...
if exist "C:\Users\user1\Documents\WindowsPowerShell\Microsoft.PowerShell_profile.ps1" (
    echo ✅ PowerShell профиль создан успешно
) else (
    echo ❌ Ошибка: профиль не найден
    pause
    exit /b 1
)

echo.
echo 📋 ШАГ 2: Инструкция по настройке Cursor...
echo.
echo 🔧 НАСТРОЙКА CURSOR:
echo 1. Откройте Cursor
echo 2. Нажмите Ctrl+Shift+P
echo 3. Введите "Preferences: Open Settings (JSON)"
echo 4. Добавьте настройки из файла cursor-terminal-settings.json
echo.
echo 📂 Файл с настройками: cursor-terminal-settings.json
echo 📁 Расположение: %cd%\cursor-terminal-settings.json
echo.

echo 📋 ШАГ 3: Перезапуск...
echo После добавления настроек:
echo 1. Закройте Cursor полностью
echo 2. Запустите Cursor заново  
echo 3. Откройте новый терминал
echo.

echo 🎯 РЕЗУЛЬТАТ:
echo • npm - будет работать корректно
echo • composer - будет работать корректно  
echo • chcp - будет работать корректно
echo • Кириллица в выводе - корректно
echo.

echo 💡 ДОПОЛНИТЕЛЬНЫЕ АЛИАСЫ:
echo • art или artisan - php artisan
echo • tinker - php artisan tinker
echo • serve - php artisan serve
echo • migrate - php artisan migrate
echo.

echo ✅ Настройка завершена!
echo.
pause