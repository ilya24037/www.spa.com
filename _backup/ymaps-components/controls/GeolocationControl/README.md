# GeolocationControl - Контрол геолокации пользователя

Высокопроизводительный компонент для определения местоположения пользователя на Яндекс Картах с поддержкой Vue 3, TypeScript и принципов CLAUDE.md.

## 🎯 Особенности

- ✅ **Production-ready** код с полной обработкой ошибок
- ✅ **TypeScript** строгая типизация без any типов
- ✅ **Vue 3 + Composition API** современный подход
- ✅ **HTML5 Geolocation API** кроссбраузерная совместимость
- ✅ **Accessibility** поддержка клавиатурного управления и ARIA
- ✅ **Mobile-первый** дизайн с touch-friendly интерфейсом
- ✅ **Автоматические геометки** с кругом точности
- ✅ **Умное позиционирование** карты на основе точности GPS
- ✅ **События и состояния** полный набор для интеграции

## 📦 Установка

### JavaScript модули (ES6)
```javascript
import GeolocationControl from './GeolocationControl.js'

// Создание контрола
const geolocationControl = new GeolocationControl({
  position: 'topLeft',
  size: { width: 36, height: 36 },
  noPlacemark: false,
  mapStateAutoApply: true,
  geolocationOptions: {
    enableHighAccuracy: true,
    timeout: 10000,
    maximumAge: 300000
  }
})

// Добавление на карту
await geolocationControl.addToMap(map)
```

### Vue 3 компонент
```vue
<template>
  <YandexMap>
    <GeolocationControlVue
      :map="mapInstance"
      position="topLeft"
      :visible="true"
      :map-state-auto-apply="true"
      @locationchange="onLocationChange"
      @locationerror="onLocationError"
    />
  </YandexMap>
</template>

<script setup>
import GeolocationControlVue from './GeolocationControl.vue'

function onLocationChange({ position, geoObjects }) {
  console.log('Местоположение найдено:', position)
  console.log('Созданы геообъекты:', geoObjects)
}

function onLocationError(error) {
  console.error('Ошибка геолокации:', error.message)
}
</script>
```

## ⚙️ API Документация

### Конструктор JavaScript

```typescript
interface GeolocationControlOptions {
  position?: 'topLeft' | 'topRight' | 'bottomLeft' | 'bottomRight'
  adjustMapMargin?: boolean
  zIndex?: number
  size?: { width: number; height: number }
  title?: string
  noPlacemark?: boolean
  useMapMargin?: boolean
  mapStateAutoApply?: boolean
  geolocationOptions?: {
    enableHighAccuracy?: boolean
    timeout?: number
    maximumAge?: number
  }
}
```

### Props Vue компонента

```typescript
interface Props {
  position?: 'topLeft' | 'topRight' | 'bottomLeft' | 'bottomRight' // 'topLeft'
  visible?: boolean                                                 // true
  size?: { width: number; height: number }                         // { width: 36, height: 36 }
  zIndex?: number                                                   // 1000
  title?: string                                                    // ''
  noPlacemark?: boolean                                             // false - создавать метку
  useMapMargin?: boolean                                            // true
  mapStateAutoApply?: boolean                                       // true - автоцентр карты
  geolocationOptions?: GeolocationOptions                          // настройки HTML5 API
  map?: YandexMap                                                   // экземпляр карты
}
```

## 🎮 Основные методы

### JavaScript API

```javascript
// Основные методы определения местоположения
const position = await geolocationControl.getCurrentPosition()  // Однократное определение
const result = await geolocationControl.locate()               // С созданием меток на карте

// Отслеживание позиции (живое)
const watchId = geolocationControl.watchPosition((position, error) => {
  if (error) {
    console.error('Ошибка:', error.message)
  } else {
    console.log('Новая позиция:', position)
  }
})

geolocationControl.clearWatch()                                 // Остановка отслеживания

// Управление состоянием
geolocationControl.getControlState()                           // 'ready' | 'pending' | 'error'
geolocationControl.getLastKnownPosition()                      // Последняя известная позиция
geolocationControl.getState()                                  // Полное состояние контрола
```

### Vue API (через ref)

