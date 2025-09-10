# 📂 Scripts Directory Structure

Организованная структура скриптов автоматизации для SPA Platform.

## 🏗️ Структура директорий

```
scripts/
├── ai-team/          # AI Team система (23 файла)
├── development/      # Скрипты разработки (25 файлов)
├── testing/          # Тестовые скрипты (1 файл)
└── deployment/       # Скрипты деплоя (2 файла)
```

## 🤖 AI Team Scripts (`/ai-team`)
Скрипты для управления AI агентами и автоматизации командной работы.

### Основные скрипты:
- `START-AI-TEAM.bat` - Запуск всей AI команды
- `ai-team-automation.ps1` - Автоматизация работы агентов
- `ai-context.ps1` - Управление контекстом AI

## 💻 Development Scripts (`/development`)
Скрипты для локальной разработки и отладки.

### Основные скрипты:
- `dev.bat` - Запуск окружения разработки
- `full-restart.bat` - Полный перезапуск сервисов
- `QUICK-START.bat` - Быстрый старт проекта
- `chat-server.ps1` - Сервер для AI чата

## 🧪 Testing Scripts (`/testing`)
Скрипты для запуска тестов.

### Основные скрипты:
- `TEST-AGENTS.bat` - Тестирование AI агентов

## 🚀 Deployment Scripts (`/deployment`)
Скрипты для развертывания и синхронизации.

### Основные скрипты:
- `copy-sections.ps1` - Копирование секций проекта
- `team-sync.ps1` - Синхронизация команды

## 📝 Использование

### Запуск из корня проекта:
```bash
# Windows CMD
scripts\development\dev.bat

# PowerShell
.\scripts\ai-team\START-AI-TEAM.bat
```

### Запуск из директории scripts:
```bash
cd scripts
development\dev.bat
```

## ⚠️ Важные замечания

1. Все пути в скриптах настроены для запуска из корня проекта
2. PowerShell скрипты требуют разрешения на выполнение:
   ```powershell
   Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
   ```
3. Некоторые скрипты требуют прав администратора

## 🔧 Обновление путей

После реорганизации скриптов обновите:
1. Документацию проекта с новыми путями
2. CI/CD конфигурации
3. README файлы с инструкциями запуска
