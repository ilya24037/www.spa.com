# 🚀 PowerShell Скрипты для SPA Platform

## 📋 **Обзор**

Этот набор PowerShell скриптов автоматизирует развертывание и управление SPA Platform проектом, поддерживая разные методы установки PowerShell.

## 🛠️ **Доступные скрипты**

### 1. **deploy.ps1** - Основной скрипт деплоя
```powershell
# Базовый деплой
.\scripts\deploy.ps1

# Деплой с параметрами
.\scripts\deploy.ps1 -Environment production -PowerShellMethod msi -SkipTests
```

**Параметры:**
- `-Environment`: `development`, `staging`, `production`
- `-PowerShellMethod`: `winget`, `msi`, `zip`
- `-SkipTests`: Пропустить тесты
- `-Force`: Принудительная установка PowerShell

### 2. **restart.ps1** - Перезапуск сервисов
```powershell
# Перезапуск с резервной копией
.\scripts\restart.ps1

# Перезапуск без резервной копии
.\scripts\restart.ps1 -SkipBackup
```

### 3. **install-powershell.ps1** - Установка PowerShell
```powershell
# Установка через WinGet
.\scripts\install-powershell.ps1 -Method winget

# Установка через MSI
.\scripts\install-powershell.ps1 -Method msi -Version 7.5.2

# Установка через ZIP
.\scripts\install-powershell.ps1 -Method zip -Force
```

## 🔍 **Различия методов установки PowerShell**

### **WinGet (Рекомендуемый для разработки)**

**Преимущества:**
- ✅ Автоматические обновления
- ✅ Простая установка/удаление
- ✅ Интеграция с Windows 10/11
- ✅ Безопасность (подписанные пакеты)
- ✅ Централизованное управление

**Недостатки:**
- ❌ Только Windows 10/11
- ❌ Требует интернет
- ❌ Ограниченный контроль версий

**Использование:**
```powershell
# Установка
winget install Microsoft.PowerShell

# Обновление
winget upgrade Microsoft.PowerShell

# Удаление
winget uninstall Microsoft.PowerShell
```

### **MSI (Лучший для продакшена)**

**Преимущества:**
- ✅ Полная интеграция с Windows
- ✅ Групповые политики
- ✅ Корпоративное развертывание
- ✅ Офлайн установка
- ✅ Логирование и откат
- ✅ Автоматические обновления через WSUS

**Недостатки:**
- ❌ Ручное обновление
- ❌ Сложная настройка
- ❌ Требует администратора
- ❌ Одна версия на систему

**Использование:**
```powershell
# Тихая установка
msiexec /i PowerShell-7.5.2-win-x64.msi /quiet

# Установка с логированием
msiexec /i PowerShell-7.5.2-win-x64.msi /quiet /log C:\temp\powershell-install.log

# Удаление
msiexec /x PowerShell-7.5.2-win-x64.msi /quiet
```

### **ZIP (Для тестирования и портативности)**

**Преимущества:**
- ✅ Несколько версий одновременно
- ✅ Портативность
- ✅ Полный контроль
- ✅ ARM поддержка
- ✅ Быстрое развертывание
- ✅ Нет прав администратора

**Недостатки:**
- ❌ Ручная настройка PATH
- ❌ Нет автообновлений
- ❌ Нет интеграции с Windows
- ❌ Сложность управления

**Использование:**
```powershell
# Распаковка
Expand-Archive PowerShell-7.5.2-win-x64.zip C:\PowerShell\7.5

# Добавление в PATH
$env:PATH += ";C:\PowerShell\7.5"

# Запуск
C:\PowerShell\7.5\pwsh.exe
```

## 🏗️ **Практические сценарии**

### **Сценарий 1: Разработка (WinGet)**
```powershell
# 1. Установка PowerShell
.\scripts\install-powershell.ps1 -Method winget

# 2. Деплой проекта
.\scripts\deploy.ps1 -Environment development -PowerShellMethod winget

# 3. Перезапуск при изменениях
.\scripts\restart.ps1
```

### **Сценарий 2: Продакшн сервер (MSI)**
```powershell
# 1. Установка PowerShell (требует админа)
.\scripts\install-powershell.ps1 -Method msi

# 2. Деплой в продакшн
.\scripts\deploy.ps1 -Environment production -PowerShellMethod msi

# 3. Мониторинг
Get-Process | Where-Object { $_.ProcessName -like "*php*" -or $_.ProcessName -like "*node*" }
```

### **Сценарий 3: Тестирование (ZIP)**
```powershell
# 1. Установка нескольких версий
.\scripts\install-powershell.ps1 -Method zip -Version 7.4.0
.\scripts\install-powershell.ps1 -Method zip -Version 7.5.2

# 2. Тестирование с разными версиями
C:\PowerShell\7.4.0\pwsh.exe -Command ".\scripts\deploy.ps1"
C:\PowerShell\7.5.2\pwsh.exe -Command ".\scripts\deploy.ps1"
```

## 🔧 **Настройка окружения**

### **Переменные окружения**
```powershell
# Создание .env файла
Copy-Item .env.example .env

# Настройка БД
php artisan key:generate
php artisan migrate
```

### **Права доступа**
```powershell
# Проверка прав администратора
if (-not ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Warning "Требуются права администратора для некоторых операций"
}
```

## 📊 **Мониторинг и логи**

### **Просмотр логов**
```powershell
# Laravel логи
Get-Content storage/logs/laravel.log -Tail 50 -Wait

# PowerShell логи
Get-EventLog -LogName Application -Source "PowerShell" -Newest 10
```

### **Проверка сервисов**
```powershell
# Статус процессов
Get-Process | Where-Object { $_.ProcessName -like "*php*" -or $_.ProcessName -like "*node*" }

# Проверка портов
netstat -an | findstr ":8000\|:5173"
```

## 🚨 **Устранение неполадок**

### **PowerShell не найден**
```powershell
# Проверка установки
pwsh --version

# Поиск в системе
Get-ChildItem -Path "C:\" -Recurse -Name "pwsh.exe" -ErrorAction SilentlyContinue
```

### **Ошибки деплоя**
```powershell
# Очистка кеша
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Проверка зависимостей
composer install
npm install
```

### **Проблемы с сервисами**
```powershell
# Принудительная остановка
Get-Process -Name "php", "node" | Stop-Process -Force

# Перезапуск
.\scripts\restart.ps1 -Force
```

## 📝 **Рекомендации**

### **Для разработки:**
- ✅ Используйте **WinGet** для простоты
- ✅ Включите автоматические обновления
- ✅ Регулярно очищайте кеш

### **Для продакшена:**
- ✅ Используйте **MSI** для стабильности
- ✅ Настройте групповые политики
- ✅ Включите логирование

### **Для тестирования:**
- ✅ Используйте **ZIP** для гибкости
- ✅ Тестируйте разные версии
- ✅ Изолируйте окружения

## 🔗 **Полезные ссылки**

- [PowerShell GitHub](https://github.com/PowerShell/PowerShell)
- [WinGet документация](https://docs.microsoft.com/en-us/windows/package-manager/winget/)
- [Laravel документация](https://laravel.com/docs)
- [Vue.js документация](https://vuejs.org/guide/)

---

**Создано для SPA Platform** 🎯 