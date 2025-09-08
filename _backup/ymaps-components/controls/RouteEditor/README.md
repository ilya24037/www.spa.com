# 🛣️ RouteEditor - Редактор маршрутов

Мощный и интуитивный редактор маршрутов для Yandex Maps с поддержкой множественных путевых точек, оптимизации и интерактивного редактирования.

## 📋 Особенности

- ✅ **Множественные путевые точки** - До 8 точек с drag & drop
- ✅ **4 режима передвижения** - Автомобиль, пешком, транспорт, велосипед
- ✅ **Оптимизация маршрутов** - Автоматический поиск кратчайшего пути
- ✅ **Интерактивное редактирование** - Клик по карте, перетаскивание точек
- ✅ **TypeScript** - Полная типизация без any
- ✅ **Vue 3 поддержка** - Готовый Vue компонент с Composition API
- ✅ **v-model интеграция** - Двустороннее связывание режима передвижения
- ✅ **Альтернативные маршруты** - Несколько вариантов пути
- ✅ **Ограничения маршрута** - Избегать платных дорог, автомагистралей
- ✅ **Навигационные инструкции** - Пошаговые направления
- ✅ **Production-ready** - Полная обработка ошибок и edge cases

## 🚀 Быстрый старт

### Vanilla JavaScript

```javascript
import RouteEditor from './RouteEditor.js'
import YMapsCore from '../../core/YMapsCore.js'

async function initMap() {
  // Создаем карту
  const mapsCore = new YMapsCore({ apiKey: 'YOUR_API_KEY' })
  await mapsCore.loadAPI()
  const map = await mapsCore.createMap('map')
  
  // Создаем редактор маршрутов
  const routeEditor = new RouteEditor({
    position: 'topLeft',
    travelModes: ['driving', 'walking', 'transit'],
    defaultTravelMode: 'driving',
    maxWaypoints: 6,
    enableOptimization: true,
    enableDragDrop: true
  })
  
  // Добавляем на карту
  await routeEditor.addToMap(map)
  
  // Устанавливаем путевые точки
  await routeEditor.setWaypoint(0, 'Москва, Красная площадь')
  await routeEditor.setWaypoint(1, 'Москва, Театральная площадь')
  
  // Обработчики событий
  routeEditor.on('routecalculated', (event) => {
    console.log(`Построено ${event.routes.length} маршрутов`)
    event.routes.forEach((route, index) => {
      console.log(`Маршрут ${index + 1}: ${route.distance}м, ${route.duration}с`)
    })
  })
  
  routeEditor.on('waypointset', (event) => {
    console.log(`Точка ${event.index}: ${event.waypoint.address}`)
  })
}
```

### Vue 3 Composition API

