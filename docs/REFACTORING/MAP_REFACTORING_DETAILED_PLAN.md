# 🗺️ МАКСИМАЛЬНО ДЕТАЛЬНЫЙ ПЛАН РЕФАКТОРИНГА КАРТ

## 📋 ОГЛАВЛЕНИЕ
1. [Исходный анализ](#исходный-анализ)
2. [Целевая архитектура](#целевая-архитектура)
3. [Пошаговый план миграции](#пошаговый-план-миграции)
4. [Детальная реализация каждого компонента](#детальная-реализация)
5. [План тестирования](#план-тестирования)
6. [Rollback стратегия](#rollback-стратегия)
7. [Timeline и ресурсы](#timeline)

---

## 🔍 ИСХОДНЫЙ АНАЛИЗ

### Текущее состояние (30 файлов, ~2500 строк)

```
/features/map/
├── composables/ (12 файлов, ~1200 строк)
│   ├── useAddressGeocoding.ts      // 120 строк - геокодирование
│   ├── useGeolocation.ts           // 80 строк - геолокация
│   ├── useMapClustering.ts         // 90 строк - кластеризация
│   ├── useMapController.ts         // 150 строк - контроллер
│   ├── useMapEventHandlers.ts      // 100 строк - обработчики событий
│   ├── useMapInitializer.ts        // 140 строк - инициализация
│   ├── useMapMarkers.ts            // 110 строк - маркеры
│   ├── useMapMethods.ts            // 130 строк - методы
│   ├── useMapMobileOptimization.ts // 70 строк - мобильная оптимизация
│   ├── useMapModes.ts              // 60 строк - режимы карты
│   ├── useMapState.ts              // 90 строк - состояние
│   └── useMapWithMasters.ts        // 60 строк - интеграция с мастерами
├── ui/ (14 компонентов, ~1100 строк)
│   ├── YandexMapBase.vue           // 180 строк - базовый компонент
│   ├── MapStates.vue               // 80 строк - состояния
│   ├── MapControls.vue             // 70 строк - контролы
│   ├── MapMarkers.vue              // 90 строк - маркеры
│   ├── MapMarkersManager.vue       // 100 строк - менеджер маркеров
│   ├── MapMarker.vue               // 60 строк - один маркер
│   ├── MapCenterMarker.vue         // 50 строк - центральный маркер
│   ├── MapAddressTooltip.vue       // 60 строк - тултип адреса
│   ├── MapGeolocationButton.vue    // 40 строк - кнопка геолокации
│   ├── MapView.vue                 // 120 строк - представление карты
│   ├── UniversalMap.vue            // 100 строк - универсальная карта
│   ├── MapSkeleton.vue             // 40 строк - скелетон
│   ├── MapEmptyState.vue           // 30 строк - пустое состояние
│   └── MapErrorState.vue           // 30 строк - состояние ошибки
├── lib/ (3 файла, ~200 строк)
│   ├── yandexMapsLoader.ts         // 100 строк - загрузчик API
│   ├── mapConstants.ts             // 50 строк - константы
│   └── deviceDetector.ts           // 50 строк - детектор устройств
└── types/ (1 файл, ~100 строк)
    └── index.ts                     // типы и интерфейсы
```

### Проблемы текущей архитектуры

#### 1. Over-engineering
- 12 composables с переплетенными зависимостями
- 14 UI компонентов для простой карты
- Слишком много абстракций для базовой функциональности

#### 2. Circular Dependencies
```
useMapController → useMapState → useMapMethods → useMapController
```

#### 3. Performance Issues
- Все composables импортируются сразу (нет lazy loading)
- Bundle size > 200KB только для карты
- Время инициализации > 2 секунды

#### 4. Maintenance Hell
- Изменение в одном composable требует проверки всех зависимых
- Сложно добавить новую функциональность
- Невозможно протестировать изолированно

#### 5. Текущие баги
- Карта не инициализируется на странице создания объявления
- Маркеры не обновляются при изменении данных
- Геолокация работает только на десктопе

---

## 🎯 ЦЕЛЕВАЯ АРХИТЕКТУРА

### Паттерн "Ядро + Плагины" (12 файлов, ~650 строк)

```
/features/map/
├── core/ (3 файла, ~280 строк)
│   ├── MapCore.vue          // 150 строк - минимальное ядро
│   ├── MapLoader.ts         // 50 строк - singleton загрузчик
│   └── MapStore.ts          // 80 строк - состояние карты
├── plugins/ (4 файла, ~220 строк)
│   ├── ClusterPlugin.ts     // 60 строк - кластеризация
│   ├── GeolocationPlugin.ts // 40 строк - геолокация
│   ├── SearchPlugin.ts      // 50 строк - поиск и геокодинг
│   └── MarkersPlugin.ts     // 70 строк - управление маркерами
├── components/ (3 файла, ~190 строк)
│   ├── MapContainer.vue     // 100 строк - главный контейнер
│   ├── MapControls.vue      // 50 строк - UI контролы
│   └── MapStates.vue        // 40 строк - состояния (loading/error)
└── utils/ (2 файла, ~80 строк)
    ├── mapConstants.ts       // 30 строк - константы
    └── mapHelpers.ts         // 50 строк - утилиты
```

### Преимущества новой архитектуры

1. **Модульность** - плагины подключаются по необходимости
2. **Производительность** - lazy loading плагинов
3. **Тестируемость** - каждый плагин тестируется отдельно
4. **Расширяемость** - легко добавить новый плагин
5. **Простота** - минимальное ядро, вся сложность в плагинах

---

## 📝 ПОШАГОВЫЙ ПЛАН МИГРАЦИИ

### ФАЗА 0: ПОДГОТОВКА (День 0, 4 часа)

#### Шаг 0.1: Создание тестового окружения (30 минут)

```bash
# 1. Создать бранч
git checkout -b feature/map-refactoring-core-plugins

# 2. Создать директорию для бэкапа
mkdir resources/js/src/features/map_backup
cp -r resources/js/src/features/map/* resources/js/src/features/map_backup/

# 3. Создать тестовую страницу
touch public/test-map-refactoring.html
```

#### Шаг 0.2: Написание E2E тестов текущей функциональности (2 часа)

```javascript
// tests/e2e/map-before-refactoring.spec.js
import { test, expect } from '@playwright/test'

test.describe('Карта ДО рефакторинга', () => {
  test('загружается на странице создания объявления', async ({ page }) => {
    await page.goto('/ad/create')
    await page.waitForSelector('.yandex-map', { timeout: 5000 })
    
    // Проверяем что Yandex Maps загрузился
    const hasYmaps = await page.evaluate(() => window.ymaps !== undefined)
    expect(hasYmaps).toBe(true)
    
    // Проверяем что карта видима
    const mapVisible = await page.isVisible('.yandex-map')
    expect(mapVisible).toBe(true)
  })

  test('реагирует на клик и устанавливает маркер', async ({ page }) => {
    await page.goto('/ad/create')
    await page.waitForSelector('.yandex-map')
    
    // Кликаем по карте
    await page.click('.yandex-map', { position: { x: 200, y: 200 } })
    
    // Проверяем что координаты записались
    const geoValue = await page.inputValue('[name="geo"]')
    expect(geoValue).toMatch(/\d+\.\d+,\d+\.\d+/)
  })

  test('показывает адрес после установки маркера', async ({ page }) => {
    await page.goto('/ad/create')
    await page.waitForSelector('.yandex-map')
    
    // Устанавливаем маркер
    await page.click('.yandex-map', { position: { x: 200, y: 200 } })
    
    // Ждем появления адреса
    await page.waitForSelector('[data-test="address-display"]')
    const address = await page.textContent('[data-test="address-display"]')
    expect(address).not.toBe('')
  })

  test('геолокация определяет текущее местоположение', async ({ page, context }) => {
    // Даем разрешение на геолокацию
    await context.grantPermissions(['geolocation'])
    await context.setGeolocation({ latitude: 59.9311, longitude: 30.3609 })
    
    await page.goto('/ad/create')
    await page.waitForSelector('.yandex-map')
    
    // Нажимаем кнопку геолокации
    await page.click('[data-test="geolocation-button"]')
    
    // Проверяем что карта переместилась
    await page.waitForTimeout(1000)
    const geoValue = await page.inputValue('[name="geo"]')
    expect(geoValue).toContain('59.93')
  })

  test('работает поиск по адресу', async ({ page }) => {
    await page.goto('/ad/create')
    await page.waitForSelector('.yandex-map')
    
    // Вводим адрес
    await page.fill('[data-test="address-search"]', 'Москва, Красная площадь')
    await page.press('[data-test="address-search"]', 'Enter')
    
    // Ждем обновления карты
    await page.waitForTimeout(2000)
    
    // Проверяем что координаты обновились
    const geoValue = await page.inputValue('[name="geo"]')
    expect(geoValue).toContain('55.75') // широта Москвы
  })
})

// tests/e2e/map-masters.spec.js
test.describe('Карта мастеров', () => {
  test('показывает маркеры мастеров', async ({ page }) => {
    await page.goto('/masters/map')
    await page.waitForSelector('.yandex-map')
    
    // Ждем загрузки маркеров
    await page.waitForSelector('[data-test="master-marker"]')
    
    // Считаем маркеры
    const markers = await page.$$('[data-test="master-marker"]')
    expect(markers.length).toBeGreaterThan(0)
  })

  test('кластеризует маркеры при зуме', async ({ page }) => {
    await page.goto('/masters/map')
    await page.waitForSelector('.yandex-map')
    
    // Уменьшаем зум
    for (let i = 0; i < 5; i++) {
      await page.keyboard.press('Minus')
      await page.waitForTimeout(300)
    }
    
    // Проверяем наличие кластеров
    const clusters = await page.$$('[data-test="marker-cluster"]')
    expect(clusters.length).toBeGreaterThan(0)
  })

  test('открывает карточку мастера по клику', async ({ page }) => {
    await page.goto('/masters/map')
    await page.waitForSelector('.yandex-map')
    await page.waitForSelector('[data-test="master-marker"]')
    
    // Кликаем по маркеру
    await page.click('[data-test="master-marker"]:first-child')
    
    // Проверяем появление карточки
    await page.waitForSelector('[data-test="master-card-popup"]')
    const cardVisible = await page.isVisible('[data-test="master-card-popup"]')
    expect(cardVisible).toBe(true)
  })
})
```

#### Шаг 0.3: Документирование текущего API (30 минут)

```typescript
// docs/REFACTORING/map-api-before.md

# API компонента YandexMap до рефакторинга

## Props
| Prop | Type | Default | Description |
|------|------|---------|-------------|
| modelValue | string | '' | Координаты "lat,lng" |
| height | number | 400 | Высота карты в px |
| center | Coordinates | PERM_CENTER | Центр карты |
| zoom | number | 14 | Уровень зума |
| apiKey | string | DEFAULT_API_KEY | API ключ Яндекс.Карт |
| mode | 'single' \| 'multiple' | 'single' | Режим маркеров |
| markers | MapMarker[] | [] | Массив маркеров |
| showGeolocationButton | boolean | false | Показать кнопку геолокации |
| autoDetectLocation | boolean | false | Автоопределение местоположения |
| clusterize | boolean | false | Кластеризация маркеров |
| draggable | boolean | true | Перетаскивание маркера |
| showSingleMarker | boolean | true | Показать одиночный маркер |
| showAddressTooltip | boolean | true | Показать тултип с адресом |
| currentAddress | string | '' | Текущий адрес |

## Events
| Event | Payload | Description |
|-------|---------|-------------|
| update:modelValue | string | Обновление координат |
| marker-moved | Coordinates | Маркер перемещен |
| marker-click | MapMarker | Клик по маркеру |
| cluster-click | MapMarker[] | Клик по кластеру |
| address-found | {address, coords} | Адрес найден |
| search-error | string | Ошибка поиска |

## Exposed Methods
| Method | Arguments | Return | Description |
|--------|-----------|--------|-------------|
| searchAddress | (address: string) | Promise<void> | Поиск по адресу |
| setCoordinates | (coords: Coordinates) | void | Установка координат |
| getCurrentAddress | () | string | Получить текущий адрес |
| clearMap | () | void | Очистить карту |
```

#### Шаг 0.4: Performance baseline (1 час)

```javascript
// scripts/measure-map-performance.js
import { chromium } from 'playwright'

async function measurePerformance() {
  const browser = await chromium.launch()
  const page = await browser.newPage()
  
  // Включаем сбор метрик
  await page.coverage.startJSCoverage()
  
  // Замеряем время загрузки
  const startTime = Date.now()
  await page.goto('/ad/create')
  await page.waitForSelector('.yandex-map')
  const loadTime = Date.now() - startTime
  
  // Собираем метрики
  const coverage = await page.coverage.stopJSCoverage()
  const metrics = await page.evaluate(() => {
    return {
      memory: performance.memory?.usedJSHeapSize || 0,
      domNodes: document.querySelectorAll('*').length,
      mapInitTime: window.__mapInitTime || 0,
    }
  })
  
  // Bundle size
  const bundleSize = coverage.reduce((acc, entry) => {
    if (entry.url.includes('/map/')) {
      return acc + entry.text.length
    }
    return acc
  }, 0)
  
  console.log('Performance Baseline:')
  console.log(`- Load time: ${loadTime}ms`)
  console.log(`- Map init: ${metrics.mapInitTime}ms`)
  console.log(`- Memory: ${(metrics.memory / 1024 / 1024).toFixed(2)}MB`)
  console.log(`- DOM nodes: ${metrics.domNodes}`)
  console.log(`- Bundle size: ${(bundleSize / 1024).toFixed(2)}KB`)
  
  await browser.close()
}

measurePerformance()
```

### Ожидаемые результаты baseline:
- Load time: ~3000ms
- Map init: ~2000ms  
- Memory: ~25MB
- DOM nodes: ~500
- Bundle size: ~200KB

---

### ФАЗА 1: СОЗДАНИЕ ЯДРА (День 1, 6 часов)

#### Шаг 1.1: MapLoader - Singleton для загрузки API (1 час)

```typescript
// features/map/core/MapLoader.ts

/**
 * Singleton загрузчик Yandex Maps API
 * Паттерн: Singleton + Promise caching
 * Размер: 50 строк
 */
export class MapLoader {
  private static instance: MapLoader | null = null
  private loadPromise: Promise<typeof ymaps> | null = null
  private isLoaded = false

  private constructor() {}

  static getInstance(): MapLoader {
    if (!MapLoader.instance) {
      MapLoader.instance = new MapLoader()
    }
    return MapLoader.instance
  }

  async load(apiKey: string): Promise<typeof ymaps> {
    // Если уже загружено, возвращаем сразу
    if (this.isLoaded && window.ymaps) {
      return window.ymaps
    }

    // Если загрузка в процессе, возвращаем существующий промис
    if (this.loadPromise) {
      return this.loadPromise
    }

    // Начинаем загрузку
    this.loadPromise = this.loadScript(apiKey)
    const ymaps = await this.loadPromise
    this.isLoaded = true
    return ymaps
  }

  private loadScript(apiKey: string): Promise<typeof ymaps> {
    return new Promise((resolve, reject) => {
      // Проверяем, может уже загружено
      if (window.ymaps?.ready) {
        window.ymaps.ready(() => resolve(window.ymaps))
        return
      }

      // Создаем script tag
      const script = document.createElement('script')
      script.src = `https://api-maps.yandex.ru/2.1/?apikey=${apiKey}&lang=ru_RU`
      script.async = true

      script.onload = () => {
        window.ymaps.ready(() => {
          console.log('[MapLoader] Yandex Maps API loaded')
          resolve(window.ymaps)
        })
      }

      script.onerror = () => {
        this.loadPromise = null
        reject(new Error('Failed to load Yandex Maps API'))
      }

      document.head.appendChild(script)
    })
  }

  // Для тестов
  reset(): void {
    this.loadPromise = null
    this.isLoaded = false
  }
}

// Экспортируем готовый инстанс
export const mapLoader = MapLoader.getInstance()
```

**Тесты для MapLoader:**

```typescript
// features/map/core/__tests__/MapLoader.test.ts
import { MapLoader } from '../MapLoader'

describe('MapLoader', () => {
  let loader: MapLoader
  
  beforeEach(() => {
    loader = MapLoader.getInstance()
    loader.reset()
    delete window.ymaps
  })

  it('should be singleton', () => {
    const loader1 = MapLoader.getInstance()
    const loader2 = MapLoader.getInstance()
    expect(loader1).toBe(loader2)
  })

  it('should load Yandex Maps API', async () => {
    // Mock script loading
    const mockYmaps = { ready: jest.fn(cb => cb()) }
    window.ymaps = mockYmaps as any

    const result = await loader.load('test-key')
    expect(result).toBe(mockYmaps)
  })

  it('should cache load promise', async () => {
    const mockYmaps = { ready: jest.fn(cb => cb()) }
    window.ymaps = mockYmaps as any

    const promise1 = loader.load('test-key')
    const promise2 = loader.load('test-key')
    
    expect(promise1).toBe(promise2)
  })

  it('should handle load errors', async () => {
    // Mock script error
    jest.spyOn(document.head, 'appendChild').mockImplementation((script: any) => {
      setTimeout(() => script.onerror(), 0)
      return script
    })

    await expect(loader.load('test-key')).rejects.toThrow('Failed to load')
  })
})
```

#### Шаг 1.2: MapStore - Централизованное состояние (1 час)

```typescript
// features/map/core/MapStore.ts

import { reactive, ref, computed } from 'vue'
import type { Coordinates, MapState, MapMarker } from '../types'

/**
 * Централизованное хранилище состояния карты
 * Паттерн: Reactive Store (Vue 3)
 * Размер: 80 строк
 */
export class MapStore {
  // Состояние карты
  private state = reactive<MapState>({
    isLoading: true,
    isReady: false,
    error: null,
    center: { lat: 58.0105, lng: 56.2502 }, // Пермь
    zoom: 14,
    bounds: null,
    address: '',
    coordinates: null
  })

  // Маркеры
  private markers = ref<Map<string, MapMarker>>(new Map())

  // Инстанс карты
  private mapInstance = ref<any>(null)

  // Getters
  get isLoading() {
    return this.state.isLoading
  }

  get isReady() {
    return this.state.isReady
  }

  get error() {
    return this.state.error
  }

  get center() {
    return this.state.center
  }

  get zoom() {
    return this.state.zoom
  }

  get address() {
    return this.state.address
  }

  get coordinates() {
    return this.state.coordinates
  }

  get markersArray() {
    return Array.from(this.markers.value.values())
  }

  // Actions
  setLoading(loading: boolean) {
    this.state.isLoading = loading
  }

  setReady(ready: boolean) {
    this.state.isReady = ready
    if (ready) {
      this.state.isLoading = false
      this.state.error = null
    }
  }

  setError(error: string | null) {
    this.state.error = error
    this.state.isLoading = false
  }

  setCenter(center: Coordinates) {
    this.state.center = center
  }

  setZoom(zoom: number) {
    this.state.zoom = zoom
  }

  setAddress(address: string) {
    this.state.address = address
  }

  setCoordinates(coords: Coordinates | null) {
    this.state.coordinates = coords
  }

  setMapInstance(instance: any) {
    this.mapInstance.value = instance
  }

  getMapInstance() {
    return this.mapInstance.value
  }

  // Маркеры
  addMarker(marker: MapMarker) {
    this.markers.value.set(marker.id, marker)
  }

  removeMarker(id: string) {
    this.markers.value.delete(id)
  }

  clearMarkers() {
    this.markers.value.clear()
  }

  reset() {
    this.state.isLoading = true
    this.state.isReady = false
    this.state.error = null
    this.state.address = ''
    this.state.coordinates = null
    this.clearMarkers()
  }
}

// Создаем store для каждого инстанса карты
export function createMapStore() {
  return new MapStore()
}
```

#### Шаг 1.3: MapCore - Минимальное ядро с плагинами (2 часа)

```vue
<!-- features/map/core/MapCore.vue -->
<template>
  <div class="map-core" :class="{ 'map-core--mobile': isMobile }">
    <div 
      ref="containerRef"
      :id="mapId"
      class="map-core__container"
      :style="{ height: `${height}px` }"
    />
    
    <!-- Слот для контролов -->
    <div v-if="$slots.controls" class="map-core__controls">
      <slot name="controls" :map="store" />
    </div>

    <!-- Слот для оверлеев -->
    <div v-if="$slots.overlays" class="map-core__overlays">
      <slot name="overlays" :map="store" />
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * MapCore - минимальное ядро карты с системой плагинов
 * Принципы:
 * 1. Минимальная функциональность в ядре
 * 2. Расширение через плагины
 * 3. Реактивное состояние через store
 * Размер: 150 строк
 */
import { ref, onMounted, onUnmounted, watch, provide } from 'vue'
import { mapLoader } from './MapLoader'
import { createMapStore } from './MapStore'
import type { MapPlugin, Coordinates, MapConfig } from '../types'
import { DEFAULT_API_KEY, PERM_CENTER, DEFAULT_ZOOM } from '../utils/mapConstants'
import { isMobileDevice } from '../utils/mapHelpers'

interface Props {
  height?: number
  center?: Coordinates
  zoom?: number
  apiKey?: string
  config?: Partial<MapConfig>
}

const props = withDefaults(defineProps<Props>(), {
  height: 400,
  center: () => PERM_CENTER,
  zoom: DEFAULT_ZOOM,
  apiKey: DEFAULT_API_KEY
})

const emit = defineEmits<{
  ready: [map: any]
  error: [error: Error]
  'center-change': [center: Coordinates]
  'zoom-change': [zoom: number]
  click: [coords: Coordinates]
}>()

// Refs
const containerRef = ref<HTMLElement>()
const mapId = `map-${Math.random().toString(36).substr(2, 9)}`
const isMobile = isMobileDevice()

// Store
const store = createMapStore()
provide('mapStore', store)

// Plugins
const plugins = new Map<string, MapPlugin>()

// Public API
async function use(plugin: MapPlugin) {
  console.log(`[MapCore] Installing plugin: ${plugin.name}`)
  plugins.set(plugin.name, plugin)
  
  // Если карта уже инициализирована, устанавливаем плагин сразу
  const mapInstance = store.getMapInstance()
  if (mapInstance && plugin.install) {
    await plugin.install(mapInstance, store)
  }
}

// Инициализация карты
async function initMap() {
  try {
    store.setLoading(true)
    
    // Загружаем API
    const ymaps = await mapLoader.load(props.apiKey)
    
    // Создаем карту
    const mapConfig = {
      center: [props.center.lat, props.center.lng],
      zoom: props.zoom,
      controls: [],
      ...props.config
    }
    
    const map = new ymaps.Map(mapId, mapConfig)
    store.setMapInstance(map)
    
    // Устанавливаем базовые обработчики
    setupBaseHandlers(map)
    
    // Устанавливаем плагины
    for (const plugin of plugins.values()) {
      if (plugin.install) {
        console.log(`[MapCore] Installing plugin: ${plugin.name}`)
        await plugin.install(map, store)
      }
    }
    
    // Мобильные оптимизации
    if (isMobile) {
      map.behaviors.disable('drag')
      map.behaviors.enable('multiTouch')
    }
    
    store.setReady(true)
    emit('ready', map)
    
  } catch (error) {
    console.error('[MapCore] Init error:', error)
    store.setError(error.message)
    emit('error', error)
  }
}

// Базовые обработчики событий
function setupBaseHandlers(map: any) {
  // Клик по карте
  map.events.add('click', (e: any) => {
    const coords = e.get('coords')
    const coordinates = {
      lat: coords[0],
      lng: coords[1]
    }
    store.setCoordinates(coordinates)
    emit('click', coordinates)
  })
  
  // Изменение центра
  map.events.add('actionend', () => {
    const center = map.getCenter()
    const newCenter = {
      lat: center[0],
      lng: center[1]
    }
    store.setCenter(newCenter)
    emit('center-change', newCenter)
    
    const zoom = map.getZoom()
    store.setZoom(zoom)
    emit('zoom-change', zoom)
  })
}

// Методы для внешнего использования
function setCenter(center: Coordinates, zoom?: number) {
  const map = store.getMapInstance()
  if (map) {
    map.setCenter([center.lat, center.lng], zoom || store.zoom)
  }
}

function getCenter(): Coordinates {
  return store.center
}

function destroy() {
  const map = store.getMapInstance()
  if (map) {
    // Вызываем destroy для всех плагинов
    for (const plugin of plugins.values()) {
      if (plugin.destroy) {
        plugin.destroy()
      }
    }
    
    map.destroy()
    store.reset()
  }
}

// Lifecycle
onMounted(() => {
  initMap()
})

onUnmounted(() => {
  destroy()
})

// Следим за изменением пропсов
watch(() => props.center, (newCenter) => {
  if (newCenter) {
    setCenter(newCenter)
  }
})

watch(() => props.zoom, (newZoom) => {
  const map = store.getMapInstance()
  if (map) {
    map.setZoom(newZoom)
  }
})

// Expose public API
defineExpose({
  use,
  setCenter,
  getCenter,
  destroy,
  store
})
</script>

<style lang="scss">
.map-core {
  position: relative;
  width: 100%;
  
  &__container {
    width: 100%;
    background: #f5f5f5;
    position: relative;
  }
  
  &__controls {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 1000;
  }
  
  &__overlays {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    z-index: 999;
    
    > * {
      pointer-events: auto;
    }
  }
  
  &--mobile {
    .map-core__controls {
      top: auto;
      bottom: 10px;
    }
  }
}
</style>
```

#### Шаг 1.4: Тестирование ядра (2 часа)

```typescript
// features/map/core/__tests__/MapCore.test.ts
import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import MapCore from '../MapCore.vue'
import { mapLoader } from '../MapLoader'

// Mock Yandex Maps
const mockYmaps = {
  Map: vi.fn().mockImplementation(() => ({
    events: {
      add: vi.fn()
    },
    behaviors: {
      disable: vi.fn(),
      enable: vi.fn()
    },
    setCenter: vi.fn(),
    setZoom: vi.fn(),
    getCenter: vi.fn(() => [58.0105, 56.2502]),
    getZoom: vi.fn(() => 14),
    destroy: vi.fn()
  }))
}

vi.mock('../MapLoader', () => ({
  mapLoader: {
    load: vi.fn().mockResolvedValue(mockYmaps)
  }
}))

describe('MapCore', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    window.ymaps = mockYmaps
  })

  it('should mount and initialize map', async () => {
    const wrapper = mount(MapCore, {
      props: {
        height: 500,
        apiKey: 'test-key'
      }
    })

    // Ждем инициализации
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 100))

    // Проверяем что карта создана
    expect(mockYmaps.Map).toHaveBeenCalled()
    
    // Проверяем что store обновился
    const store = wrapper.vm.store
    expect(store.isReady).toBe(true)
    expect(store.isLoading).toBe(false)
  })

  it('should emit ready event after init', async () => {
    const wrapper = mount(MapCore)

    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 100))

    // Проверяем событие
    expect(wrapper.emitted('ready')).toBeTruthy()
    expect(wrapper.emitted('ready')[0][0]).toBeDefined()
  })

  it('should handle click events', async () => {
    const wrapper = mount(MapCore)

    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 100))

    // Получаем mock карты
    const mapInstance = wrapper.vm.store.getMapInstance()
    const clickHandler = mapInstance.events.add.mock.calls.find(
      call => call[0] === 'click'
    )[1]

    // Симулируем клик
    const mockEvent = {
      get: vi.fn(() => [55.7558, 37.6173])
    }
    clickHandler(mockEvent)

    // Проверяем событие
    expect(wrapper.emitted('click')).toBeTruthy()
    expect(wrapper.emitted('click')[0][0]).toEqual({
      lat: 55.7558,
      lng: 37.6173
    })
  })

  it('should install plugins', async () => {
    const mockPlugin = {
      name: 'test-plugin',
      install: vi.fn()
    }

    const wrapper = mount(MapCore)
    
    // Добавляем плагин
    await wrapper.vm.use(mockPlugin)
    
    // Ждем инициализации
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 100))

    // Проверяем что плагин установлен
    expect(mockPlugin.install).toHaveBeenCalledWith(
      expect.any(Object),
      expect.any(Object)
    )
  })

  it('should handle errors', async () => {
    // Заставляем загрузчик выбросить ошибку
    mapLoader.load.mockRejectedValueOnce(new Error('Load failed'))

    const wrapper = mount(MapCore)

    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 100))

    // Проверяем состояние ошибки
    expect(wrapper.vm.store.error).toBe('Load failed')
    expect(wrapper.emitted('error')).toBeTruthy()
  })

  it('should destroy map on unmount', async () => {
    const wrapper = mount(MapCore)

    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 100))

    const mapInstance = wrapper.vm.store.getMapInstance()
    
    // Размонтируем компонент
    wrapper.unmount()

    // Проверяем что destroy вызван
    expect(mapInstance.destroy).toHaveBeenCalled()
  })
})
```

---

### ФАЗА 2: СОЗДАНИЕ ПЛАГИНОВ (День 2, 6 часов)

#### Шаг 2.1: ClusterPlugin - Кластеризация маркеров (1.5 часа)

```typescript
// features/map/plugins/ClusterPlugin.ts