```vue
<template>
  <GeolocationControlVue ref="geoRef" />
</template>

<script setup>
const geoRef = ref()

// Методы доступны через ref
const position = await geoRef.value.getCurrentPosition()
await geoRef.value.locate()
const lastPosition = geoRef.value.getLastKnownPosition()
const state = geoRef.value.getControlState() // 'ready' | 'pending' | 'error'
</script>
```

## 📡 События

### JavaScript события

```javascript
// Успешное определение местоположения
geolocationControl.events.add('locationchange', (e) => {
  console.log('Координаты:', e.position.coords)
  console.log('Точность:', e.position.accuracy + ' м')
  console.log('Геообъекты:', e.geoObjects)
  console.log('Время:', new Date(e.position.timestamp))
})

// Ошибка определения местоположения
geolocationControl.events.add('locationerror', (e) => {
  console.error('Код ошибки:', e.error.code)
  console.error('Сообщение:', e.error.message)
  
  switch (e.error.code) {
    case 'PERMISSION_DENIED':
      showPermissionDialog()
      break
    case 'POSITION_UNAVAILABLE':
      showOfflineMessage()
      break
    case 'TIMEOUT':
      showRetryButton()
      break
  }
})

// Изменение состояния контрола
geolocationControl.events.add('statechange', (e) => {
  console.log('Новое состояние:', e.state)
  console.log('Идет определение:', e.isLocating)
  
  if (e.state === 'pending') {
    showLoadingIndicator()
  } else {
    hideLoadingIndicator()
  }
})

// Нажатие на кнопку
geolocationControl.events.add('press', () => {
  trackEvent('geolocation_button_click')
})
```

### Vue события

```vue
<GeolocationControlVue
  @locationchange="onLocationChange"
  @locationerror="onLocationError"
  @statechange="onStateChange"
  @press="onPress"
  @click="onClick"
/>

<script setup>
function onLocationChange({ position, geoObjects }) {
  console.log('Найдено местоположение:')
  console.log('- Координаты:', position.coords)
  console.log('- Точность:', position.accuracy + ' м')
  console.log('- Высота:', position.altitude + ' м')
  console.log('- Скорость:', position.speed * 3.6 + ' км/ч')
}

function onLocationError(error) {
  switch (error.code) {
    case 'PERMISSION_DENIED':
      showNotification('Разрешите доступ к геолокации', 'error')
      break
    case 'POSITION_UNAVAILABLE':
      showNotification('GPS недоступен', 'warning')  
      break
    case 'TIMEOUT':
      showNotification('Превышено время ожидания', 'info')
      break
  }
}

function onStateChange({ state, isLocating }) {
  if (isLocating) {
    startLoadingAnimation()
  } else {
    stopLoadingAnimation()
  }
}
</script>
```

## 🛠️ Настройка геолокации

### Опции HTML5 Geolocation API

```javascript
const geolocationControl = new GeolocationControl({
  geolocationOptions: {
    // Запрашивать максимальную точность (GPS, медленнее)
    enableHighAccuracy: true,
    
    // Таймаут запроса (10 секунд)
    timeout: 10000,
    
    // Использовать кешированные данные не старше 5 минут
    maximumAge: 300000,
  }
})

// Оптимальные настройки для разных сценариев

// Быстрое определение (сеть, низкая точность)
const quickGeoOptions = {
  enableHighAccuracy: false,
  timeout: 5000,
  maximumAge: 600000 // 10 минут
}

// Точное определение (GPS, высокая точность)
const preciseGeoOptions = {
  enableHighAccuracy: true,
  timeout: 30000,
  maximumAge: 60000  // 1 минута
}

// Экономия батареи (кеш, редкие обновления)
const batterySavingOptions = {
  enableHighAccuracy: false,
  timeout: 15000,
  maximumAge: 1800000 // 30 минут
}
```

### Адаптивная конфигурация

```javascript
// Определение настроек на основе устройства
function getOptimalGeolocationOptions() {
  const isDesktop = !('ontouchstart' in window)
  const isSlowConnection = navigator.connection?.effectiveType === 'slow-2g'
  
  if (isDesktop) {
    return {
      enableHighAccuracy: false, // Обычно нет GPS
      timeout: 8000,
      maximumAge: 600000
    }
  } else if (isSlowConnection) {
    return {
      enableHighAccuracy: false,
      timeout: 15000,
      maximumAge: 1800000 // Больше кеширования
    }
  } else {
    return {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 300000
    }
  }
}

const geolocationControl = new GeolocationControl({
  geolocationOptions: getOptimalGeolocationOptions()
})
```

