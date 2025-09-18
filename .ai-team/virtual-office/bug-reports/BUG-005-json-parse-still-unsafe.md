# 🐛 BUG-005: JSON.parse остается небезопасным в AdForm

## 📋 Информация о баге
- **ID:** BUG-005
- **Дата:** 2025-09-17
- **Приоритет:** 🔴 **КРИТИЧЕСКИЙ**
- **Компонент:** AdForm.vue
- **Строка:** 805
- **Статус:** OPEN
- **Версия:** Current (17.09.2025)

## 🔍 Описание проблемы

JSON.parse используется без try-catch блока, что может привести к краху приложения при некорректных данных в поле geo.

## 📍 Местоположение в коде

**Файл:** `resources/js/src/features/ad-creation/ui/AdForm.vue`
**Строка:** 805

```typescript
// ТЕКУЩИЙ НЕБЕЗОПАСНЫЙ КОД:
const geoData = typeof form.geo === 'string' ? JSON.parse(form.geo || '{}') : form.geo
```

## 🎯 Шаги для воспроизведения

1. Открыть форму создания объявления
2. Программно установить в поле `form.geo` некорректный JSON, например: `"invalid{json"`
3. Попытаться сохранить форму
4. **Результат:** Приложение крашится с ошибкой `Uncaught SyntaxError: Unexpected token`

## 💥 Возможные последствия

1. **Крах приложения** при некорректных данных
2. **Потеря данных пользователя** при заполнении формы
3. **Плохой UX** - белый экран смерти
4. **Уязвимость безопасности** - возможность DoS атаки

## ✅ Рекомендуемое решение

```typescript
// БЕЗОПАСНЫЙ КОД С ОБРАБОТКОЙ ОШИБОК:
const parseGeoData = (geoString: string | object): object => {
  if (typeof geoString !== 'string') {
    return geoString || {}
  }

  try {
    return JSON.parse(geoString || '{}')
  } catch (error) {
    console.warn('Ошибка парсинга geo данных:', error)
    // Можно также показать toast уведомление пользователю
    return {}
  }
}

const geoData = parseGeoData(form.geo)
```

## 🧪 Тестовые кейсы

### Test Case 1: Корректный JSON
```javascript
Input: '{"city": "Moscow", "district": "Center"}'
Expected: { city: "Moscow", district: "Center" }
```

### Test Case 2: Пустая строка
```javascript
Input: ''
Expected: {}
```

### Test Case 3: Некорректный JSON
```javascript
Input: 'invalid{json'
Expected: {} (без краша)
```

### Test Case 4: null/undefined
```javascript
Input: null
Expected: {}
```

## 📊 Метрики

- **Частота возникновения:** Потенциально высокая (при любом сбое сохранения geo данных)
- **Влияние на пользователей:** Критическое (полная потеря функциональности)
- **Сложность исправления:** Низкая (5 минут работы)

## 🔗 Связанные баги

- BUG-002: Оригинальный репорт о JSON.parse проблеме
- BUG-001: TypeScript any types в том же компоненте

## 📝 Примечания

Этот баг был изначально обнаружен в BUG-002, но не был исправлен. Требуется немедленное исправление перед релизом в production.

## 🏷️ Теги
`critical`, `crash`, `json`, `error-handling`, `production-blocker`