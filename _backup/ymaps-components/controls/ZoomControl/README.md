# 🎯 ZoomControl - Контрол управления масштабом

Элегантный и функциональный контрол для управления масштабом Yandex Maps с поддержкой кнопок +/-, слайдера и плавной анимации.

## 📋 Особенности

- ✅ **Кнопки управления** - Увеличение/уменьшение масштаба
- ✅ **Интерактивный слайдер** - Плавное изменение зума с drag & drop
- ✅ **Плавная анимация** - Анимированные переходы между уровнями зума  
- ✅ **Адаптивные размеры** - Small, Medium, Large варианты
- ✅ **TypeScript** - Полная типизация без any
- ✅ **Vue 3 поддержка** - Готовый Vue компонент с Composition API
- ✅ **Мобильная адаптация** - Оптимизировано для touch устройств
- ✅ **Production-ready** - Полная обработка ошибок и edge cases

## 🚀 Быстрый старт

### Vanilla JavaScript

```javascript
import ZoomControl from './ZoomControl.js'
import YMapsCore from '../../core/YMapsCore.js'

async function initMap() {
  // Создаем карту
  const mapsCore = new YMapsCore({ apiKey: 'YOUR_API_KEY' })
  await mapsCore.loadAPI()
  const map = await mapsCore.createMap('map')
  
  // Создаем контрол масштаба
  const zoomControl = new ZoomControl({
    size: 'medium',
    position: 'topLeft',
    showButtons: true,
    showSlider: true
  })
  
  // Добавляем на карту
  await zoomControl.addToMap(map)
  
  // Обработчики событий
  zoomControl.on('zoomchange', (event) => {
    console.log(`Зум изменен: ${event.oldZoom} → ${event.newZoom}`)
  })
}
```

### Vue 3 Composition API

```vue
<template>
  <div id="map" style="height: 400px"></div>
  
  <!-- ZoomControl с v-model поддержкой -->
  <ZoomControlVue
    :map="map"
    v-model:zoom="currentZoom"
    :zoom-range="{ min: 5, max: 18 }"
    size="large"
    position="topRight"
    :smooth="true"
    @zoomchange="onZoomChange"
    @ready="onControlReady"
  />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import YMapsCore from '@/ymaps-components/core/YMapsCore'
import ZoomControlVue from '@/ymaps-components/controls/ZoomControl/ZoomControl.vue'

const map = ref(null)
const currentZoom = ref(10)

onMounted(async () => {
  const mapsCore = new YMapsCore({ apiKey: 'YOUR_KEY' })
  await mapsCore.loadAPI()
  map.value = await mapsCore.createMap('map', {
    center: [55.753994, 37.622093],
    zoom: currentZoom.value
  })
})

const onZoomChange = (event) => {
  console.log('Новый зум:', event.newZoom)
}

const onControlReady = (control) => {
  console.log('ZoomControl готов к использованию')
}
</script>
```

## ⚙️ Конфигурация

### Опции конструктора (JavaScript)

```typescript
interface ZoomControlOptions {
  // Основные настройки
  size?: 'small' | 'medium' | 'large'    // Размер контрола
  position?: string                       // Позиция на карте
  showSlider?: boolean                    // Показать слайдер
  showButtons?: boolean                   // Показать кнопки +/-
  
  // Поведение
  zoomDuration?: number                   // Длительность анимации (мс)
  smooth?: boolean                        // Плавная анимация
  step?: number                          // Шаг изменения зума кнопками
  
  // Слайдер
  slider?: {
    continuous?: boolean                  // Непрерывное изменение при drag
  }
  
  // Внешний вид
  visible?: boolean                       // Видимость
  enabled?: boolean                       // Активность
  zIndex?: number                        // Z-index
  margin?: {                             // Отступы
    top?: number
    right?: number  
    bottom?: number
    left?: number
  }
}
```

### Props Vue компонента

```typescript
interface Props {
  map?: any                              // Экземпляр карты
  size?: 'small' | 'medium' | 'large'   // Размер
  position?: string                      // Позиция
  showSlider?: boolean                   // Слайдер
  showButtons?: boolean                  // Кнопки
  visible?: boolean                      // Видимость
  enabled?: boolean                      // Активность
  zoom?: number                          // Текущий зум (v-model)
  zoomRange?: { min: number, max: number } // Диапазон зума
  zoomDuration?: number                  // Анимация
  smooth?: boolean                       // Плавность
  step?: number                         // Шаг
  margin?: object                       // Отступы
  zIndex?: number                       // Z-index
  class?: string | string[] | object    // CSS классы
  style?: string | object               // Inline стили
}
```

## 🔧 API методы

### JavaScript класс