## 🗺️ Кастомизация геообъектов

### Настройка метки местоположения

```javascript
const geolocationControl = new GeolocationControl({
  noPlacemark: false // Создавать метки
})

// Кастомизация через события
geolocationControl.events.add('locationchange', (e) => {
  const placemark = e.geoObjects.get(0) // Первый объект - метка
  
  // Изменение иконки
  placemark.options.set({
    preset: 'islands#blueDotIcon',
    iconColor: '#00ff00'
  })
  
  // Кастомный балун
  placemark.properties.set({
    balloonContentHeader: 'Вы здесь!',
    balloonContentBody: createCustomBalloonContent(e.position),
    hintContent: 'Ваше текущее местоположение'
  })
})

function createCustomBalloonContent(position) {
  const accuracy = Math.round(position.accuracy)
  const time = new Date(position.timestamp).toLocaleString()
  
  return `
    <div style="max-width: 250px;">
      <h4 style="margin: 0 0 8px;">📍 Ваше местоположение</h4>
      <p><strong>Координаты:</strong><br>
         ${position.coords[0].toFixed(6)}, ${position.coords[1].toFixed(6)}</p>
      <p><strong>Точность:</strong> ±${accuracy} м</p>
      <p><strong>Время:</strong> ${time}</p>
      ${position.altitude ? `<p><strong>Высота:</strong> ${Math.round(position.altitude)} м</p>` : ''}
      ${position.speed && position.speed > 0 ? 
        `<p><strong>Скорость:</strong> ${Math.round(position.speed * 3.6)} км/ч</p>` : ''}
    </div>
  `
}
```

### Создание собственных геообъектов

```javascript
// Отключить автоматические метки
const geolocationControl = new GeolocationControl({
  noPlacemark: true
})

// Создать собственные геообъекты
geolocationControl.events.add('locationchange', (e) => {
  const { position } = e
  const map = geolocationControl.getMap()
  
  // Кастомная метка с анимацией
  const placemark = new ymaps.Placemark(position.coords, {
    hintContent: 'Вы здесь'
  }, {
    preset: 'islands#redCircleDotIcon',
    iconColor: '#ff6b6b'
  })
  
  // Пульсирующий круг
  const pulseCircle = new ymaps.Circle([position.coords, 100], {}, {
    fillColor: '#ff6b6b',
    fillOpacity: 0.2,
    strokeColor: '#ff6b6b',
    strokeOpacity: 0.8,
    strokeWidth: 2
  })
  
  // Анимация пульсации
  let radius = 50
  const pulseAnimation = setInterval(() => {
    radius = radius === 50 ? 200 : 50
    pulseCircle.geometry.setRadius(radius)
  }, 1500)
  
  map.geoObjects.add(placemark)
  map.geoObjects.add(pulseCircle)
  
  // Очистка при следующем обновлении позиции
  geolocationControl.events.add('locationchange', () => {
    clearInterval(pulseAnimation)
    map.geoObjects.remove(placemark)
    map.geoObjects.remove(pulseCircle)
  }, { once: true })
})
```

## 🌐 Браузерная совместимость

| Браузер | Поддержка | Особенности |
|---------|-----------|-------------|
| Chrome 5+ | ✅ Full | GPS + Network |
| Firefox 3.5+ | ✅ Full | GPS + Network |
| Safari 5+ | ✅ Full | Требует HTTPS |
| Edge 12+ | ✅ Full | GPS + Network |
| IE 9+ | ⚠️ Limited | Только Network |
| Mobile Safari | ✅ Full | Отличная точность |
| Chrome Mobile | ✅ Full | GPS + Network |

### Проверка поддержки и fallback

```javascript
import { isGeolocationSupported } from './GeolocationControl.js'

if (isGeolocationSupported()) {
  // Создавать контрол
  const geolocationControl = new GeolocationControl()
  await geolocationControl.addToMap(map)
} else {
  // Fallback: использовать IP-геолокацию
  const ipLocation = await getLocationByIP()
  map.setCenter(ipLocation.coords, 10)
  
  showNotification('Геолокация недоступна, показано приблизительное местоположение', 'info')
}

// IP-геолокация как fallback
async function getLocationByIP() {
  try {
    const response = await fetch('https://ipapi.co/json/')
    const data = await response.json()
    
    return {
      coords: [data.latitude, data.longitude],
      accuracy: 50000, // ~50км точность для IP
      source: 'ip'
    }
  } catch (error) {
    // Дефолтная позиция (Москва)
    return {
      coords: [55.76, 37.64],
      accuracy: 100000,
      source: 'default'
    }
  }
}
```