import type { MapPlugin, MapMarker, MapStore } from '../types'

/**
 * Плагин кластеризации маркеров
 * Группирует близкие маркеры в кластеры при уменьшении масштаба
 * Размер: 60 строк
 */
export class ClusterPlugin implements MapPlugin {
  name = 'cluster'
  private clusterer: any = null
  private map: any = null
  private store: MapStore | null = null

  constructor(private options: any = {}) {
    this.options = {
      preset: 'islands#invertedVioletClusterIcons',
      clusterDisableClickZoom: true,
      clusterOpenBalloonOnClick: false,
      gridSize: 64,
      ...options
    }
  }

  async install(map: any, store: MapStore) {
    this.map = map
    this.store = store

    // Создаем кластеризатор
    this.clusterer = new ymaps.Clusterer(this.options)

    // Обработчик клика по кластеру
    this.clusterer.events.add('click', (e: any) => {
      const cluster = e.get('target')
      const markers = cluster.getGeoObjects()
      
      // Эмитируем событие через store
      if (this.store) {
        this.store.emit('cluster-click', markers)
      }
    })

    // Добавляем на карту
    map.geoObjects.add(this.clusterer)

    // Следим за изменением маркеров
    if (store) {
      store.on('markers-change', this.updateMarkers.bind(this))
    }
  }

