import { ref, computed, watch, type Ref } from 'vue'
import type { Coordinates } from '../types'

export type MapMode = 'single' | 'multiple'

export interface MapModesOptions {
  mode?: MapMode
  modelValue?: string
  onCenterChange?: (coords: Coordinates) => void
  onMarkerMoved?: (coords: Coordinates) => void
}

export function useMapModes(options: MapModesOptions = {}) {
  const currentMode = ref<MapMode>(options.mode ?? 'single')
  const currentCoordinates = ref<Coordinates | null>(null)

  // Parse coordinates from string
  const parseCoordinates = (value: string): Coordinates | null => {
    if (!value || !value.includes(',')) return null
    
    const parts = value.split(',')
    const lat = Number(parts[0])
    const lng = Number(parts[1])
    
    if (isNaN(lat) || isNaN(lng)) return null
    
    return { lat, lng }
  }

  // Format coordinates to string
  const formatCoordinates = (coords: Coordinates): string => {
    return `${coords.lat},${coords.lng}`
  }

  // Setup single mode
  const setupSingleMode = (modelValue?: string): Coordinates | null => {
    if (!modelValue) return null
    
    const coords = parseCoordinates(modelValue)
    if (coords) {
      currentCoordinates.value = coords
    }
    
    return coords
  }

  // Setup multiple mode
  const setupMultipleMode = () => {
    // Multiple mode doesn't track single coordinates
    currentCoordinates.value = null
  }

  // Handle center change in single mode
  const handleSingleModeChange = (center: Coordinates): string => {
    currentCoordinates.value = center
    const formatted = formatCoordinates(center)
    
    options.onCenterChange?.(center)
    options.onMarkerMoved?.(center)
    
    return formatted
  }

  // Check if mode is single
  const isSingleMode = computed(() => currentMode.value === 'single')
  const isMultipleMode = computed(() => currentMode.value === 'multiple')

  // Switch mode
  const setMode = (mode: MapMode) => {
    currentMode.value = mode
    
    if (mode === 'single' && options.modelValue) {
      setupSingleMode(options.modelValue)
    } else if (mode === 'multiple') {
      setupMultipleMode()
    }
  }

  return {
    // State
    currentMode: currentMode as Readonly<Ref<MapMode>>,
    currentCoordinates: currentCoordinates as Readonly<Ref<Coordinates | null>>,
    
    // Computed
    isSingleMode,
    isMultipleMode,
    
    // Methods
    parseCoordinates,
    formatCoordinates,
    setupSingleMode,
    setupMultipleMode,
    handleSingleModeChange,
    setMode
  }
}