## 📱 Мобильная оптимизация

### Адаптивный дизайн
```css
/* Увеличенная кнопка для touch устройств */
@media (max-width: 768px) {
  .ymaps-geolocation-control {
    width: 48px !important;
    height: 48px !important;
    font-size: 20px;
  }
}

/* Оптимизация для планшетов */
@media (min-width: 768px) and (max-width: 1024px) {
  .ymaps-geolocation-control {
    width: 44px !important;
    height: 44px !important;
  }
}
```

### iOS Safari особенности
```javascript
// Специальная обработка для iOS
const geolocationOptions = {
  enableHighAccuracy: true,
  timeout: 15000, // Увеличенный таймаут для iOS
  maximumAge: 300000
}

// Обработка ошибки разрешений на iOS
geolocationControl.events.add('locationerror', (e) => {
  if (e.error.code === 'PERMISSION_DENIED' && /iPhone|iPad/.test(navigator.userAgent)) {
    showIOSPermissionInstructions()
  }
})

function showIOSPermissionInstructions() {
  const modal = createModal({
    title: 'Разрешите доступ к геолокации',
    content: `
      <p>Для определения местоположения:</p>
      <ol>
        <li>Откройте Настройки iOS</li>
        <li>Найдите Safari</li>
        <li>Включите "Службы геолокации"</li>
        <li>Перезагрузите страницу</li>
      </ol>
    `
  })
  modal.show()
}
```

## ♿ Accessibility (WCAG 2.1 AA)

### Полная поддержка accessibility

```javascript
const geolocationControl = new GeolocationControl({
  title: 'Найти мое местоположение на карте'
})

// Программное управление ARIA атрибутами
geolocationControl.events.add('statechange', (e) => {
  const button = geolocationControl.getButton()
  
  switch (e.state) {
    case 'pending':
      button.setAttribute('aria-label', 'Определение местоположения, пожалуйста подождите')
      button.setAttribute('aria-busy', 'true')
      break
    case 'ready':
      button.setAttribute('aria-label', 'Определить мое местоположение')
      button.setAttribute('aria-busy', 'false')
      break
    case 'error':
      button.setAttribute('aria-label', 'Ошибка определения местоположения, повторить попытку')
      button.setAttribute('aria-busy', 'false')
      break
  }
})

// Screen reader поддержка
geolocationControl.events.add('locationchange', (e) => {
  const accuracy = Math.round(e.position.accuracy)
  const announcement = `Местоположение найдено с точностью ${accuracy} метров`
  
  announceToScreenReader(announcement)
})

function announceToScreenReader(message) {
  const announcement = document.createElement('div')
  announcement.setAttribute('aria-live', 'polite')
  announcement.setAttribute('aria-atomic', 'true')
  announcement.className = 'sr-only'
  announcement.textContent = message
  
  document.body.appendChild(announcement)
  
  setTimeout(() => {
    document.body.removeChild(announcement)
  }, 1000)
}
```

### Клавиатурное управление

```javascript
// Расширенное клавиатурное управление
geolocationControl.events.add('keydown', (e) => {
  switch (e.key) {
    case 'Enter':
    case ' ': // Пробел
      e.preventDefault()
      geolocationControl.locate()
      break
    case 'Escape':
      if (geolocationControl.getControlState() === 'pending') {
        geolocationControl.cancelCurrentRequest()
      }
      break
  }
})

// Навигация с помощью клавиатуры
document.addEventListener('keydown', (e) => {
  if (e.ctrlKey && e.key === 'l') { // Ctrl+L
    e.preventDefault()
    geolocationControl.locate()
    geolocationControl.focus()
  }
})
```

## 🔧 Расширенные возможности

### Persistence позиции в localStorage

