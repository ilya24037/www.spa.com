# 🏢 Virtual Office - AI Team 3.0

## 📋 Что нового в Virtual Office?

Virtual Office - это продвинутое расширение AI Team системы, добавляющее профессиональные возможности управления командой:

### ✨ Новые возможности:

1. **5-й агент QA** - инженер по тестированию для контроля качества
2. **Inbox/Outbox система** - персональные сообщения каждому агенту
3. **JSON задачи** - структурированные задачи с приоритетами и дедлайнами
4. **CEO интерфейс** - Python панель управления для руководителя
5. **Система мониторинга** - метрики и KPI каждого агента
6. **Отдельные каналы** - general, standup, help для организованной коммуникации
7. **Интеграционный мост** - синхронизация со старой системой chat.md

## 🚀 Быстрый старт

### Вариант 1: Полная система Virtual Office (рекомендуется)
```batch
START-VIRTUAL-OFFICE.bat
```

### Вариант 2: Классическая система (как раньше)
```batch
scripts\START-AI-TEAM-FINAL.bat
```

## 📁 Структура Virtual Office

```
.ai-team/
├── virtual-office/
│   ├── inbox/           # Личные входящие для агентов
│   │   ├── teamlead/
│   │   ├── backend/
│   │   ├── frontend/
│   │   ├── qa/         # НОВЫЙ агент
│   │   └── devops/
│   ├── outbox/         # Исходящие сообщения
│   ├── tasks/          # JSON задачи
│   ├── reports/        # Отчеты
│   ├── channels/       # Каналы коммуникации
│   │   ├── general/
│   │   ├── standup/
│   │   └── help/
│   ├── ceo_interface.py   # CEO панель
│   └── monitor.py          # Мониторинг
├── system/
│   ├── agents.json     # Конфигурация всех 5 агентов
│   ├── status.json     # Статусы в реальном времени
│   └── metrics.json    # Метрики производительности
└── qa/                 # Новый QA агент
    └── CLAUDE.md       # Инструкции для тестировщика
```

## 👥 Команда (5 агентов)

| Агент | Роль | Новые возможности |
|-------|------|-------------------|
| **TeamLead** | Координатор | Управляет 5 агентами (включая QA) |
| **Backend** | Laravel разработчик | Inbox для персональных задач |
| **Frontend** | Vue.js разработчик | Структурированные задачи |
| **QA** 🆕 | Тестировщик | Баг-репорты, тестирование |
| **DevOps** | Инфраструктура | Метрики деплоев |

## 💼 CEO Интерфейс (Python)

### Запуск интерактивного режима:
```bash
python virtual-office\ceo_interface.py
```

### Команды из командной строки:
```bash
# Создать задачу
python virtual-office\ceo_interface.py task "Fix login bug" backend

# Отправить сообщение
python virtual-office\ceo_interface.py message backend "Check the API"

# Сообщение всей команде
python virtual-office\ceo_interface.py broadcast "Team meeting at 15:00"

# Сгенерировать отчет
python virtual-office\ceo_interface.py report
```

## 📊 Система задач

### PowerShell команды:
```powershell
# Создать задачу
.\scripts\task-manager.ps1 -Action create -Title "Add payment system" -Assignee backend -Priority high

# Просмотр задач
.\scripts\task-manager.ps1 -Action list

# Фильтр по агенту
.\scripts\task-manager.ps1 -Action list -Assignee backend

# Обновить статус
.\scripts\task-manager.ps1 -Action update -TaskId TASK-20250916-001 -Status in_progress
```

### Структура задачи (JSON):
```json
{
  "task_id": "TASK-20250916-001",
  "title": "Implement payment gateway",
  "assignee": "backend",
  "priority": "high",
  "status": "in_progress",
  "deadline": "2025-09-20",
  "dependencies": []
}
```

## 📬 Система сообщений Inbox/Outbox

### Отправка персонального сообщения:
```powershell
.\scripts\message-router.ps1 -Action send -From ceo -To backend -Message "Priority task" -Priority high
```

### Проверка inbox агента:
```powershell
.\scripts\message-router.ps1 -Action check -From backend
```

### Мониторинг всех inbox:
```powershell
.\scripts\message-router.ps1 -Action monitor
```

## 📢 Каналы коммуникации

### Доступные каналы:
- **#general** - общие обсуждения
- **#standup** - ежедневные стендапы
- **#help** - запросы помощи

### Управление каналами:
```powershell
# Отправить в канал
.\scripts\channel-manager.ps1 -Action post -Channel general -Message "New sprint started"

# Просмотр канала
.\scripts\channel-manager.ps1 -Action list -Channel standup

# Запустить стендап
.\scripts\channel-manager.ps1 -Action standup
```

## 📈 Мониторинг

### Запуск монитора системы:
```bash
python virtual-office\monitor.py
```

Показывает:
- Статус всех агентов (online/offline)
- Количество непрочитанных сообщений
- Назначенные задачи
- Системные метрики
- KPI агентов

## 🔄 Интеграция с существующей системой

Virtual Office работает параллельно со старой системой chat.md:

### Автоматическая синхронизация:
```powershell
# Однократная синхронизация
.\scripts\integration-bridge.ps1 -Mode sync

# Постоянный мониторинг и синхронизация
.\scripts\integration-bridge.ps1 -Mode monitor

# Миграция истории в Virtual Office
.\scripts\integration-bridge.ps1 -Mode migrate
```

## 🎯 Примеры использования

### Сценарий 1: Создание и отслеживание задачи
```powershell
# CEO создает задачу
python virtual-office\ceo_interface.py task "Create user dashboard" frontend

# Frontend проверяет свои задачи
.\scripts\task-manager.ps1 -Action list -Assignee frontend

# Frontend обновляет статус
.\scripts\task-manager.ps1 -Action update -TaskId TASK-xxx -Status in_progress

# QA тестирует после завершения
.\scripts\message-router.ps1 -Action send -From qa -To frontend -Message "Found bug in dashboard"
```

### Сценарий 2: Ежедневный стендап
```powershell
# Запуск стендапа
.\scripts\channel-manager.ps1 -Action standup

# Агенты отвечают в канал #standup
# CEO просматривает отчеты
.\scripts\channel-manager.ps1 -Action list -Channel standup
```

## ⚙️ Настройки

Конфигурация в `system\agents.json`:
- Интервалы проверки сообщений
- Пути к inbox/outbox
- Приоритеты агентов
- Время стендапов

## 🐛 Решение проблем

### Python не установлен:
Virtual Office требует Python 3.8+. Если не установлен, система автоматически переключится на классический режим.

### Порт 8082 занят:
```powershell
# Найти процесс
netstat -ano | findstr :8082

# Убить процесс
taskkill /F /PID [номер_процесса]
```

### Агенты не отвечают:
1. Проверьте Claude: `claude --version`
2. Запустите тест: `.\scripts\TEST-AGENTS.bat`
3. Проверьте inbox агентов

## 📝 Заметки

- Virtual Office полностью совместим со старой системой
- Веб-интерфейс продолжает работать на http://localhost:8082
- QA агент автоматически тестирует код других агентов
- CEO интерфейс требует Python, остальное работает на PowerShell
- Все данные сохраняются в JSON для простого доступа

## 🚦 Статус проекта

✅ **Готово к использованию!**

Все компоненты Virtual Office установлены и настроены. Система готова к запуску через `START-VIRTUAL-OFFICE.bat`.

---

**Версия:** 3.0 (Virtual Office)
**Дата:** 2025-09-16