  updateMarkers(markers: MapMarker[]) {
    if (!this.clusterer) return

    // Очищаем старые маркеры
    this.clusterer.removeAll()

    // Добавляем новые
    const placemarks = markers.map(marker => {
      const placemark = new ymaps.Placemark(
        [marker.coordinates.lat, marker.coordinates.lng],
        {
          balloonContentHeader: marker.title,
          balloonContentBody: marker.description,
          markerId: marker.id
        },
        {
          preset: marker.preset || 'islands#blueIcon',
          iconColor: marker.color || '#0095b6'
        }
      )

      // Обработчик клика по маркеру
      placemark.events.add('click', () => {
        if (this.store) {
          this.store.emit('marker-click', marker)
        }
      })

      return placemark
    })

    this.clusterer.add(placemarks)
  }

  destroy() {
    if (this.clusterer && this.map) {
      this.map.geoObjects.remove(this.clusterer)
      this.clusterer = null
    }
  }
}
```

#### Шаг 2.2: GeolocationPlugin - Определение местоположения (1 час)

```typescript
// features/map/plugins/GeolocationPlugin.ts

import type { MapPlugin, MapStore, Coordinates } from '../types'

/**
 * Плагин геолокации
 * Добавляет кнопку определения местоположения и автоопределение
 * Размер: 40 строк
 */
export class GeolocationPlugin implements MapPlugin {
  name = 'geolocation'
  private control: any = null
  private map: any = null

  constructor(private options: any = {}) {
    this.options = {
      autoDetect: false,
      showButton: true,
      ...options
    }
  }

  async install(map: any, store: MapStore) {
    this.map = map

    // Добавляем кнопку геолокации
    if (this.options.showButton) {
      this.control = new ymaps.control.GeolocationControl({
        options: {
          float: 'right',
          position: {
            bottom: 20,
            right: 20
          }
        }
      })

      map.controls.add(this.control)

      // Обработчик успешной геолокации
      this.control.events.add('locationchange', (e: any) => {
        const position = e.get('position')
        const coords: Coordinates = {
          lat: position[0],
          lng: position[1]
        }
        
        store.setCenter(coords)
        store.emit('geolocation-success', coords)
      })
    }

    // Автоопределение при загрузке
    if (this.options.autoDetect) {
      this.detectLocation(store)
    }
  }

  private async detectLocation(store: MapStore) {
    if (!navigator.geolocation) {
      console.warn('[GeolocationPlugin] Geolocation not supported')
      return
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const coords: Coordinates = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        }
        
        this.map.setCenter([coords.lat, coords.lng], 15)
        store.setCenter(coords)
        store.emit('geolocation-auto-detected', coords)
      },
      (error) => {
        console.warn('[GeolocationPlugin] Auto-detect failed:', error)
      }
    )
  }

  destroy() {
    if (this.control && this.map) {
      this.map.controls.remove(this.control)
      this.control = null
    }
  }
}
```

#### Шаг 2.3: SearchPlugin - Поиск и геокодинг (1.5 часа)

```typescript
// features/map/plugins/SearchPlugin.ts

import type { MapPlugin, MapStore, Coordinates } from '../types'

/**
 * Плагин поиска по адресу и обратного геокодинга
 * Размер: 50 строк
 */
export class SearchPlugin implements MapPlugin {
  name = 'search'
  private geocoder: any = null
  private searchControl: any = null
  private map: any = null

