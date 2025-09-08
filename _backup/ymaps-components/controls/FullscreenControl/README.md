# FullscreenControl - Контрол полноэкранного режима

Высокопроизводительный компонент для управления полноэкранным режимом Яндекс Карт с поддержкой Vue 3, TypeScript и принципов CLAUDE.md.

## 🎯 Особенности

- ✅ **Production-ready** код с полной обработкой ошибок
- ✅ **TypeScript** строгая типизация без any типов
- ✅ **Vue 3 + Composition API** современный подход
- ✅ **Кроссбраузерная совместимость** всех Fullscreen API
- ✅ **Accessibility** поддержка клавиатурного управления и ARIA
- ✅ **Mobile-первый** дизайн с адаптивным интерфейсом
- ✅ **Автоматическое состояние** синхронизация с браузерным API
- ✅ **События** полный набор для интеграции

## 📦 Установка

### JavaScript модули (ES6)
```javascript
import FullscreenControl from './FullscreenControl.js'

// Создание контрола
const fullscreenControl = new FullscreenControl({
  position: 'topRight',
  size: { width: 36, height: 36 },
  icons: {
    enter: '⛶',
    exit: '⛷'
  }
})

// Добавление на карту
await fullscreenControl.addToMap(map)
```

### Vue 3 компонент
```vue
<template>
  <YandexMap>
    <FullscreenControlVue
      :map="mapInstance"
      position="topRight"
      :visible="true"
      @fullscreenenter="onFullscreenEnter"
      @fullscreenexit="onFullscreenExit"
    />
  </YandexMap>
</template>

<script setup>
import FullscreenControlVue from './FullscreenControl.vue'

function onFullscreenEnter() {
  console.log('Вход в полноэкранный режим')
}

function onFullscreenExit() {
  console.log('Выход из полноэкранного режима')
}
</script>
```

## ⚙️ API Документация

### Конструктор JavaScript

```typescript
interface FullscreenControlOptions {
  position?: 'topLeft' | 'topRight' | 'bottomLeft' | 'bottomRight'
  adjustMapMargin?: boolean
  zIndex?: number
  size?: { width: number; height: number }
  title?: string
  icons?: {
    enter?: string
    exit?: string
  }
}
```

### Props Vue компонента

```typescript
interface Props {
  position?: 'topLeft' | 'topRight' | 'bottomLeft' | 'bottomRight' // 'topRight'
  visible?: boolean                                                  // true
  size?: { width: number; height: number }                          // { width: 36, height: 36 }
  zIndex?: number                                                    // 1000
  title?: string                                                     // ''
  icons?: { enter?: string; exit?: string }                         // { enter: '⛶', exit: '⛷' }
  map?: YandexMap                                                    // экземпляр карты
}
```

## 🎮 Основные методы

### JavaScript API

```javascript
// Управление полноэкранным режимом
await fullscreenControl.enterFullscreen()      // Вход в полноэкранный режим
await fullscreenControl.exitFullscreen()       // Выход из полноэкранного режима
await fullscreenControl.toggleFullscreen()     // Переключение режима

// Проверка состояния
fullscreenControl.isFullscreen()               // boolean - текущее состояние
fullscreenControl.isVisible()                  // boolean - видимость контрола

// Управление контролом
fullscreenControl.setVisible(true)             // Установка видимости
fullscreenControl.getState()                   // Получение полного состояния
await fullscreenControl.setState(state)        // Установка состояния
```

### Vue API (через ref)

```vue
<template>
  <FullscreenControlVue ref="fullscreenRef" />
</template>

<script setup>
const fullscreenRef = ref()

// Методы доступны через ref
await fullscreenRef.value.enterFullscreen()
await fullscreenRef.value.exitFullscreen() 
await fullscreenRef.value.toggleFullscreen()
const isActive = fullscreenRef.value.isFullscreen()
</script>
```

## 📡 События

### JavaScript события

```javascript
fullscreenControl.events.add('fullscreenenter', (e) => {
  console.log('Полноэкранный режим активирован')
  console.log('Состояние:', e.isFullscreen) // true
})

fullscreenControl.events.add('fullscreenexit', (e) => {
  console.log('Полноэкранный режим деактивирован')
  console.log('Состояние:', e.isFullscreen) // false
})

fullscreenControl.events.add('buttonclick', (e) => {
  console.log('Клик по кнопке контрола')
})

fullscreenControl.events.add('create', (e) => {
  console.log('Контрол создан и добавлен на карту')
})
```

### Vue события

