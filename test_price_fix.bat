@echo off
chcp 65001 >nul
echo 🧪 ТЕСТИРОВАНИЕ ИСПРАВЛЕНИЯ ОШИБОК С PRICE
echo.

echo 📋 Что было исправлено:
echo ✅ PriceSection.vue - изменен тип пропса price на [String, Number]
echo ✅ PriceSection.vue - emit('update:price') теперь отправляет строку
echo ✅ useAdForm.js - добавлена проверка типа price при загрузке данных
echo ✅ AdController.php - price всегда возвращается как строка из API
echo.

echo 🎯 Проверьте в браузере:
echo 1. Откройте черновик объявления
echo 2. Измените цену в любом поле
echo 3. Проверьте консоль - ошибок быть не должно
echo.

echo ✅ Исправления применены!
echo.
pause 