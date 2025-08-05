@echo off
echo ========================================
echo   УДАЛЕНИЕ LEGACY КОМПОНЕНТОВ
echo ========================================
echo.
echo Будет удалено: 18 файлов
echo.
pause

del /F "resources\js\Components\Booking\BookingForm.vue"
del /F "resources\js\Components\Cards\Card.vue"
del /F "resources\js\Components\Features\MasterShow\components\BookingWidget.vue"
del /F "resources\js\Components\Features\MasterShow\components\MasterInfo.vue"
del /F "resources\js\Components\Features\MasterShow\index.vue"
del /F "resources\js\Components\Features\PhotoUploader\index.vue"
del /F "resources\js\Components\Features\Services\index.vue"
del /F "resources\js\Components\Masters\MasterDescription\index.vue"
del /F "resources\js\Components\Masters\MasterDetails\index.vue"
del /F "resources\js\Components\Masters\MasterHeader\index.vue"
del /F "resources\js\Components\Media\MediaGallery\index.vue"
del /F "resources\js\Components\Media\MediaUploader\index.vue"
del /F "resources\js\Components\Modals\BookingModal.vue"
del /F "resources\js\Components\UI\Forms\InputError.vue"
del /F "resources\js\Components\UI\Forms\InputLabel.vue"
del /F "resources\js\Components\UI\Forms\PrimaryButton.vue"
del /F "resources\js\Components\UI\Forms\SecondaryButton.vue"
del /F "resources\js\Components\UI\Forms\TextInput.vue"
:: Удаление пустых директорий
for /f "delims=" %d in ('dir /s /b /ad resources\js\Components ^| sort /r') do rd "%d" 2>nul

echo.
echo ========================================
echo   УДАЛЕНИЕ ЗАВЕРШЕНО
echo ========================================
pause