```vue
<template>
  <div id="map" style="height: 500px"></div>
  
  <!-- RouteEditor с полной интеграцией -->
  <RouteEditorVue
    :map="map"
    v-model:travel-mode="currentTravelMode"
    :max-waypoints="8"
    :enable-optimization="true"
    :show-alternatives="true"
    :avoid-tolls="avoidTolls"
    :avoid-highways="avoidHighways"
    :show-external-waypoints="true"
    :show-external-routes="true"
    :show-instructions="true"
    :show-stats="true"
    position="topRight"
    @routecalculated="onRouteCalculated"
    @waypointset="onWaypointSet"
    @optimize="onRouteOptimized"
    @ready="onEditorReady"
  />
  
  <!-- Панель управления -->
  <div class="route-controls">
    <h3>Управление маршрутом</h3>
    
    <div class="control-group">
      <label>
        <input 
          v-model="avoidTolls" 
          type="checkbox"
        >
        Избегать платных дорог
      </label>
      <label>
        <input 
          v-model="avoidHighways" 
          type="checkbox"
        >
        Избегать автомагистралей
      </label>
    </div>
    
    <div class="route-actions">
      <button @click="calculateRoute" :disabled="isCalculating">
        {{ isCalculating ? 'Расчет...' : 'Построить маршрут' }}
      </button>
      <button @click="optimizeRoute" :disabled="!canOptimize">
        Оптимизировать
      </button>
      <button @click="clearRoute">
        Очистить
      </button>
    </div>
    
    <!-- Экспорт маршрута -->
    <div v-if="activeRoute" class="route-export">
      <button @click="exportRoute('gpx')">Экспорт GPX</button>
      <button @click="exportRoute('kml')">Экспорт KML</button>
    </div>
  </div>
  
  <!-- Сводка маршрута -->
  <div v-if="activeRoute" class="route-summary">
    <h4>{{ activeRoute.description || 'Текущий маршрут' }}</h4>
    <div class="summary-stats">
      <div class="stat">
        <span class="stat-label">Расстояние:</span>
        <span class="stat-value">{{ formatDistance(activeRoute.distance) }}</span>
      </div>
      <div class="stat">
        <span class="stat-label">Время в пути:</span>
        <span class="stat-value">{{ formatDuration(activeRoute.duration) }}</span>
      </div>
      <div class="stat">
        <span class="stat-label">Путевых точек:</span>
        <span class="stat-value">{{ activeRoute.waypoints.length }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import YMapsCore from '@/ymaps-components/core/YMapsCore'
import RouteEditorVue from '@/ymaps-components/controls/RouteEditor/RouteEditor.vue'
import type { Route, Waypoint } from '@/ymaps-components/controls/RouteEditor/RouteEditor.d.ts'

const map = ref(null)
const routeEditor = ref()
const currentTravelMode = ref('driving')
const avoidTolls = ref(false)
const avoidHighways = ref(false)
const activeRoute = ref<Route | null>(null)
const isCalculating = ref(false)

const canOptimize = computed(() => {
  return routeEditor.value?.getWaypoints().length >= 3
})

onMounted(async () => {
  const mapsCore = new YMapsCore({ apiKey: 'YOUR_KEY' })
  await mapsCore.loadAPI()
  map.value = await mapsCore.createMap('map', {
    center: [55.753994, 37.622093],
    zoom: 11
  })
})

const onRouteCalculated = (event) => {
  console.log('Маршруты построены:', event.routes)
  activeRoute.value = event.routes[event.activeIndex] || null
}

const onWaypointSet = (event) => {
  console.log(`Установлена точка ${event.index}: ${event.waypoint.address}`)
}

const onRouteOptimized = (event) => {
  console.log('Маршрут оптимизирован:', event.waypoints)
}

const onEditorReady = (editor) => {
  console.log('RouteEditor готов к использованию')
  routeEditor.value = editor
  
  // Устанавливаем начальные точки
  editor.setWaypoint(0, 'Москва, Кремль')
  editor.setWaypoint(1, 'Москва, МГУ')
}

const calculateRoute = async () => {
  if (!routeEditor.value) return
  
  isCalculating.value = true
  try {
    const routes = await routeEditor.value.calculateRoute()
    activeRoute.value = routes[0] || null
  } catch (error) {
    console.error('Ошибка расчета маршрута:', error)
  } finally {
    isCalculating.value = false
  }
}

const optimizeRoute = async () => {
  if (!routeEditor.value) return
  
  try {
    await routeEditor.value.optimizeRoute()
  } catch (error) {
    console.error('Ошибка оптимизации:', error)
  }
}

const clearRoute = () => {
  if (routeEditor.value) {
    routeEditor.value.clear()
    activeRoute.value = null
  }
}

const exportRoute = (format: 'gpx' | 'kml') => {
  if (!activeRoute.value) return
  
  // Логика экспорта маршрута
  console.log(`Экспорт маршрута в формате ${format.toUpperCase()}`)
  
  // Пример создания GPX
  if (format === 'gpx') {
    const gpxData = createGPX(activeRoute.value)
    downloadFile(gpxData, 'route.gpx', 'application/gpx+xml')
  }
}

const formatDistance = (meters: number): string => {
  return meters < 1000 
    ? `${Math.round(meters)} м` 
    : `${(meters / 1000).toFixed(1)} км`
}

const formatDuration = (seconds: number): string => {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  return hours > 0 ? `${hours} ч ${minutes} мин` : `${minutes} мин`
}
</script>
```

