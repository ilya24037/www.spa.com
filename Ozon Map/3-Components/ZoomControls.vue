<template>
  <div class="zoom-controls">
    <!-- Zoom In -->
    <button
      class="maplibregl-ctrl-zoom-in zoom-button"
      type="button"
      title="Увеличить"
      aria-label="Увеличить"
      :disabled="disabled || isAtMaxZoom"
      @click="zoomIn"
    >
      <span class="maplibregl-ctrl-icon zoom-in-icon" aria-hidden="true"></span>
    </button>
    
    <!-- Zoom Out -->
    <button
      class="maplibregl-ctrl-zoom-out zoom-button"
      type="button"
      title="Уменьшить"
      aria-label="Уменьшить"
      :disabled="disabled || isAtMinZoom"
      @click="zoomOut"
    >
      <span class="maplibregl-ctrl-icon zoom-out-icon" aria-hidden="true"></span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import type { Map as MapLibreMap } from 'maplibre-gl'

export interface ZoomControlsProps {
  /** Instance карты MapLibre */
  map?: MapLibreMap
  /** Кнопки отключены */
  disabled?: boolean
  /** Шаг зуммирования */
  zoomStep?: number
  /** Длительность анимации в ms */
  duration?: number
}

export interface ZoomControlsEmits {
  (e: 'zoom-in', zoom: number): void
  (e: 'zoom-out', zoom: number): void
  (e: 'zoom-change', zoom: number): void
}

const props = withDefaults(defineProps<ZoomControlsProps>(), {
  disabled: false,
  zoomStep: 1,
  duration: 300
})

const emit = defineEmits<ZoomControlsEmits>()

// State
const currentZoom = ref(10)
const maxZoom = ref(18)
const minZoom = ref(0)

// Computed
const isAtMaxZoom = computed(() => 
  currentZoom.value >= maxZoom.value
)

const isAtMinZoom = computed(() => 
  currentZoom.value <= minZoom.value
)

// Methods
const zoomIn = () => {
  if (props.disabled || !props.map || isAtMaxZoom.value) return
  
  const newZoom = Math.min(currentZoom.value + props.zoomStep, maxZoom.value)
  props.map.zoomTo(newZoom, { duration: props.duration })
  emit('zoom-in', newZoom)
}

const zoomOut = () => {
  if (props.disabled || !props.map || isAtMinZoom.value) return
  
  const newZoom = Math.max(currentZoom.value - props.zoomStep, minZoom.value)
  props.map.zoomTo(newZoom, { duration: props.duration })
  emit('zoom-out', newZoom)
}

const updateZoomState = () => {
  if (!props.map) return
  
  currentZoom.value = props.map.getZoom()
  maxZoom.value = props.map.getMaxZoom()
  minZoom.value = props.map.getMinZoom()
  emit('zoom-change', currentZoom.value)
}

// Event handlers
const handleZoomEnd = () => {
  updateZoomState()
}

const handleKeyDown = (event: KeyboardEvent) => {
  if (props.disabled || !props.map) return
  
  // Keyboard shortcuts
  if (event.key === '+' || event.key === '=') {
    event.preventDefault()
    zoomIn()
  } else if (event.key === '-') {
    event.preventDefault()
    zoomOut()
  }
}

// Watchers
watch(() => props.map, (newMap, oldMap) => {
  // Remove old listeners
  if (oldMap) {
    oldMap.off('zoomend', handleZoomEnd)
  }
  
  // Add new listeners
  if (newMap) {
    updateZoomState()
    newMap.on('zoomend', handleZoomEnd)
  }
}, { immediate: true })

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleKeyDown)
  updateZoomState()
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeyDown)
  if (props.map) {
    props.map.off('zoomend', handleZoomEnd)
  }
})
</script>

<style scoped>
.zoom-controls {
  display: flex;
  flex-direction: column;
}

.zoom-button {
  background-color: transparent;
  border: 0;
  box-sizing: border-box;
  cursor: pointer;
  display: block;
  height: var(--map-control-size, 29px);
  outline: none;
  padding: 0;
  width: var(--map-control-size, 29px);
  transition: background-color var(--transition, 0.2s);
}

.zoom-button:disabled {
  cursor: not-allowed;
  opacity: 0.25;
}

