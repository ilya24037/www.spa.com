<template>
  <div 
    :class="routeEditorClasses"
    :style="routeEditorStyle"
    role="region"
    aria-label="Редактор маршрутов"
  >
    <!-- Скелетон загрузки -->
    <div v-if="isLoading && !routeEditor" class="route-editor-skeleton">
      <div class="skeleton-header">
        <div class="skeleton-title"></div>
        <div class="skeleton-select"></div>
      </div>
      <div class="skeleton-waypoints">
        <div v-for="n in 3" :key="n" class="skeleton-waypoint"></div>
      </div>
      <div class="skeleton-buttons">
        <div v-for="n in 3" :key="n" class="skeleton-button"></div>
      </div>
    </div>

    <!-- Основной контент редактора -->
    <div v-else-if="routeEditor" class="route-editor-content">
      <!-- Заголовок с режимом передвижения (рендерится в JS) -->
      
      <!-- Внешний список путевых точек -->
      <div 
        v-if="showExternalWaypoints && waypoints.length > 0"
        class="route-editor-external-waypoints"
        role="group"
        aria-label="Путевые точки маршрута"
      >
        <h4 class="waypoints-title">Путевые точки</h4>
        <div class="waypoints-list">
          <div 
            v-for="(waypoint, index) in waypoints"
            :key="`waypoint-${index}`"
            :class="[
              'external-waypoint',
              { 'external-waypoint--empty': !waypoint }
            ]"
          >
            <div class="waypoint-info">
              <span class="waypoint-number">{{ getWaypointNumber(index) }}</span>
              <div class="waypoint-details">
                <span v-if="waypoint?.address" class="waypoint-address">
                  {{ waypoint.address }}
                </span>
                <span v-else class="waypoint-placeholder">
                  {{ getWaypointPlaceholder(index) }}
                </span>
                <div v-if="waypoint?.coordinates" class="waypoint-coordinates">
                  {{ formatCoordinates(waypoint.coordinates) }}
                </div>
              </div>
            </div>
            <button 
              v-if="index > 0 && index < waypoints.length - 1"
              @click="removeWaypointExternal(index)"
              class="waypoint-remove-external"
              type="button"
              :aria-label="`Удалить точку ${index + 1}`"
            >
              ×
            </button>
          </div>
        </div>
        <button 
          v-if="waypoints.length < maxWaypoints"
          @click="addWaypointExternal"
          class="add-waypoint-external"
          type="button"
        >
          <span class="add-icon">+</span>
          <span class="add-text">Добавить остановку</span>
        </button>
      </div>

      <!-- Внешний список маршрутов -->
      <div 
        v-if="showExternalRoutes && routes.length > 0"
        class="route-editor-external-routes"
        role="group"
        aria-label="Рассчитанные маршруты"
      >
        <h4 class="routes-title">
          {{ routes.length === 1 ? 'Маршрут' : `Маршруты (${routes.length})` }}
        </h4>
        <div class="routes-list">
          <div 
            v-for="(route, index) in routes"
            :key="`route-${index}`"
            :class="[
              'external-route',
              { 'external-route--active': index === activeRouteIndex }
            ]"
            @click="selectExternalRoute(index)"
            role="button"
            tabindex="0"
            :aria-pressed="index === activeRouteIndex"
            @keydown.enter="selectExternalRoute(index)"
            @keydown.space="selectExternalRoute(index)"
          >
            <div class="route-summary">
              <div class="route-main-info">
                <span class="route-distance">{{ formatDistance(route.distance) }}</span>
                <span class="route-duration">{{ formatDuration(route.duration) }}</span>
              </div>
              <div class="route-description">
                {{ route.description || `Маршрут ${index + 1}` }}
              </div>
            </div>
            <div class="route-badge">
              {{ index === activeRouteIndex ? 'Выбран' : 'Выбрать' }}
            </div>
          </div>
        </div>
      </div>

      <!-- Инструкции навигации -->
      <div 
        v-if="showInstructions && activeRoute?.instructions?.length > 0"
        class="route-editor-instructions"
        role="group"
        aria-label="Инструкции навигации"
      >
        <h4 class="instructions-title">Направления движения</h4>
        <div class="instructions-list">
          <div 
            v-for="(instruction, index) in activeRoute.instructions"
            :key="`instruction-${index}`"
            class="instruction-item"
          >
            <div class="instruction-number">{{ index + 1 }}</div>
            <div class="instruction-content">
              <div class="instruction-text">{{ instruction.text }}</div>
              <div class="instruction-details">
                <span class="instruction-distance">{{ formatDistance(instruction.distance) }}</span>
                <span v-if="instruction.duration" class="instruction-duration">
                  {{ formatDuration(instruction.duration) }}
                </span>
              </div>
            </div>
            <div v-if="instruction.direction" class="instruction-direction">
              {{ getDirectionIcon(instruction.direction) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Статистика маршрута -->
      <div 
        v-if="showStats && activeRoute"
        class="route-editor-stats"
      >
        <h4 class="stats-title">Статистика</h4>
        <div class="stats-grid">
          <div class="stat-item">
            <span class="stat-label">Расстояние</span>
            <span class="stat-value">{{ formatDistance(activeRoute.distance) }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Время в пути</span>
            <span class="stat-value">{{ formatDuration(activeRoute.duration) }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Сегментов</span>
            <span class="stat-value">{{ activeRoute.segments?.length || 0 }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Средняя скорость</span>
            <span class="stat-value">{{ calculateAverageSpeed(activeRoute) }} км/ч</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Состояние ошибки -->
    <div v-else-if="error" class="route-editor-error">
      <div class="error-icon">🗺️</div>
      <div class="error-message">
        <p class="error-title">Ошибка редактора маршрутов</p>
        <p class="error-description">{{ error.message }}</p>
        <button 
          @click="recreate" 
          class="error-retry-button"
          type="button"
        >
          Попробовать снова
        </button>
      </div>
    </div>

    <!-- Отладочная информация -->
    <div v-if="showDebugInfo && debugInfo" class="route-editor-debug">
      <details class="debug-panel">
        <summary class="debug-summary">Debug: Route Editor</summary>
        <div class="debug-content">
          <div class="debug-section">
            <h5>Состояние</h5>
            <p>Режим: <code>{{ debugInfo.travelMode }}</code></p>
            <p>Точек: {{ debugInfo.waypointCount }}</p>
            <p>Маршрутов: {{ debugInfo.routeCount }}</p>
            <p>Расчет: {{ debugInfo.isCalculating ? '✅' : '❌' }}</p>
          </div>
          <div class="debug-section">
            <h5>API</h5>
            <p>Маршрутизатор: {{ debugInfo.hasRouter ? '✅' : '❌' }}</p>
            <p>Drag&Drop: {{ debugInfo.hasDragProvider ? '✅' : '❌' }}</p>
          </div>
          <div class="debug-section">
            <h5>Последний расчет</h5>
            <p>Время: {{ debugInfo.lastCalculationTime }}мс</p>
            <p>Статус: {{ debugInfo.lastCalculationStatus }}</p>
          </div>
        </div>
      </details>
    </div>
  </div>
</template>

<script setup lang="ts">
import { 
  ref, 
  computed, 
  watch, 
  onMounted, 
  onUnmounted, 
  nextTick,
  defineProps,
  defineEmits,
  defineExpose
} from 'vue'
import RouteEditor from './RouteEditor.js'
import type { 
  RouteEditorOptions,
  Route,
  Waypoint,
  TravelMode,
  RouteEditorEvents,
  ExtendedRouteEditorOptions
} from './RouteEditor.d.ts'

// === ТИПЫ И ИНТЕРФЕЙСЫ ===

interface Props {
  /** Экземпляр карты */
  map?: any
  /** Доступные режимы передвижения */
  travelModes?: TravelMode[]
  /** Режим передвижения по умолчанию */
  defaultTravelMode?: TravelMode
  /** Текущий режим передвижения (v-model) */
  travelMode?: TravelMode
  /** Максимальное количество путевых точек */
  maxWaypoints?: number
  /** Включить перетаскивание точек */
  enableDragDrop?: boolean
  /** Включить оптимизацию маршрута */
  enableOptimization?: boolean
  /** Показать расстояние и время */
  showDistanceTime?: boolean
  /** Показать альтернативные маршруты */
  showAlternatives?: boolean
  /** Ограничения маршрута */
  avoidTolls?: boolean
  avoidHighways?: boolean
  avoidFerries?: boolean
  /** Единицы измерения */
  units?: 'metric' | 'imperial'
  /** Язык направлений */
  language?: string
  /** Позиция контрола на карте */
  position?: string
  /** Z-index контрола */
  zIndex?: number
  /** Отступы контрола */
  margin?: object
  /** Видимость контрола */
  visible?: boolean
  /** Активность контрола */
  enabled?: boolean
  /** Показать внешний список путевых точек */
  showExternalWaypoints?: boolean
  /** Показать внешний список маршрутов */
  showExternalRoutes?: boolean
  /** Показать инструкции навигации */
  showInstructions?: boolean
  /** Показать статистику маршрута */
  showStats?: boolean
  /** Показать отладочную информацию */
  showDebugInfo?: boolean
  /** CSS классы */
  class?: string | string[] | object
  /** Inline стили */
  style?: string | object
  /** Расширенные опции */
  extendedOptions?: ExtendedRouteEditorOptions
}

interface Emits {
  /** Обновление режима передвижения (v-model) */
  (event: 'update:travelMode', mode: TravelMode): void
  /** Изменение режима передвижения */
  (event: 'travelmodechange', data: { oldMode: TravelMode; newMode: TravelMode }): void
  /** Добавление путевой точки */
  (event: 'waypointadd', data: { index: number; total: number }): void
  /** Удаление путевой точки */
  (event: 'waypointremove', data: { index: number; waypoint: Waypoint; total: number }): void
  /** Установка путевой точки */
  (event: 'waypointset', data: { index: number; waypoint: Waypoint }): void
  /** Изменение путевой точки */
  (event: 'waypointchange', data: { index: number; value: string }): void
  /** Начало расчета маршрута */
  (event: 'calculatestart'): void
  /** Окончание расчета маршрута */
  (event: 'calculateend'): void
  /** Маршрут рассчитан */
  (event: 'routecalculated', data: { routes: Route[]; activeIndex: number }): void
  /** Выбор маршрута */
  (event: 'routeselect', data: { oldIndex: number; newIndex: number; route: Route }): void
  /** Оптимизация маршрута */
  (event: 'optimize', data: { waypoints: Waypoint[] }): void
  /** Очистка маршрута */
  (event: 'clear'): void
  /** Готовность контрола */
  (event: 'ready', control: RouteEditor): void
  /** Ошибка */
  (event: 'error', error: Error): void
}

interface DebugInfo {
  travelMode: TravelMode
  waypointCount: number
  routeCount: number
  isCalculating: boolean
  hasRouter: boolean
  hasDragProvider: boolean
  lastCalculationTime: number
  lastCalculationStatus: string
}

// === PROPS И EMITS ===

const props = withDefaults(defineProps<Props>(), {
  travelModes: () => ['driving', 'walking', 'transit', 'bicycle'],
  defaultTravelMode: 'driving',
  maxWaypoints: 8,
  enableDragDrop: true,
  enableOptimization: true,
  showDistanceTime: true,
  showAlternatives: false,
  avoidTolls: false,
  avoidHighways: false,
  avoidFerries: false,
  units: 'metric',
  language: 'ru',
  position: 'topLeft',
  zIndex: 1000,
  visible: true,
  enabled: true,
  showExternalWaypoints: false,
  showExternalRoutes: false,
  showInstructions: false,
  showStats: false,
  showDebugInfo: false
})

const emit = defineEmits<Emits>()

// === РЕАКТИВНЫЕ СОСТОЯНИЯ ===

const routeEditor = ref<RouteEditor | null>(null)
const isLoading = ref(false)
const error = ref<Error | null>(null)
const waypoints = ref<(Waypoint | null)[]>([])
const routes = ref<Route[]>([])
const activeRouteIndex = ref(-1)
const isCalculating = ref(false)

// Debug информация
const debugInfo = ref<DebugInfo | null>(null)
const calculationStartTime = ref(0)

// === ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ===

const routeEditorClasses = computed(() => {
  const classes: string[] = ['vue-route-editor']
  
  if (isLoading.value) classes.push('route-editor--loading')
  if (error.value) classes.push('route-editor--error')
  if (!props.enabled) classes.push('route-editor--disabled')
  if (!props.visible) classes.push('route-editor--hidden')
  if (isCalculating.value) classes.push('route-editor--calculating')
  if (routes.value.length > 0) classes.push('route-editor--has-routes')
  
  // Пользовательские классы
  if (props.class) {
    if (typeof props.class === 'string') {
      classes.push(props.class)
    } else if (Array.isArray(props.class)) {
      classes.push(...props.class)
    } else {
      Object.entries(props.class).forEach(([className, condition]) => {
        if (condition) classes.push(className)
      })
    }
  }
  
  return classes
})

const routeEditorStyle = computed(() => {
  const styles: Record<string, string> = {}
  
  if (props.zIndex !== undefined) {
    styles['z-index'] = String(props.zIndex)
  }
  
  if (props.margin) {
    Object.entries(props.margin).forEach(([key, value]) => {
      if (typeof value === 'number') {
        styles[`margin-${key}`] = `${value}px`
      }
    })
  }
  
  // Пользовательские стили
  if (props.style) {
    if (typeof props.style === 'string') {
      return [styles, props.style].filter(Boolean).join('; ')
    } else {
      Object.assign(styles, props.style)
    }
  }
  
  return styles
})

const activeRoute = computed(() => {
  if (activeRouteIndex.value >= 0 && activeRouteIndex.value < routes.value.length) {
    return routes.value[activeRouteIndex.value]
  }
  return null
})

// === МЕТОДЫ ===

/**
 * Создает экземпляр RouteEditor
 */
const createRouteEditor = async (): Promise<void> => {
  if (!props.map) return

  try {
    isLoading.value = true
    error.value = null

    // Базовые опции
    const options: RouteEditorOptions = {
      travelModes: props.travelModes,
      defaultTravelMode: props.defaultTravelMode || props.travelMode,
      maxWaypoints: props.maxWaypoints,
      enableDragDrop: props.enableDragDrop,
      enableOptimization: props.enableOptimization,
      showDistanceTime: props.showDistanceTime,
      showAlternatives: props.showAlternatives,
      avoidTolls: props.avoidTolls,
      avoidHighways: props.avoidHighways,
      avoidFerries: props.avoidFerries,
      units: props.units,
      language: props.language,
      position: props.position,
      zIndex: props.zIndex,
      visible: props.visible,
      enabled: props.enabled,
      margin: props.margin as any
    }

    // Расширенные опции
    if (props.extendedOptions) {
      Object.assign(options, props.extendedOptions)
    }

    // Создаем редактор
    const editor = new RouteEditor(options)
    
    // Подключаем обработчики событий
    setupEventHandlers(editor)
    
    // Добавляем на карту
    await editor.addToMap(props.map)
    
    routeEditor.value = editor
    
    // Устанавливаем начальный режим
    if (props.travelMode && props.travelMode !== editor.getTravelMode()) {
      editor.setTravelMode(props.travelMode)
    }

    // Инициализируем debug информацию
    if (props.showDebugInfo) {
      initializeDebugInfo()
    }

    emit('ready', editor)

  } catch (err) {
    console.error('[RouteEditorVue] Ошибка создания редактора:', err)
    error.value = err instanceof Error ? err : new Error(String(err))
    emit('error', error.value)
  } finally {
    isLoading.value = false
  }
}

/**
 * Настраивает обработчики событий RouteEditor
 */
const setupEventHandlers = (editor: RouteEditor): void => {
  // Изменение режима передвижения
  editor.on('travelmodechange', (event) => {
    emit('update:travelMode', event.newMode)
    emit('travelmodechange', event)
    updateDebugInfo({ travelMode: event.newMode })
  })

  // События путевых точек
  editor.on('waypointadd', (event) => {
    updateWaypointsFromEditor()
    updateDebugInfo({ waypointCount: event.total })
    emit('waypointadd', event)
  })

  editor.on('waypointremove', (event) => {
    updateWaypointsFromEditor()
    updateDebugInfo({ waypointCount: event.total })
    emit('waypointremove', event)
  })

  editor.on('waypointset', (event) => {
    updateWaypointsFromEditor()
    emit('waypointset', event)
  })

  editor.on('waypointchange', (event) => {
    emit('waypointchange', event)
  })

  // События расчета маршрута
  editor.on('calculatestart', () => {
    isCalculating.value = true
    calculationStartTime.value = Date.now()
    updateDebugInfo({ isCalculating: true })
    emit('calculatestart')
  })

  editor.on('calculateend', () => {
    isCalculating.value = false
    const calculationTime = Date.now() - calculationStartTime.value
    updateDebugInfo({ 
      isCalculating: false,
      lastCalculationTime: calculationTime,
      lastCalculationStatus: 'success'
    })
    emit('calculateend')
  })

  editor.on('routecalculated', (event) => {
    routes.value = event.routes
    activeRouteIndex.value = event.activeIndex
    updateDebugInfo({ routeCount: event.routes.length })
    emit('routecalculated', event)
  })

  // Выбор маршрута
  editor.on('routeselect', (event) => {
    activeRouteIndex.value = event.newIndex
    emit('routeselect', event)
  })

  // Оптимизация и очистка
  editor.on('optimize', (event) => {
    updateWaypointsFromEditor()
    emit('optimize', event)
  })

  editor.on('clear', () => {
    waypoints.value = []
    routes.value = []
    activeRouteIndex.value = -1
    updateDebugInfo({ 
      waypointCount: 0, 
      routeCount: 0,
      lastCalculationStatus: 'cleared'
    })
    emit('clear')
  })

  // Готовность API
  editor.on('apiready', (event) => {
    updateDebugInfo({
      hasRouter: event.router,
      hasDragProvider: event.dragProvider
    })
  })

  // Ошибки
  editor.on('error', (event) => {
    console.error('[RouteEditorVue] Ошибка редактора:', event.error)
    error.value = event.error
    updateDebugInfo({ lastCalculationStatus: 'error' })
    emit('error', event.error)
  })
}

/**
 * Обновляет список путевых точек из редактора
 */
const updateWaypointsFromEditor = (): void => {
  if (routeEditor.value) {
    waypoints.value = routeEditor.value.getWaypoints()
  }
}

/**
 * Инициализирует debug информацию
 */
const initializeDebugInfo = (): void => {
  debugInfo.value = {
    travelMode: props.travelMode || props.defaultTravelMode,
    waypointCount: 0,
    routeCount: 0,
    isCalculating: false,
    hasRouter: false,
    hasDragProvider: false,
    lastCalculationTime: 0,
    lastCalculationStatus: 'none'
  }
}

/**
 * Обновляет debug информацию
 */
const updateDebugInfo = (updates: Partial<DebugInfo>): void => {
  if (!debugInfo.value) return
  Object.assign(debugInfo.value, updates)
}

/**
 * Получает номер путевой точки для отображения
 */
const getWaypointNumber = (index: number): string => {
  if (index === 0) return '🚀'
  if (index === waypoints.value.length - 1) return '🏁'
  return String.fromCharCode(65 + index - 1) // A, B, C...
}

/**
 * Получает плейсхолдер для путевой точки
 */
const getWaypointPlaceholder = (index: number): string => {
  if (index === 0) return 'Откуда'
  if (index === waypoints.value.length - 1) return 'Куда'
  return `Остановка ${String.fromCharCode(65 + index - 1)}`
}

/**
 * Форматирует координаты для отображения
 */
const formatCoordinates = (coordinates: [number, number]): string => {
  const [lat, lng] = coordinates
  return `${lat.toFixed(4)}, ${lng.toFixed(4)}`
}

/**
 * Форматирует расстояние
 */
const formatDistance = (meters: number): string => {
  if (props.units === 'imperial') {
    const miles = meters * 0.000621371
    return miles >= 1 ? `${miles.toFixed(1)} mi` : `${(meters * 3.28084).toFixed(0)} ft`
  }
  return meters < 1000 ? `${Math.round(meters)} м` : `${(meters / 1000).toFixed(1)} км`
}

/**
 * Форматирует время
 */
const formatDuration = (seconds: number): string => {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  
  if (hours > 0) {
    return `${hours} ч ${minutes} мин`
  }
  return `${minutes} мин`
}

/**
 * Получает иконку направления
 */
const getDirectionIcon = (direction: string): string => {
  const icons = {
    left: '↰',
    right: '↱',
    straight: '↑',
    'u-turn': '↶'
  }
  return icons[direction as keyof typeof icons] || '→'
}

/**
 * Вычисляет среднюю скорость
 */
const calculateAverageSpeed = (route: Route): string => {
  if (route.duration === 0) return '0'
  const speedKmh = (route.distance / 1000) / (route.duration / 3600)
  return speedKmh.toFixed(1)
}

/**
 * Добавляет путевую точку через внешний интерфейс
 */
const addWaypointExternal = async (): Promise<void> => {
  if (!routeEditor.value) return
  
  try {
    await routeEditor.value.addWaypoint()
  } catch (err) {
    console.error('Ошибка добавления точки:', err)
    emit('error', err instanceof Error ? err : new Error(String(err)))
  }
}

/**
 * Удаляет путевую точку через внешний интерфейс
 */
const removeWaypointExternal = (index: number): void => {
  if (!routeEditor.value) return
  
  try {
    routeEditor.value.removeWaypoint(index)
  } catch (err) {
    console.error('Ошибка удаления точки:', err)
    emit('error', err instanceof Error ? err : new Error(String(err)))
  }
}

/**
 * Выбирает маршрут через внешний интерфейс
 */
const selectExternalRoute = (index: number): void => {
  if (!routeEditor.value) return
  
  try {
    routeEditor.value.selectRoute(index)
  } catch (err) {
    console.error('Ошибка выбора маршрута:', err)
    emit('error', err instanceof Error ? err : new Error(String(err)))
  }
}

/**
 * Пересоздает редактор после ошибки
 */
const recreate = async (): Promise<void> => {
  if (routeEditor.value) {
    routeEditor.value.destroy()
    routeEditor.value = null
  }
  
  error.value = null
  waypoints.value = []
  routes.value = []
  activeRouteIndex.value = -1
  
  await nextTick()
  await createRouteEditor()
}

// === WATCHERS ===

// Реагируем на изменение карты
watch(() => props.map, async (newMap, oldMap) => {
  if (newMap !== oldMap) {
    if (routeEditor.value) {
      routeEditor.value.destroy()
      routeEditor.value = null
    }
    
    if (newMap) {
      await createRouteEditor()
    }
  }
}, { immediate: false })

// Реагируем на изменение режима передвижения извне
watch(() => props.travelMode, (newMode) => {
  if (routeEditor.value && newMode && routeEditor.value.getTravelMode() !== newMode) {
    routeEditor.value.setTravelMode(newMode)
  }
})

// Реагируем на изменение видимости
watch(() => props.visible, (visible) => {
  if (routeEditor.value) {
    if (visible) {
      routeEditor.value.show()
    } else {
      routeEditor.value.hide()
    }
  }
})

// Реагируем на изменение активности
watch(() => props.enabled, (enabled) => {
  if (routeEditor.value) {
    if (enabled) {
      routeEditor.value.enable()
    } else {
      routeEditor.value.disable()
    }
  }
})

// Реагируем на изменение ограничений
watch([
  () => props.avoidTolls,
  () => props.avoidHighways,
  () => props.avoidFerries
], ([tolls, highways, ferries]) => {
  if (routeEditor.value) {
    routeEditor.value.setConstraints({
      avoidTolls: tolls,
      avoidHighways: highways,
      avoidFerries: ferries
    })
  }
})

// === LIFECYCLE HOOKS ===

onMounted(async () => {
  if (props.map) {
    await createRouteEditor()
  }
})

onUnmounted(() => {
  if (routeEditor.value) {
    routeEditor.value.destroy()
    routeEditor.value = null
  }
})

// === EXPOSE METHODS ===

defineExpose({
  /**
   * Получить экземпляр RouteEditor
   */
  getControl(): RouteEditor | null {
    return routeEditor.value
  },

  /**
   * Установить путевую точку
   */
  async setWaypoint(index: number, location: string | [number, number]): Promise<void> {
    if (routeEditor.value) {
      await routeEditor.value.setWaypoint(index, location)
      updateWaypointsFromEditor()
    }
  },

  /**
   * Получить путевую точку
   */
  getWaypoint(index: number): Waypoint | null {
    return routeEditor.value?.getWaypoint(index) || null
  },

  /**
   * Получить все путевые точки
   */
  getWaypoints(): Waypoint[] {
    return waypoints.value.filter(Boolean) as Waypoint[]
  },

  /**
   * Добавить путевую точку
   */
  async addWaypoint(location?: string | [number, number], index?: number): Promise<number> {
    if (routeEditor.value) {
      const result = await routeEditor.value.addWaypoint(location, index)
      updateWaypointsFromEditor()
      return result
    }
    return -1
  },

  /**
   * Удалить путевую точку
   */
  removeWaypoint(index: number): void {
    if (routeEditor.value) {
      routeEditor.value.removeWaypoint(index)
      updateWaypointsFromEditor()
    }
  },

  /**
   * Установить режим передвижения
   */
  setTravelMode(mode: TravelMode): void {
    if (routeEditor.value) {
      routeEditor.value.setTravelMode(mode)
      emit('update:travelMode', mode)
    }
  },

  /**
   * Получить режим передвижения
   */
  getTravelMode(): TravelMode {
    return routeEditor.value?.getTravelMode() || props.defaultTravelMode
  },

  /**
   * Рассчитать маршрут
   */
  async calculateRoute(): Promise<Route[]> {
    if (routeEditor.value) {
      const result = await routeEditor.value.calculateRoute()
      routes.value = result
      return result
    }
    return []
  },

  /**
   * Получить маршруты
   */
  getRoutes(): Route[] {
    return routes.value
  },

  /**
   * Получить активный маршрут
   */
  getActiveRoute(): Route | null {
    return activeRoute.value
  },

  /**
   * Выбрать маршрут
   */
  selectRoute(index: number): void {
    if (routeEditor.value) {
      routeEditor.value.selectRoute(index)
    }
  },

  /**
   * Оптимизировать маршрут
   */
  async optimizeRoute(): Promise<Waypoint[]> {
    if (routeEditor.value) {
      const result = await routeEditor.value.optimizeRoute()
      updateWaypointsFromEditor()
      return result
    }
    return []
  },

  /**
   * Очистить маршруты
   */
  clear(): void {
    if (routeEditor.value) {
      routeEditor.value.clear()
    }
  },

  /**
   * Пересоздать редактор
   */
  async recreate(): Promise<void> {
    await recreate()
  }
})
</script>

<style scoped>
/* === ОСНОВНЫЕ СТИЛИ === */
.vue-route-editor {
  position: relative;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-size: 14px;
  line-height: 1.5;
}

/* === СКЕЛЕТОН ЗАГРУЗКИ === */
.route-editor-skeleton {
  padding: 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  animation: pulse 1.5s ease-in-out infinite alternate;
}

.skeleton-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.skeleton-title {
  width: 120px;
  height: 20px;
  background: #e2e8f0;
  border-radius: 4px;
}

.skeleton-select {
  width: 150px;
  height: 32px;
  background: #e2e8f0;
  border-radius: 4px;
}

.skeleton-waypoints {
  margin-bottom: 16px;
}

.skeleton-waypoint {
  height: 40px;
  background: #e2e8f0;
  border-radius: 4px;
  margin-bottom: 8px;
}

.skeleton-buttons {
  display: flex;
  gap: 8px;
}

.skeleton-button {
  flex: 1;
  height: 36px;
  background: #e2e8f0;
  border-radius: 4px;
}

@keyframes pulse {
  from { opacity: 0.6; }
  to { opacity: 1; }
}

/* === СОСТОЯНИЕ ОШИБКИ === */
.route-editor-error {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #991b1b;
}

.error-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.error-message {
  flex: 1;
}

.error-title {
  margin: 0 0 4px 0;
  font-weight: 600;
  font-size: 14px;
}

.error-description {
  margin: 0 0 12px 0;
  font-size: 13px;
  color: #7f1d1d;
}

.error-retry-button {
  padding: 6px 12px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.error-retry-button:hover {
  background: #b91c1c;
}

/* === ВНЕШНИЕ ПУТЕВЫЕ ТОЧКИ === */
.route-editor-external-waypoints {
  margin-bottom: 16px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.waypoints-title {
  margin: 0;
  padding: 12px 16px;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.waypoints-list {
  padding: 8px;
}

.external-waypoint {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 6px;
  transition: background-color 0.15s;
}

.external-waypoint:hover {
  background: #f9fafb;
}

.external-waypoint--empty {
  opacity: 0.6;
}

.waypoint-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 0;
}

.waypoint-number {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #3b82f6;
  color: white;
  border-radius: 50%;
  font-size: 12px;
  font-weight: 600;
  flex-shrink: 0;
}

.waypoint-details {
  flex: 1;
  min-width: 0;
}

.waypoint-address {
  display: block;
  font-weight: 500;
  color: #111827;
  word-break: break-word;
}

.waypoint-placeholder {
  display: block;
  color: #9ca3af;
  font-style: italic;
}

.waypoint-coordinates {
  font-size: 12px;
  color: #6b7280;
  font-family: 'SF Mono', Monaco, monospace;
  margin-top: 2px;
}

.waypoint-remove-external {
  width: 24px;
  height: 24px;
  border: none;
  background: #ef4444;
  color: white;
  border-radius: 50%;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.15s;
}

.waypoint-remove-external:hover {
  background: #dc2626;
}

.add-waypoint-external {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 12px;
  background: none;
  border: 2px dashed #d1d5db;
  border-radius: 6px;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.15s;
}

.add-waypoint-external:hover {
  border-color: #3b82f6;
  color: #3b82f6;
}

.add-icon {
  font-size: 16px;
  font-weight: bold;
}

.add-text {
  font-size: 13px;
}

/* === ВНЕШНИЕ МАРШРУТЫ === */
.route-editor-external-routes {
  margin-bottom: 16px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.routes-title {
  margin: 0;
  padding: 12px 16px;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.routes-list {
  padding: 8px;
}

.external-route {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s;
  border: 2px solid transparent;
}

.external-route:hover {
  background: #f9fafb;
}

.external-route--active {
  background: #eff6ff;
  border-color: #3b82f6;
}

.route-summary {
  flex: 1;
  min-width: 0;
}

.route-main-info {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 4px;
}

.route-distance {
  font-weight: 600;
  color: #111827;
}

.route-duration {
  color: #6b7280;
}

.route-description {
  font-size: 12px;
  color: #9ca3af;
}

.route-badge {
  padding: 4px 12px;
  background: #f3f4f6;
  color: #374151;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
  text-transform: uppercase;
}

.external-route--active .route-badge {
  background: #3b82f6;
  color: white;
}

/* === ИНСТРУКЦИИ НАВИГАЦИИ === */
.route-editor-instructions {
  margin-bottom: 16px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.instructions-title {
  margin: 0;
  padding: 12px 16px;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.instructions-list {
  max-height: 300px;
  overflow-y: auto;
  padding: 8px;
}

.instruction-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px;
  border-radius: 6px;
  transition: background-color 0.15s;
}

.instruction-item:hover {
  background: #f9fafb;
}

.instruction-number {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #e5e7eb;
  color: #374151;
  border-radius: 50%;
  font-size: 11px;
  font-weight: 600;
  flex-shrink: 0;
  margin-top: 2px;
}

.instruction-content {
  flex: 1;
  min-width: 0;
}

.instruction-text {
  color: #111827;
  margin-bottom: 4px;
  word-break: break-word;
}

.instruction-details {
  display: flex;
  gap: 12px;
  font-size: 12px;
  color: #6b7280;
}

.instruction-direction {
  font-size: 18px;
  color: #3b82f6;
  flex-shrink: 0;
}

/* === СТАТИСТИКА === */
.route-editor-stats {
  margin-bottom: 16px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.stats-title {
  margin: 0;
  padding: 12px 16px;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1px;
  background: #e5e7eb;
}

.stat-item {
  padding: 12px 16px;
  background: white;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-label {
  font-size: 12px;
  color: #6b7280;
  text-transform: uppercase;
  font-weight: 500;
  letter-spacing: 0.05em;
}

.stat-value {
  font-weight: 600;
  color: #111827;
}

/* === DEBUG ПАНЕЛЬ === */
.route-editor-debug {
  margin-top: 16px;
  font-size: 12px;
}

.debug-panel {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  overflow: hidden;
}

.debug-summary {
  padding: 8px 12px;
  background: #e2e8f0;
  cursor: pointer;
  font-weight: 600;
  color: #374151;
  user-select: none;
}

.debug-summary:hover {
  background: #d1d5db;
}

.debug-content {
  padding: 12px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
}

.debug-section h5 {
  margin: 0 0 6px 0;
  font-size: 11px;
  font-weight: 700;
  color: #6b7280;
  text-transform: uppercase;
}

.debug-section p {
  margin: 0 0 4px 0;
  color: #374151;
  display: flex;
  justify-content: space-between;
}

.debug-section code {
  background: #e5e7eb;
  padding: 2px 4px;
  border-radius: 2px;
  font-family: 'SF Mono', Monaco, monospace;
}

/* === СОСТОЯНИЯ КОМПОНЕНТА === */
.route-editor--loading {
  pointer-events: none;
  opacity: 0.8;
}

.route-editor--disabled {
  pointer-events: none;
  opacity: 0.5;
}

.route-editor--hidden {
  display: none;
}

.route-editor--calculating .external-waypoint,
.route-editor--calculating .external-route {
  opacity: 0.6;
}

/* === АДАПТИВНОСТЬ === */
@media (max-width: 768px) {
  .vue-route-editor {
    font-size: 16px; /* Предотвращаем zoom в iOS */
  }
  
  .external-waypoint,
  .external-route,
  .instruction-item {
    padding: 16px;
  }
  
  .waypoint-number {
    width: 32px;
    height: 32px;
    font-size: 14px;
  }
  
  .route-main-info {
    gap: 12px;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .debug-content {
    grid-template-columns: 1fr;
  }
}

/* === ТЕМНАЯ ТЕМА === */
@media (prefers-color-scheme: dark) {
  .route-editor-skeleton {
    background: #1f2937;
    border-color: #374151;
  }
  
  .skeleton-title,
  .skeleton-select,
  .skeleton-waypoint,
  .skeleton-button {
    background: #374151;
  }
  
  .route-editor-external-waypoints,
  .route-editor-external-routes,
  .route-editor-instructions,
  .route-editor-stats {
    background: #1f2937;
    border-color: #374151;
  }
  
  .waypoints-title,
  .routes-title,
  .instructions-title,
  .stats-title {
    background: #111827;
    color: #e5e7eb;
    border-color: #374151;
  }
  
  .external-waypoint:hover,
  .external-route:hover,
  .instruction-item:hover {
    background: #111827;
  }
  
  .external-route--active {
    background: #1e3a8a;
  }
  
  .waypoint-address,
  .route-distance,
  .instruction-text,
  .stat-value {
    color: #f9fafb;
  }
  
  .waypoint-coordinates,
  .route-duration,
  .route-description,
  .instruction-details,
  .stat-label {
    color: #9ca3af;
  }
  
  .stat-item {
    background: #1f2937;
  }
  
  .stats-grid {
    background: #374151;
  }
}

/* === ВЫСОКИЙ КОНТРАСТ === */
@media (prefers-contrast: high) {
  .external-waypoint,
  .external-route,
  .instruction-item {
    border: 1px solid #374151;
  }
  
  .external-route--active {
    border-width: 2px;
    border-color: #1d4ed8;
  }
}

/* === СНИЖЕННАЯ АНИМАЦИЯ === */
@media (prefers-reduced-motion: reduce) {
  .route-editor-skeleton {
    animation: none;
  }
  
  .external-waypoint,
  .external-route,
  .instruction-item,
  .add-waypoint-external,
  .error-retry-button,
  .waypoint-remove-external {
    transition: none;
  }
}
</style>