## ⚙️ Конфигурация

### Опции конструктора (JavaScript)

```typescript
interface RouteEditorOptions {
  // Режимы передвижения
  travelModes?: TravelMode[]                    // Доступные режимы
  defaultTravelMode?: TravelMode               // Режим по умолчанию
  
  // Путевые точки
  maxWaypoints?: number                        // Максимум точек (2-23)
  enableDragDrop?: boolean                     // Перетаскивание точек
  enableOptimization?: boolean                 // Оптимизация порядка
  
  // Отображение
  showDistanceTime?: boolean                   // Показать расст./время
  showAlternatives?: boolean                   // Альтернативные маршруты
  
  // Ограничения маршрута
  avoidTolls?: boolean                         // Избегать платных дорог
  avoidHighways?: boolean                      // Избегать автомагистралей
  avoidFerries?: boolean                       // Избегать паромов
  
  // Локализация
  units?: 'metric' | 'imperial'                // Единицы измерения
  language?: string                            // Язык инструкций
  
  // Кастомизация
  waypointRenderer?: (waypoint, index) => HTMLElement
  routeRenderer?: (route) => HTMLElement
  instructionsRenderer?: (instructions) => HTMLElement
  
  // Базовые опции контрола
  position?: string                            // Позиция на карте
  visible?: boolean                            // Видимость
  enabled?: boolean                            // Активность
  zIndex?: number                              // Z-index
  margin?: object                              // Отступы
}
```

### Props Vue компонента

```typescript
interface Props {
  map?: any                                    // Экземпляр карты
  travelMode?: TravelMode                      // Режим передвижения (v-model)
  travelModes?: TravelMode[]                   // Доступные режимы
  defaultTravelMode?: TravelMode               // По умолчанию
  maxWaypoints?: number                        // Максимум точек
  enableDragDrop?: boolean                     // Drag & drop
  enableOptimization?: boolean                 // Оптимизация
  showDistanceTime?: boolean                   // Показать время
  showAlternatives?: boolean                   // Альтернативы
  avoidTolls?: boolean                         // Избегать платных
  avoidHighways?: boolean                      // Избегать автомагистралей
  avoidFerries?: boolean                       // Избегать паромов
  units?: 'metric' | 'imperial'                // Единицы
  language?: string                            // Язык
  position?: string                            // Позиция
  visible?: boolean                            // Видимость
  enabled?: boolean                            // Активность
  showExternalWaypoints?: boolean              // Внешний список точек
  showExternalRoutes?: boolean                 // Внешний список маршрутов
  showInstructions?: boolean                   // Инструкции
  showStats?: boolean                          // Статистика
  showDebugInfo?: boolean                      // Debug информация
  zIndex?: number                              // Z-index
  margin?: object                              // Отступы
  extendedOptions?: object                     // Расширенные опции
}
```

## 🔧 API методы

### JavaScript класс

