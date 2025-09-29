# 🔧 Решение конфликта Tailwind CSS v4 + vue-yandex-maps

## 🚨 Проблема
При использовании Tailwind CSS v4 с библиотекой `vue-yandex-maps` возникали конфликты:
- `Cannot convert undefined or null to object`
- Неправильное отображение элементов карты
- Конфликт стилей между Tailwind reset и стилями Yandex Maps

## ✅ Решение

### 1. Простой CSS импорт (ИСПРАВЛЕНО)
```css
/* resources/css/app.css */
@import "tailwindcss";
@import './yandex-maps.css';
```

### 2. Отдельный файл изоляции (БЕЗ @layer)
```css
/* resources/css/yandex-maps.css */
.yandex-map-container {
  all: initial;
  font-family: inherit;
  position: relative;
  width: 100%;
  height: 100%;
}

.yandex-map-container * {
  box-sizing: border-box;
}

/* Защита от Tailwind reset */
.yandex-map-container button,
.yandex-map-container input {
  background-color: initial;
  border: initial;
  color: initial;
}
```

### 3. Vue компонент-обертка
```vue
<!-- resources/js/src/shared/ui/molecules/YandexMapWrapper/YandexMapWrapper.vue -->
<template>
  <div class="yandex-map-container">
    <slot />
  </div>
</template>

<style scoped>
.yandex-map-container {
  all: initial;
  font-family: inherit;
  box-sizing: border-box;
}

.yandex-map-container :deep(*) {
  box-sizing: border-box;
}
</style>
```

### 4. Конфигурация Vite
```javascript
// vite.config.js
css: {
  postcss: {
    plugins: [
      {
        postcssPlugin: 'yandex-maps-isolation',
        Rule(rule) {
          if (rule.selector && rule.selector.includes('ymaps')) {
            rule.selector = `.yandex-map-container ${rule.selector}`;
          }
        }
      }
    ]
  }
}
```

## 🎯 Использование

### Старый способ (с конфликтами):
```vue
<YandexMap />
```

### Новый способ (изолированно):
```vue
<YandexMapWrapper>
  <YandexMap />
</YandexMapWrapper>
```

## 📋 Результат
- ✅ Tailwind CSS v4 работает полностью
- ✅ vue-yandex-maps работает без конфликтов
- ✅ Стили изолированы друг от друга
- ✅ Нет ошибок в консоли

## 🔍 Принцип работы
1. **Простой CSS импорт** - избегаем @layer для совместимости с Tailwind v4
2. **Scoped Styles** - изолируют стили компонента
3. **CSS Reset Protection** - защищают элементы карты от Tailwind reset
4. **Упрощенная конфигурация** - минимум PostCSS плагинов

## ⚠️ ВАЖНЫЕ ИСПРАВЛЕНИЯ В v4:
- **НЕ используйте @layer** - может вызывать ошибки "Cannot convert undefined"
- **Упростите PostCSS конфигурацию** - убирайте сложные плагины
- **Используйте простые CSS импорты** - @import работает стабильнее

## 🚀 Дополнительные рекомендации
1. Всегда оборачивайте Yandex Maps в `YandexMapWrapper`
2. Используйте `:deep()` для стилизации элементов карты
3. Тестируйте изменения на разных размерах экрана
4. Следите за обновлениями обеих библиотек

---
**Дата создания:** $(Get-Date)
**Статус:** ✅ Решено и протестировано
