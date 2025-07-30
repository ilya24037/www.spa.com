@echo off
chcp 65001 >nul
echo 🧹 ОЧИСТКА КОРНЯ ПРОЕКТА SPA PLATFORM
echo.

echo 📁 Создаю папку temp для временных файлов...
if not exist "temp" mkdir temp
if not exist "temp\backup" mkdir temp\backup

echo.
echo 📋 Перемещаю временные файлы в temp\backup...

:: Перемещаем временные PHP файлы
if exist "create_test_ad.php" (
    move "create_test_ad.php" "temp\backup\"
    echo ✅ create_test_ad.php
)
if exist "create_test_images.php" (
    move "create_test_images.php" "temp\backup\"
    echo ✅ create_test_images.php
)
if exist "add_photos.php" (
    move "add_photos.php" "temp\backup\"
    echo ✅ add_photos.php
)
if exist "skip_migration.php" (
    move "skip_migration.php" "temp\backup\"
    echo ✅ skip_migration.php
)
if exist "fix_migrations.sql" (
    move "fix_migrations.sql" "temp\backup\"
    echo ✅ fix_migrations.sql
)

:: Перемещаем временные HTML файлы
if exist "test_upload.html" (
    move "test_upload.html" "temp\backup\"
    echo ✅ test_upload.html
)
if exist "test_media_upload.html" (
    move "test_media_upload.html" "temp\backup\"
    echo ✅ test_media_upload.html
)
if exist "upload_photos.html" (
    move "upload_photos.html" "temp\backup\"
    echo ✅ upload_photos.html
)
if exist "test_add_photos.html" (
    move "test_add_photos.html" "temp\backup\"
    echo ✅ test_add_photos.html
)
if exist "create-placeholder.html" (
    move "create-placeholder.html" "temp\backup\"
    echo ✅ create-placeholder.html
)

:: Перемещаем временные .txt файлы
if exist "js_structure.txt" (
    move "js_structure.txt" "temp\backup\"
    echo ✅ js_structure.txt
)
if exist "full_js_structure.txt" (
    move "full_js_structure.txt" "temp\backup\"
    echo ✅ full_js_structure.txt
)
if exist "masters_structure.txt" (
    move "masters_structure.txt" "temp\backup\"
    echo ✅ masters_structure.txt
)
if exist "project_structure.txt" (
    move "project_structure.txt" "temp\backup\"
    echo ✅ project_structure.txt
)
if exist "components-tree.txt" (
    move "components-tree.txt" "temp\backup\"
    echo ✅ components-tree.txt
)
if exist "tailwind.config.js.txt" (
    move "tailwind.config.js.txt" "temp\backup\"
    echo ✅ tailwind.config.js.txt
)