```typescript
class RouteEditor {
  // Путевые точки
  setWaypoint(index: number, location: string | [number, number]): Promise<void>
  getWaypoint(index: number): Waypoint | null
  getWaypoints(): Waypoint[]
  addWaypoint(location?, index?): Promise<number>
  removeWaypoint(index: number): void
  
  // Режим передвижения
  setTravelMode(mode: TravelMode): void
  getTravelMode(): TravelMode
  getAvailableTravelModes(): TravelMode[]
  
  // Маршруты
  calculateRoute(): Promise<Route[]>
  getRoutes(): Route[]
  getActiveRoute(): Route | null
  selectRoute(routeIndex: number): void
  optimizeRoute(): Promise<Waypoint[]>
  
  // Управление
  clear(): void
  setEditingMode(enabled: boolean): void
  isEditingMode(): boolean
  isCalculating(): boolean
  
  // Настройки
  setConstraints(constraints: Partial<RouteConstraints>): void
  getConstraints(): RouteConstraints
  setUnits(units: 'metric' | 'imperial'): void
  getUnits(): 'metric' | 'imperial'
  setMaxWaypoints(maxWaypoints: number): void
  getMaxWaypoints(): number
  
  // События
  on(event: string, handler: Function): void
  off(event: string, handler: Function): void
  
  // Жизненный цикл
  addToMap(map: ymaps.Map): Promise<void>
  removeFromMap(): Promise<void>
  destroy(): void
}
```

### Vue компонент (defineExpose)

```typescript
// Методы, доступные через template ref
interface ExposedMethods {
  getControl(): RouteEditor | null             // JS экземпляр
  setWaypoint(index: number, location: string | [number, number]): Promise<void>
  getWaypoint(index: number): Waypoint | null
  getWaypoints(): Waypoint[]
  addWaypoint(location?, index?): Promise<number>
  removeWaypoint(index: number): void
  setTravelMode(mode: TravelMode): void
  getTravelMode(): TravelMode
  calculateRoute(): Promise<Route[]>
  getRoutes(): Route[]
  getActiveRoute(): Route | null
  selectRoute(index: number): void
  optimizeRoute(): Promise<Waypoint[]>
  clear(): void
  recreate(): Promise<void>
}

// Использование в родительском компоненте
const routeEditorRef = ref()

const addStopover = async () => {
  await routeEditorRef.value.addWaypoint('Москва, Парк Горького')
}

const optimizeTrip = async () => {
  await routeEditorRef.value.optimizeRoute()
}
```

## 📡 События

### JavaScript

```javascript
routeEditor.on('travelmodechange', (event) => {
  console.log(`Режим изменен: ${event.oldMode} → ${event.newMode}`)
})

routeEditor.on('waypointadd', (event) => {
  console.log(`Добавлена точка ${event.index}, всего: ${event.total}`)
})

routeEditor.on('waypointset', (event) => {
  console.log(`Точка ${event.index}: ${event.waypoint.address}`)
})

routeEditor.on('calculatestart', () => {
  console.log('Начинаем расчет маршрута...')
  showLoadingSpinner()
})

routeEditor.on('routecalculated', (event) => {
  console.log(`Построено ${event.routes.length} маршрутов`)
  event.routes.forEach((route, index) => {
    console.log(`Маршрут ${index + 1}:`)
    console.log(`  Расстояние: ${route.distance}м`)
    console.log(`  Время: ${route.duration}с`)
    console.log(`  Инструкций: ${route.instructions.length}`)
  })
})

routeEditor.on('routeselect', (event) => {
  console.log(`Выбран маршрут ${event.newIndex}`)
  updateNavigationPanel(event.route)
})

routeEditor.on('optimize', (event) => {
  console.log('Маршрут оптимизирован')
  console.log('Новый порядок точек:', event.waypoints.map(wp => wp.address))
})

// Интерактивные события
routeEditor.on('routeclick', (event) => {
  console.log(`Клик по маршруту ${event.routeIndex} в точке:`, event.coordinates)
})

routeEditor.on('waypointdrag', (event) => {
  console.log(`Точка ${event.index} перемещена:`, event.coordinates)
})
```

### Vue