.zoom-button:not(:disabled):hover {
  background-color: var(--map-control-hover, rgba(0, 0, 0, 0.05));
}

.zoom-button + .zoom-button {
  border-top: 1px solid var(--map-control-border, #ddd);
}

.zoom-button:focus {
  box-shadow: 0 0 2px 2px var(--map-control-focus, #0096ff);
}

.zoom-button:focus:not(:focus-visible) {
  box-shadow: none;
}

.zoom-button:focus:first-child {
  border-radius: var(--map-control-border-radius, 4px) var(--map-control-border-radius, 4px) 0 0;
}

.zoom-button:focus:last-child {
  border-radius: 0 0 var(--map-control-border-radius, 4px) var(--map-control-border-radius, 4px);
}

.zoom-button:focus:only-child {
  border-radius: var(--map-control-border-radius, 4px);
}

/* Icons */
.maplibregl-ctrl-icon {
  background-position: 50%;
  background-repeat: no-repeat;
  display: block;
  height: 100%;
  width: 100%;
}

.zoom-in-icon {
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' fill='%23333' viewBox='0 0 29 29'%3E%3Cpath d='M14.5 8.5c-.75 0-1.5.75-1.5 1.5v3h-3c-.75 0-1.5.75-1.5 1.5S9.25 16 10 16h3v3c0 .75.75 1.5 1.5 1.5S16 19.75 16 19v-3h3c.75 0 1.5-.75 1.5-1.5S19.75 13 19 13h-3v-3c0-.75-.75-1.5-1.5-1.5'/%3E%3C/svg%3E");
}

.zoom-out-icon {
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' fill='%23333' viewBox='0 0 29 29'%3E%3Cpath d='M10 13c-.75 0-1.5.75-1.5 1.5S9.25 16 10 16h9c.75 0 1.5-.75 1.5-1.5S19.75 13 19 13z'/%3E%3C/svg%3E");
}

/* Forced Colors Mode */
@media (forced-colors: active) {
  .maplibregl-ctrl-icon {
    background-color: transparent;
  }
  
  .zoom-button + .zoom-button {
    border-top: 1px solid ButtonText;
  }
  
  .zoom-in-icon {
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' fill='%23fff' viewBox='0 0 29 29'%3E%3Cpath d='M14.5 8.5c-.75 0-1.5.75-1.5 1.5v3h-3c-.75 0-1.5.75-1.5 1.5S9.25 16 10 16h3v3c0 .75.75 1.5 1.5 1.5S16 19.75 16 19v-3h3c.75 0 1.5-.75 1.5-1.5S19.75 13 19 13h-3v-3c0-.75-.75-1.5-1.5-1.5'/%3E%3C/svg%3E");
  }
  
  .zoom-out-icon {
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' fill='%23fff' viewBox='0 0 29 29'%3E%3Cpath d='M10 13c-.75 0-1.5.75-1.5 1.5S9.25 16 10 16h9c.75 0 1.5-.75 1.5-1.5S19.75 13 19 13z'/%3E%3C/svg%3E");
  }
}

/* Light Theme Override for Forced Colors */
@media (forced-colors: active) and (prefers-color-scheme: light) {
  .zoom-in-icon {
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' viewBox='0 0 29 29'%3E%3Cpath d='M14.5 8.5c-.75 0-1.5.75-1.5 1.5v3h-3c-.75 0-1.5.75-1.5 1.5S9.25 16 10 16h3v3c0 .75.75 1.5 1.5 1.5S16 19.75 16 19v-3h3c.75 0 1.5-.75 1.5-1.5S19.75 13 19 13h-3v-3c0-.75-.75-1.5-1.5-1.5'/%3E%3C/svg%3E");
  }
  
  .zoom-out-icon {
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' viewBox='0 0 29 29'%3E%3Cpath d='M10 13c-.75 0-1.5.75-1.5 1.5S9.25 16 10 16h9c.75 0 1.5-.75 1.5-1.5S19.75 13 19 13z'/%3E%3C/svg%3E");
  }
}

/* Mobile Optimizations */
@media (max-width: 768px) {
  .zoom-button {
    min-height: 44px;
    min-width: 44px;
    padding: 8px;
  }
}
</style>