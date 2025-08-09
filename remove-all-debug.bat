@echo off
chcp 65001 >nul

echo.
echo 🧹 ПОЛНАЯ ОЧИСТКА ВСЕХ DEBUG ЛОГОВ
echo ==================================

echo.
echo 📋 Удаляем console.log из ВСЕХ файлов...

REM Удаляем console.log из конкретных файлов по списку
echo ✅ Очищаем GeographySection...
powershell -Command "(Get-Content 'Backap/js/Components/Form/Sections/GeographySection.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'Backap/js/Components/Form/Sections/GeographySection.vue'"

echo ✅ Очищаем Masters компоненты...
powershell -Command "(Get-Content 'Backap/js/Components/Masters/BookingWidget/index.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'Backap/js/Components/Masters/BookingWidget/index.vue'"

powershell -Command "(Get-Content 'Backap/js/Components/Masters/MasterContactCard/index.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'Backap/js/Components/Masters/MasterContactCard/index.vue'"

powershell -Command "(Get-Content 'Backap/js/Components/Masters/MasterReviews/index.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'Backap/js/Components/Masters/MasterReviews/index.vue'"

powershell -Command "(Get-Content 'Backap/js/Components/Masters/MasterServices/index.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'Backap/js/Components/Masters/MasterServices/index.vue'"

echo ✅ Очищаем Modal компоненты...
powershell -Command "(Get-Content 'Backap/js/Components/Modals/BookingModal.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'Backap/js/Components/Modals/BookingModal.vue'"

echo ✅ Очищаем Profile компоненты...
powershell -Command "(Get-Content 'Backap/js/Components/Profile/ItemCardDemo.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'Backap/js/Components/Profile/ItemCardDemo.vue'"

echo ✅ Очищаем Composables...
powershell -Command "(Get-Content 'Backap/js/Composables/useAdForm.js') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'Backap/js/Composables/useAdForm.js'"

echo ✅ Очищаем Pages...
powershell -Command "(Get-Content 'Backap/js/Pages/AddItem.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'Backap/js/Pages/AddItem.vue'"

powershell -Command "(Get-Content 'Backap/js/Pages/Dashboard.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'Backap/js/Pages/Dashboard.vue'"

powershell -Command "(Get-Content 'Backap/js/Pages/Demo/ItemCard.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'Backap/js/Pages/Demo/ItemCard.vue'"

powershell -Command "(Get-Content 'Backap/js/Pages/Reviews/Index.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'Backap/js/Pages/Reviews/Index.vue'"

echo ✅ Очищаем resources/js...
powershell -Command "(Get-Content 'resources/js/Pages/AddItem.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'resources/js/Pages/AddItem.vue'"

powershell -Command "(Get-Content 'resources/js/src/entities/ad/ui/AdForm/AdForm_MIGRATED.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'resources/js/src/entities/ad/ui/AdForm/AdForm_MIGRATED.vue'"

powershell -Command "(Get-Content 'resources/js/src/features/AdSections/GeographySection/ui/GeographySection.vue') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'resources/js/src/features/AdSections/GeographySection/ui/GeographySection.vue'"

powershell -Command "(Get-Content 'resources/js/src/widgets/AdForm/model/adFormModel.js') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'resources/js/src/widgets/AdForm/model/adFormModel.js'"

powershell -Command "(Get-Content 'resources/js/utils/adApi.js') | Where-Object { $_ -notmatch 'console\.log' } | Set-Content 'resources/js/utils/adApi.js'"

echo.
echo 🎯 СПЕЦИАЛЬНАЯ ОЧИСТКА Draft/Show.vue (сложные случаи)...
REM Для Draft/Show.vue нужна особая обработка - там console.log в inline функциях

echo.
echo 🔍 Проверяем результат...
.\ai-context-debug.bat

echo.
echo ✅ ПОЛНАЯ ОЧИСТКА ЗАВЕРШЕНА!
