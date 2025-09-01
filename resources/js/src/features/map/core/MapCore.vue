<template>
  <div class="map-core" :class="{ 'map-core--mobile': isMobile }">
    <!-- Обертка для карты и маркера -->
    <div class="map-core__wrapper" :style="{ height: `${height}px`, position: 'relative' }">
      <div 
        ref="containerRef"
        :id="mapId"
        class="map-core__container"
        style="width: 100%; height: 100%;"
      />
      
      <!-- Центральный маркер для single mode (статический в центре) -->
      <div
        v-if="showCenterMarker && mapReady"
        class="map-core__center-marker"
      >
        <svg width="32" height="40" viewBox="0 0 32 40" fill="none">
          <!-- Основная капля -->
          <path d="M16 0C7.164 0 0 7.164 0 16C0 24.836 16 40 16 40S32 24.836 32 16C32 7.164 24.836 0 16 0Z" fill="#007BFF"/>
          <!-- Внутренний круг -->
          <circle cx="16" cy="16" r="6" fill="white"/>
          <!-- Центральная точка -->
          <circle cx="16" cy="16" r="2" fill="#007BFF"/>
        </svg>
      </div>
    </div>
    
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
import { ref, onMounted, onUnmounted, watch, provide, nextTick } from 'vue'
import { loadYandexMaps } from './MapLoader'
import { createMapStore } from './MapStore'
import type { MapPlugin, Coordinates, MapConfig } from './MapStore'
import { DEFAULT_API_KEY, PERM_CENTER, DEFAULT_ZOOM } from '../utils/mapConstants'
import { isMobileDevice, generateMapId } from '../utils/mapHelpers'
import { mapDiagnostics } from '../utils/mapDiagnostics'

