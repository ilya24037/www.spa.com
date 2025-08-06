@echo off
echo ======================================
echo   LEGACY FILES CLEANUP SCRIPT
echo   Удаление старых файлов после FSD миграции
echo ======================================
echo.

REM Переход в директорию проекта
cd /d C:\www.spa.com

echo [1/5] Проверка текущей директории...
echo Текущая папка: %CD%
echo.

REM Проверка существования папки Components
if not exist "resources\js\Components\" (
    echo [!] Папка Components не найдена! Возможно, уже удалена.
    goto :end
)

echo [2/5] Найдены следующие legacy файлы для удаления:
echo.

REM Список файлов для удаления
echo Заменённые файлы (импорты обновлены):
echo - Components\Form\Sections\EducationSection.vue
echo - Components\Form\Sections\MediaSection.vue
echo - Components\Booking\Calendar.vue
echo - Components\Header\Navbar.vue
echo - Components\Footer\Footer.vue
echo - Components\UI\ConfirmModal.vue
echo.

echo Неиспользуемые файлы:
echo - Components\Features\MasterShow\components\ReviewsList.vue
echo - Components\Features\MasterShow\components\ServicesList.vue
echo - Components\Features\PhotoUploader\VideoUploader.vue
echo - Components\Features\PhotoUploader\архив index.vue
echo - Components\Header\UserMenu.vue
echo.

REM Запрос подтверждения
set /p confirm="Удалить ВСЕ эти файлы? (Y/N): "
if /i not "%confirm%"=="Y" (
    echo.
    echo [x] Отменено пользователем.
    goto :end
)

echo.
echo [3/5] Удаление неиспользуемых файлов...

REM Удаление неиспользуемых файлов
if exist "resources\js\Components\Features\MasterShow\" (
    rmdir /s /q "resources\js\Components\Features\MasterShow"
    echo - Удалено: Features\MasterShow\
)

if exist "resources\js\Components\Features\PhotoUploader\" (
    rmdir /s /q "resources\js\Components\Features\PhotoUploader"
    echo - Удалено: Features\PhotoUploader\
)

if exist "resources\js\Components\Header\UserMenu.vue" (
    del /q "resources\js\Components\Header\UserMenu.vue"
    echo - Удалено: Header\UserMenu.vue
)

echo.
echo [4/5] Удаление заменённых файлов...

REM Удаление заменённых файлов
if exist "resources\js\Components\Form\Sections\" (
    rmdir /s /q "resources\js\Components\Form\Sections"
    echo - Удалено: Form\Sections\
)

if exist "resources\js\Components\Booking\Calendar.vue" (
    del /q "resources\js\Components\Booking\Calendar.vue"
    echo - Удалено: Booking\Calendar.vue
)

if exist "resources\js\Components\Header\Navbar.vue" (
    del /q "resources\js\Components\Header\Navbar.vue"
    echo - Удалено: Header\Navbar.vue
)

if exist "resources\js\Components\Footer\Footer.vue" (
    del /q "resources\js\Components\Footer\Footer.vue"
    echo - Удалено: Footer\Footer.vue
)

if exist "resources\js\Components\UI\ConfirmModal.vue" (
    del /q "resources\js\Components\UI\ConfirmModal.vue"
    echo - Удалено: UI\ConfirmModal.vue
)

echo.
echo [5/5] Очистка пустых директорий...

REM Удаление пустых директорий
if exist "resources\js\Components\Booking\" (
    rmdir /q "resources\js\Components\Booking" 2>nul
)

if exist "resources\js\Components\Header\" (
    rmdir /q "resources\js\Components\Header" 2>nul
)

if exist "resources\js\Components\Footer\" (
    rmdir /q "resources\js\Components\Footer" 2>nul
)

if exist "resources\js\Components\Form\" (
    rmdir /q "resources\js\Components\Form" 2>nul
)

if exist "resources\js\Components\UI\" (
    rmdir /q "resources\js\Components\UI" 2>nul
)

if exist "resources\js\Components\Features\" (
    rmdir /q "resources\js\Components\Features" 2>nul
)

REM Финальная проверка и удаление главной папки Components
if exist "resources\js\Components\" (
    dir /b "resources\js\Components\" | findstr "^" >nul
    if errorlevel 1 (
        rmdir /q "resources\js\Components"
        echo - Удалена пустая директория Components\
    ) else (
        echo [!] В папке Components остались файлы:
        dir /b "resources\js\Components\"
    )
)

echo.
echo ======================================
echo   ОЧИСТКА ЗАВЕРШЕНА!
echo ======================================
echo.
echo Следующие шаги:
echo 1. Запустите npm run dev для проверки
echo 2. Протестируйте все страницы
echo 3. Выполните git add и commit
echo.

:end
pause