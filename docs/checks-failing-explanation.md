# Что такое "Checks failing"?

## Определение

"Checks failing" (проверки не пройдены) - это сообщение о том, что автоматические проверки кода в вашем проекте завершились с ошибками. Это может появляться в разных местах:

- **GitHub Pull Request** - красная галочка рядом с коммитом
- **CI/CD Pipeline** - в логах сборки
- **IDE/Редактор** - предупреждения в интерфейсе
- **Терминал** - при запуске команд проверки

## Какие проверки есть в проекте

### 1. **TypeScript проверка типов** (`npm run type-check`)
```bash
npm run type-check
# или
npm run build  # включает проверку типов
```

**Что проверяет:**
- Корректность типов TypeScript
- Соответствие интерфейсов
- Отсутствие undefined/null ошибок

### 2. **ESLint** (`npm run lint:check`)
```bash
npm run lint:check  # только проверка
npm run lint        # проверка + автоисправление
```

**Что проверяет:**
- Стиль кода
- Потенциальные ошибки
- Best practices
- Неиспользуемые переменные

### 3. **Проверка отладочного кода** (`npm run check:debug`)
```bash
npm run check:debug
```

**Что проверяет:**
- Наличие console.log
- Отладочные комментарии
- Временный код

### 4. **GitHub Actions** (CI/CD)
В файле `.github/workflows/deploy.yml` настроены проверки при деплое:
- Сборка проекта
- Миграции базы данных
- Health checks (проверка доступности)

## Типичные причины ошибок

### 1. **TypeScript ошибки**
```typescript
// ❌ Ошибка: Property 'name' does not exist on type 'User'
const userName = user.name;

// ✅ Исправление
const userName = user?.name || '';
```

### 2. **ESLint ошибки**
```javascript
// ❌ Ошибка: 'unused' is defined but never used
const unused = 'value';

// ❌ Ошибка: Missing semicolon
const data = {}

// ✅ Исправление
const data = {};
```

### 3. **Ошибки сборки**
```bash
# Не установлены зависимости
Error: Cannot find module 'vue'

# Решение:
npm install
```

## Как исправить "Checks failing"

### Шаг 1: Установите зависимости
```bash
npm install
composer install
```

### Шаг 2: Запустите локальные проверки
```bash
# TypeScript
npm run type-check

# ESLint (с автоисправлением)
npm run lint

# Проверка отладочного кода
npm run check:debug

# Сборка проекта
npm run build
```

### Шаг 3: Исправьте ошибки
1. **TypeScript ошибки:**
   - Добавьте недостающие типы
   - Используйте optional chaining (`?.`)
   - Проверяйте на null/undefined

2. **ESLint ошибки:**
   - Запустите `npm run lint` для автоисправления
   - Удалите неиспользуемый код
   - Следуйте стилю кода проекта

3. **Ошибки сборки:**
   - Проверьте импорты
   - Убедитесь что все файлы существуют
   - Проверьте синтаксис

### Шаг 4: Проверьте перед коммитом
```bash
# Полная проверка
npm run type-check && npm run lint:check && npm run build

# Или используйте Git hooks (если настроены)
git commit -m "fix: исправлены ошибки TypeScript"
```

## Где смотреть детали ошибок

### 1. **GitHub Pull Request**
- Кликните на красный крестик рядом с коммитом
- Откройте "Details" для просмотра логов
- Найдите конкретные ошибки в выводе

### 2. **Локально в терминале**
```bash
# Подробный вывод TypeScript ошибок
npm run type-check

# Подробный вывод ESLint
npm run lint:check
```

### 3. **В IDE (VS Code/Cursor)**
- Откройте панель "Problems" (Ctrl+Shift+M)
- Красные подчеркивания в коде
- Hover над ошибкой для деталей

## Предотвращение ошибок

### 1. **Настройте IDE**
- Установите расширения ESLint и TypeScript
- Включите автоформатирование при сохранении
- Настройте автоисправление ESLint

### 2. **Используйте Git Hooks**
Проект уже настроен с Git hooks через скрипт:
```json
"prepare": "node scripts/install-git-hooks.js"
```

### 3. **Проверяйте перед push**
```bash
# Создайте алиас для полной проверки
alias check-all="npm run type-check && npm run lint:check && npm run build"
```

## Быстрые команды

```bash
# Исправить большинство проблем автоматически
npm run lint

# Проверить TypeScript без сборки
npm run type-check

# Быстрая сборка без проверки типов
npm run build:fast

# Полная проверка и сборка
npm run build
```

## Если ничего не помогает

1. **Очистите кеш:**
   ```bash
   rm -rf node_modules package-lock.json
   npm install
   ```

2. **Проверьте версию Node.js:**
   ```bash
   node --version  # Должна быть 18+ для этого проекта
   ```

3. **Откатите проблемный коммит:**
   ```bash
   git revert HEAD
   ```

4. **Обратитесь за помощью:**
   - Скопируйте полный текст ошибки
   - Укажите что вы уже попробовали
   - Приложите скриншоты если нужно