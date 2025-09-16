# ⚡ VIRTUAL OFFICE - БЫСТРЫЙ СТАРТ ЗА 30 СЕКУНД

## 🚀 ЗАПУСК ОДНОЙ КОМАНДОЙ

```batch
C:\www.spa.com\.ai-team\START-VIRTUAL-OFFICE.bat
```

**Нажмите Enter → Выберите 1 или 3 → Готово!**

---

## 📱 3 ГЛАВНЫХ ИНТЕРФЕЙСА

### 1. CEO Panel (Нажмите 1 после запуска)
```
Создать задачу → 1
Посмотреть метрики → 4
Отправить сообщение → 3
```

### 2. Web Dashboard (Откроется автоматически)
```
http://localhost:8082
```

### 3. PowerShell (Для быстрых команд)
```powershell
cd C:\www.spa.com\.ai-team\scripts

# Создать задачу
.\task-manager.ps1 -Action create -Title "Задача" -Assignee backend

# Отправить сообщение
.\message-router.ps1 -Action send -From ceo -To all -Message "Текст"
```

---

## 👨‍💼 КОМАНДА (5 АГЕНТОВ)

| Агент | Что делает | Команда для задачи |
|-------|-----------|-------------------|
| **TeamLead** | Координирует | -Assignee teamlead |
| **Backend** | Laravel/API | -Assignee backend |
| **Frontend** | Vue.js/UI | -Assignee frontend |
| **QA** | Тестирует | -Assignee qa |
| **DevOps** | Деплоит | -Assignee devops |

---

## 💬 ПРИМЕРЫ РЕАЛЬНЫХ КОМАНД

### Создать новую функцию:
```
CEO Interface → 1 (Create task)
Заголовок: Добавить фильтры на странице
Назначить: teamlead
```

### Исправить баг:
```
CEO Interface → 3 (Send message)
Кому: qa
Сообщение: Протестируй форму оплаты, пользователи жалуются
```

### Срочный деплой:
```powershell
.\message-router.ps1 -Action send -From ceo -To devops -Message "СРОЧНО: задеплой hotfix на production"
```

---

## 📊 ЧТО ПРОИСХОДИТ АВТОМАТИЧЕСКИ

- ✅ **9:00** - Агенты постят стендапы
- ✅ **Каждые 5 мин** - Проверка help-канала
- ✅ **По ключевым словам** - Обновление метрик
- ✅ **При создании задачи** - Распределение через inbox
- ✅ **Постоянно** - Синхронизация с chat.md

---

## 🔍 ГДЕ СМОТРЕТЬ РЕЗУЛЬТАТЫ

| Что | Где |
|-----|-----|
| **Чат агентов** | `C:\www.spa.com\.ai-team\chat.md` |
| **Задачи** | `virtual-office\tasks\*.json` |
| **Метрики** | Dashboard или `virtual-office\metrics\` |
| **Стендапы** | `virtual-office\channels\standup\` |

---

## 🛑 ОСТАНОВКА

**Вариант 1:** В меню выберите 0
**Вариант 2:** Ctrl+C в окне запуска
**Вариант 3:** Закройте все окна PowerShell

---

## 🆘 ЕСЛИ НЕ РАБОТАЕТ

```powershell
# Проверить Python
python --version

# Проверить Node.js
node --version

# Проверить порт 8082
netstat -an | findstr 8082
```

---

## 📞 ГОРЯЧИЕ КЛАВИШИ CEO INTERFACE

| Клавиша | Действие |
|---------|----------|
| **1** | Создать задачу |
| **2** | Список задач |
| **3** | Отправить сообщение |
| **4** | Метрики команды |
| **5** | Сгенерировать отчет |
| **6** | Статус агентов |
| **0** | Выход |

---

**🎯 ВСЁ! Система готова к работе!**

Просто запустите `START-VIRTUAL-OFFICE.bat` и управляйте своей AI командой!