```javascript
class PersistentGeolocationControl extends GeolocationControl {
  constructor(options) {
    super(options)
    this.restoreLastPosition()
  }
  
  async locate() {
    const result = await super.locate()
    
    if (result) {
      this.savePosition(result.position)
    }
    
    return result
  }
  
  savePosition(position) {
    const data = {
      position,
      timestamp: Date.now(),
      version: '1.0'
    }
    
    localStorage.setItem('geolocation_last_position', JSON.stringify(data))
  }
  
  restoreLastPosition() {
    try {
      const saved = localStorage.getItem('geolocation_last_position')
      if (!saved) return
      
      const data = JSON.parse(saved)
      const age = Date.now() - data.timestamp
      
      // Использовать позицию если младше 1 часа
      if (age < 3600000) {
        this.events.fire('locationchange', {
          position: data.position,
          source: 'cache'
        })
      }
    } catch (error) {
      console.warn('Ошибка восстановления позиции:', error)
    }
  }
}
```

### Батч-геолокация с debounce

```javascript
class BatchGeolocationControl extends GeolocationControl {
  constructor(options) {
    super(options)
    this.pendingRequests = []
    this.debounceTimeout = null
  }
  
  // Группировка запросов для экономии батареи
  async locate() {
    return new Promise((resolve, reject) => {
      this.pendingRequests.push({ resolve, reject })
      
      // Debounce: выполнить через 300ms если нет новых запросов
      if (this.debounceTimeout) {
        clearTimeout(this.debounceTimeout)
      }
      
      this.debounceTimeout = setTimeout(() => {
        this.executeBatchedLocation()
      }, 300)
    })
  }
  
  async executeBatchedLocation() {
    const requests = [...this.pendingRequests]
    this.pendingRequests = []
    
    try {
      const result = await super.locate()
      
      // Отправить результат всем ожидающим
      requests.forEach(({ resolve }) => resolve(result))
    } catch (error) {
      // Отправить ошибку всем ожидающим
      requests.forEach(({ reject }) => reject(error))
    }
  }
}
```

### Интеграция с аналитикой

```javascript
// Аналитика использования геолокации
geolocationControl.events.add('locationchange', (e) => {
  // Успешное определение
  gtag('event', 'geolocation_success', {
    event_category: 'map_interaction',
    custom_parameters: {
      accuracy: Math.round(e.position.accuracy),
      has_altitude: e.position.altitude !== null,
      response_time: Date.now() - e.requestStartTime
    }
  })
})

geolocationControl.events.add('locationerror', (e) => {
  // Ошибки геолокации
  gtag('event', 'geolocation_error', {
    event_category: 'map_interaction',
    custom_parameters: {
      error_code: e.error.code,
      error_message: e.error.message,
      user_agent: navigator.userAgent
    }
  })
})

// Производительность
let locationStartTime = 0

geolocationControl.events.add('statechange', (e) => {
  if (e.state === 'pending') {
    locationStartTime = Date.now()
  } else if (e.state === 'ready' && locationStartTime) {
    const duration = Date.now() - locationStartTime
    
    // Отправить метрику производительности
    gtag('event', 'timing_complete', {
      name: 'geolocation_duration',
      value: duration
    })
  }
})
```

## 🧪 Тестирование

### Unit тесты (Jest/Vitest)

```javascript
import { describe, it, expect, vi, beforeEach } from 'vitest'
import GeolocationControl from './GeolocationControl.js'

// Мокаем Geolocation API
const mockGeolocation = {
  getCurrentPosition: vi.fn(),
  watchPosition: vi.fn(),
  clearWatch: vi.fn()
}

Object.defineProperty(global.navigator, 'geolocation', {
  value: mockGeolocation
})

describe('GeolocationControl', () => {
  let control
  let mockMap

  beforeEach(() => {
    control = new GeolocationControl()
    mockMap = {
      container: { getElement: () => document.body },
      geoObjects: { add: vi.fn(), remove: vi.fn() },
      setCenter: vi.fn()
    }
    vi.clearAllMocks()
  })

  it('should create control with default options', () => {
    expect(control.getControlState()).toBe('ready')
    expect(control.isVisible()).toBe(true)
  })

  it('should handle successful geolocation', async () => {
    const mockPosition = {
      coords: {
        latitude: 55.7558,
        longitude: 37.6173,
        accuracy: 10
      },
      timestamp: Date.now()
    }

    mockGeolocation.getCurrentPosition.mockImplementationOnce((success) => {
      setTimeout(() => success(mockPosition), 100)
    })

    await control.addToMap(mockMap)
    const result = await control.locate()

    expect(result.position.coords).toEqual([55.7558, 37.6173])
    expect(mockMap.setCenter).toHaveBeenCalled()
  })

  it('should handle geolocation errors', async () => {
    const mockError = {
      code: 1, // PERMISSION_DENIED
      message: 'User denied geolocation'
    }

    mockGeolocation.getCurrentPosition.mockImplementationOnce((success, error) => {
      setTimeout(() => error(mockError), 100)
    })

    await control.addToMap(mockMap)
    
    await expect(control.locate()).rejects.toMatchObject({
      code: 'PERMISSION_DENIED',
      message: expect.stringContaining('запрещен')
    })
  })
})
```

