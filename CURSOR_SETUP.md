# 🚀 Настройка Cursor для SPA Platform

## 📋 Что уже создано

✅ **Структура проекта**:
- `project/idea.md` - Идея проекта
- `project/vision.md` - Техническое видение  
- `project/conventions.md` - Правила кодирования
- `project/tasklist.md` - План работы
- `project/workflow.md` - Процесс разработки

✅ **Правила Cursor**:
- `.cursor/rules/conventions.mdc` - Стандарты кодирования
- `.cursor/rules/workflow.mdc` - Рабочий процесс
- `.cursor/settings.json` - Настройки редактора
- `.cursor/snippets.md` - Шаблоны промптов

✅ **Документация**:
- `README.md` - Основная документация проекта

## ⚙️ Настройка Cursor

### 1. Открыть настройки
- **Windows**: `Ctrl + ,`
- **Mac**: `Cmd + ,`

### 2. AI Model (AI модель)
```
AI Model: [выберите предпочитаемую модель]
Alternative: [выберите альтернативную модель]
```

### 3. Context Window (окно контекста)
```
Long Context Chat: ✅ ВКЛЮЧЕНО
Include Images: ✅ ВКЛЮЧЕНО (для UI работы)
```

### 4. Autocomplete (автодополнение)
```
Copilot++: ✅ ВКЛЮЧЕНО
Partial Accepts: ✅ ВКЛЮЧЕНО
Trigger Delay: 200ms
```

### 5. Privacy settings (настройки приватности)
```
Privacy Mode: strict
Telemetry: disabled
Codebase Indexing: local-only
```

## ⌨️ Горячие клавиши

### Основные команды
```
Ctrl + K          → AI Chat
Ctrl + Shift + K  → Inline edit
Ctrl + L          → Add to chat
Tab               → Accept suggestion
Esc               → Reject suggestion
```

### Навигация
```
Ctrl + P          → Quick Open
Ctrl + Shift + P  → Command Palette
Ctrl + B          → Toggle Sidebar
Ctrl + J          → Toggle Terminal
```

## 🔧 Дополнительные настройки

### 1. Исключить папки из индексации
```
node_modules/
dist/
.git/
vendor/
storage/logs/
storage/framework/cache/
```

### 2. Настройки для PHP
```
PHP Validation: ✅ ВКЛЮЧЕНО
PHP Executable Path: php
Blade Formatting: ✅ ВКЛЮЧЕНО
```

### 3. Настройки для Vue.js
```
Vue Code Actions: ✅ ВКЛЮЧЕНО
Vue Complete Casing Props: camel
Vue Complete Casing Tags: pascal
```

### 4. Настройки для Tailwind CSS
```
Tailwind CSS Include Languages: vue, html, blade
Tailwind CSS Experimental Class Regex: ✅ ВКЛЮЧЕНО
```

## 📝 Использование шаблонов промптов

### 1. Рефакторинг
```markdown
Refactor this code following:
- SOLID principles
- Clear naming conventions  
- Extract methods >10 lines
- Add error handling
- Keep original logic intact

Code to refactor:
[вставить код]
```

### 2. Отладка
```markdown
Debug this code:
1. Identify issues (найди проблемы)
2. Explain root cause (объясни причину)
3. Provide fix (предложи исправление)
4. Add defensive code (добавь защитный код)
5. Suggest tests (предложи тесты)

Code to debug:
[вставить код]
```

### 3. Создание компонента
```markdown
Create a new Vue component for SPA Platform following the modular architecture:

Component name: [название]
Purpose: [назначение]
Props: [входящие данные]
Events: [события]
Features: [функциональность]

Requirements:
- Use Composition API with <script setup>
- TypeScript interfaces for props
- Tailwind CSS for styling
- Mobile-first responsive design
- Follow project naming conventions
- Include error handling
```

## 🎯 Лучшие практики

### 1. Управление контекстом
- **Держите открытыми** только связанные файлы
- **Максимум 5-7 файлов** в контексте
- **Закрывайте** нерелевантные вкладки

### 2. Работа с AI
- **Используйте конкретные шаблоны** для лучших результатов
- **Предоставляйте контекст** о вашей задаче
- **Указывайте требования** четко и подробно
- **Тестируйте код** после получения ответа

### 3. Процесс разработки
- **Анализируйте задачу** перед началом
- **Предлагайте решение** с примерами кода
- **Ждите подтверждения** перед реализацией
- **Реализуйте пошагово** по одному файлу
- **Тестируйте немедленно** после создания

## 🚨 Важные напоминания

### 1. Безопасность
- **НЕ доверяйте слепо** AI коду
- **Проверяйте каждую строку** перед использованием
- **Тестируйте немедленно** после генерации
- **Документируйте решения** для будущего

### 2. Качество кода
- **Следуйте конвенциям** проекта
- **Добавляйте обработку ошибок**
- **Пишите тесты** для нового функционала
- **Используйте TypeScript** для типизации

### 3. Производительность
- **Очищайте кеш** еженедельно
- **Индексируйте только исходники**
- **Ограничивайте размер файлов** <1MB
- **Исключайте ненужные папки**

## 🔍 Проверка настроек

### 1. Тест AI модели
```
Создайте простой Vue компонент с помощью Ctrl + K
Проверьте качество и соответствие конвенциям
```

### 2. Тест автодополнения
```
Начните писать код в .vue файле
Проверьте работу Copilot++ и Partial Accepts
```

### 3. Тест горячих клавиш
```
Попробуйте все основные горячие клавиши
Убедитесь что они работают корректно
```

## 📚 Полезные ресурсы

### Документация
- [Cursor документация](https://cursor.sh/docs)
- [Laravel документация](https://laravel.com/docs)
- [Vue.js документация](https://vuejs.org/guide/)
- [Tailwind CSS документация](https://tailwindcss.com/docs)

### Сообщество
- [Cursor Discord](https://discord.gg/cursor)
- [Laravel Community](https://laravel.com/community)
- [Vue.js Community](https://vuejs.org/community/)

---

## ✅ Чек-лист настройки

- [ ] Открыты настройки Cursor
- [ ] Настроена AI модель (выбрана предпочитаемая)
- [ ] Включен Long Context Chat
- [ ] Включен Include Images
- [ ] Настроены горячие клавиши
- [ ] Исключены ненужные папки
- [ ] Настроены PHP/Vue.js/Tailwind
- [ ] Протестированы AI функции
- [ ] Протестированы горячие клавиши
- [ ] Созданы сниппеты промптов

**Готово! 🎉 Теперь Cursor настроен для максимальной эффективности разработки SPA Platform.**