  constructor(private options: any = {}) {
    this.options = {
      showSearchControl: false,
      reverseGeocode: true,
      ...options
    }
  }

  async install(map: any, store: MapStore) {
    this.map = map

    // Добавляем контрол поиска
    if (this.options.showSearchControl) {
      this.searchControl = new ymaps.control.SearchControl({
        options: {
          float: 'left',
          floatIndex: 100,
          noPlacemark: true
        }
      })

      map.controls.add(this.searchControl)

      // Обработчик результата поиска
      this.searchControl.events.add('resultselect', (e: any) => {
        const index = e.get('index')
        this.searchControl.getResult(index).then((res: any) => {
          const coords = res.geometry.getCoordinates()
          const address = res.properties.get('text')
          
          store.setCoordinates({
            lat: coords[0],
            lng: coords[1]
          })
          store.setAddress(address)
          store.emit('search-result', { address, coords })
        })
      })
    }

    // Обратное геокодирование при клике
    if (this.options.reverseGeocode) {
      map.events.add('click', (e: any) => {
        const coords = e.get('coords')
        this.reverseGeocode(coords, store)
      })
    }
  }

  // Поиск адреса по координатам
  async reverseGeocode(coords: number[], store: MapStore) {
    try {
      const geocodeResult = await ymaps.geocode(coords)
      const firstGeoObject = geocodeResult.geoObjects.get(0)
      
      if (firstGeoObject) {
        const address = firstGeoObject.getAddressLine()
        store.setAddress(address)
        store.emit('address-found', {
          address,
          coords: {
            lat: coords[0],
            lng: coords[1]
          }
        })
      }
    } catch (error) {
      console.error('[SearchPlugin] Reverse geocode failed:', error)
    }
  }

  // Поиск координат по адресу
  async searchAddress(address: string): Promise<Coordinates | null> {
    try {
      const geocodeResult = await ymaps.geocode(address)
      const firstGeoObject = geocodeResult.geoObjects.get(0)
      
      if (firstGeoObject) {
        const coords = firstGeoObject.geometry.getCoordinates()
        return {
          lat: coords[0],
          lng: coords[1]
        }
      }
    } catch (error) {
      console.error('[SearchPlugin] Address search failed:', error)
    }
    
    return null
  }

  destroy() {
    if (this.searchControl && this.map) {
      this.map.controls.remove(this.searchControl)
      this.searchControl = null
    }
  }
}
```

#### Шаг 2.4: MarkersPlugin - Управление маркерами (2 часа)

```typescript
// features/map/plugins/MarkersPlugin.ts

import type { MapPlugin, MapStore, MapMarker, Coordinates } from '../types'

/**
 * Плагин управления маркерами
 * Отвечает за добавление, удаление и управление маркерами
 * Размер: 70 строк
 */
export class MarkersPlugin implements MapPlugin {
  name = 'markers'
  private map: any = null
  private store: MapStore | null = null
  private markers: Map<string, any> = new Map()
  private singleMarker: any = null

  constructor(private options: any = {}) {
    this.options = {
      mode: 'single', // 'single' | 'multiple'
      draggable: true,
      preset: 'islands#blueIcon',
      ...options
    }
  }

  async install(map: any, store: MapStore) {
    this.map = map
    this.store = store

    // В режиме single добавляем маркер при клике
    if (this.options.mode === 'single') {
      map.events.add('click', (e: any) => {
        const coords = e.get('coords')
        this.setSingleMarker({
          lat: coords[0],
          lng: coords[1]
        })
      })
    }

    // Слушаем изменения в store
    store.on('markers-add', this.addMarker.bind(this))
    store.on('markers-remove', this.removeMarker.bind(this))
    store.on('markers-clear', this.clearMarkers.bind(this))
  }

  // Установка одиночного маркера
  setSingleMarker(coords: Coordinates) {
    // Удаляем старый маркер
    if (this.singleMarker) {
      this.map.geoObjects.remove(this.singleMarker)
    }

    // Создаем новый
    this.singleMarker = new ymaps.Placemark(
      [coords.lat, coords.lng],
      {
        balloonContentHeader: 'Выбранное место',
        balloonContentBody: this.store?.address || 'Загрузка адреса...'
      },
      {
        preset: this.options.preset,
        draggable: this.options.draggable
      }
    )

    // Обработчик перетаскивания
    if (this.options.draggable) {
      this.singleMarker.events.add('dragend', () => {
        const newCoords = this.singleMarker.geometry.getCoordinates()
        const coordinates = {
          lat: newCoords[0],
          lng: newCoords[1]
        }
        
        this.store?.setCoordinates(coordinates)
        this.store?.emit('marker-moved', coordinates)
      })
    }

    this.map.geoObjects.add(this.singleMarker)
    
    // Обновляем store
    this.store?.setCoordinates(coords)
  }

  // Добавление маркера в режиме multiple
  addMarker(marker: MapMarker) {
    if (this.options.mode !== 'multiple') return

    const placemark = new ymaps.Placemark(
      [marker.coordinates.lat, marker.coordinates.lng],
      {
        balloonContentHeader: marker.title,
        balloonContentBody: marker.description,
        hintContent: marker.title
      },
      {
        preset: marker.preset || this.options.preset,
        iconColor: marker.color
      }
    )

    // Обработчик клика
    placemark.events.add('click', () => {
      this.store?.emit('marker-click', marker)
    })

    this.markers.set(marker.id, placemark)
    this.map.geoObjects.add(placemark)
  }

  // Удаление маркера
  removeMarker(id: string) {
    const placemark = this.markers.get(id)
    if (placemark) {
      this.map.geoObjects.remove(placemark)
      this.markers.delete(id)
    }
  }

  // Очистка всех маркеров
  clearMarkers() {
    if (this.singleMarker) {
      this.map.geoObjects.remove(this.singleMarker)
      this.singleMarker = null
    }

    for (const placemark of this.markers.values()) {
      this.map.geoObjects.remove(placemark)
    }
    this.markers.clear()
  }

  destroy() {
    this.clearMarkers()
  }
}
```

---

### ФАЗА 3: СОЗДАНИЕ UI КОМПОНЕНТОВ (День 3, 4 часа)

#### Шаг 3.1: MapContainer - Главный контейнер (1.5 часа)

```vue
<!-- features/map/components/MapContainer.vue -->
<template>
  <div class="map-container" :class="containerClasses">
    <MapStates 
      :loading="loading"
      :error="error"
      @retry="handleRetry"
    >
      <MapCore
        ref="mapCoreRef"
        v-bind="coreProps"
        @ready="handleMapReady"
        @error="handleMapError"
        @click="handleMapClick"
        @center-change="$emit('center-change', $event)"
        @zoom-change="$emit('zoom-change', $event)"
      >
        <template #controls>
          <MapControls 
            v-if="showControls"
            :show-geolocation="showGeolocationButton"
            :show-search="showSearchControl"
            @geolocation-click="handleGeolocationClick"
            @search="handleSearch"
          />
        </template>
        
        <template #overlays>
          <slot name="overlays" />
        </template>
      </MapCore>
    </MapStates>
  </div>
</template>

<script setup lang="ts">
/**
 * MapContainer - главный контейнер карты
 * Объединяет ядро, плагины и UI
 * Размер: 100 строк
 */
import { ref, computed, onMounted, watch } from 'vue'
import MapCore from '../core/MapCore.vue'
import MapStates from './MapStates.vue'
import MapControls from './MapControls.vue'

// Плагины
import { ClusterPlugin } from '../plugins/ClusterPlugin'
import { GeolocationPlugin } from '../plugins/GeolocationPlugin'
import { SearchPlugin } from '../plugins/SearchPlugin'
import { MarkersPlugin } from '../plugins/MarkersPlugin'

import type { MapMarker, Coordinates } from '../types'

interface Props {
  // Основные
  modelValue?: string
  height?: number
  center?: Coordinates
  zoom?: number
  
  // Режимы
  mode?: 'single' | 'multiple'
  markers?: MapMarker[]
  
  // UI
  showControls?: boolean
  showGeolocationButton?: boolean
  showSearchControl?: boolean
  
  // Опции
  clusterize?: boolean
  draggable?: boolean
  autoDetectLocation?: boolean
  reverseGeocode?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  height: 400,
  zoom: 14,
  mode: 'single',
  markers: () => [],
  showControls: true,
  showGeolocationButton: false,
  showSearchControl: false,
  clusterize: false,
  draggable: true,
  autoDetectLocation: false,
  reverseGeocode: true
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'ready': [map: any]
  'marker-click': [marker: MapMarker]
  'cluster-click': [markers: MapMarker[]]
  'address-found': [data: { address: string, coords: Coordinates }]
  'center-change': [center: Coordinates]
  'zoom-change': [zoom: number]
}>()

// Refs
const mapCoreRef = ref<InstanceType<typeof MapCore>>()
const loading = ref(true)
const error = ref<string | null>(null)

// Computed
const containerClasses = computed(() => ({
  'map-container--loading': loading.value,
  'map-container--error': !!error.value
}))

const coreProps = computed(() => ({
  height: props.height,
  center: props.center || parseCoordinates(props.modelValue),
  zoom: props.zoom
}))

// Methods
function parseCoordinates(value?: string): Coordinates | undefined {
  if (!value) return undefined
  const [lat, lng] = value.split(',').map(Number)
  return { lat, lng }
}

function formatCoordinates(coords: Coordinates): string {
  return `${coords.lat},${coords.lng}`
}

// Handlers
async function handleMapReady(map: any) {
  loading.value = false
  
  // Устанавливаем плагины
  const core = mapCoreRef.value
  if (!core) return

  // Markers плагин
  await core.use(new MarkersPlugin({
    mode: props.mode,
    draggable: props.draggable
  }))

  // Cluster плагин
  if (props.clusterize && props.mode === 'multiple') {
    await core.use(new ClusterPlugin())
  }

  // Geolocation плагин
  if (props.showGeolocationButton || props.autoDetectLocation) {
    await core.use(new GeolocationPlugin({
      showButton: props.showGeolocationButton,
      autoDetect: props.autoDetectLocation
    }))
  }

  // Search плагин
  if (props.showSearchControl || props.reverseGeocode) {
    await core.use(new SearchPlugin({
      showSearchControl: props.showSearchControl,
      reverseGeocode: props.reverseGeocode
    }))
  }

  // Подписываемся на события store
  const store = core.store
  store.on('coordinates-change', (coords: Coordinates) => {
    emit('update:modelValue', formatCoordinates(coords))
  })
  
  store.on('marker-click', (marker: MapMarker) => {
    emit('marker-click', marker)
  })
  
  store.on('cluster-click', (markers: MapMarker[]) => {
    emit('cluster-click', markers)
  })
  
  store.on('address-found', (data: any) => {
    emit('address-found', data)
  })

  emit('ready', map)
}

function handleMapError(err: Error) {
  error.value = err.message
  loading.value = false
}

function handleMapClick(coords: Coordinates) {
  if (props.mode === 'single') {
    emit('update:modelValue', formatCoordinates(coords))
  }
}

function handleRetry() {
  error.value = null
  loading.value = true
  mapCoreRef.value?.initMap()
}

function handleGeolocationClick() {
  // Обработка клика по кнопке геолокации
  console.log('Geolocation clicked')
}

function handleSearch(query: string) {
  // Обработка поиска
  console.log('Search:', query)
}

