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
import type { MapPlugin, Coordinates, MapConfig } from './MapStore'
import { DEFAULT_API_KEY, PERM_CENTER, DEFAULT_ZOOM } from '../utils/mapConstants'
import { isMobileDevice, generateMapId } from '../utils/mapHelpers'

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
const mapId = generateMapId()
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
    
  } catch (error: any) {
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