:: Перемещаем временные .md файлы
if exist "UNUSED_FILES_REPORT.md" (
    move "UNUSED_FILES_REPORT.md" "temp\backup\"
    echo ✅ UNUSED_FILES_REPORT.md
)
if exist "PROJECT_ANALYSIS_REPORT.md" (
    move "PROJECT_ANALYSIS_REPORT.md" "temp\backup\"
    echo ✅ PROJECT_ANALYSIS_REPORT.md
)
if exist "АНАЛИЗ_ФОРМЫ_АВИТО.md" (
    move "АНАЛИЗ_ФОРМЫ_АВИТО.md" "temp\backup\"
    echo ✅ АНАЛИЗ_ФОРМЫ_АВИТО.md
)
if exist "ИСПРАВЛЕНИЕ_СОХРАНЕНИЯ_ЧЕРНОВИКА.md" (
    move "ИСПРАВЛЕНИЕ_СОХРАНЕНИЯ_ЧЕРНОВИКА.md" "temp\backup\"
    echo ✅ ИСПРАВЛЕНИЕ_СОХРАНЕНИЯ_ЧЕРНОВИКА.md
)
if exist "УПРОЩЕНИЕ_КНОПОК.md" (
    move "УПРОЩЕНИЕ_КНОПОК.md" "temp\backup\"
    echo ✅ УПРОЩЕНИЕ_КНОПОК.md
)
if exist "ТЕСТИРОВАНИЕ_НОВОЙ_АРХИТЕКТУРЫ.md" (
    move "ТЕСТИРОВАНИЕ_НОВОЙ_АРХИТЕКТУРЫ.md" "temp\backup\"
    echo ✅ ТЕСТИРОВАНИЕ_НОВОЙ_АРХИТЕКТУРЫ.md
)
if exist "НОВАЯ_АРХИТЕКТУРА.md" (
    move "НОВАЯ_АРХИТЕКТУРА.md" "temp\backup\"
    echo ✅ НОВАЯ_АРХИТЕКТУРА.md
)
if exist "AVITO_COMPONENT_GUIDE.md" (
    move "AVITO_COMPONENT_GUIDE.md" "temp\backup\"
    echo ✅ AVITO_COMPONENT_GUIDE.md
)
if exist "FORM_ARCHITECTURE.md" (
    move "FORM_ARCHITECTURE.md" "temp\backup\"
    echo ✅ FORM_ARCHITECTURE.md
)
if exist "ИТОГОВОЕ_ИСПРАВЛЕНИЕ.md" (
    move "ИТОГОВОЕ_ИСПРАВЛЕНИЕ.md" "temp\backup\"
    echo ✅ ИТОГОВОЕ_ИСПРАВЛЕНИЕ.md
)
if exist "ИСПРАВЛЕНИЕ_ОШИБОК.md" (
    move "ИСПРАВЛЕНИЕ_ОШИБОК.md" "temp\backup\"
    echo ✅ ИСПРАВЛЕНИЕ_ОШИБОК.md
)
if exist "PROJECT_ANALYSIS.md" (
    move "PROJECT_ANALYSIS.md" "temp\backup\"
    echo ✅ PROJECT_ANALYSIS.md
)
if exist "MASTER_PAGE_ANALYSIS.md" (
    move "MASTER_PAGE_ANALYSIS.md" "temp\backup\"
    echo ✅ MASTER_PAGE_ANALYSIS.md
)
if exist "ФИНАЛЬНАЯ_ИНСТРУКЦИЯ.md" (
    move "ФИНАЛЬНАЯ_ИНСТРУКЦИЯ.md" "temp\backup\"
    echo ✅ ФИНАЛЬНАЯ_ИНСТРУКЦИЯ.md
)
if exist "ИТОГОВАЯ_ИНСТРУКЦИЯ.md" (
    move "ИТОГОВАЯ_ИНСТРУКЦИЯ.md" "temp\backup\"
    echo ✅ ИТОГОВАЯ_ИНСТРУКЦИЯ.md
)
if exist "КРАТКАЯ_ИНСТРУКЦИЯ_ДОБАВЛЕНИЯ_ФОТО.md" (
    move "КРАТКАЯ_ИНСТРУКЦИЯ_ДОБАВЛЕНИЯ_ФОТО.md" "temp\backup\"
    echo ✅ КРАТКАЯ_ИНСТРУКЦИЯ_ДОБАВЛЕНИЯ_ФОТО.md
)
if exist "ДОБАВЛЕНИЕ_ФОТО_МАСТЕРА.md" (
    move "ДОБАВЛЕНИЕ_ФОТО_МАСТЕРА.md" "temp\backup\"
    echo ✅ ДОБАВЛЕНИЕ_ФОТО_МАСТЕРА.md
)
if exist "README_PHOTOS.md" (
    move "README_PHOTOS.md" "temp\backup\"
    echo ✅ README_PHOTOS.md
)

echo.
echo 🗑️ Удаляю временные тестовые .bat файлы...

:: Удаляем временные test-*.bat файлы
for %%f in (test-*.bat) do (
    del "%%f"
    echo ✅ Удален: %%f
)

:: Удаляем временные check_*.bat файлы
for %%f in (check_*.bat) do (
    del "%%f"
    echo ✅ Удален: %%f
)

:: Удаляем временные fix_*.bat файлы
for %%f in (fix_*.bat) do (
    del "%%f"
    echo ✅ Удален: %%f
)

:: Удаляем test_artisan.bat
if exist "test_artisan.bat" (
    del "test_artisan.bat"
    echo ✅ Удален: test_artisan.bat
)

echo.
echo 📊 РЕЗУЛЬТАТ ОЧИСТКИ:
echo.
echo ✅ Создана папка: temp\backup\
echo ✅ Перемещены временные файлы в backup
echo ✅ Удалены тестовые .bat файлы
echo.
echo 📁 Оставлены важные файлы:
echo    - composer.json, composer.lock
echo    - package.json, package-lock.json
echo    - vite.config.js, tailwind.config.js
echo    - jsconfig.json, .gitignore
echo    - .cursorrules.txt, AI_CONTEXT.md
echo    - Полезные .bat файлы (dev.bat, build.bat, etc.)
echo.
echo 🎯 Корень проекта очищен!
echo 📂 Временные файлы сохранены в: temp\backup\
echo.
pause 