// Watchers
watch(() => props.markers, (newMarkers) => {
  if (mapCoreRef.value && props.mode === 'multiple') {
    const store = mapCoreRef.value.store
    store.emit('markers-change', newMarkers)
  }
}, { deep: true })

// Public API
defineExpose({
  setCenter: (coords: Coordinates, zoom?: number) => {
    mapCoreRef.value?.setCenter(coords, zoom)
  },
  searchAddress: async (address: string) => {
    // TODO: Implement via SearchPlugin
  }
})
</script>

<style lang="scss">
.map-container {
  position: relative;
  width: 100%;
  
  &--loading {
    pointer-events: none;
  }
  
  &--error {
    .map-core {
      opacity: 0.5;
    }
  }
}
</style>
```

#### Шаг 3.2: MapStates - Компонент состояний (1 час)

```vue
<!-- features/map/components/MapStates.vue -->
<template>
  <div class="map-states">
    <!-- Loading состояние -->
    <div v-if="loading" class="map-states__loading">
      <div class="map-states__spinner">
        <svg class="animate-spin h-8 w-8 text-blue-600" viewBox="0 0 24 24">
          <circle 
            class="opacity-25" 
            cx="12" 
            cy="12" 
            r="10" 
            stroke="currentColor" 
            stroke-width="4"
          />
          <path 
            class="opacity-75" 
            fill="currentColor" 
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
          />
        </svg>
      </div>
      <p class="map-states__loading-text">
        {{ loadingText }}
      </p>
    </div>

    <!-- Error состояние -->
    <div v-else-if="error" class="map-states__error">
      <div class="map-states__error-icon">
        <svg class="w-12 h-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" 
          />
        </svg>
      </div>
      <h3 class="map-states__error-title">{{ errorTitle }}</h3>
      <p class="map-states__error-message">{{ error }}</p>
      <button 
        @click="$emit('retry')"
        class="map-states__retry-button"
      >
        Попробовать снова
      </button>
    </div>

    <!-- Content -->
    <div v-else class="map-states__content">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * MapStates - управление состояниями карты
 * Показывает loading, error или content
 * Размер: 40 строк
 */
interface Props {
  loading?: boolean
  error?: string | null
  loadingText?: string
  errorTitle?: string
}

withDefaults(defineProps<Props>(), {
  loading: false,
  error: null,
  loadingText: 'Загрузка карты...',
  errorTitle: 'Ошибка загрузки карты'
})

defineEmits<{
  retry: []
}>()
</script>

<style lang="scss">
.map-states {
  position: relative;
  width: 100%;
  height: 100%;
  
  &__loading,
  &__error {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.95);
    z-index: 1000;
  }
  
  &__spinner {
    margin-bottom: 1rem;
  }
  
  &__loading-text {
    color: #6b7280;
    font-size: 0.875rem;
  }
  
  &__error-icon {
    margin-bottom: 1rem;
  }
  
  &__error-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
  }
  
  &__error-message {
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
    text-align: center;
    max-width: 300px;
  }
  
  &__retry-button {
    padding: 0.5rem 1rem;
    background: #3b82f6;
    color: white;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: background 0.2s;
    
    &:hover {
      background: #2563eb;
    }
    
    &:active {
      transform: scale(0.98);
    }
  }
  
  &__content {
    width: 100%;
    height: 100%;
  }
}
</style>
```

#### Шаг 3.3: MapControls - UI контролы (1.5 часа)

```vue
<!-- features/map/components/MapControls.vue -->
<template>
  <div class="map-controls">
    <!-- Поиск -->
    <div v-if="showSearch" class="map-controls__search">
      <input
        v-model="searchQuery"
        @keyup.enter="handleSearch"
        type="text"
        placeholder="Поиск места..."
        class="map-controls__search-input"
      />
      <button 
        @click="handleSearch"
        class="map-controls__search-button"
        :disabled="!searchQuery"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" 
          />
        </svg>
      </button>
    </div>

    <!-- Геолокация -->
    <button
      v-if="showGeolocation"
      @click="$emit('geolocation-click')"
      class="map-controls__button map-controls__button--geolocation"
      title="Моё местоположение"
    >
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" 
        />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" 
        />
      </svg>
    </button>

    <!-- Дополнительные контролы -->
    <slot />
  </div>
</template>

<script setup lang="ts">
/**
 * MapControls - UI контролы карты
 * Поиск, геолокация и другие кнопки управления
 * Размер: 50 строк
 */
import { ref } from 'vue'

interface Props {
  showSearch?: boolean
  showGeolocation?: boolean
}

withDefaults(defineProps<Props>(), {
  showSearch: false,
  showGeolocation: false
})

const emit = defineEmits<{
  'search': [query: string]
  'geolocation-click': []
}>()

const searchQuery = ref('')

function handleSearch() {
  if (searchQuery.value.trim()) {
    emit('search', searchQuery.value.trim())
  }
}
</script>

<style lang="scss">
.map-controls {
  display: flex;
  gap: 0.5rem;
  
  &__search {
    display: flex;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    overflow: hidden;
  }
  
  &__search-input {
    padding: 0.5rem 1rem;
    border: none;
    outline: none;
    min-width: 200px;
    font-size: 0.875rem;
    
    &::placeholder {
      color: #9ca3af;
    }
  }
  
  &__search-button {
    padding: 0.5rem;
    background: transparent;
    border: none;
    border-left: 1px solid #e5e7eb;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    
    &:hover:not(:disabled) {
      background: #f3f4f6;
      color: #1f2937;
    }
    
    &:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
  }
  
  &__button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: white;
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    
    &:hover {
      background: #f3f4f6;
      color: #1f2937;
      transform: scale(1.05);
    }
    
    &:active {
      transform: scale(0.95);
    }
    
    &--geolocation {
      color: #3b82f6;
      
      &:hover {
        color: #2563eb;
      }
    }
  }
  
  // Mobile styles
  @media (max-width: 640px) {
    &__search {
      flex: 1;
    }
    
    &__search-input {
      min-width: auto;
      width: 100%;
    }
  }
}
</style>
```

---

### ФАЗА 4: МИГРАЦИЯ И АДАПТЕР (День 4, 4 часа)

#### Шаг 4.1: Адаптер для обратной совместимости (2 часа)

```vue
<!-- shared/ui/molecules/YandexMapPicker/YandexMap.vue -->
<template>
  <MapContainer
    ref="mapContainerRef"
    v-bind="adapterProps"
    @update:modelValue="handleUpdate"
    @ready="handleReady"
    @marker-click="$emit('marker-click', $event)"
    @cluster-click="$emit('cluster-click', $event)"
    @address-found="handleAddressFound"
    @center-change="$emit('bounds-change', { center: $event })"
  >
    <template #overlays>
      <!-- Сохраняем старые оверлеи для совместимости -->
      <slot name="overlays" />
    </template>
  </MapContainer>
</template>

<script setup lang="ts">
/**
 * YandexMap - адаптер для обратной совместимости
 * Преобразует старое API к новому MapContainer
 */
import { ref, computed } from 'vue'
import MapContainer from '@/src/features/map/components/MapContainer.vue'
import type { MapMarker, Coordinates } from '@/src/features/map/types'

// Сохраняем старый интерфейс props
interface Props {
  modelValue?: string
  height?: number
  center?: Coordinates
  zoom?: number
  apiKey?: string
  mode?: 'single' | 'multiple'
  markers?: MapMarker[]
  showGeolocationButton?: boolean
  autoDetectLocation?: boolean
  clusterize?: boolean
  draggable?: boolean
  showSingleMarker?: boolean
  showAddressTooltip?: boolean
  currentAddress?: string
  loadingText?: string
  errorTitle?: string
  emptyTitle?: string
  emptyMessage?: string
  ariaLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  // Сохраняем все старые defaults
  height: 400,
  zoom: 14,
  mode: 'single',
  markers: () => [],
  showGeolocationButton: false,
  autoDetectLocation: false,
  clusterize: false,
  draggable: true,
  showSingleMarker: true,
  showAddressTooltip: true,
  currentAddress: '',
  ariaLabel: 'Интерактивная карта'
})

// Сохраняем старые events
const emit = defineEmits<{
  'update:modelValue': [value: string]
  'marker-moved': [coords: Coordinates]
  'marker-click': [marker: MapMarker]
  'cluster-click': [markers: MapMarker[]]
  'address-found': [address: string, coords: Coordinates]
  'search-error': [error: string]
  'marker-address-hover': [address: string]
  'bounds-change': [bounds: any]
}>()

const mapContainerRef = ref<InstanceType<typeof MapContainer>>()

// Адаптируем props к новому API
const adapterProps = computed(() => ({
  modelValue: props.modelValue,
  height: props.height,
  center: props.center,
  zoom: props.zoom,
  mode: props.mode,
  markers: props.markers,
  showControls: props.showGeolocationButton || props.showAddressTooltip,
  showGeolocationButton: props.showGeolocationButton,
  showSearchControl: false, // В старом API не было
  clusterize: props.clusterize,
  draggable: props.draggable,
  autoDetectLocation: props.autoDetectLocation,
  reverseGeocode: props.showAddressTooltip
}))

// Handlers
function handleUpdate(value: string) {
  emit('update:modelValue', value)
  
  // Эмулируем старое событие marker-moved
  if (value) {
    const [lat, lng] = value.split(',').map(Number)
    emit('marker-moved', { lat, lng })
  }
}

function handleReady(map: any) {
  console.log('[YandexMap Adapter] Map ready, old API preserved')
}

function handleAddressFound(data: { address: string, coords: Coordinates }) {
  emit('address-found', data.address, data.coords)
}

// Expose старые методы для совместимости
defineExpose({
  // Старые методы
  searchAddress: async (address: string) => {
    await mapContainerRef.value?.searchAddress(address)
  },
  setCoordinates: (coords: Coordinates) => {
    mapContainerRef.value?.setCenter(coords)
  },
  getCurrentAddress: () => {
    // TODO: Implement via store
    return props.currentAddress
  },
  clearMap: () => {
    // TODO: Implement
  }
})
</script>
```

#### Шаг 4.2: Обновление импортов (1 час)

```typescript
// scripts/migrate-map-imports.js

const glob = require('glob')
const fs = require('fs')
const path = require('path')

// Находим все файлы с импортами старой карты
const files = glob.sync('resources/js/**/*.{vue,ts,js}', {
  ignore: ['**/node_modules/**', '**/map/**']
})

const OLD_IMPORTS = [
  '@/src/features/map/ui/YandexMapBase/YandexMapBase.vue',
  '@/src/features/map/ui/UniversalMap/UniversalMap.vue',
  '@/src/features/map/composables/useMapController',
  '@/src/features/map/composables/useMapWithMasters'
]

const NEW_IMPORT = '@/src/shared/ui/molecules/YandexMapPicker/YandexMap.vue'

let updatedCount = 0

files.forEach(file => {
  let content = fs.readFileSync(file, 'utf8')
  let hasChanges = false
  
  OLD_IMPORTS.forEach(oldImport => {
    if (content.includes(oldImport)) {
      content = content.replace(oldImport, NEW_IMPORT)
      hasChanges = true
      console.log(`✅ Updated: ${file}`)
    }
  })
  
  if (hasChanges) {
    fs.writeFileSync(file, content)
    updatedCount++
  }
})

console.log(`\n📊 Migration complete: ${updatedCount} files updated`)
```

#### Шаг 4.3: Удаление старых файлов (1 час)

```bash
#!/bin/bash
# scripts/cleanup-old-map-files.sh

