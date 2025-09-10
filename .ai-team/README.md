# 🤖 AI Team System

Система управления AI агентами для автоматизации разработки SPA Platform.

## 📁 Структура

```
.ai-team/
├── README.md                 # Этот файл
├── chat.md                  # Коммуникационный чат агентов
├── scripts/                 # 25 скриптов автоматизации
│   ├── START-AI-TEAM.bat   # Основной запуск
│   ├── ai-team-automation.ps1
│   └── ... (другие скрипты)
├── backend/                 # Backend агент
│   └── CLAUDE.md           # Инструкции для backend разработчика
├── frontend/                # Frontend агент  
│   └── CLAUDE.md           # Инструкции для frontend разработчика
├── devops/                  # DevOps агент
│   └── CLAUDE.md           # Инструкции для DevOps инженера
├── teamlead/                # TeamLead агент
└── logs/                    # Логи работы агентов
```

## 📚 Документация

- **AI-TEAM-GUIDE.md** - Быстрый гайд по использованию
- **AI-TEAM-USAGE.md** - Подробная инструкция  
- **AGENTS.md** - Инструкции для AI агентов
- **AI_CONTEXT.md** - Контекст проекта
- **TEAMLEAD-USAGE.md** - Руководство TeamLead

## 🚀 Быстрый старт

### Запуск AI Team
```bash
# Из корня проекта
start-ai-team.bat

# Или полный путь
.ai-team\scripts\START-AI-TEAM.bat
```

### Остановка
```bash
stop-ai-team.bat
```

## 💬 Команды управления

```bash
# Отправить сообщение всем агентам
msg-all "create review system with ratings"

# Конкретному агенту
msg-back "create Review model"
msg-front "create ReviewCard component"  
msg-dev "setup Redis for caching"

# Проверить статус
status
chat
```

## 🎯 Как это работает

1. **Агенты мониторят** `chat.md` каждые 2 секунды
2. **Реагируют на упоминания** @backend, @frontend, @devops, @all
3. **Выполняют задачи** автономно с полными правами
4. **Отчитываются** о результатах в чат

## ⚙️ Конфигурация

Каждый агент имеет свой `CLAUDE.md` с инструкциями:
- Специализация и обязанности
- Техническ��й стек и инструменты  
- Шаблоны ответов и workflow
- Правила взаимодействия

## 🔧 Настройка

Все агенты запускаются с параметрами:
- `--dangerously-skip-permissions` - полный доступ к файлам
- `--mode normal` - обычный режим (не план-режим)

## 📝 Логи

Все действия агентов логируются в `logs/` директорию.

## ⚠️ Важное

- Система экспериментальная
- Требует установленный Claude CLI
- Агенты имеют полные права на изменение кода
- Использовать только в development окружении