```vue
<FullscreenControlVue
  @fullscreenenter="onEnter"
  @fullscreenexit="onExit"
  @click="onClick"
  @statechange="onStateChange"
/>

<script setup>
function onEnter() {
  console.log('Вход в полноэкранный режим')
}

function onExit() {
  console.log('Выход из полноэкранного режима')
}

function onClick() {
  console.log('Клик по контролу')
}

function onStateChange(state) {
  console.log('Изменение состояния:', state.isFullscreen)
}
</script>
```

## 🎨 Кастомизация стилей

### CSS переменные (Vue компонент)

```css
.ymaps-fullscreen-control {
  /* Размеры */
  --control-size: 36px;
  --control-border-radius: 6px;
  
  /* Цвета */
  --control-bg: #ffffff;
  --control-border: #e5e7eb;
  --control-text: #374151;
  --control-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  
  /* Активное состояние */
  --control-active-bg: #3b82f6;
  --control-active-text: #ffffff;
  --control-active-border: #3b82f6;
  
  /* Анимации */
  --control-transition: all 0.2s ease-in-out;
}
```

### Кастомные иконки

```javascript
const fullscreenControl = new FullscreenControl({
  icons: {
    enter: '🔍', // Любые Unicode символы
    exit: '❌',
    // Или HTML
    enter: '<svg>...</svg>',
    exit: '<i class="icon-exit"></i>'
  }
})
```

## 🌐 Браузерная совместимость

| Браузер | Поддержка | API |
|---------|-----------|-----|
| Chrome 71+ | ✅ Full | `requestFullscreen` |
| Firefox 64+ | ✅ Full | `mozRequestFullScreen` |
| Safari 12+ | ✅ Full | `webkitRequestFullscreen` |
| Edge 79+ | ✅ Full | `requestFullscreen` |
| IE 11 | ⚠️ Limited | `msRequestFullscreen` |

### Проверка поддержки

```javascript
import { isFullscreenSupported } from './FullscreenControl.js'

if (isFullscreenSupported()) {
  // Создавать контрол
} else {
  console.warn('Fullscreen API не поддерживается')
}
```

## 📱 Мобильная адаптивность

- **Touch-friendly** размеры кнопок (минимум 44px)
- **Responsive дизайн** автоматически адаптируется
- **iOS Safari** особая обработка viewport
- **Android Chrome** поддержка жестов

```css
@media (max-width: 768px) {
  .ymaps-fullscreen-control {
    width: 48px !important;
    height: 48px !important;
    font-size: 18px;
  }
}
```

## ♿ Accessibility (WCAG 2.1 AA)

- **ARIA атрибуты** полная поддержка
- **Клавиатурное управление** Enter, Space, F11
- **Screen readers** семантическая разметка
- **High contrast** автоматическая поддержка
- **Reduced motion** отключение анимаций по запросу

```javascript
// Программное управление accessibility
fullscreenControl.setAccessible(true)
fullscreenControl.setAriaLabel('Переключить полноэкранный режим карты')
```

## 🔧 Расширенная конфигурация

### Интеграция с другими контролами

```javascript
// Совместная работа с ZoomControl
const fullscreen = new FullscreenControl({ position: 'topRight' })
const zoom = new ZoomControl({ position: 'topLeft' })

// Автоматическое скрытие при входе в fullscreen
fullscreen.events.add('fullscreenenter', () => {
  zoom.setVisible(false)
})

fullscreen.events.add('fullscreenexit', () => {
  zoom.setVisible(true)
})
```

### Persistence состояния

```javascript
// Сохранение состояния в localStorage
fullscreenControl.events.add('statechange', (e) => {
  localStorage.setItem('mapFullscreen', JSON.stringify({
    isFullscreen: e.isFullscreen,
    timestamp: Date.now()
  }))
})

// Восстановление при загрузке
const savedState = JSON.parse(localStorage.getItem('mapFullscreen') || '{}')
if (savedState.isFullscreen && Date.now() - savedState.timestamp < 3600000) {
  await fullscreenControl.enterFullscreen()
}
```

## 🐛 Отладка и диагностика

### Debug режим

```javascript
const fullscreenControl = new FullscreenControl({
  debug: true // Включает детальное логирование
})

// Диагностическая информация
console.log('API поддержка:', fullscreenControl.getApiSupport())
console.log('Текущее состояние:', fullscreenControl.getState())
console.log('События браузера:', fullscreenControl.getEventHistory())
```

### Типичные проблемы