echo "🗑️ Cleaning up old map files..."

# Создаем backup
echo "📦 Creating backup..."
tar -czf map-backup-$(date +%Y%m%d-%H%M%S).tar.gz resources/js/src/features/map

# Удаляем старые composables
echo "Removing old composables..."
rm -f resources/js/src/features/map/composables/useAddressGeocoding.ts
rm -f resources/js/src/features/map/composables/useGeolocation.ts
rm -f resources/js/src/features/map/composables/useMapClustering.ts
rm -f resources/js/src/features/map/composables/useMapController.ts
rm -f resources/js/src/features/map/composables/useMapEventHandlers.ts
rm -f resources/js/src/features/map/composables/useMapMarkers.ts
rm -f resources/js/src/features/map/composables/useMapMethods.ts
rm -f resources/js/src/features/map/composables/useMapMobileOptimization.ts
rm -f resources/js/src/features/map/composables/useMapModes.ts
rm -f resources/js/src/features/map/composables/useMapState.ts
rm -f resources/js/src/features/map/composables/useMapWithMasters.ts

# Удаляем старые UI компоненты
echo "Removing old UI components..."
rm -rf resources/js/src/features/map/ui/MapEmptyState
rm -rf resources/js/src/features/map/ui/MapErrorState
rm -rf resources/js/src/features/map/ui/MapSkeleton
rm -rf resources/js/src/features/map/ui/MapView
rm -rf resources/js/src/features/map/ui/UniversalMap
rm -rf resources/js/src/features/map/ui/MapAddressTooltip
rm -rf resources/js/src/features/map/ui/MapCenterMarker
rm -rf resources/js/src/features/map/ui/MapGeolocationButton
rm -rf resources/js/src/features/map/ui/MapMarker
rm -rf resources/js/src/features/map/ui/MapMarkers
rm -rf resources/js/src/features/map/ui/MapMarkersManager

# Оставляем только новые файлы
echo "✅ Cleanup complete!"
echo "📁 New structure:"
tree resources/js/src/features/map -I 'node_modules|__tests__'
```

---

### ФАЗА 5: ТЕСТИРОВАНИЕ И ОПТИМИЗАЦИЯ (День 5, 4 часа)

#### Шаг 5.1: E2E тестирование новой архитектуры (2 часа)

```javascript
// tests/e2e/map-after-refactoring.spec.js

import { test, expect } from '@playwright/test'

test.describe('Карта ПОСЛЕ рефакторинга', () => {
  // Проверяем что все старые тесты проходят
  
  test('обратная совместимость сохранена', async ({ page }) => {
    await page.goto('/ad/create')
    
    // Старый селектор должен работать
    await page.waitForSelector('.yandex-map')
    
    // API должно быть доступно
    const hasOldApi = await page.evaluate(() => {
      const mapComponent = document.querySelector('.yandex-map').__vue__
      return typeof mapComponent.searchAddress === 'function'
    })
    expect(hasOldApi).toBe(true)
  })
  
  test('производительность улучшена', async ({ page }) => {
    const metrics = await page.evaluate(() => {
      performance.mark('map-start')
    })
    
    await page.goto('/ad/create')
    await page.waitForSelector('.yandex-map')
    
    const loadTime = await page.evaluate(() => {
      performance.mark('map-end')
      performance.measure('map-load', 'map-start', 'map-end')
      const measure = performance.getEntriesByType('measure')[0]
      return measure.duration
    })
    
    // Должно быть меньше 1 секунды
    expect(loadTime).toBeLessThan(1000)
  })
  
  test('размер bundle уменьшен', async ({ page }) => {
    const coverage = await page.coverage.startJSCoverage()
    await page.goto('/ad/create')
    const entries = await page.coverage.stopJSCoverage()
    
    const mapBundle = entries.filter(entry => 
      entry.url.includes('/map/')
    )
    
    const totalSize = mapBundle.reduce((acc, entry) => 
      acc + entry.text.length, 0
    )
    
    // Должно быть меньше 100KB
    expect(totalSize).toBeLessThan(100 * 1024)
  })
})
```

#### Шаг 5.2: Performance оптимизация (1 час)

```typescript
// features/map/utils/performance.ts

/**
 * Утилиты для оптимизации производительности
 */

// Debounce для частых событий
export function debounce<T extends (...args: any[]) => any>(
  fn: T,
  delay: number
): T {
  let timeoutId: ReturnType<typeof setTimeout>
  
  return function(this: any, ...args: Parameters<T>) {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => fn.apply(this, args), delay)
  } as T
}

// Throttle для событий карты
export function throttle<T extends (...args: any[]) => any>(
  fn: T,
  limit: number
): T {
  let inThrottle: boolean
  
  return function(this: any, ...args: Parameters<T>) {
    if (!inThrottle) {
      fn.apply(this, args)
      inThrottle = true
      setTimeout(() => inThrottle = false, limit)
    }
  } as T
}

// Lazy loading для плагинов
export async function lazyLoadPlugin(name: string) {
  switch(name) {
    case 'cluster':
      return import(
        /* webpackChunkName: "map-cluster" */
        '../plugins/ClusterPlugin'
      )
    case 'geolocation':
      return import(
        /* webpackChunkName: "map-geo" */
        '../plugins/GeolocationPlugin'
      )
    case 'search':
      return import(
        /* webpackChunkName: "map-search" */
        '../plugins/SearchPlugin'
      )
    case 'markers':
      return import(
        /* webpackChunkName: "map-markers" */
        '../plugins/MarkersPlugin'
      )
    default:
      throw new Error(`Unknown plugin: ${name}`)
  }
}

// Мемоизация для тяжелых вычислений
export function memoize<T extends (...args: any[]) => any>(fn: T): T {
  const cache = new Map()
  
  return function(this: any, ...args: Parameters<T>) {
    const key = JSON.stringify(args)
    
    if (cache.has(key)) {
      return cache.get(key)
    }
    
    const result = fn.apply(this, args)
    cache.set(key, result)
    return result
  } as T
}
```

#### Шаг 5.3: Финальная проверка (1 час)

```bash
#!/bin/bash
# scripts/final-check.sh

echo "🔍 Running final checks..."

# 1. Lint
echo "📝 Linting..."
npm run lint

# 2. Type check
echo "🔍 Type checking..."
npm run type-check

# 3. Unit tests
echo "🧪 Unit tests..."
npm run test:unit -- features/map

# 4. E2E tests
echo "🌐 E2E tests..."
npm run test:e2e -- map

# 5. Bundle analysis
echo "📊 Bundle analysis..."
npm run build:analyze

# 6. Performance test
echo "⚡ Performance test..."
node scripts/measure-map-performance.js

echo "✅ All checks passed!"
```

---

## 📊 МЕТРИКИ УСПЕХА

### До рефакторинга
- Файлов: 30
- Строк кода: ~2500
- Bundle size: ~200KB
- Время загрузки: ~3 секунды
- Покрытие тестами: 0%
- Работает: ❌

### После рефакторинга
- Файлов: 12 (-60%)
- Строк кода: ~650 (-74%)
- Bundle size: <100KB (-50%)
- Время загрузки: <1 секунда (-67%)
- Покрытие тестами: >80%
- Работает: ✅

---

## 🚨 ROLLBACK СТРАТЕГИЯ

### В случае критических проблем:

1. **Быстрый откат (5 минут)**
```bash
# Восстановить из backup
tar -xzf map-backup-*.tar.gz
git checkout -- resources/js/src/shared/ui/molecules/YandexMapPicker/YandexMap.vue
```

2. **Feature flag (если подготовлен)**
```typescript
// config/features.ts
export const USE_NEW_MAP = process.env.VUE_APP_NEW_MAP === 'true'

// В компонентах
import OldMap from './OldYandexMap.vue'
import NewMap from './YandexMap.vue'

const YandexMap = USE_NEW_MAP ? NewMap : OldMap
```

3. **Полный revert**
```bash
git revert HEAD~10..HEAD
git push origin feature/map-refactoring-core-plugins
```

---

## 📅 TIMELINE

### Неделя 1
- **День 1-2**: Фаза 0-1 (Подготовка + Ядро)
- **День 3**: Фаза 2 (Плагины)
- **День 4**: Фаза 3 (UI компоненты)
- **День 5**: Фаза 4 (Миграция)

### Неделя 2
- **День 1**: Фаза 5 (Тестирование)
- **День 2**: Исправление багов
- **День 3**: Документация
- **День 4**: Code review
- **День 5**: Deploy

---

## ✅ ФИНАЛЬНЫЙ ЧЕКЛИСТ

### Перед началом
- [ ] Создан бранч `feature/map-refactoring-core-plugins`
- [ ] Написаны E2E тесты текущей функциональности
- [ ] Измерена текущая производительность
- [ ] Создан backup

### Разработка
- [ ] MapCore создан и протестирован
- [ ] MapLoader работает корректно
- [ ] MapStore управляет состоянием
- [ ] Все 4 плагина реализованы
- [ ] UI компоненты созданы
- [ ] Адаптер обеспечивает совместимость

### Тестирование
- [ ] Unit тесты покрывают >80%
- [ ] E2E тесты проходят
- [ ] Performance улучшена
- [ ] Обратная совместимость сохранена

### Завершение
- [ ] Старые файлы удалены
- [ ] Документация обновлена
- [ ] Code review пройден
- [ ] PR создан и approved

---

## 📚 ДОКУМЕНТАЦИЯ

### Для разработчиков
- [Как добавить новый плагин](./docs/add-plugin.md)
- [API Reference](./docs/api-reference.md)
- [Migration Guide](./docs/migration-guide.md)

### Примеры использования
```vue
<!-- Простая карта -->
<YandexMap v-model="coordinates" />

<!-- С геолокацией и поиском -->
<YandexMap 
  v-model="coordinates"
  show-geolocation-button
  auto-detect-location
  @address-found="handleAddress"
/>

<!-- Карта с маркерами -->
<YandexMap
  mode="multiple"
  :markers="masters"
  clusterize
  @marker-click="showMasterCard"
/>
```

---

## 🔥 ДОПОЛНИТЕЛЬНАЯ ФАЗА 6: ИНТЕГРАЦИЯ ЛУЧШЕГО ИЗ AV PATERN

**📅 СТАТУС ПЛАНА НА 31.08.2025: 85% выполнено**
- ✅ Фазы 0-4 завершены  
- ❌ Фаза 5 (оптимизация) НЕ выполнена
- 🚨 MapCore.vue 544 строки вместо плановых 150
- 🚨 40+ console.log в production коде

### 🎯 **ГИБРИДНАЯ АРХИТЕКТУРА: Vue 3 + Av patern алгоритмы**

После анализа `C:\Проект SPA\Av patern\ymaps-components` выявлены критически важные функции, отсутствующие в текущей реализации:

#### **6.1: Добавление продвинутой анимации panTo() (День 6, 4 часа)**

```typescript
// features/map/lib/AnimationEngine.ts - НОВЫЙ ФАЙЛ
/**
 * Движок анимаций карты
 * Адаптированный из Av patern Map.js:330-396
 * Добавляет плавные переходы между точками
 */
import type { Coordinates, PanToOptions } from '../types'

interface AnimationAction {
  center: Coordinates
  duration: number
  delay: number
  zoom?: number
}

export class AnimationEngine {
  private map: any
  private isAnimating = false
  