```vue
<template>
  <RouteEditorVue
    :map="map"
    v-model:travel-mode="travelMode"
    @travelmodechange="onTravelModeChange"
    @waypointadd="onWaypointAdd"
    @waypointset="onWaypointSet"
    @waypointremove="onWaypointRemove"
    @calculatestart="onCalculateStart"
    @calculateend="onCalculateEnd"
    @routecalculated="onRouteCalculated"
    @routeselect="onRouteSelect"
    @optimize="onOptimize"
    @clear="onClear"
    @error="onError"
    @ready="onReady"
  />
</template>

<script setup>
const onRouteCalculated = (data) => {
  const { routes, activeIndex } = data
  console.log(`Построено маршрутов: ${routes.length}`)
  
  // Сохраняем в аналитику
  analytics.track('route_calculated', {
    routeCount: routes.length,
    totalDistance: routes[activeIndex]?.distance,
    totalDuration: routes[activeIndex]?.duration,
    waypointCount: routes[activeIndex]?.waypoints.length
  })
}

const onOptimize = (data) => {
  console.log('Оптимизация выполнена')
  
  // Показываем уведомление
  showNotification('Маршрут оптимизирован для экономии времени')
}

const onError = (error) => {
  console.error('Ошибка редактора маршрутов:', error.message)
  showErrorToast('Не удалось построить маршрут. Попробуйте позже.')
}
</script>
```

## 🎨 Кастомизация стилей

### CSS переменные

```css
.ymaps-route-editor {
  --panel-width: 320px;           /* Ширина панели */
  --panel-padding: 16px;          /* Внутренние отступы */
  --waypoint-height: 44px;        /* Высота путевой точки */
  --button-height: 36px;          /* Высота кнопок */
  --border-radius: 8px;           /* Скругление */
  --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Размеры */
.ymaps-route-editor--compact {
  --panel-width: 280px;
  --panel-padding: 12px;
  --waypoint-height: 36px;
  --button-height: 32px;
}

.ymaps-route-editor--expanded {
  --panel-width: 400px;
  --panel-padding: 20px;
  --waypoint-height: 52px;
  --button-height: 40px;
}
```

### Кастомные стили

```css
/* Стильная панель редактора */
.ymaps-route-editor-panel {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  backdrop-filter: blur(10px);
}

/* Красивые путевые точки */
.route-editor-waypoint {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  transition: all 0.3s ease;
}

.route-editor-waypoint:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* Анимированные кнопки */
.route-editor-calculate {
  background: linear-gradient(45deg, #ff6b6b, #ee5a52);
  border: none;
  color: white;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  transition: all 0.3s ease;
}

.route-editor-calculate:hover {
  background: linear-gradient(45deg, #ee5a52, #ff6b6b);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(238, 90, 82, 0.4);
}

/* Стилизация результатов маршрута */
.route-item--active {
  background: linear-gradient(90deg, rgba(59, 130, 246, 0.1) 0%, transparent 100%);
  border-left: 4px solid #3b82f6;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
}

/* Темная тема */
@media (prefers-color-scheme: dark) {
  .ymaps-route-editor-panel {
    background: rgba(30, 30, 30, 0.95);
    border: 1px solid #374151;
  }
  
  .route-editor-waypoint {
    background: rgba(55, 65, 81, 0.8);
    border-color: #4b5563;
    color: #f9fafb;
  }
  
  .waypoint-input {
    background: transparent;
    color: #f9fafb;
    border: 1px solid #4b5563;
  }
  
  .waypoint-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }
}
```

## 📱 Адаптивность

RouteEditor автоматически адаптируется под разные экраны:

```css
/* Мобильные устройства */
@media (max-width: 768px) {
  .ymaps-route-editor {
    --panel-width: 100vw;        /* Полная ширина */
    --waypoint-height: 52px;     /* Больше для touch */
    --button-height: 44px;       /* Минимум 44px для iOS */
  }
  
  .ymaps-route-editor-panel {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    max-height: 60vh;
    border-radius: 16px 16px 0 0;
    z-index: 10000;
  }
  
  .waypoint-input {
    font-size: 16px;             /* Предотвращает zoom в iOS */
    padding: 12px 16px;
  }
  
  /* Сенсорные элементы */
  .waypoint-remove,
  .waypoint-drag-handle {
    min-width: 44px;
    min-height: 44px;
  }
}

/* Планшеты */
@media (min-width: 768px) and (max-width: 1024px) {
  .ymaps-route-editor {
    --panel-width: 350px;
  }
  
  .ymaps-route-editor-panel {
    max-height: 80vh;
    overflow-y: auto;
  }
}

/* Десктоп */
@media (min-width: 1024px) {
  .ymaps-route-editor-panel {
    max-height: 90vh;
  }
  
  /* Показываем дополнительные элементы */
  .route-editor-advanced-options {
    display: block;
  }
}
```

