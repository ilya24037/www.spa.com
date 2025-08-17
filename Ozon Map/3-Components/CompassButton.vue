<template>
  <button
    class="maplibregl-ctrl-compass compass-button"
    type="button"
    title="Сбросить направление севера"
    aria-label="Сбросить направление севера"
    :disabled="disabled || !canReset"
    :style="{ visibility: isVisible ? 'visible' : 'hidden' }"
    @click="resetNorth"
  >
    <span 
      class="maplibregl-ctrl-icon compass-icon" 
      :style="{ transform: `rotate(${-bearing}deg)` }"
      aria-hidden="true"
    ></span>
  </button>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import type { Map as MapLibreMap } from 'maplibre-gl'

export interface CompassButtonProps {
  /** Instance карты MapLibre */
  map?: MapLibreMap
  /** Кнопка отключена */
  disabled?: boolean
  /** Показывать только при повороте карты */
  hideWhenNorth?: boolean
  /** Длительность анимации поворота */
  duration?: number
  /** Порог для скрытия (в градусах) */
  threshold?: number
}

export interface CompassButtonEmits {
  (e: 'bearing-reset'): void
  (e: 'bearing-change', bearing: number): void
}

const props = withDefaults(defineProps<CompassButtonProps>(), {
  disabled: false,
  hideWhenNorth: true,
  duration: 500,
  threshold: 1
})

const emit = defineEmits<CompassButtonEmits>()

// State
const bearing = ref(0)
const pitch = ref(0)

// Computed
const canReset = computed(() => {
  return Math.abs(bearing.value) > props.threshold || Math.abs(pitch.value) > props.threshold
})

const isVisible = computed(() => {
  if (!props.hideWhenNorth) return true
  return canReset.value
})

// Methods
const resetNorth = () => {
  if (props.disabled || !props.map || !canReset.value) return

  // Reset bearing and pitch to 0
  props.map.easeTo({
    bearing: 0,
    pitch: 0,
    duration: props.duration
  })

  emit('bearing-reset')
}

const updateBearing = () => {
  if (!props.map) return
  
  const currentBearing = props.map.getBearing()
  const currentPitch = props.map.getPitch()
  
  bearing.value = currentBearing
  pitch.value = currentPitch
  
  emit('bearing-change', currentBearing)
}

const handleRotateStart = () => {
  // Add visual feedback when rotation starts
  if (props.map) {
    props.map.getContainer().style.cursor = 'grabbing'
  }
}

const handleRotateEnd = () => {
  updateBearing()
  
  // Reset cursor
  if (props.map) {
    props.map.getContainer().style.cursor = ''
  }
}

const handleKeyDown = (event: KeyboardEvent) => {
  if (props.disabled || !props.map) return

  // Reset with 'R' key
  if (event.key.toLowerCase() === 'r' && canReset.value) {
    event.preventDefault()
    resetNorth()
  }
  
  // Arrow keys for manual bearing adjustment
  if (event.shiftKey) {
    let newBearing = bearing.value
    
    switch (event.key) {
      case 'ArrowLeft':
        event.preventDefault()
        newBearing -= 15
        break
      case 'ArrowRight':
        event.preventDefault()
        newBearing += 15
        break
      default:
        return
    }
    
    props.map.easeTo({
      bearing: newBearing,
      duration: 200
    })
  }
}

// Watchers
watch(() => props.map, (newMap, oldMap) => {
  // Remove old listeners
  if (oldMap) {
    oldMap.off('rotate', updateBearing)
    oldMap.off('rotatestart', handleRotateStart)
    oldMap.off('rotateend', handleRotateEnd)
    oldMap.off('pitch', updateBearing)
  }
  
  // Add new listeners
  if (newMap) {
    updateBearing()
    newMap.on('rotate', updateBearing)
    newMap.on('rotatestart', handleRotateStart)
    newMap.on('rotateend', handleRotateEnd)
    newMap.on('pitch', updateBearing)
  }
}, { immediate: true })

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleKeyDown)
  updateBearing()
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeyDown)
  
  if (props.map) {
    props.map.off('rotate', updateBearing)
    props.map.off('rotatestart', handleRotateStart)
    props.map.off('rotateend', handleRotateEnd)
    props.map.off('pitch', updateBearing)
  }
})
</script>

<style scoped>
.compass-button {
  background-color: transparent;
  border: 0;
  box-sizing: border-box;
  cursor: pointer;
  display: block;
  height: var(--map-control-size, 29px);
  outline: none;
  padding: 0;
  width: var(--map-control-size, 29px);
  transition: background-color var(--transition, 0.2s), visibility var(--transition, 0.2s);
  touch-action: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  user-select: none;
}

.compass-button:disabled {
  cursor: not-allowed;
  opacity: 0.25;
}

.compass-button:not(:disabled):hover {
  background-color: var(--map-control-hover, rgba(0, 0, 0, 0.05));
}

.compass-button:not(:disabled):active {
  cursor: grabbing;
}

.compass-button:focus {
  box-shadow: 0 0 2px 2px var(--map-control-focus, #0096ff);
}

.compass-button:focus:not(:focus-visible) {
  box-shadow: none;
}

/* Icon */
.compass-icon {
  background-position: 50%;
  background-repeat: no-repeat;
  display: block;
  height: 100%;
  width: 100%;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' fill='%23333' viewBox='0 0 29 29'%3E%3Cpath d='m10.5 14 4-8 4 8z'/%3E%3Cpath fill='%23ccc' d='m10.5 16 4 8 4-8z'/%3E%3C/svg%3E");
}

/* Forced Colors Mode */
@media (forced-colors: active) {
  .compass-icon {
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' fill='%23fff' viewBox='0 0 29 29'%3E%3Cpath d='m10.5 14 4-8 4 8z'/%3E%3Cpath fill='%23ccc' d='m10.5 16 4 8 4-8z'/%3E%3C/svg%3E");
  }
}

/* Light Theme Override for Forced Colors */
@media (forced-colors: active) and (prefers-color-scheme: light) {
  .compass-icon {
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' viewBox='0 0 29 29'%3E%3Cpath d='m10.5 14 4-8 4 8z'/%3E%3Cpath fill='%23ccc' d='m10.5 16 4 8 4-8z'/%3E%3C/svg%3E");
  }
}

/* Mobile Optimizations */
@media (max-width: 768px) {
  .compass-button {
    min-height: 44px;
    min-width: 44px;
    padding: 8px;
  }
}

/* Accessibility - Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .compass-icon {
    transition: none;
  }
}

/* Tooltip Hint */
.compass-button::after {
  content: attr(title);
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transition: opacity var(--transition, 0.2s);
  z-index: 1000;
  margin-top: 4px;
}

.compass-button:hover::after {
  opacity: 1;
}

@media (max-width: 768px) {
  .compass-button::after {
    display: none;
  }
}
</style>