  constructor(map: any) {
    this.map = map
  }

  /**
   * Плавное перемещение по нескольким точкам
   * Портированная логика из Av patern Map.js
   */
  async panTo(points: Coordinates[], options: PanToOptions = {}): Promise<void> {
    if (this.isAnimating) {
      console.warn('[AnimationEngine] Animation already in progress')
      return
    }

    const {
      duration = 500,
      delay = 1000,
      flying = true,
      zoom
    } = options

    this.isAnimating = true

    try {
      // Подготавливаем действия (логика из Av patern)
      const actions: AnimationAction[] = points.map((point, index) => ({
        center: point,
        duration,
        delay: index > 0 ? delay : 0,
        zoom: zoom || this.map.getZoom()
      }))

      // Выполняем плавные переходы
      if (flying) {
        await this.executeFlyingAnimation(actions)
      } else {
        await this.executeLinearAnimation(actions)
      }
    } finally {
      this.isAnimating = false
    }
  }

  /**
   * Установка границ с учетом отступов
   * Адаптированная логика из Av patern Map.js:442-485
   */
  setBounds(bounds: [[number, number], [number, number]], options: any = {}) {
    const {
      zoomMargin = 0,
      useMapMargin = true,
      checkZoomRange = true,
      duration = 300
    } = options

    // Вычисляем центр и зум с учетом отступов
    const containerSize = this.map.container.getSize()
    let margin = zoomMargin

    // Учитываем отступы карты (логика из Av patern)
    if (useMapMargin) {
      const mapMargin = this.map.margin?.getMargin() || 0
      margin = this.sumMargins(margin, mapMargin)
    }

    // Вычисляем оптимальный центр и зум
    const { center, zoom } = this.calculateBoundsCenter(bounds, containerSize, margin)

    // Проверка диапазона зума (из Av patern)
    if (checkZoomRange) {
      const validZoom = this.validateZoomRange(zoom)
      return this.map.setCenter(center, validZoom, { duration })
    }

    return this.map.setCenter(center, zoom, { duration })
  }

  private async executeFlyingAnimation(actions: AnimationAction[]) {
    // Алгоритм плавных переходов из Av patern
    for (const action of actions) {
      await new Promise(resolve => {
        setTimeout(() => {
          this.map.setCenter(
            [action.center.lat, action.center.lng], 
            action.zoom,
            { duration: action.duration }
          )
          // Слушаем завершение анимации
          const handler = () => {
            this.map.events.remove('actionend', handler)
            resolve(void 0)
          }
          this.map.events.add('actionend', handler)
        }, action.delay)
      })
    }
  }

  // Вспомогательные методы из Av patern
  private sumMargins(margin1: number | number[], margin2: number | number[]) {
    // Логика сложения отступов из Av patern
    if (typeof margin1 === 'number' && typeof margin2 === 'number') {
      return margin1 + margin2
    }
    // Обработка массивов отступов [top, right, bottom, left]
    return [
      (margin1[0] || 0) + (margin2[0] || 0),
      (margin1[1] || 0) + (margin2[1] || 0), 
      (margin1[2] || 0) + (margin2[2] || 0),
      (margin1[3] || 0) + (margin2[3] || 0)
    ]
  }
}
```

#### **6.2: ObjectManager для больших данных (День 7, 6 часов)**

```typescript
// features/map/plugins/ObjectManagerPlugin.ts - НОВЫЙ ФАЙЛ  
/**
 * Высокопроизводительный менеджер объектов
 * Адаптированный из Av patern ObjectManager.js
 * Для работы с 10,000+ маркеров
 */
import type { MapPlugin, MapStore, MapMarker } from '../types'

export class ObjectManagerPlugin implements MapPlugin {
  name = 'objectManager'
  private objectManager: any = null
  private map: any = null
  private store: MapStore | null = null
  private filterFunction: ((object: any) => boolean) | null = null

  constructor(private options: any = {}) {
    this.options = {
      clusterize: true,
      gridSize: 64,
      clusterDisableClickZoom: true,
      clusterOpenBalloonOnClick: false,
      preset: 'islands#invertedVioletClusterIcons',
      ...options
    }
  }

  async install(map: any, store: MapStore) {
    this.map = map
    this.store = store

    // Создаем ObjectManager (логика из Av patern)
    this.objectManager = new ymaps.ObjectManager(this.options)
    
    // Добавляем на карту
    map.geoObjects.add(this.objectManager)

    // События ObjectManager
    this.setupObjectManagerEvents()

    // Слушаем события store
    store.on('bulk-markers-add', this.addBulkMarkers.bind(this))
    store.on('markers-filter', this.setFilter.bind(this))
    store.on('markers-clear', this.clearAll.bind(this))
  }

  /**
   * Добавление множества маркеров (оптимизированно)
   * Адаптированная логика из Av patern ObjectManager.js:388-410
   */
  addBulkMarkers(markers: MapMarker[]) {
    if (!this.objectManager) return

    // Преобразуем в формат GeoJSON для ObjectManager
    const features = markers.map(marker => ({
      type: 'Feature',
      id: marker.id,
      geometry: {
        type: 'Point',
        coordinates: [marker.coordinates.lng, marker.coordinates.lat]
      },
      properties: {
        balloonContentHeader: marker.title,
        balloonContentBody: marker.description,
        hintContent: marker.title,
        markerId: marker.id,
        masterData: marker.masterData // Для интеграции с мастерами
      },
      options: {
        preset: marker.preset || 'islands#blueIcon',
        iconColor: marker.color || '#0095b6'
      }
    }))

    // Добавляем все маркеры одним вызовом (высокая производительность)
    this.objectManager.add({
      type: 'FeatureCollection',
      features
    })
  }

  /**
   * Установка фильтра объектов
   * Логика из Av patern ObjectManager.js:127-159
   */
  setFilter(filterFunction: (object: any) => boolean) {
    this.filterFunction = filterFunction
    this.objectManager.setFilter(this.filterFunction)
  }

  /**
   * Получение состояния объекта  
   * Логика из Av patern ObjectManager.js:174-188
   */
  getObjectState(objectId: string) {
    if (!this.objectManager) {
      return { found: false }
    }

    return {
      found: true,
      isFilteredOut: this.filterFunction ? 
        !this.filterFunction({ id: objectId }) : false,
      inCluster: this.objectManager.clusters.state.get(objectId) !== undefined
    }
  }

  private setupObjectManagerEvents() {
    // Клик по объекту
    this.objectManager.objects.events.add('click', (e: any) => {
      const objectId = e.get('objectId')
      const object = this.objectManager.objects.getById(objectId)
      
      if (object && this.store) {
        this.store.emit('object-click', {
          id: objectId,
          properties: object.properties,
          coordinates: {
            lat: object.geometry.coordinates[1],
            lng: object.geometry.coordinates[0]
          }
        })
      }
    })

    // Клик по кластеру  
    this.objectManager.clusters.events.add('click', (e: any) => {
      const cluster = e.get('target')
      const objects = cluster.properties.geoObjects
      
      if (this.store) {
        this.store.emit('cluster-click', objects)
      }
    })
  }

  clearAll() {
    if (this.objectManager) {
      this.objectManager.removeAll()
    }
  }

  destroy() {
    if (this.objectManager && this.map) {
      this.map.geoObjects.remove(this.objectManager)
      this.objectManager = null
    }
  }
}
```

#### **6.3: Расширение MapStore для продвинутых возможностей (День 8, 3 часа)**

```typescript
// features/map/core/MapStore.ts - ДОПОЛНЕНИЕ К СУЩЕСТВУЮЩЕМУ
/**
 * ДОПОЛНИТЕЛЬНЫЕ методы из Av patern
 * Добавляем к существующему MapStore
 */

// Дополнительные методы (добавить в класс MapStore)
export class MapStore {
  // ... существующие методы ...

  /**
   * Система событий как в Av patern
   * Детальные события с полной информацией
   */
  private eventListeners = new Map<string, Function[]>()

  on(event: string, handler: Function) {
    if (!this.eventListeners.has(event)) {
      this.eventListeners.set(event, [])
    }
    this.eventListeners.get(event)!.push(handler)
  }

  off(event: string, handler?: Function) {
    if (!handler) {
      this.eventListeners.delete(event)
      return
    }
    
    const handlers = this.eventListeners.get(event)
    if (handlers) {
      const index = handlers.indexOf(handler)
      if (index > -1) {
        handlers.splice(index, 1)
      }
    }
  }

  emit(event: string, data?: any) {
    const handlers = this.eventListeners.get(event) || []
    handlers.forEach(handler => handler(data))
  }

  /**
   * Продвинутое управление границами
   * Адаптированная логика из Av patern Map.js:442-485
   */
  setBounds(bounds: [[number, number], [number, number]], options: any = {}) {
    const map = this.getMapInstance()
    if (!map) return

    const {
      zoomMargin = 0,
      useMapMargin = true, 
      checkZoomRange = true,
      duration = 300
    } = options

    // Интеграция с AnimationEngine
    if (this.animationEngine) {
      return this.animationEngine.setBounds(bounds, options)
    }

    // Fallback к обычному методу
    return map.setBounds(bounds, { duration })
  }

  /**
   * Плавное перемещение по точкам
   * Интеграция с AnimationEngine из Av patern
   */
  async panTo(points: Coordinates[], options?: any) {
    if (this.animationEngine) {
      return this.animationEngine.panTo(points, options)
    }
    
    // Fallback - простое перемещение к последней точке
    const lastPoint = points[points.length - 1]
    this.setCenter(lastPoint)
  }

  /**
   * Bulk операции для ObjectManager
   */
  addBulkMarkers(markers: MapMarker[]) {
    this.emit('bulk-markers-add', markers)
  }

  setMarkersFilter(filterFn: (marker: MapMarker) => boolean) {
    this.emit('markers-filter', filterFn)
  }

  // Инициализация AnimationEngine
  private animationEngine: any = null
  
  setAnimationEngine(engine: any) {
    this.animationEngine = engine
  }
}
```

#### **6.4: Улучшенная система плагинов с lazy loading (День 9, 3 часа)**

```typescript
// features/map/core/PluginManager.ts - НОВЫЙ ФАЙЛ
/**
 * Менеджер плагинов с lazy loading
 * Inspired by Av patern модульной системы
 */
export class PluginManager {
  private plugins = new Map<string, MapPlugin>()
  private loadedPlugins = new Set<string>()
  private map: any = null
  private store: MapStore | null = null

  constructor(map: any, store: MapStore) {
    this.map = map
    this.store = store
  }

  /**
   * Lazy loading плагинов по требованию
   */
  async use(pluginName: string, options?: any) {
    if (this.loadedPlugins.has(pluginName)) {
      console.warn(`[PluginManager] Plugin ${pluginName} already loaded`)
      return
    }

    try {
      // Динамический импорт плагина
      const { default: PluginClass } = await this.importPlugin(pluginName)
      const plugin = new PluginClass(options)
      
      // Устанавливаем плагин
      if (plugin.install) {
        await plugin.install(this.map, this.store!)
      }

      this.plugins.set(pluginName, plugin)
      this.loadedPlugins.add(pluginName)
      
      console.log(`[PluginManager] ✅ Plugin ${pluginName} loaded`)
    } catch (error) {
      console.error(`[PluginManager] ❌ Failed to load plugin ${pluginName}:`, error)
      throw error
    }
  }