## 🎯 Продвинутые примеры

### Интеграция с доставкой

```javascript
const deliveryRouteEditor = new RouteEditor({
  travelModes: ['driving'],
  defaultTravelMode: 'driving',
  maxWaypoints: 15,
  enableOptimization: true,
  avoidTolls: true, // Экономим на доставке
  
  // Кастомные иконки для типов точек
  waypointRenderer: (waypoint, index) => {
    const icons = {
      0: '🏪',      // Склад
      '-1': '🏠'    // Клиент (последняя точка)
    }
    const icon = index === 0 ? icons[0] : 
                 index === waypoint.length - 1 ? icons[-1] : '📦'
    
    return `<div class="delivery-waypoint">
      <span class="waypoint-icon">${icon}</span>
      <span class="waypoint-address">${waypoint.address}</span>
    </div>`
  }
})

// Добавляем точки доставки
const deliveryPoints = [
  'Москва, склад на Каширском шоссе',
  'Москва, ул. Тверская, 15',
  'Москва, проспект Мира, 45',
  'Москва, Садовое кольцо, 23'
]

deliveryPoints.forEach(async (point, index) => {
  await deliveryRouteEditor.setWaypoint(index, point)
})

// Оптимизируем маршрут для экономии топлива
deliveryRouteEditor.on('routecalculated', async (event) => {
  console.log(`Маршрут доставки: ${event.routes[0].distance}м`)
  
  // Автоматическая оптимизация
  if (deliveryPoints.length > 3) {
    await deliveryRouteEditor.optimizeRoute()
  }
})
```

### Туристические маршруты

```javascript
const touristRouteEditor = new RouteEditor({
  travelModes: ['walking', 'transit'],
  defaultTravelMode: 'walking',
  maxWaypoints: 8,
  showAlternatives: true,
  units: 'metric',
  
  // Кастомное отображение достопримечательностей
  waypointRenderer: (waypoint, index) => {
    const attractions = {
      'Красная площадь': '🏛️',
      'Третьяковская галерея': '🎨',
      'Парк Горького': '🌳',
      'Храм Христа Спасителя': '⛪'
    }
    
    const icon = Object.keys(attractions).find(attraction => 
      waypoint.address.includes(attraction)
    ) ? attractions[attraction] : '📍'
    
    return `<div class="tourist-waypoint">
      ${icon} ${waypoint.address}
      <div class="waypoint-type">Достопримечательность</div>
    </div>`
  }
})

// Создаем туристический маршрут
const touristRoute = [
  'Москва, Красная площадь',
  'Москва, Третьяковская галерея', 
  'Москва, Парк Горького',
  'Москва, Храм Христа Спасителя'
]

touristRoute.forEach(async (attraction, index) => {
  await touristRouteEditor.setWaypoint(index, attraction)
})

// Показываем альтернативные пешеходные маршруты
touristRouteEditor.on('routecalculated', (event) => {
  event.routes.forEach((route, index) => {
    console.log(`Вариант ${index + 1}:`)
    console.log(`  Расстояние: ${(route.distance / 1000).toFixed(1)} км`)
    console.log(`  Время пешком: ${Math.round(route.duration / 60)} минут`)
  })
})
```

### Vue с локальным хранением

