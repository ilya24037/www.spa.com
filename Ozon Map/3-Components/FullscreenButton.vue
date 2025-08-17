<template>
  <button
    class="maplibregl-ctrl-fullscreen fullscreen-button"
    :class="{ 'maplibregl-ctrl-shrink': isFullscreen }"
    type="button"
    :title="buttonTitle"
    :aria-label="buttonTitle"
    :disabled="disabled || !isFullscreenSupported"
    @click="toggleFullscreen"
  >
    <span 
      class="maplibregl-ctrl-icon fullscreen-icon" 
      :class="{ 'shrink-icon': isFullscreen }"
      aria-hidden="true"
    ></span>
  </button>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import type { Map as MapLibreMap } from 'maplibre-gl'

export interface FullscreenButtonProps {
  /** Instance карты MapLibre */
  map?: MapLibreMap
  /** Кнопка отключена */
  disabled?: boolean
  /** Контейнер для fullscreen (по умолчанию - родитель карты) */
  container?: HTMLElement
}

export interface FullscreenButtonEmits {
  (e: 'fullscreen-change', isFullscreen: boolean): void
  (e: 'fullscreen-error', error: string): void
}

const props = withDefaults(defineProps<FullscreenButtonProps>(), {
  disabled: false
})

const emit = defineEmits<FullscreenButtonEmits>()

// State
const isFullscreen = ref(false)
const isFullscreenSupported = ref(false)

// Computed
const buttonTitle = computed(() => {
  if (!isFullscreenSupported.value) return 'Полноэкранный режим не поддерживается'
  return isFullscreen.value ? 'Выйти из полноэкранного режима' : 'Перейти в полноэкранный режим'
})

// Methods
const getFullscreenElement = (): Element | null => {
  return document.fullscreenElement || 
         (document as any).webkitFullscreenElement || 
         (document as any).mozFullScreenElement || 
         (document as any).msFullscreenElement
}

const requestFullscreen = (element: HTMLElement): Promise<void> => {
  if (element.requestFullscreen) {
    return element.requestFullscreen()
  } else if ((element as any).webkitRequestFullscreen) {
    return (element as any).webkitRequestFullscreen()
  } else if ((element as any).mozRequestFullScreen) {
    return (element as any).mozRequestFullScreen()
  } else if ((element as any).msRequestFullscreen) {
    return (element as any).msRequestFullscreen()
  }
  return Promise.reject(new Error('Fullscreen API not supported'))
}

const exitFullscreen = (): Promise<void> => {
  if (document.exitFullscreen) {
    return document.exitFullscreen()
  } else if ((document as any).webkitExitFullscreen) {
    return (document as any).webkitExitFullscreen()
  } else if ((document as any).mozCancelFullScreen) {
    return (document as any).mozCancelFullScreen()
  } else if ((document as any).msExitFullscreen) {
    return (document as any).msExitFullscreen()
  }
  return Promise.reject(new Error('Exit fullscreen not supported'))
}

const getMapContainer = (): HTMLElement | null => {
  if (props.container) return props.container
  if (props.map) return props.map.getContainer()
  return null
}

const toggleFullscreen = async () => {
  if (props.disabled || !isFullscreenSupported.value) return

  try {
    if (isFullscreen.value) {
      await exitFullscreen()
    } else {
      const container = getMapContainer()
      if (container) {
        await requestFullscreen(container)
      } else {
        throw new Error('Map container not found')
      }
    }
  } catch (error) {
    console.error('Fullscreen toggle error:', error)
    emit('fullscreen-error', error instanceof Error ? error.message : 'Ошибка полноэкранного режима')
  }
}

const handleFullscreenChange = () => {
  const fullscreenElement = getFullscreenElement()
  const mapContainer = getMapContainer()
  
  isFullscreen.value = !!(fullscreenElement && fullscreenElement.contains(mapContainer))
  
  // Resize map after fullscreen change
  if (props.map) {
    setTimeout(() => {
      props.map!.resize()
    }, 100)
  }
  
  emit('fullscreen-change', isFullscreen.value)
}

const handleKeyDown = (event: KeyboardEvent) => {
  // ESC key to exit fullscreen
  if (event.key === 'Escape' && isFullscreen.value) {
    toggleFullscreen()
  }
  
  // F11 key to toggle fullscreen
  if (event.key === 'F11') {
    event.preventDefault()
    toggleFullscreen()
  }
}

// Lifecycle
onMounted(() => {
  // Check fullscreen support
  isFullscreenSupported.value = !!(
    document.fullscreenEnabled ||
    (document as any).webkitFullscreenEnabled ||
    (document as any).mozFullScreenEnabled ||
    (document as any).msFullscreenEnabled
  )

  // Add event listeners
  const events = [
    'fullscreenchange',
    'webkitfullscreenchange',
    'mozfullscreenchange',
    'MSFullscreenChange'
  ]
  
  events.forEach(event => {
    document.addEventListener(event, handleFullscreenChange)
  })
  
  document.addEventListener('keydown', handleKeyDown)
  
  // Initial check
  handleFullscreenChange()
})

onUnmounted(() => {
  const events = [
    'fullscreenchange',
    'webkitfullscreenchange',
    'mozfullscreenchange',
    'MSFullscreenChange'
  ]
  
  events.forEach(event => {
    document.removeEventListener(event, handleFullscreenChange)
  })
  
  document.removeEventListener('keydown', handleKeyDown)
})
</script>

