<template>
  <div class="yandex-map-base">
    <div 
      :id="mapId" 
      class="yandex-map-base__container"
      :style="{ height: height + 'px' }"
    >
      <!-- Загрузка -->
      <div v-if="loading" class="yandex-map-base__loading">
        <div class="flex flex-col items-center justify-center h-full">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="text-sm text-gray-600 mt-2">{{ loadingText }}</p>
        </div>
      </div>
      
      <!-- Слоты для дополнительных элементов -->
      <slot name="controls" />
      <slot name="overlays" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useId } from '@/src/shared/composables/useId'
import { isMobileDevice } from '../../lib/deviceDetector'
import type { Coordinates, MapConfig } from '../../types'
import { DEFAULT_API_KEY, PERM_CENTER, DEFAULT_ZOOM } from '../../lib/mapConstants'

// Composables
import { useMapInitializer } from '../../composables/useMapInitializer'
import { useMapMobileOptimization } from '../../composables/useMapMobileOptimization'
import { useMapMethods } from '../../composables/useMapMethods'

interface Props {
  height?: number
  center?: Coordinates
  zoom?: number
  apiKey?: string
  mode?: 'single' | 'multiple'
  autoDetectLocation?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  height: 400,
  center: () => PERM_CENTER,
  zoom: DEFAULT_ZOOM,
  apiKey: DEFAULT_API_KEY,
  mode: 'single',
  autoDetectLocation: false
})

const emit = defineEmits<{
  ready: [map: any]
  'bounds-change': [bounds: number[][]]
  'center-change': [center: Coordinates]
  'zoom-change': [zoom: number]
  click: [coords: Coordinates]
  error: [error: string]
}>()

// State
const mapId = useId('yandex-map-base')
const mapInstance = ref<any>(null)

// Composables
const { loading, loadingText, initMap } = useMapInitializer()
const { setupMobileOptimizations } = useMapMobileOptimization(mapInstance)
const { setCenter, setZoom, getBounds, getCenter, getZoom, autoDetectLocation, getMap } = useMapMethods(mapInstance)

// Инициализация карты
const init = async () => {
  console.log('🗺️ YandexMapBase: Starting initialization...', {
    mapId,
    apiKey: props.apiKey,
    center: props.center,
    zoom: props.zoom
  })
  
  const config: MapConfig = {
    apiKey: props.apiKey,
    center: props.center,
    zoom: props.zoom,
    mode: props.mode
  }

  try {
    mapInstance.value = await initMap(mapId, config, {
      onReady: (map) => {
        console.log('🗺️ YandexMapBase: Map ready callback triggered')
        setupEventHandlers(map)
        if (isMobileDevice()) setupMobileOptimizations()
        if (props.autoDetectLocation) autoDetectLocation()
        emit('ready', map)
      },
      onError: (error) => {
        console.error('🗺️ YandexMapBase: Map error callback triggered', error)
        emit('error', error)
      }
    })
  } catch (error) {
    console.error('🗺️ YandexMapBase: Init failed', error)
    emit('error', error instanceof Error ? error.message : 'Initialization failed')
  }
}

// Обработчики событий карты
const setupEventHandlers = (map: any) => {
  let boundsChangeTimeout: NodeJS.Timeout | null = null
  
  map.events.add('boundschange', () => {
    if (boundsChangeTimeout) clearTimeout(boundsChangeTimeout)
    boundsChangeTimeout = setTimeout(() => {
      emit('bounds-change', map.getBounds())
      const center = map.getCenter()
      emit('center-change', { lat: center[0], lng: center[1] })
      emit('zoom-change', map.getZoom())
    }, 300)
  })
  
  map.events.add('click', (e: any) => {
    const coords = e.get('coords')
    emit('click', { lat: coords[0], lng: coords[1] })
  })
}

// Watchers
watch(() => props.center, (newCenter) => {
  if (mapInstance.value && newCenter) setCenter(newCenter)
})

watch(() => props.zoom, (newZoom) => {
  if (mapInstance.value && newZoom) setZoom(newZoom)
})

// Lifecycle
onMounted(() => {
  console.log('🗺️ YandexMapBase: Component mounted, starting init...')
  init()
})

onUnmounted(() => {
  if (mapInstance.value) {
    mapInstance.value.destroy()
    mapInstance.value = null
  }
})

// Экспорт методов
defineExpose({ setCenter, setZoom, getBounds, getCenter, getZoom, getMap })
</script>

<style scoped>
.yandex-map-base { @apply relative w-full; }
.yandex-map-base__container { @apply relative w-full rounded-lg overflow-hidden bg-gray-100; }
.yandex-map-base__loading { @apply absolute inset-0 bg-white/90 z-10 flex items-center justify-center; }
@media (max-width: 768px) { .yandex-map-base__container { @apply rounded-none; } }
</style>