### E2E тесты (Playwright)

```javascript
import { test, expect } from '@playwright/test'

test.describe('GeolocationControl', () => {
  test.beforeEach(async ({ page, context }) => {
    // Разрешить геолокацию для тестов
    await context.grantPermissions(['geolocation'])
    await context.setGeolocation({ latitude: 55.7558, longitude: 37.6173 })
  })

  test('should show geolocation button', async ({ page }) => {
    await page.goto('/map')
    
    const geoButton = page.locator('.ymaps-geolocation-control')
    await expect(geoButton).toBeVisible()
    await expect(geoButton).toHaveAttribute('title', /местоположение/i)
  })

  test('should determine location on click', async ({ page }) => {
    await page.goto('/map')
    
    const geoButton = page.locator('.ymaps-geolocation-control')
    await geoButton.click()
    
    // Ждем появления спиннера
    await expect(page.locator('.ymaps-geolocation-spinner')).toBeVisible()
    
    // Ждем завершения определения местоположения
    await expect(page.locator('.ymaps-geolocation-spinner')).toBeHidden()
    
    // Проверяем что карта переместилась
    const mapCenter = await page.evaluate(() => {
      return window.map.getCenter()
    })
    
    expect(mapCenter[0]).toBeCloseTo(55.7558, 2)
    expect(mapCenter[1]).toBeCloseTo(37.6173, 2)
  })

  test('should handle permission denied', async ({ page, context }) => {
    // Запретить геолокацию
    await context.clearPermissions()
    await page.goto('/map')
    
    const geoButton = page.locator('.ymaps-geolocation-control')
    await geoButton.click()
    
    // Проверить ошибочное состояние
    await expect(geoButton).toHaveClass(/error/)
    
    // Проверить сообщение об ошибке
    const errorMessage = page.locator('.error-notification')
    await expect(errorMessage).toContainText(/разреш/i)
  })
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
    import GeolocationControl from './GeolocationControl.js'
    
    ymaps.ready(async () => {
      const map = new ymaps.Map('map', {
        center: [55.76, 37.64],
        zoom: 10
      })
      
      const geolocationControl = new GeolocationControl({
        position: 'topLeft'
      })
      
      await geolocationControl.addToMap(map)
      
      // Автоматически определить местоположение при загрузке
      try {
        await geolocationControl.locate()
      } catch (error) {
        console.log('Автоопределение не удалось:', error.message)
      }
    })
  </script>
</body>
</html>
```

### Vue 3 приложение с Pinia