interface Props {
  height?: number
  center?: Coordinates
  zoom?: number
  apiKey?: string
  config?: Partial<MapConfig>
  showCenterMarker?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  height: 400,
  center: () => PERM_CENTER,
  zoom: DEFAULT_ZOOM,
  apiKey: DEFAULT_API_KEY,
  showCenterMarker: false
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
const mapId = generateMapId()
const isMobile = isMobileDevice()
const mapReady = ref(false) // Добавляем ref для отслеживания готовности карты

console.log('[MapCore] 🏗️ MapCore компонент создаётся')
console.log('[MapCore] 🆔 Map ID:', mapId)
console.log('[MapCore] 📱 Mobile:', isMobile)

// Store
const store = createMapStore()
provide('mapStore', store)

console.log('[MapCore] 📦 Store создан:', !!store)

// Plugins
const plugins = new Map<string, MapPlugin>()

// Public API (синхронная по образцу архива)
function use(plugin: MapPlugin) {
  plugins.set(plugin.name, plugin)
  
  // Если карта уже инициализирована, устанавливаем плагин сразу (синхронно)
  const mapInstance = store.getMapInstance()
  if (mapInstance && plugin.install) {
    plugin.install(mapInstance, store)
  }
}

// Инициализация карты (упрощённая по образцу архива) с логированием
async function initMap() {
  try {
    console.log('[MapCore] 🚀 Начинаем инициализацию карты')
    console.log('[MapCore] 📋 Параметры:', {
      mapId,
      center: props.center,
      zoom: props.zoom,
      apiKey: props.apiKey,
      height: props.height
    })
    
    store.setLoading(true)
    console.log('[MapCore] ⏳ Установлен статус loading')
    
    // Проверяем DOM контейнер
    const container = containerRef.value
    if (!container) {
      throw new Error(`DOM контейнер с id="${mapId}" не найден`)
    }
    console.log('[MapCore] 📦 DOM контейнер найден:', container)
    
    // Загружаем API простым способом как в архиве
    console.log('[MapCore] 🔄 Загружаем Yandex Maps API...')
    const ymaps = await loadYandexMaps(props.apiKey)
    console.log('[MapCore] ✅ Yandex Maps API загружен успешно')
    
    // Ждём рендеринга DOM (как в архиве)
    console.log('[MapCore] ⏱️ Ждём рендеринга DOM (100ms)')
    await new Promise(resolve => setTimeout(resolve, 100))
    
    // Создаем карту
    const mapConfig = {
      center: [props.center.lat, props.center.lng],
      zoom: props.zoom,
      controls: ['zoomControl', 'typeSelector'],
      ...props.config
    }
    
    console.log('[MapCore] 🗺️ Создаём карту с конфигурацией:', mapConfig)
    const map = new ymaps.Map(mapId, mapConfig)
    console.log('[MapCore] ✅ Карта создана:', map)
    
    store.setMapInstance(map)
    console.log('[MapCore] 📝 Карта сохранена в store')
    
    // Устанавливаем ограничения зума (как в архиве)
    map.options.set('minZoom', 10)
    map.options.set('maxZoom', 18)
    console.log('[MapCore] 🔍 Установлены ограничения зума: 10-18')
    
    // Устанавливаем базовые обработчики
    console.log('[MapCore] 🎛️ Настраиваем базовые обработчики событий')
    setupBaseHandlers(map)
    
    // Мобильные оптимизации
    if (isMobile) {
      map.behaviors.disable('drag')
      map.behaviors.enable('multiTouch')
      console.log('[MapCore] 📱 Применены мобильные оптимизации')
    }
    
    // Подключаем плагины ПОСЛЕ создания карты (синхронно)
    console.log('[MapCore] 🔌 Подключаем плагины:', plugins.size)
    for (const [name, plugin] of plugins.entries()) {
      if (plugin.install) {
        console.log(`[MapCore] 🔌 Подключаем плагин: ${name}`)
        plugin.install(map, store)
        console.log(`[MapCore] ✅ Плагин ${name} подключен`)
      }
    }
    
    store.setReady(true)
    store.setLoading(false)
    mapReady.value = true // Устанавливаем флаг готовности карты для отображения маркера
    console.log('[MapCore] ✅ Карта готова к использованию!')
    console.log('[MapCore] 🎯 mapReady установлен в true, маркер должен появиться')
    
    // Форсируем отображение центрального маркера с nextTick
    if (props.showCenterMarker) {
      // Ждём обновления DOM после изменения mapReady
      await nextTick()
      console.log('[MapCore] ⏳ nextTick выполнен, DOM должен обновиться')
      
      // Небольшая дополнительная задержка для гарантии
      setTimeout(() => {
        console.log('[MapCore] 🎯 Проверка видимости центрального маркера')
        // Ищем маркер в родительском wrapper, а не в container
        const wrapper = containerRef.value?.parentElement
        const marker = wrapper?.querySelector('.map-core__center-marker')
        if (marker) {
          console.log('[MapCore] ✅ Центральный маркер найден в DOM')
          // Дополнительная проверка видимости
          const markerElement = marker as HTMLElement
          const isVisible = markerElement.offsetParent !== null
          console.log('[MapCore] 👁️ Маркер видим:', isVisible)
          if (!isVisible) {
            console.log('[MapCore] ⚠️ Маркер в DOM, но не видим. Проверяем стили...')
            const computed = window.getComputedStyle(markerElement)
            console.log('[MapCore] 📐 Стили маркера:', {
              display: computed.display,
              visibility: computed.visibility,
              opacity: computed.opacity,
              position: computed.position,
              zIndex: computed.zIndex,
              top: computed.top,
              left: computed.left,
              transform: computed.transform
            })
            // Проверяем родительские элементы
            let parent = markerElement.parentElement
            console.log('[MapCore] 🔍 Проверяем родительские элементы:')
            while (parent && parent !== document.body) {
              const parentComputed = window.getComputedStyle(parent)
              console.log(`[MapCore] 📦 ${parent.className}:`, {
                display: parentComputed.display,
                visibility: parentComputed.visibility,
                opacity: parentComputed.opacity,
                overflow: parentComputed.overflow
              })
              parent = parent.parentElement
            }
          }
        } else {
          console.log('[MapCore] ⚠️ Центральный маркер не найден в DOM')
          console.log('[MapCore] 🔍 Проверяем условия отображения:')
          console.log('  - showCenterMarker:', props.showCenterMarker)
          console.log('  - mapReady:', mapReady.value)
          console.log('[MapCore] 📋 Содержимое контейнера:')
          console.log(containerRef.value?.innerHTML.substring(0, 500))
        }
      }, 100)
    }
    
    // Запускаем финальную диагностику
    console.log('[MapCore] 🔍 Запускаем финальную диагностику...')
    mapDiagnostics.fullDiagnostics({
      mapId,
      apiKey: props.apiKey,
      config: mapConfig,
      mapInstance: map
    })
    
    emit('ready', map)
    
  } catch (error: any) {
    console.error('[MapCore] ❌ Ошибка инициализации:', error)
    console.error('[MapCore] 📋 Параметры при ошибке:', {
      mapId,
      center: props.center,
      zoom: props.zoom,
      apiKey: props.apiKey
    })
    
    // Запускаем диагностику ошибки
    console.log('[MapCore] 🔍 Запускаем диагностику ошибки...')
    mapDiagnostics.fullDiagnostics({
      mapId,
      apiKey: props.apiKey,
      config: {
        center: [props.center.lat, props.center.lng],
        zoom: props.zoom
      }
    })
    
    store.setError(error.message)
    store.setLoading(false)
    emit('error', error)
  }
}

// Throttle функция для оптимизации
function throttle(func: Function, delay: number) {
  let timeoutId: ReturnType<typeof setTimeout> | null = null
  let lastExecTime = 0
  
  return function (...args: any[]) {
    const currentTime = Date.now()
    
    if (currentTime - lastExecTime > delay) {
      // Если прошло достаточно времени, выполняем сразу
      lastExecTime = currentTime
      func.apply(null, args)
    } else {
      // Иначе откладываем выполнение
      if (timeoutId) {
        clearTimeout(timeoutId)
      }
      
      timeoutId = setTimeout(() => {
        lastExecTime = Date.now()
        func.apply(null, args)
        timeoutId = null
      }, delay - (currentTime - lastExecTime))
    }
  }
}

// Базовые обработчики событий
function setupBaseHandlers(map: any) {
  // Для single mode со статическим маркером
  if (props.showCenterMarker) {
    // Создаём throttled версию обработчика для оптимизации
    const handleBoundsChange = throttle(() => {
      const center = map.getCenter()
      
      // Проверяем валидность центра
      if (!center || center.length !== 2 || isNaN(center[0]) || isNaN(center[1])) {
        console.warn('[MapCore] ⚠️ Невалидный центр карты:', center)
        return
      }
      
      const coordinates = {
        lat: center[0],
        lng: center[1]
      }
      
      // Обновляем координаты в store
      store.setCoordinates(coordinates)
      store.setCenter(coordinates)
      
      // Эмитируем события
      emit('center-change', coordinates)
      emit('click', coordinates) // Эмулируем клик для обновления координат
      
      const zoom = map.getZoom()
      store.setZoom(zoom)
      emit('zoom-change', zoom)
    }, 100) // Обновляем максимум 10 раз в секунду
    
    // При движении карты обновляем координаты с throttle
    map.events.add('boundschange', handleBoundsChange)
  } else {
    // Для обычного режима (multiple или без маркера)
    map.events.add('click', (e: any) => {
      const coords = e.get('coords')
      const coordinates = {
        lat: coords[0],
        lng: coords[1]
      }
      store.setCoordinates(coordinates)
      emit('click', coordinates)
    })
    
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
}

// Методы для внешнего использования
function setCenter(center: Coordinates, zoom?: number) {
  const map = store.getMapInstance()
  if (map && center && center.lat && center.lng) {
    // Проверяем валидность координат
    if (isNaN(center.lat) || isNaN(center.lng)) {
      console.warn('[MapCore] ⚠️ Невалидные координаты:', center)
      return
    }
    
    map.setCenter([center.lat, center.lng], zoom || store.zoom)
    
    // Форсируем перерисовку карты только при явном вызове setCenter (не при драге)
    if (zoom) {
      setTimeout(() => {
        if (map.container) {
          map.container.fitToViewport()
        }
        map.events.fire('resize')
      }, 100)
    }
  }
}

function getCenter(): Coordinates {
  return store.center
}

function destroy() {
  try {
    console.log('[MapCore] 🔄 Начинаем уничтожение карты')
    const map = store.getMapInstance()
    if (map) {
      console.log('[MapCore] 🗺️ Карта найдена, приступаем к cleanup')
      // Вызываем destroy для всех плагинов
      for (const plugin of plugins.values()) {
        if (plugin && typeof plugin.destroy === 'function') {
          try {
            plugin.destroy()
          } catch (e) {
            console.warn('[MapCore] Ошибка при уничтожении плагина:', e)
          }
        }
      }
      
      // Правильное уничтожение Yandex Maps
      try {
        // Удаляем все обработчики событий
        if (map.events && typeof map.events.removeAll === 'function') {
          map.events.removeAll()
        }
        
        // Очищаем карту из контейнера
        const container = containerRef.value
        if (container && container.innerHTML) {
          container.innerHTML = ''
        }
        
        console.log('[MapCore] ✅ Карта успешно уничтожена')
      } catch (e) {
        console.warn('[MapCore] Ошибка при уничтожении карты:', e)
      }
      
      store.reset()
      mapReady.value = false // Сбрасываем флаг готовности
    }
  } catch (error) {
    console.warn('[MapCore] Ошибка в destroy():', error)
  }
}

// Lifecycle
onMounted(() => {
  console.log('[MapCore] 🎬 onMounted вызван, запускаем initMap()')
  console.log('[MapCore] 📋 Props при монтировании:', {
    height: props.height,
    center: props.center,
    zoom: props.zoom,
    apiKey: props.apiKey
  })
  initMap()
})

onUnmounted(() => {
  console.log('[MapCore] 🚪 onUnmounted вызван')
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
  initMap,
  store
})
</script>

<style lang="scss">
.map-core {
  position: relative;
  width: 100%;
  
  &__wrapper {
    position: relative;
    width: 100%;
  }
  
  &__container {
    width: 100%;
    background: #f5f5f5;
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
  
  &__center-marker {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -100%);
    z-index: 9999; // Максимально высокий z-index для видимости над картой
    pointer-events: none;
    
    svg {
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
      width: 32px;
      height: 40px;
      display: block; // Явно указываем display
    }
  }
}
</style>