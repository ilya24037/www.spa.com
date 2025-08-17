/**
 * Composable для управления контролами карты
 */

import { ref, computed, watch, onUnmounted } from 'vue'
import type { Ref } from 'vue'
import type { Map as MapLibreMap } from 'maplibre-gl'

export interface MapControlsState {
  /** Текущий зум */
  zoom: Ref<number>
  /** Минимальный зум */
  minZoom: Ref<number>
  /** Максимальный зум */
  maxZoom: Ref<number>
  /** Текущий bearing (поворот) */
  bearing: Ref<number>
  /** Текущий pitch (наклон) */
  pitch: Ref<number>
  /** Можно увеличить зум */
  canZoomIn: Ref<boolean>
  /** Можно уменьшить зум */
  canZoomOut: Ref<boolean>
  /** Можно сбросить поворот */
  canResetBearing: Ref<boolean>
}

export interface MapControlsOptions {
  /** Шаг зуммирования */
  zoomStep?: number
  /** Длительность анимации */
  animationDuration?: number
  /** Порог для скрытия компаса */
  bearingThreshold?: number
  /** Максимальный наклон */
  maxPitch?: number
}

export interface MapControlsMethods {
  /** Увеличить зум */
  zoomIn: () => void
  /** Уменьшить зум */
  zoomOut: () => void
  /** Установить зум */
  setZoom: (zoom: number) => void
  /** Сбросить поворот и наклон */
  resetBearing: () => void
  /** Установить поворот */
  setBearing: (bearing: number) => void
  /** Установить наклон */
  setPitch: (pitch: number) => void
  /** Установить центр */
  setCenter: (center: [number, number]) => void
  /** Плавный переход */
  flyTo: (options: {
    center?: [number, number]
    zoom?: number
    bearing?: number
    pitch?: number
    duration?: number
  }) => void
}

export interface UseMapControlsReturn extends MapControlsState, MapControlsMethods {}

/**
 * Composable для управления контролами карты
 */
export function useMapControls(
  map: Ref<MapLibreMap | null>,
  options: MapControlsOptions = {}
): UseMapControlsReturn {
  const {
    zoomStep = 1,
    animationDuration = 300,
    bearingThreshold = 1,
    maxPitch = 60
  } = options

  // State
  const zoom = ref(10)
  const minZoom = ref(0)
  const maxZoom = ref(18)
  const bearing = ref(0)
  const pitch = ref(0)

  // Computed
  const canZoomIn = computed(() => zoom.value < maxZoom.value)
  const canZoomOut = computed(() => zoom.value > minZoom.value)
  const canResetBearing = computed(() => 
    Math.abs(bearing.value) > bearingThreshold || Math.abs(pitch.value) > bearingThreshold
  )

  // Methods
  const updateState = () => {
    if (!map.value) return

    zoom.value = map.value.getZoom()
    minZoom.value = map.value.getMinZoom()
    maxZoom.value = map.value.getMaxZoom()
    bearing.value = map.value.getBearing()
    pitch.value = map.value.getPitch()
  }

  const zoomIn = () => {
    if (!map.value || !canZoomIn.value) return
    
    const newZoom = Math.min(zoom.value + zoomStep, maxZoom.value)
    map.value.zoomTo(newZoom, { duration: animationDuration })
  }

  const zoomOut = () => {
    if (!map.value || !canZoomOut.value) return
    
    const newZoom = Math.max(zoom.value - zoomStep, minZoom.value)
    map.value.zoomTo(newZoom, { duration: animationDuration })
  }

  const setZoom = (newZoom: number) => {
    if (!map.value) return
    
    const clampedZoom = Math.max(minZoom.value, Math.min(newZoom, maxZoom.value))
    map.value.zoomTo(clampedZoom, { duration: animationDuration })
  }

  const resetBearing = () => {
    if (!map.value || !canResetBearing.value) return

    map.value.easeTo({
      bearing: 0,
      pitch: 0,
      duration: animationDuration
    })
  }

  const setBearing = (newBearing: number) => {
    if (!map.value) return

    map.value.easeTo({
      bearing: newBearing,
      duration: animationDuration
    })
  }

  const setPitch = (newPitch: number) => {
    if (!map.value) return

    const clampedPitch = Math.max(0, Math.min(newPitch, maxPitch))
    map.value.easeTo({
      pitch: clampedPitch,
      duration: animationDuration
    })
  }

  const setCenter = (center: [number, number]) => {
    if (!map.value) return

    map.value.easeTo({
      center,
      duration: animationDuration
    })
  }

  const flyTo = (flyOptions: {
    center?: [number, number]
    zoom?: number
    bearing?: number
    pitch?: number
    duration?: number
  }) => {
    if (!map.value) return

    const options = {
      duration: animationDuration,
      ...flyOptions
    }

    map.value.flyTo(options)
  }

  // Event handlers
  const handleZoomEnd = () => updateState()
  const handleRotateEnd = () => updateState()
  const handlePitchEnd = () => updateState()

  // Watchers
  watch(map, (newMap, oldMap) => {
    // Remove old listeners
    if (oldMap) {
      oldMap.off('zoomend', handleZoomEnd)
      oldMap.off('rotateend', handleRotateEnd)
      oldMap.off('pitchend', handlePitchEnd)
    }

    // Add new listeners
    if (newMap) {
      updateState()
      newMap.on('zoomend', handleZoomEnd)
      newMap.on('rotateend', handleRotateEnd)
      newMap.on('pitchend', handlePitchEnd)
    }
  }, { immediate: true })

  // Cleanup
  onUnmounted(() => {
    if (map.value) {
      map.value.off('zoomend', handleZoomEnd)
      map.value.off('rotateend', handleRotateEnd)
      map.value.off('pitchend', handlePitchEnd)
    }
  })

  return {
    // State
    zoom,
    minZoom,
    maxZoom,
    bearing,
    pitch,
    canZoomIn,
    canZoomOut,
    canResetBearing,

    // Methods
    zoomIn,
    zoomOut,
    setZoom,
    resetBearing,
    setBearing,
    setPitch,
    setCenter,
    flyTo
  }
}