```typescript
class ZoomControl {
  // Управление зумом
  getZoom(): number                               // Текущий зум
  setZoom(zoom: number, options?): Promise<void>  // Установить зум
  zoomIn(): Promise<void>                         // Увеличить
  zoomOut(): Promise<void>                        // Уменьшить
  
  // Диапазон зума
  getZoomRange(): { min: number, max: number }    // Получить диапазон
  setZoomRange(min: number, max: number): void    // Установить диапазон
  
  // Управление видимостью
  show(): void                                    // Показать
  hide(): void                                    // Скрыть
  enable(): void                                  // Включить
  disable(): void                                 // Отключить
  
  // События
  on(event: string, handler: Function): void     // Подписаться
  off(event: string, handler: Function): void    // Отписаться
  
  // Жизненный цикл
  addToMap(map: ymaps.Map): Promise<void>        // Добавить на карту
  removeFromMap(): Promise<void>                  // Удалить с карты
  destroy(): void                                 // Уничтожить
}
```

### Vue компонент (defineExpose)

```typescript
// Методы, доступные через template ref
interface ExposedMethods {
  getControl(): ZoomControl | null        // Получить JS экземпляр
  getZoom(): number                       // Текущий зум
  setZoom(zoom: number): Promise<void>    // Установить зум
  zoomIn(): Promise<void>                 // Увеличить
  zoomOut(): Promise<void>                // Уменьшить
  getZoomRange(): ZoomRange | null        // Диапазон зума
  setZoomRange(min: number, max: number): void // Установить диапазон
  recreate(): Promise<void>               // Пересоздать контрол
}

// Использование в родительском компоненте
const zoomControlRef = ref()

const handleZoomIn = async () => {
  await zoomControlRef.value.zoomIn()
}
```

## 📡 События

### JavaScript

```javascript
zoomControl.on('zoomchange', (event) => {
  console.log('Зум изменен:', event.oldZoom, '→', event.newZoom)
})

zoomControl.on('zoomin', (event) => {
  console.log('Увеличили зум до:', event.zoom)
})

zoomControl.on('zoomout', (event) => {
  console.log('Уменьшили зум до:', event.zoom)
})

// Drag & Drop события слайдера
zoomControl.on('dragstart', (event) => {
  console.log('Начали перетаскивание, зум:', event.zoom)
})

zoomControl.on('drag', (event) => {
  console.log('Перетаскиваем, текущий зум:', event.zoom)
})

zoomControl.on('dragend', (event) => {
  console.log('Закончили перетаскивание, финальный зум:', event.zoom)
})
```

### Vue

```vue
<template>
  <ZoomControlVue
    :map="map"
    v-model:zoom="currentZoom"
    @zoomchange="onZoomChange"
    @zoomin="onZoomIn"
    @zoomout="onZoomOut"
    @dragstart="onDragStart"
    @drag="onDrag"
    @dragend="onDragEnd"
    @error="onError"
    @ready="onReady"
  />
</template>
```

## 🎨 Кастомизация стилей

### CSS переменные

```css
.ymaps-zoom-control {
  --button-size: 34px;        /* Размер кнопок */
  --slider-height: 80px;      /* Высота слайдера */
  --font-size: 14px;          /* Размер шрифта */
}

/* Размеры */
.ymaps-zoom-control--small {
  --button-size: 28px;
  --slider-height: 60px;
  --font-size: 12px;
}

.ymaps-zoom-control--large {
  --button-size: 40px;
  --slider-height: 100px;
  --font-size: 16px;
}
```

### Кастомные стили

```css
/* Изменение цветовой схемы */
.ymaps-zoom-control-button {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.ymaps-zoom-control-button:hover {
  background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

/* Стилизация слайдера */
.ymaps-zoom-control-slider-handle {
  background: #ff6b6b;
  border-color: white;
  box-shadow: 0 2px 8px rgba(255, 107, 107, 0.4);
}

/* Темная тема */
@media (prefers-color-scheme: dark) {
  .ymaps-zoom-control-group {
    background: rgba(30, 30, 30, 0.9);
  }
  
  .ymaps-zoom-control-button {
    color: #fff;
  }
}
```

## 📱 Адаптивность

ZoomControl автоматически адаптируется для мобильных устройств:

```css
@media (max-width: 768px) {
  .ymaps-zoom-control {
    --button-size: 40px;      /* Увеличенные кнопки для touch */
    --font-size: 16px;        /* Больший шрифт */
  }
  
  .ymaps-zoom-control-slider-handle {
    width: 14px;              /* Увеличенный handle */
    height: 14px;
  }
}
```

## 🎯 Продвинутые примеры

### Синхронизация с внешним элементом

