<template>
  <div 
    ref="mapContainer" 
    class="ozon-map-container"
    :class="{ 
      'fullscreen': isFullscreen,
      'loading': isLoading 
    }"
  >
    <!-- Прелоадер карты -->
    <div v-if="isLoading" class="map-loading">
      <div class="map-loading-spinner"></div>
      <span class="map-loading-text">Загрузка карты...</span>
    </div>
    
    <!-- Ошибка загрузки -->
    <div v-if="error" class="map-error">
      <div class="map-error-icon">⚠️</div>
      <h3 class="map-error-title">Ошибка загрузки карты</h3>
      <p class="map-error-message">{{ error }}</p>
      <button @click="retryLoad" class="map-error-retry">
        Попробовать снова
      </button>
    </div>
    
    <!-- Слот для дополнительных элементов управления -->
    <slot name="controls" :map="map" :isLoaded="isMapLoaded" />
    
    <!-- Слот для маркеров и попапов -->
    <slot name="markers" :map="map" :isLoaded="isMapLoaded" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import type { Map as MapLibreMap, MapOptions } from 'maplibre-gl'
import { useMapInit } from '../5-Logic/composables/useMapInit'

export interface MapContainerProps {
  /** Стиль карты (URL или объект) */
  style?: string | object
  /** Центр карты [lng, lat] */
  center?: [number, number]
  /** Уровень zoom */
  zoom?: number
  /** Максимальные границы карты */
  maxBounds?: [[number, number], [number, number]]
  /** Показывать элементы управления */
  showControls?: boolean
  /** Кастомные опции MapLibre */
  options?: Partial<MapOptions>
}

export interface MapContainerEmits {
  (e: 'map-loaded', map: MapLibreMap): void
  (e: 'map-error', error: string): void
  (e: 'map-click', event: any): void
  (e: 'map-move', event: any): void
}

const props = withDefaults(defineProps<MapContainerProps>(), {
  style: '/ozon-map/map-style.json',
  center: () => [37.6173, 55.7558],
  zoom: 10,
  showControls: true,
  options: () => ({})
})

const emit = defineEmits<MapContainerEmits>()

// Refs
const mapContainer = ref<HTMLDivElement>()
const map = ref<MapLibreMap>()
const isLoading = ref(true)
const isMapLoaded = ref(false)
const error = ref<string>('')
const isFullscreen = ref(false)

// Composables
const { initializeMap, destroyMap } = useMapInit()

// Методы
const retryLoad = async () => {
  error.value = ''
  isLoading.value = true
  await loadMap()
}

const loadMap = async () => {
  if (!mapContainer.value) return
  
  try {
    const mapOptions: MapOptions = {
      container: mapContainer.value,
      style: props.style,
      center: props.center,
      zoom: props.zoom,
      maxBounds: props.maxBounds,
      ...props.options
    }
    
    const mapInstance = await initializeMap(mapOptions)
    
    // События карты
    mapInstance.on('load', () => {
      isLoading.value = false
      isMapLoaded.value = true
      emit('map-loaded', mapInstance)
    })
    
    mapInstance.on('error', (e) => {
      console.error('Map error:', e)
      error.value = e.error?.message || 'Неизвестная ошибка'
      isLoading.value = false
      emit('map-error', error.value)
    })
    
    mapInstance.on('click', (e) => {
      emit('map-click', e)
    })
    
    mapInstance.on('move', (e) => {
      emit('map-move', e)
    })
    
    map.value = mapInstance
    
  } catch (err) {
    console.error('Failed to initialize map:', err)
    error.value = err instanceof Error ? err.message : 'Ошибка инициализации карты'
    isLoading.value = false
    emit('map-error', error.value)
  }
}

// Отслеживание fullscreen
const handleFullscreenChange = () => {
  isFullscreen.value = !!document.fullscreenElement
}

// Lifecycle
onMounted(async () => {
  await nextTick()
  document.addEventListener('fullscreenchange', handleFullscreenChange)
  await loadMap()
})

onUnmounted(() => {
  document.removeEventListener('fullscreenchange', handleFullscreenChange)
  if (map.value) {
    destroyMap(map.value)
  }
})

// Watchers для реактивности
watch(() => props.center, (newCenter) => {
  if (map.value && isMapLoaded.value) {
    map.value.setCenter(newCenter)
  }
})

watch(() => props.zoom, (newZoom) => {
  if (map.value && isMapLoaded.value) {
    map.value.setZoom(newZoom)
  }
})

// Expose map instance
defineExpose({
  map: map,
  isLoaded: isMapLoaded,
  retry: retryLoad
})
</script>

<style scoped>
.ozon-map-container {
  position: relative;
  width: 100%;
  height: 100%;
  background-color: var(--map-background, #f5f5f5);
  border-radius: var(--map-control-border-radius, 4px);
  overflow: hidden;
}

.ozon-map-container.fullscreen {
  border-radius: 0;
}

.ozon-map-container.loading {
  pointer-events: none;
}

/* Прелоадер */
.map-loading {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.9);
  z-index: 1000;
}

.map-loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid var(--ozon-primary, #005bff);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

.map-loading-text {
  font-size: 14px;
  color: var(--ozon-text-secondary, #707f8d);
  font-weight: 500;
}

/* Ошибка */
.map-error {
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
  padding: 24px;
  text-align: center;
}

.map-error-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.map-error-title {
  font-size: 18px;
  font-weight: 600;
  color: var(--ozon-accent-alert, #f91155);
  margin: 0 0 8px 0;
}

.map-error-message {
  font-size: 14px;
  color: var(--ozon-text-secondary, #707f8d);
  margin: 0 0 24px 0;
  line-height: 1.4;
}

.map-error-retry {
  padding: 8px 16px;
  background: var(--ozon-primary, #005bff);
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color var(--transition, 0.2s);
}

.map-error-retry:hover {
  background: var(--ozon-primary-hover, #0050e0);
}

/* Анимации */
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Адаптивность */
@media (max-width: 768px) {
  .map-loading-text {
    font-size: 12px;
  }
  
  .map-error {
    padding: 16px;
  }
  
  .map-error-icon {
    font-size: 36px;
  }
  
  .map-error-title {
    font-size: 16px;
  }
}
</style>