/**
 * Composable для работы с клавиатурными сокращениями карты
 */
export function useMapKeyboardControls(
  map: Ref<MapLibreMap | null>,
  controls: UseMapControlsReturn,
  options: { disabled?: Ref<boolean> } = {}
) {
  const { disabled = ref(false) } = options

  const handleKeyDown = (event: KeyboardEvent) => {
    if (disabled.value || !map.value) return

    const { key, ctrlKey, shiftKey, metaKey } = event

    // Prevent default if we handle the key
    let handled = true

    switch (key) {
      case '+':
      case '=':
        controls.zoomIn()
        break

      case '-':
        controls.zoomOut()
        break

      case 'r':
      case 'R':
        if (controls.canResetBearing.value) {
          controls.resetBearing()
        }
        break

      case 'ArrowLeft':
        if (shiftKey) {
          // Rotate left
          controls.setBearing(controls.bearing.value - 15)
        } else {
          // Pan left
          const center = map.value.getCenter()
          const offset = map.value.getZoom() > 10 ? 0.01 : 0.1
          controls.setCenter([center.lng - offset, center.lat])
        }
        break

      case 'ArrowRight':
        if (shiftKey) {
          // Rotate right
          controls.setBearing(controls.bearing.value + 15)
        } else {
          // Pan right
          const center = map.value.getCenter()
          const offset = map.value.getZoom() > 10 ? 0.01 : 0.1
          controls.setCenter([center.lng + offset, center.lat])
        }
        break

      case 'ArrowUp':
        if (shiftKey) {
          // Pitch up
          controls.setPitch(Math.min(controls.pitch.value + 10, 60))
        } else {
          // Pan up
          const center = map.value.getCenter()
          const offset = map.value.getZoom() > 10 ? 0.01 : 0.1
          controls.setCenter([center.lng, center.lat + offset])
        }
        break

      case 'ArrowDown':
        if (shiftKey) {
          // Pitch down
          controls.setPitch(Math.max(controls.pitch.value - 10, 0))
        } else {
          // Pan down
          const center = map.value.getCenter()
          const offset = map.value.getZoom() > 10 ? 0.01 : 0.1
          controls.setCenter([center.lng, center.lat - offset])
        }
        break

      default:
        handled = false
    }

    if (handled) {
      event.preventDefault()
    }
  }

  // Event listeners
  document.addEventListener('keydown', handleKeyDown)

  onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyDown)
  })

  return {
    handleKeyDown
  }
}

/**
 * Composable для работы с мышью и touch событиями
 */
export function useMapInteraction(
  map: Ref<MapLibreMap | null>,
  options: {
    enableDoubleClickZoom?: boolean
    enableScrollZoom?: boolean
    enableDragPan?: boolean
    enableDragRotate?: boolean
    enableTouchZoom?: boolean
  } = {}
) {
  const {
    enableDoubleClickZoom = true,
    enableScrollZoom = true,
    enableDragPan = true,
    enableDragRotate = true,
    enableTouchZoom = true
  } = options

  const isInteracting = ref(false)
  const lastInteractionTime = ref(0)

  const updateInteractionState = () => {
    if (!map.value) return

    // Enable/disable interactions
    if (enableDoubleClickZoom) {
      map.value.doubleClickZoom.enable()
    } else {
      map.value.doubleClickZoom.disable()
    }

    if (enableScrollZoom) {
      map.value.scrollZoom.enable()
    } else {
      map.value.scrollZoom.disable()
    }

    if (enableDragPan) {
      map.value.dragPan.enable()
    } else {
      map.value.dragPan.disable()
    }

    if (enableDragRotate) {
      map.value.dragRotate.enable()
    } else {
      map.value.dragRotate.disable()
    }

    if (enableTouchZoom) {
      map.value.touchZoomRotate.enable()
    } else {
      map.value.touchZoomRotate.disable()
    }
  }

  const handleInteractionStart = () => {
    isInteracting.value = true
    lastInteractionTime.value = Date.now()
  }

  const handleInteractionEnd = () => {
    isInteracting.value = false
  }

  watch(map, (newMap, oldMap) => {
    if (oldMap) {
      oldMap.off('dragstart', handleInteractionStart)
      oldMap.off('dragend', handleInteractionEnd)
      oldMap.off('zoomstart', handleInteractionStart)
      oldMap.off('zoomend', handleInteractionEnd)
      oldMap.off('rotatestart', handleInteractionStart)
      oldMap.off('rotateend', handleInteractionEnd)
    }

    if (newMap) {
      updateInteractionState()
      
      newMap.on('dragstart', handleInteractionStart)
      newMap.on('dragend', handleInteractionEnd)
      newMap.on('zoomstart', handleInteractionStart)
      newMap.on('zoomend', handleInteractionEnd)
      newMap.on('rotatestart', handleInteractionStart)
      newMap.on('rotateend', handleInteractionEnd)
    }
  }, { immediate: true })

  onUnmounted(() => {
    if (map.value) {
      map.value.off('dragstart', handleInteractionStart)
      map.value.off('dragend', handleInteractionEnd)
      map.value.off('zoomstart', handleInteractionStart)
      map.value.off('zoomend', handleInteractionEnd)
      map.value.off('rotatestart', handleInteractionStart)
      map.value.off('rotateend', handleInteractionEnd)
    }
  })

  return {
    isInteracting,
    lastInteractionTime,
    updateInteractionState
  }
}