# 📝 Руководство по логированию в проекте SPA Platform

## 📊 Обзор

В проекте реализована комплексная система логирования, которая включает:

1. **Pre-commit hooks** - автоматическая проверка debug кода перед коммитом
2. **Универсальный logger** - единообразное логирование для frontend
3. **Production-ready** - console.error/warn оставлены для критических ошибок

## 🛡️ Pre-commit Hook

### Установка

Hook автоматически устанавливается при выполнении:
```bash
npm install
```

Или вручную:
```bash
npm run prepare
```

### Что проверяется

**PHP файлы:**
- `dd()`
- `dump()`
- `var_dump()`
- `print_r()`
- `var_export()`
- `die()`
- `exit()`

**JS/Vue/TS файлы:**
- `console.log()`
- `debugger`

### Пропуск проверки

Если необходимо сделать коммит с debug кодом (не рекомендуется):
```bash
git commit --no-verify -m "Ваше сообщение"
```

## 📦 Logger для Frontend

### Базовое использование

```typescript
import { logger } from '@/shared/lib/logger'

// Debug уровень (только в dev режиме)
logger.debug('Детальная информация для отладки')

// Info уровень
logger.info('Информация о работе приложения')

// Warning уровень
logger.warn('Предупреждение о потенциальной проблеме')

// Error уровень
logger.error('Ошибка при выполнении операции', error)

// Fatal уровень (критические ошибки)
logger.fatal('Критическая ошибка', error)
```

### Использование во Vue компонентах

```vue
<script setup lang="ts">
import { useLogger } from '@/shared/composables/useLogger'

const logger = useLogger('MyComponent')

const handleSubmit = async () => {
  try {
    logger.info('Начало отправки формы')
    const result = await submitForm()
    logger.info('Форма успешно отправлена', { formId: result.id })
  } catch (error) {
    logger.error('Ошибка при отправке формы', error, { 
      userId: currentUser.value?.id 
    })
  }
}
</script>
```

### Контекст логирования

Logger поддерживает добавление контекста для лучшего понимания ошибок:

```typescript
logger.error('Ошибка загрузки данных', error, {
  module: 'UserProfile',
  action: 'loadUserData',
  userId: 123,
  metadata: {
    endpoint: '/api/users/123',
    attemptNumber: 3
  }
})
```

## 🔧 Конфигурация Logger

### Настройки по умолчанию

```typescript
{
  enabled: true,                    // В production включено
  level: 'warn',                    // В production только warn и выше
  prefix: '[SPA]',                  // Префикс для всех сообщений
  sendToServer: true,               // Отправка на сервер в production
  serverEndpoint: '/api/logs',      // Endpoint для логов
  includeTimestamp: true,           // Добавлять timestamp
  includeStackTrace: true           // Включать stack trace для ошибок
}
```

### Изменение конфигурации

```typescript
import { logger } from '@/shared/lib/logger'

// Изменить уровень логирования
logger.updateConfig({ level: 'debug' })

// Отключить отправку на сервер
logger.updateConfig({ sendToServer: false })
```

## 📋 Политика логирования

### ✅ Что логировать

1. **Критические ошибки** - всегда используйте `logger.error()` или `logger.fatal()`
2. **Важные события** - начало/окончание важных операций через `logger.info()`
3. **Предупреждения** - потенциальные проблемы через `logger.warn()`
4. **Отладочная информация** - только в dev режиме через `logger.debug()`

### ❌ Что НЕ логировать

1. **Пользовательские данные** - пароли, токены, личная информация
2. **Временная отладка** - используйте debugger в dev режиме
3. **Частые события** - не логируйте каждый клик или скролл

### 📊 Уровни логирования

| Уровень | Когда использовать | Production | Development |
|---------|-------------------|------------|-------------|
| `debug` | Детальная отладочная информация | ❌ | ✅ |
| `info` | Важные события в приложении | ❌ | ✅ |
| `warn` | Предупреждения и потенциальные проблемы | ✅ | ✅ |
| `error` | Ошибки, не прерывающие работу | ✅ | ✅ |
| `fatal` | Критические ошибки | ✅ | ✅ |

## 🔄 Миграция с console

### Было
```javascript
console.log('User logged in', { userId: user.id })
console.error('Failed to load user data', error)
console.warn('API response slow', { duration: 5000 })
```

### Стало
```javascript
logger.info('User logged in', { userId: user.id })
logger.error('Failed to load user data', error)
logger.warn('API response slow', { metadata: { duration: 5000 } })
```

## 🚀 Best Practices

1. **Используйте правильный уровень** - не используйте `error` для информационных сообщений
2. **Добавляйте контекст** - всегда указывайте модуль и действие
3. **Структурируйте метаданные** - используйте объект metadata для дополнительной информации
4. **Обрабатывайте ошибки** - оборачивайте критический код в try/catch
5. **Не злоупотребляйте** - логируйте только важную информацию

## 📡 Серверная часть

Logger автоматически отправляет ошибки уровня `error` и `fatal` на сервер в production режиме.

### Формат отправляемых данных

```json
{
  "level": "error",
  "message": "Failed to load user data",
  "context": {
    "module": "UserProfile",
    "userId": 123
  },
  "error": {
    "message": "Network error",
    "stack": "Error: Network error\n    at ...",
    "name": "NetworkError"
  },
  "timestamp": "2025-08-05T10:30:00.000Z",
  "userAgent": "Mozilla/5.0 ...",
  "url": "https://spa.com/profile/123"
}
```

## 🔍 Отладка

### Включение debug режима в production

```javascript
// В консоли браузера
localStorage.setItem('debug_mode', 'true')
location.reload()
```

### Просмотр всех логов

```javascript
// Временно включить все уровни
logger.updateConfig({ level: 'debug', enabled: true })
```

## 📚 Дополнительные ресурсы

- [Исходный код logger](/resources/js/src/shared/lib/logger/logger.ts)
- [Composable для Vue](/resources/js/src/shared/composables/useLogger.ts)
- [Pre-commit hook скрипт](/scripts/check-debug-code.js)