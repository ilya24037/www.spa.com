@echo off
chcp 65001 >nul

echo.
echo 🧹 МАССОВАЯ ОЧИСТКА DEBUG КОДА
echo ================================

echo.
echo 📋 Удаляем console.log из JS/Vue файлов...

REM Удаляем строки с console.log из всех .vue и .js файлов
powershell -Command "(Get-Content 'Backap/js/Components/Features/PhotoUploader/архив index.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'Backap/js/Components/Features/PhotoUploader/архив index.vue'"

powershell -Command "(Get-Content 'Backap/js/Components/Footer/Footer.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'Backap/js/Components/Footer/Footer.vue'"

powershell -Command "(Get-Content 'Backap/js/Components/Form/AdForm.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'Backap/js/Components/Form/AdForm.vue'"

powershell -Command "(Get-Content 'Backap/js/stores/bookingStore.js') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'Backap/js/stores/bookingStore.js'"

powershell -Command "(Get-Content 'resources/js/src/entities/ad/ui/AdForm/AdForm.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'resources/js/src/entities/ad/ui/AdForm/AdForm.vue'"

powershell -Command "(Get-Content 'resources/js/src/shared/utils/logger.ts') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'resources/js/src/shared/utils/logger.ts'"

echo ✅ Базовые файлы очищены
echo.
echo 🔍 Проверяем результат...
.\ai-context-debug.bat

echo.
echo ✅ ОЧИСТКА ЗАВЕРШЕНА!