```vue
<template>
  <div class="map-container">
    <YandexMap 
      ref="mapRef"
      :settings="mapSettings"
      @ready="onMapReady"
    >
      <GeolocationControlVue
        :map="mapInstance"
        position="topLeft"
        :geolocation-options="geoOptions"
        @locationchange="handleLocationChange"
        @locationerror="handleLocationError"
        @statechange="handleStateChange"
      />
    </YandexMap>
    
    <!-- Статус панель -->
    <div v-if="locationStore.isLocating" class="location-status">
      Определяется местоположение...
    </div>
    
    <div v-if="locationStore.lastError" class="location-error">
      {{ locationStore.lastError }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useLocationStore } from '@/stores/location'
import GeolocationControlVue from './GeolocationControl.vue'

const locationStore = useLocationStore()
const mapInstance = ref(null)

const mapSettings = {
  center: [55.76, 37.64],
  zoom: 10
}

// Адаптивные настройки геолокации
const geoOptions = computed(() => ({
  enableHighAccuracy: !locationStore.isBatterySaving,
  timeout: locationStore.isBatterySaving ? 15000 : 10000,
  maximumAge: locationStore.isBatterySaving ? 1800000 : 300000
}))

function onMapReady(map: any) {
  mapInstance.value = map
}

function handleLocationChange({ position, geoObjects }) {
  // Сохранить в store
  locationStore.setCurrentPosition(position)
  locationStore.setGeoObjects(geoObjects)
  
  // Аналитика
  gtag('event', 'geolocation_success', {
    event_category: 'user_interaction',
    custom_parameters: {
      accuracy: Math.round(position.accuracy)
    }
  })
  
  // Показать уведомление
  showSuccess(`Местоположение определено с точностью ±${Math.round(position.accuracy)} м`)
}

function handleLocationError(error) {
  locationStore.setError(error.message)
  
  // Аналитика
  gtag('event', 'geolocation_error', {
    event_category: 'user_interaction',
    custom_parameters: {
      error_code: error.code
    }
  })
}

function handleStateChange({ isLocating }) {
  locationStore.setLocatingState(isLocating)
}
</script>

<style scoped>
.map-container {
  position: relative;
  width: 100%;
  height: 500px;
}

.location-status {
  position: absolute;
  top: 60px;
  left: 10px;
  background: rgba(59, 130, 246, 0.9);
  color: white;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 14px;
  z-index: 1001;
}

.location-error {
  position: absolute;
  top: 60px;
  left: 10px;
  background: rgba(220, 38, 38, 0.9);
  color: white;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 14px;
  z-index: 1001;
}
</style>
```

### Интеграция с Pinia Store

```typescript
// stores/location.ts
import { defineStore } from 'pinia'

interface LocationState {
  currentPosition: GeolocationResult | null
  isLocating: boolean
  lastError: string | null
  geoObjects: any
  isBatterySaving: boolean
}

export const useLocationStore = defineStore('location', {
  state: (): LocationState => ({
    currentPosition: null,
    isLocating: false,
    lastError: null,
    geoObjects: null,
    isBatterySaving: false
  }),

  getters: {
    hasPosition: (state) => state.currentPosition !== null,
    
    positionAccuracy: (state) => 
      state.currentPosition ? Math.round(state.currentPosition.accuracy) : null,
      
    positionAge: (state) => {
      if (!state.currentPosition) return null
      return Date.now() - state.currentPosition.timestamp
    },
    
    isPositionFresh: (state) => {
      const age = state.positionAge
      return age !== null && age < 300000 // 5 минут
    }
  },

  actions: {
    setCurrentPosition(position: GeolocationResult) {
      this.currentPosition = position
      this.lastError = null
      
      // Сохранить в localStorage
      localStorage.setItem('lastKnownPosition', JSON.stringify({
        position,
        timestamp: Date.now()
      }))
    },

    setLocatingState(isLocating: boolean) {
      this.isLocating = isLocating
    },

    setError(error: string) {
      this.lastError = error
      this.isLocating = false
    },

    setGeoObjects(geoObjects: any) {
      this.geoObjects = geoObjects
    },

    toggleBatterySaving() {
      this.isBatterySaving = !this.isBatterySaving
    },

    restoreFromStorage() {
      try {
        const saved = localStorage.getItem('lastKnownPosition')
        if (saved) {
          const data = JSON.parse(saved)
          const age = Date.now() - data.timestamp
          
          // Восстановить если младше 1 часа
          if (age < 3600000) {
            this.currentPosition = data.position
          }
        }
      } catch (error) {
        console.warn('Ошибка восстановления позиции:', error)
      }
    }
  }
})
```

## 🔗 Связанные компоненты

- **[FullscreenControl](../FullscreenControl/README.md)** - Полноэкранный режим карты
- **[ZoomControl](../ZoomControl/README.md)** - Контрол масштабирования
- **[TypeSelector](../TypeSelector/README.md)** - Выбор типа карты

## 📄 Лицензия

MIT License - свободное использование в коммерческих и некоммерческих проектах.

---

**Создано с ❤️ по принципам CLAUDE.md: KISS, SOLID, DRY, Test-First**

*Версия 1.0.0 | Обновлено: 2025-09-04*