  private async importPlugin(name: string) {
    switch (name) {
      case 'cluster':
        return import(
          /* webpackChunkName: "map-cluster" */
          '../plugins/ClusterPlugin'
        )
      case 'objectManager':
        return import(
          /* webpackChunkName: "map-object-manager" */
          '../plugins/ObjectManagerPlugin'
        )
      case 'geolocation':
        return import(
          /* webpackChunkName: "map-geolocation" */  
          '../plugins/GeolocationPlugin'
        )
      case 'search':
        return import(
          /* webpackChunkName: "map-search" */
          '../plugins/SearchPlugin'
        )
      case 'markers':
        return import(
          /* webpackChunkName: "map-markers" */
          '../plugins/MarkersPlugin'
        )
      default:
        throw new Error(`Unknown plugin: ${name}`)
    }
  }

  /**
   * Получение плагина (для доступа к API)
   */
  getPlugin<T = any>(name: string): T | null {
    return this.plugins.get(name) as T || null
  }

  /**
   * Проверка загрузки плагина
   */
  isPluginLoaded(name: string): boolean {
    return this.loadedPlugins.has(name)
  }

  /**
   * Уничтожение всех плагинов
   */
  destroy() {
    for (const plugin of this.plugins.values()) {
      if (plugin.destroy) {
        plugin.destroy()
      }
    }
    this.plugins.clear()
    this.loadedPlugins.clear()
  }
}
```

#### **6.5: Интеграция в MapCore.vue (День 10, 2 часа)**

```vue
<!-- Обновление MapCore.vue для интеграции новых возможностей -->
<script setup lang="ts">
// ... существующие импорты ...
import { AnimationEngine } from '../lib/AnimationEngine'
import { PluginManager } from '../core/PluginManager'

// ... существующий код ...

// Добавляем новые возможности
let animationEngine: AnimationEngine | null = null
let pluginManager: PluginManager | null = null

// Обновляем инициализацию
async function initMap() {
  try {
    store.setLoading(true)
    
    const ymaps = await mapLoader.load(props.apiKey)
    const map = new ymaps.Map(mapId, mapConfig)
    
    // НОВОЕ: Инициализируем движки
    animationEngine = new AnimationEngine(map)
    pluginManager = new PluginManager(map, store)
    
    // Связываем с store
    store.setAnimationEngine(animationEngine)
    store.setMapInstance(map)
    
    setupBaseHandlers(map)
    store.setReady(true)
    emit('ready', map)
    
  } catch (error) {
    // ... обработка ошибки ...
  }
}

// НОВОЕ: Расширенное публичное API
function panTo(points: Coordinates[], options?: any) {
  return animationEngine?.panTo(points, options)
}

function setBounds(bounds: any, options?: any) {
  const map = store.getMapInstance()
  return animationEngine?.setBounds(bounds, options)
}

async function usePlugin(name: string, options?: any) {
  return pluginManager?.use(name, options)
}

function getPlugin<T = any>(name: string): T | null {
  return pluginManager?.getPlugin<T>(name) || null
}

// Обновляем expose
defineExpose({
  // Старые методы
  use: usePlugin, // Совместимость
  setCenter,
  getCenter,
  destroy,
  store,
  
  // НОВЫЕ методы из Av patern
  panTo,
  setBounds,
  usePlugin,
  getPlugin,
  
  // Доступ к движкам
  animationEngine,
  pluginManager
})
</script>
```

---

## 🏆 **ОБНОВЛЕННАЯ ЦЕЛЕВАЯ АРХИТЕКТУРА: Гибридная**

### **📁 Финальная структура (15 файлов, ~900 строк)**

```
/features/map/
├── core/ (4 файла, ~350 строк)
│   ├── MapCore.vue          // 150 строк - оптимизированное ядро
│   ├── MapStore.ts          // 120 строк - расширенный store
│   ├── MapLoader.ts         // 50 строк - singleton загрузчик
│   └── PluginManager.ts     // 80 строк - менеджер плагинов
├── lib/ (2 файла, ~200 строк)  
│   ├── AnimationEngine.ts   // 150 строк - движок анимаций из Av patern
│   └── mapHelpers.ts        // 50 строк - утилиты
├── plugins/ (5 файлов, ~300 строк)
│   ├── ClusterPlugin.ts     // 60 строк
│   ├── GeolocationPlugin.ts // 40 строк
│   ├── SearchPlugin.ts      // 50 строк
│   ├── MarkersPlugin.ts     // 70 строк
│   └── ObjectManagerPlugin.ts // 80 строк - НОВЫЙ для больших данных
├── components/ (3 файла, ~190 строк)
│   ├── MapContainer.vue     // 100 строк - главный контейнер
│   ├── MapControls.vue      // 50 строк - UI контролы
│   └── MapStates.vue        // 40 строк - состояния
└── types/ (1 файл, ~100 строк)
    └── index.ts             // Все TypeScript типы
```

### **⚡ Ключевые преимущества гибридной архитектуры:**

1. **Vue 3 реактивность + Av patern алгоритмы** 💎
2. **TypeScript типизация + нативная производительность** 🚀  
3. **Модульность FSD + функциональная полнота** 🏗️
4. **Lazy loading плагинов + минимальный ядро** ⚡
5. **Обратная совместимость + расширяемость** 🔄

---

## 📋 **ПЛАН ДОРАБОТКИ НА ОСНОВЕ ТЕКУЩЕГО СОСТОЯНИЯ**

### **🚨 СОСТОЯНИЕ НА 31.08.2025:**
- ✅ **Фазы 0-4 выполнены на 100%**
- ❌ **Фаза 5 (оптимизация) НЕ ВЫПОЛНЕНА**  
- 🚨 **MapCore.vue: 544 строки (план: 150)**
- 🚨 **Console.log в production**

### **⚡ ФАЗА 5: КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ (1-2 дня)**

#### **День 11 (СРОЧНО): Техническая очистка**

**Утро (2 часа):**
```bash
# 1. Убрать ALL console.log из production (30 мин)
find resources/js/src/features/map -name "*.vue" -o -name "*.ts" | \
  xargs sed -i '/console\.log/d'

# 2. Проверить что карта работает (30 мин)  
npm run dev
# Открыть /ad/create и проверить функциональность

# 3. Замерить performance baseline (1 час)
node scripts/measure-map-performance.js
```

**День (4 часа):**  
```vue
<!-- 4. Разбить MapCore.vue на composables -->
<!-- features/map/core/MapCore.vue (ЦЕЛЬ: 150 строк) -->
<script setup lang="ts">
// Вместо 544 строк в одном файле
import { useMapInitializer } from '../composables/useMapInitializer'  // 140 строк
import { useMapHandlers } from '../composables/useMapHandlers'        // 100 строк  
import { useMapLifecycle } from '../composables/useMapLifecycle'      // 60 строк

const { initMap, loading, error } = useMapInitializer(props)
const { setupHandlers } = useMapHandlers()  
const { onMount, onUnmount } = useMapLifecycle()

// Основной код: только 150 строк!
</script>
```

### **🚀 ФАЗА 6: ИНТЕГРАЦИЯ AV PATERN (2-3 дня)**

#### **День 12: AnimationEngine**
- Портировать panTo() из Av patern Map.js:330-396
- Портировать setBounds() из Av patern Map.js:442-485
- Интегрировать в MapCore через animationEngine

#### **День 13: ObjectManagerPlugin**  
- Адаптировать ObjectManager из Av patern ObjectManager.js
- Добавить поддержку 10,000+ маркеров
- Интегрировать фильтрацию и кластеризацию

#### **День 14: Расширенные события**
- Портировать систему событий из Av patern
- Добавить детальные события boundschange, statechange
- Обновить все плагины для новых событий

---

## 🎯 **ОБНОВЛЕННЫЕ МЕТРИКИ УСПЕХА**

### **📊 Текущие показатели (оценочно):**
- Файлов: ~15 
- Строк кода: ~1200  
- Bundle size: ~150KB (нужна проверка)
- Время загрузки: ~1.5 сек (нужна проверка)
- Покрытие тестами: ~60%
- Работает: ✅ (с избыточным логированием)

### **🎯 Целевые показатели после доработки:**
- Файлов: 15 (стабильно)
- Строк кода: ~900 ✨ **(-25%)**
- Bundle size: <100KB ✨ **(-33%)**  
- Время загрузки: <1 секунда ✨ **(-33%)**
- Покрытие тестами: >80% ✨ **+20%**
- **НОВОЕ**: Поддержка 10,000+ маркеров ⚡
- **НОВОЕ**: Плавные анимации panTo() 🎬
- **НОВОЕ**: Расширенные границы setBounds() 📐

---

## 📚 **ОБНОВЛЕННАЯ ДОКУМЕНТАЦИЯ**

### **Новые примеры использования:**

```vue
<!-- Базовая карта (как было) -->
<YandexMap v-model="coordinates" />

<!-- С продвинутыми анимациями (НОВОЕ) -->
<YandexMap 
  ref="mapRef"
  v-model="coordinates"
  @ready="setupAnimations"
/>
<script>
async function setupAnimations() {
  // Плавный тур по нескольким точкам
  await mapRef.value.panTo([
    { lat: 55.7558, lng: 37.6176 }, // Москва
    { lat: 59.9311, lng: 30.3609 }, // СПб  
    { lat: 55.8431, lng: 37.3806 }  // Зеленоград
  ], {
    duration: 1000,
    delay: 2000,
    flying: true
  })
}
</script>

<!-- Карта с большими данными (НОВОЕ) -->
<YandexMap
  ref="bigDataMapRef" 
  mode="big-data"
  :markers="tenThousandMarkers"
  @ready="setupBigDataMode"
/>
<script>
async function setupBigDataMode() {
  // Включаем ObjectManager для производительности
  await mapRef.value.usePlugin('objectManager', {
    clusterize: true,
    gridSize: 64
  })
  
  // Добавляем фильтр
  mapRef.value.setMarkersFilter(marker => 
    marker.rating > 4.5 && marker.isActive
  )
}
</script>

<!-- Карта с умными границами (НОВОЕ) -->  
<YandexMap
  ref="boundsMapRef"
  @ready="setupSmartBounds"
/>
<script>
async function setupSmartBounds() {
  // Установка границ с отступами и автозумом
  mapRef.value.setBounds([
    [55.55, 37.42], // ЮЗ угол
    [55.95, 37.82]  // СВ угол
  ], {
    zoomMargin: 50,        // Отступ в пикселях
    useMapMargin: true,    // Учесть отступы карты
    checkZoomRange: true,  // Проверить диапазон
    duration: 500          // Анимация
  })
}
</script>
```

---

## 🎯 РЕЗУЛЬТАТ ПОСЛЕ ДОРАБОТКИ

Получим **ULTIMATE карту** с лучшим из двух миров:

1. ✅ **Современная архитектура** (Vue 3 + TypeScript + FSD)
2. ✅ **Продвинутая функциональность** (panTo, setBounds, ObjectManager) 
3. ✅ **Высокая производительность** (lazy loading, оптимизация памяти)
4. ✅ **Простота использования** (минимальное API для простых задач)
5. ✅ **Расширяемость** (плагинная система + composables)
6. ✅ **Обратная совместимость** (100% сохранена)
7. ✅ **Enterprise готовность** (тесты, документация, производительность)

**🚨 КРИТИЧЕСКИ ВАЖНО: Сначала выполнить Фазу 5 (очистка), потом Фазу 6 (интеграция)!**