<style scoped>
.fullscreen-button {
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

.fullscreen-button:disabled {
  cursor: not-allowed;
  opacity: 0.25;
}

.fullscreen-button:not(:disabled):hover {
  background-color: var(--map-control-hover, rgba(0, 0, 0, 0.05));
}

.fullscreen-button:focus {
  box-shadow: 0 0 2px 2px var(--map-control-focus, #0096ff);
}

.fullscreen-button:focus:not(:focus-visible) {
  box-shadow: none;
}

/* Icons */
.fullscreen-icon {
  background-position: 50%;
  background-repeat: no-repeat;
  display: block;
  height: 100%;
  width: 100%;
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' fill='%23333' viewBox='0 0 29 29'%3E%3Cpath d='M24 16v5.5c0 1.75-.75 2.5-2.5 2.5H16v-1l3-1.5-4-5.5 1-1 5.5 4 1.5-3zM6 16l1.5 3 5.5-4 1 1-4 5.5 3 1.5v1H7.5C5.75 24 5 23.25 5 21.5V16zm7-11v1l-3 1.5 4 5.5-1 1-5.5-4L6 13H5V7.5C5 5.75 5.75 5 7.5 5zm11 2.5c0-1.75-.75-2.5-2.5-2.5H16v1l3 1.5-4 5.5 1 1 5.5-4 1.5 3h1z'/%3E%3C/svg%3E");
}

.shrink-icon {
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' viewBox='0 0 29 29'%3E%3Cpath d='M18.5 16c-1.75 0-2.5.75-2.5 2.5V24h1l1.5-3 5.5 4 1-1-4-5.5 3-1.5v-1zM13 18.5c0-1.75-.75-2.5-2.5-2.5H5v1l3 1.5L4 24l1 1 5.5-4 1.5 3h1zm3-8c0 1.75.75 2.5 2.5 2.5H24v-1l-3-1.5L25 5l-1-1-5.5 4L17 5h-1zM10.5 13c1.75 0 2.5-.75 2.5-2.5V5h-1l-1.5 3L5 4 4 5l4 5.5L5 12v1z'/%3E%3C/svg%3E");
}

/* Forced Colors Mode */
@media (forced-colors: active) {
  .fullscreen-icon {
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' fill='%23fff' viewBox='0 0 29 29'%3E%3Cpath d='M24 16v5.5c0 1.75-.75 2.5-2.5 2.5H16v-1l3-1.5-4-5.5 1-1 5.5 4 1.5-3zM6 16l1.5 3 5.5-4 1 1-4 5.5 3 1.5v1H7.5C5.75 24 5 23.25 5 21.5V16zm7-11v1l-3 1.5 4 5.5-1 1-5.5-4L6 13H5V7.5C5 5.75 5.75 5 7.5 5zm11 2.5c0-1.75-.75-2.5-2.5-2.5H16v1l3 1.5-4 5.5 1 1 5.5-4 1.5 3h1z'/%3E%3C/svg%3E");
  }
  
  .shrink-icon {
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' fill='%23fff' viewBox='0 0 29 29'%3E%3Cpath d='M18.5 16c-1.75 0-2.5.75-2.5 2.5V24h1l1.5-3 5.5 4 1-1-4-5.5 3-1.5v-1zM13 18.5c0-1.75-.75-2.5-2.5-2.5H5v1l3 1.5L4 24l1 1 5.5-4 1.5 3h1zm3-8c0 1.75.75 2.5 2.5 2.5H24v-1l-3-1.5L25 5l-1-1-5.5 4L17 5h-1zM10.5 13c1.75 0 2.5-.75 2.5-2.5V5h-1l-1.5 3L5 4 4 5l4 5.5L5 12v1z'/%3E%3C/svg%3E");
  }
}

/* Light Theme Override for Forced Colors */
@media (forced-colors: active) and (prefers-color-scheme: light) {
  .fullscreen-icon {
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' viewBox='0 0 29 29'%3E%3Cpath d='M24 16v5.5c0 1.75-.75 2.5-2.5 2.5H16v-1l3-1.5-4-5.5 1-1 5.5 4 1.5-3zM6 16l1.5 3 5.5-4 1 1-4 5.5 3 1.5v1H7.5C5.75 24 5 23.25 5 21.5V16zm7-11v1l-3 1.5 4 5.5-1 1-5.5-4L6 13H5V7.5C5 5.75 5.75 5 7.5 5zm11 2.5c0-1.75-.75-2.5-2.5-2.5H16v1l3 1.5-4 5.5 1 1 5.5-4 1.5 3h1z'/%3E%3C/svg%3E");
  }
  
  .shrink-icon {
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' viewBox='0 0 29 29'%3E%3Cpath d='M18.5 16c-1.75 0-2.5.75-2.5 2.5V24h1l1.5-3 5.5 4 1-1-4-5.5 3-1.5v-1zM13 18.5c0-1.75-.75-2.5-2.5-2.5H5v1l3 1.5L4 24l1 1 5.5-4 1.5 3h1zm3-8c0 1.75.75 2.5 2.5 2.5H24v-1l-3-1.5L25 5l-1-1-5.5 4L17 5h-1zM10.5 13c1.75 0 2.5-.75 2.5-2.5V5h-1l-1.5 3L5 4 4 5l4 5.5L5 12v1z'/%3E%3C/svg%3E");
  }
}

/* Mobile Optimizations */
@media (max-width: 768px) {
  .fullscreen-button {
    min-height: 44px;
    min-width: 44px;
    padding: 8px;
  }
}
</style>