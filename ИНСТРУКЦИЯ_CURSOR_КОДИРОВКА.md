# 🚀 ИСПРАВЛЕНИЕ КОДИРОВКИ В CURSOR - ИНСТРУКЦИЯ

## ✅ Что уже сделано:

1. **PowerShell профиль создан**: `C:\Users\user1\Documents\WindowsPowerShell\Microsoft.PowerShell_profile.ps1`
2. **Настройки Cursor подготовлены**: `cursor-terminal-settings.json`
3. **Скрипт установки создан**: `setup-cursor-encoding.bat`

---

## 🔧 ЧТО НУЖНО СДЕЛАТЬ В CURSOR:

### Шаг 1: Откройте настройки Cursor
1. Нажмите `Ctrl + Shift + P`
2. Введите: `Preferences: Open Settings (JSON)`
3. Нажмите Enter

### Шаг 2: Добавьте настройки терминала
Скопируйте и добавьте в settings.json:

```json
{
  "terminal.integrated.defaultProfile.windows": "PowerShell",
  "terminal.integrated.profiles.windows": {
    "PowerShell": {
      "source": "PowerShell",
      "args": ["-NoLogo", "-ExecutionPolicy", "Bypass"]
    }
  },
  "terminal.integrated.env.windows": {
    "PYTHONUTF8": "1",
    "PYTHONIOENCODING": "utf-8"
  },
  "terminal.integrated.fontSize": 14,
  "terminal.integrated.fontFamily": "Consolas, 'Courier New', monospace"
}
```

### Шаг 3: Перезапустите Cursor
1. Полностью закройте Cursor
2. Запустите заново
3. Откройте новый терминал

---

## 🎯 ПРОВЕРКА ИСПРАВЛЕНИЯ:

После перезапуска протестируйте команды:

```bash
# Эти команды должны работать БЕЗ искажений:
npm --version
composer --version  
chcp

# Дополнительные алиасы Laravel:
art --version          # = php artisan --version
tinker                # = php artisan tinker
serve                 # = php artisan serve
```

---

## 🛠️ ФУНКЦИИ PowerShell ПРОФИЛЯ:

### Исправления кодировки:
- ✅ UTF-8 установлена по умолчанию
- ✅ Функции-обёртки для npm, composer, chcp
- ✅ Переменные окружения для Python UTF-8

### Laravel алиасы:
- `art` или `artisan` → `php artisan`
- `tinker` → `php artisan tinker`
- `serve` → `php artisan serve`  
- `migrate` → `php artisan migrate`

### Умные функции:
- `composer` автоматически ищет Herd или стандартный Composer
- `chcp` работает с правильной кодировкой
- `npm` обходит проблемы с символами

---

## 🚨 ЕСЛИ НЕ РАБОТАЕТ:

### Вариант A: Ручной запуск профиля
```powershell
powershell -ExecutionPolicy Bypass -NoProfile -File "C:\Users\user1\Documents\WindowsPowerShell\Microsoft.PowerShell_profile.ps1"
```

### Вариант B: Разрешить выполнение скриптов
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Вариант C: Проверить путь к профилю
```powershell
echo $PROFILE
Test-Path $PROFILE
```

---

## 💡 ДОПОЛНИТЕЛЬНЫЕ СОВЕТЫ:

1. **Шрифт**: Используйте Consolas или Cascadia Code для лучшей поддержки символов
2. **Размер**: Рекомендуемый размер шрифта 14px
3. **Копирование**: Включите копирование по выделению в настройках терминала

---

## 🎉 РЕЗУЛЬТАТ:

После выполнения всех шагов:
- ✅ `npm` команды работают корректно
- ✅ `composer` команды работают корректно  
- ✅ `chcp` работает без искажений
- ✅ Кириллица отображается правильно
- ✅ Laravel алиасы работают
- ✅ Настройки применяются автоматически

**Постоянное решение готово!** 🚀