```vue
<template>
  <RouteEditorVue
    :map="map"
    v-model:travel-mode="travelMode"
    @routecalculated="saveRouteToStorage"
    @clear="clearStoredRoute"
    @ready="loadStoredRoute"
  />
  
  <!-- История маршрутов -->
  <div v-if="routeHistory.length > 0" class="route-history">
    <h3>Сохраненные маршруты</h3>
    <div 
      v-for="savedRoute in routeHistory" 
      :key="savedRoute.id"
      class="saved-route"
      @click="loadRoute(savedRoute)"
    >
      <div class="route-info">
        <h4>{{ savedRoute.name }}</h4>
        <p>{{ savedRoute.waypoints.length }} точек, {{ formatDistance(savedRoute.distance) }}</p>
        <span class="route-date">{{ formatDate(savedRoute.date) }}</span>
      </div>
      <button @click.stop="deleteRoute(savedRoute.id)">
        Удалить
      </button>
    </div>
  </div>
</template>

<script setup>
const routeHistory = ref([])
const routeEditor = ref()

const saveRouteToStorage = (event) => {
  const route = event.routes[event.activeIndex]
  if (!route) return
  
  const savedRoute = {
    id: Date.now(),
    name: `Маршрут ${new Date().toLocaleDateString()}`,
    waypoints: route.waypoints,
    distance: route.distance,
    duration: route.duration,
    travelMode: travelMode.value,
    date: new Date().toISOString()
  }
  
  routeHistory.value.unshift(savedRoute)
  
  // Ограничиваем историю 10 маршрутами
  if (routeHistory.value.length > 10) {
    routeHistory.value = routeHistory.value.slice(0, 10)
  }
  
  // Сохраняем в localStorage
  localStorage.setItem('routeHistory', JSON.stringify(routeHistory.value))
}

const loadStoredRoute = () => {
  // Загружаем историю из localStorage
  const saved = localStorage.getItem('routeHistory')
  if (saved) {
    routeHistory.value = JSON.parse(saved)
  }
}

const loadRoute = async (savedRoute) => {
  if (!routeEditor.value) return
  
  // Очищаем текущий маршрут
  routeEditor.value.clear()
  
  // Устанавливаем режим передвижения
  if (savedRoute.travelMode) {
    travelMode.value = savedRoute.travelMode
  }
  
  // Загружаем путевые точки
  for (let i = 0; i < savedRoute.waypoints.length; i++) {
    const waypoint = savedRoute.waypoints[i]
    await routeEditor.value.setWaypoint(i, waypoint.coordinates)
  }
  
  // Запускаем расчет
  await routeEditor.value.calculateRoute()
}

const deleteRoute = (routeId) => {
  routeHistory.value = routeHistory.value.filter(route => route.id !== routeId)
  localStorage.setItem('routeHistory', JSON.stringify(routeHistory.value))
}
</script>
```

### Интеграция с внешними сервисами

```javascript
// Интеграция с API погоды
routeEditor.on('routecalculated', async (event) => {
  const route = event.routes[event.activeIndex]
  
  try {
    // Получаем погоду для всех точек маршрута
    const weatherPromises = route.waypoints.map(waypoint =>
      fetch(`/api/weather?lat=${waypoint.coordinates[0]}&lng=${waypoint.coordinates[1]}`)
        .then(res => res.json())
    )
    
    const weatherData = await Promise.all(weatherPromises)
    
    // Показываем предупреждения о погоде
    weatherData.forEach((weather, index) => {
      if (weather.conditions === 'rain') {
        showWeatherAlert(`Дождь в точке ${index + 1}: ${route.waypoints[index].address}`)
      }
    })
    
  } catch (error) {
    console.error('Ошибка получения погоды:', error)
  }
})

// Интеграция с трафиком
routeEditor.on('calculatestart', () => {
  // Включаем слой пробок
  if (map.layers) {
    map.layers.add('traffic')
  }
})

// Интеграция с аналитикой
routeEditor.on('routeselect', (event) => {
  analytics.track('route_selected', {
    routeIndex: event.newIndex,
    distance: event.route.distance,
    duration: event.route.duration,
    travelMode: routeEditor.getTravelMode()
  })
})
```