```javascript
const zoomControl = new ZoomControl({ size: 'medium' })
const externalZoomDisplay = document.getElementById('zoom-display')

zoomControl.on('zoomchange', (event) => {
  externalZoomDisplay.textContent = `Зум: ${event.newZoom}`
})

// Внешняя кнопка управления
document.getElementById('external-zoom-in').addEventListener('click', () => {
  zoomControl.zoomIn()
})
```

### Ограничение диапазона по регионам

```javascript
const regionZoomLimits = {
  moscow: { min: 10, max: 18 },
  russia: { min: 4, max: 15 },
  world: { min: 1, max: 12 }
}

map.events.add('boundschange', () => {
  const center = map.getCenter()
  const region = detectRegion(center)
  const limits = regionZoomLimits[region] || regionZoomLimits.world
  
  zoomControl.setZoomRange(limits.min, limits.max)
})
```

### Vue с реактивными ограничениями

```vue
<template>
  <ZoomControlVue
    :map="map"
    v-model:zoom="currentZoom"
    :zoom-range="dynamicZoomRange"
    :enabled="isMapReady && !isLoading"
  />
</template>

<script setup>
import { computed } from 'vue'

const dynamicZoomRange = computed(() => {
  if (selectedRegion.value === 'city') {
    return { min: 12, max: 18 }
  } else if (selectedRegion.value === 'country') {
    return { min: 6, max: 14 }
  }
  return { min: 1, max: 23 }
})
</script>
```

### Интеграция с состоянием приложения (Pinia/Vuex)

```typescript
// store/mapStore.ts
export const useMapStore = defineStore('map', () => {
  const zoom = ref(10)
  const zoomRange = ref({ min: 1, max: 23 })
  
  const setZoom = (newZoom: number) => {
    zoom.value = newZoom
    // Сохранить в localStorage, отправить аналитику, etc.
  }
  
  return { zoom, zoomRange, setZoom }
})

// Component.vue
<template>
  <ZoomControlVue
    :map="map"
    v-model:zoom="mapStore.zoom"
    :zoom-range="mapStore.zoomRange"
    @zoomchange="mapStore.setZoom"
  />
</template>
```

## 🐛 Решение проблем

### Контрол не отображается

```javascript
// Проверьте API ключ
const zoomControl = new ZoomControl({
  // ... options
})

// Убедитесь что карта создана
if (map && map.container) {
  await zoomControl.addToMap(map)
} else {
  console.error('Карта не готова или не существует')
}

// Проверьте CSS стили
const element = zoomControl.getElement()
console.log('Стили элемента:', window.getComputedStyle(element))
```

### Слайдер не реагирует на мышь

```javascript
// Убедитесь что элемент получает события
const zoomControl = new ZoomControl({
  showSlider: true,
  slider: {
    continuous: true  // Включить непрерывное обновление
  }
})

// Проверьте z-index
zoomControl.setOption('zIndex', 1000)
```

### Анимация тормозит

```javascript
// Отключите плавную анимацию на слабых устройствах
const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent)
const zoomControl = new ZoomControl({
  smooth: !isMobile,
  zoomDuration: isMobile ? 0 : 300
})
```

### Vue компонент не обновляется

```vue
<template>
  <!-- Убедитесь что передается корректный map -->
  <ZoomControlVue
    :key="mapKey"  // Принудительное обновление при изменении карты
    :map="map"
    v-model:zoom="zoom"
  />
</template>

<script setup>
// Пересоздание при критических изменениях
watch([mapType, apiKey], async () => {
  mapKey.value++  // Принудительное пересоздание компонента
})
</script>
```

## 🔍 Отладка и диагностика

### Включение debug режима

```javascript
// Глобальный debug режим
window.YMAPS_DEBUG = true

const zoomControl = new ZoomControl({
  // ... options
})

// События для отладки
zoomControl.on('*', (event) => {
  console.log(`[ZoomControl] ${event.type}:`, event)
})
```

### Проверка состояния

```javascript
// Получение информации о состоянии
console.log('Текущий зум:', zoomControl.getZoom())
console.log('Диапазон зума:', zoomControl.getZoomRange())
console.log('Элемент DOM:', zoomControl.getElement())
console.log('Опции:', zoomControl.getOptions())
console.log('Видимость:', zoomControl.isVisible())
console.log('Активность:', zoomControl.isEnabled())
```

## 📚 См. также

- [ControlBase](../ControlBase.js) - Базовый класс для всех контролов
- [controlHelpers](../../utils/controlHelpers.js) - Утилиты для создания контролов
- [TypeSelector](../TypeSelector/) - Контрол переключения типов карт
- [SearchControl](../SearchControl/) - Контрол поиска на карте

---

<div align="center">
  <strong>Создано с ❤️ для SPA Platform</strong><br>
  <sub>ZoomControl v1.0.0 | Production Ready</sub>
</div>