```javascript
// Проблема: контрол не отображается
if (!fullscreenControl.isVisible()) {
  console.log('Причины:')
  console.log('- API не поддерживается:', !fullscreenControl.isSupported())
  console.log('- visible=false:', !fullscreenControl._options.visible)
  console.log('- Контейнер не найден:', !fullscreenControl._map)
}

// Проблема: fullscreen не работает
fullscreenControl.events.add('error', (e) => {
  console.error('Ошибка Fullscreen API:', e.error)
  // Показать fallback UI или уведомление
})
```

## 📈 Производительность

- **Lazy loading** контрол создается только при необходимости
- **Event debouncing** оптимизация частых событий
- **Memory cleanup** автоматическая очистка при размонтировании
- **Minimal DOM** только один элемент в DOM дереве

```javascript
// Отслеживание производительности
const observer = new PerformanceObserver((list) => {
  for (const entry of list.getEntries()) {
    if (entry.name.includes('fullscreen')) {
      console.log(`Fullscreen ${entry.name}: ${entry.duration}ms`)
    }
  }
})

observer.observe({ entryTypes: ['measure'] })
```

## 🧪 Тестирование

### Unit тесты (Jest/Vitest)

```javascript
import { describe, it, expect, vi } from 'vitest'
import FullscreenControl from './FullscreenControl.js'

describe('FullscreenControl', () => {
  it('should create control with default options', () => {
    const control = new FullscreenControl()
    expect(control.getState().position).toBe('topRight')
  })

  it('should toggle fullscreen mode', async () => {
    const control = new FullscreenControl()
    const mockMap = { container: { getElement: () => document.body } }
    
    await control.addToMap(mockMap)
    await control.toggleFullscreen()
    
    expect(control.isFullscreen()).toBe(true)
  })
})
```

### E2E тесты (Playwright)

```javascript
test('fullscreen control interaction', async ({ page }) => {
  await page.goto('/map')
  
  const fullscreenButton = page.locator('.ymaps-fullscreen-control')
  await expect(fullscreenButton).toBeVisible()
  
  await fullscreenButton.click()
  await expect(page.locator(':fullscreen')).toBeVisible()
  
  await page.keyboard.press('Escape')
  await expect(page.locator(':fullscreen')).not.toBeVisible()
})
```

## 📚 Примеры использования

### Базовая интеграция

```html
<!DOCTYPE html>
<html>
<head>
  <script src="https://api-maps.yandex.ru/2.1/?apikey=YOUR_KEY&lang=ru_RU"></script>
</head>
<body>
  <div id="map" style="width: 100%; height: 400px;"></div>
  
  <script type="module">
    import FullscreenControl from './FullscreenControl.js'
    
    ymaps.ready(async () => {
      const map = new ymaps.Map('map', {
        center: [55.76, 37.64],
        zoom: 10
      })
      
      const fullscreenControl = new FullscreenControl({
        position: 'topRight'
      })
      
      await fullscreenControl.addToMap(map)
    })
  </script>
</body>
</html>
```

### Vue 3 приложение

```vue
<template>
  <div class="map-container">
    <YandexMap 
      ref="mapRef"
      :settings="mapSettings"
      @ready="onMapReady"
    >
      <FullscreenControlVue
        :map="mapInstance"
        position="topRight"
        :size="{ width: 40, height: 40 }"
        @fullscreenenter="trackFullscreenEnter"
        @fullscreenexit="trackFullscreenExit"
      />
    </YandexMap>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import FullscreenControlVue from './FullscreenControl.vue'

const mapInstance = ref(null)
const mapSettings = {
  center: [55.76, 37.64],
  zoom: 10
}

function onMapReady(map: any) {
  mapInstance.value = map
}

function trackFullscreenEnter() {
  // Аналитика или другая логика
  gtag('event', 'fullscreen_enter', {
    event_category: 'map_interaction'
  })
}

function trackFullscreenExit() {
  gtag('event', 'fullscreen_exit', {
    event_category: 'map_interaction'
  })
}
</script>
```

## 🔗 Связанные компоненты

- **[ZoomControl](../ZoomControl/README.md)** - Контрол масштабирования
- **[TypeSelector](../TypeSelector/README.md)** - Выбор типа карты
- **[GeolocationControl](../GeolocationControl/README.md)** - Геолокация

## 📄 Лицензия

MIT License - свободное использование в коммерческих и некоммерческих проектах.

---

**Создано с ❤️ по принципам CLAUDE.md: KISS, SOLID, DRY, Test-First**

*Версия 1.0.0 | Обновлено: 2025-09-04*