## 🐛 Решение проблем

### Маршрут не строится

```javascript
// Проверяем наличие точек
const waypoints = routeEditor.getWaypoints()
console.log('Путевые точки:', waypoints)

if (waypoints.length < 2) {
  console.error('Нужно минимум 2 точки для построения маршрута')
  return
}

// Проверяем корректность координат
waypoints.forEach((waypoint, index) => {
  if (!waypoint || !waypoint.coordinates) {
    console.error(`Точка ${index} не имеет координат:`, waypoint)
  }
})

// Проверяем API
routeEditor.on('apierror', (event) => {
  console.error('Ошибка API маршрутизации:', event.error.message)
  
  // Возможные причины:
  // 1. Неверный API ключ
  // 2. Превышен лимит запросов
  // 3. Точки слишком далеко друг от друга
  // 4. Невозможно построить маршрут выбранным способом
})
```

### Оптимизация не работает

```javascript
// Проверяем количество точек
const waypoints = routeEditor.getWaypoints()
if (waypoints.length < 3) {
  console.error('Для оптимизации нужно минимум 3 точки')
  return
}

// Проверяем включена ли оптимизация
if (!routeEditor.getOptions().enableOptimization) {
  console.error('Оптимизация отключена в настройках')
  return
}

// Принудительная оптимизация
try {
  const optimizedWaypoints = await routeEditor.optimizeRoute()
  console.log('Оптимизация выполнена:', optimizedWaypoints)
} catch (error) {
  console.error('Ошибка оптимизации:', error.message)
}
```

### Vue компонент не обновляется

```vue
<template>
  <!-- Убедитесь что map передается корректно -->
  <RouteEditorVue
    :key="mapKey"  // Принудительное обновление
    :map="map"
    v-model:travel-mode="travelMode"
  />
</template>

<script setup>
// Пересоздание при критических изменениях
watch([apiKey, mapType], () => {
  mapKey.value++  // Принудительное пересоздание
})

// Отслеживание изменений режима
watch(() => travelMode.value, (newMode) => {
  console.log('Режим изменился на:', newMode)
})
</script>
```

## 🔍 Отладка и диагностика

### Включение debug режима

```javascript
// Глобальный debug
window.YMAPS_DEBUG = true

const routeEditor = new RouteEditor({
  // опции
})

// Подробное логирование
routeEditor.on('*', (event) => {
  console.log(`[RouteEditor] ${event.type}:`, event)
})
```

### Vue с отладочной информацией

```vue
<template>
  <RouteEditorVue
    :map="map"
    :show-debug-info="true"
    v-model:travel-mode="travelMode"
  />
</template>
```

### Проверка состояния

```javascript
console.log('Режим передвижения:', routeEditor.getTravelMode())
console.log('Путевые точки:', routeEditor.getWaypoints())
console.log('Маршруты:', routeEditor.getRoutes())
console.log('Активный маршрут:', routeEditor.getActiveRoute())
console.log('Идет расчет:', routeEditor.isCalculating())
console.log('Режим редактирования:', routeEditor.isEditingMode())
console.log('Ограничения:', routeEditor.getConstraints())
console.log('Единицы измерения:', routeEditor.getUnits())
```

## 📚 См. также

- [ControlBase](../ControlBase.js) - Базовый класс для всех контролов
- [controlHelpers](../../utils/controlHelpers.js) - Утилиты для создания контролов
- [SearchControl](../SearchControl/) - Контрол поиска на карте
- [ZoomControl](../ZoomControl/) - Контрол управления масштабом
- [TypeSelector](../TypeSelector/) - Контрол переключения типов карт

---

<div align="center">
  <strong>Создано с ❤️ для SPA Platform</strong><br>
  <sub>RouteEditor v1.0.0 | Production Ready</sub>
</div>